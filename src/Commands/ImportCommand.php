<?php

namespace Drupal\inova_common\Commands;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\ctek_common\ReliabilityHelper;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use phpseclib\Net\SFTP;
use Stiphle\Throttle\TimeWindow;
use STS\Backoff\Backoff;
use STS\Backoff\Strategies\PolynomialStrategy;
use Symfony\Component\Console\Style\OutputStyle;

abstract class ImportCommand extends DrushCommands {

  const VERSION = 0;

  const NEW = 1;
  const EXISTING_CHANGED = 2;
  const EXISTING_UNCHANGED = 3;

  protected $nodeStorage;
  protected $termStorage;
  protected $renderer;
  protected $httpClient;
  protected $database;
  protected $timeWindow;
  protected $fileSystem;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    RendererInterface $renderer,
    ClientInterface $httpClient,
    Connection $database,
    FileSystemInterface $fileSystem
  ) {
    parent::__construct();
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->renderer = $renderer;
    $this->httpClient = $httpClient;
    $this->database = $database;
    $this->timeWindow = new TimeWindow();
    $this->fileSystem = $fileSystem;
  }

  protected function isVerbose() : bool {
    return $this->io()->isVerbose();
  }

  protected function doImport(callable $callback) : void {
    $io = $this->io();
    $io->success('Beginning import...');
    $transaction = $this->database->startTransaction();
    try {
      $this->renderer->executeInRenderContext(new RenderContext(), $callback);
      $io->success('Import complete.');
    } catch (\Throwable $e) {
      $io->error($e->getMessage());
      $io->error($e->getTraceAsString());
      $transaction->rollBack();
    }
  }

  /**
   * @param string|\Psr\Http\Message\UriInterface $url
   * @param array $options
   *
   * @param int $maxAttempts
   * @param int $limit
   * @param int $milliseconds
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function httpRequest($url, array $options = [], $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    $io = $this->io();
    $isVerbose = $io->isVerbose();
    $client = \Drupal::httpClient();
    if ($isVerbose) {
      $io->text("Calling $url...");
    }
    if ($maxAttempts < 1) {
      throw new \InvalidArgumentException('$maxAttempts must be greater than or equal to 1');
    }
    $helper = new ReliabilityHelper(function() use ($client, $url, $options, $io) {
      return $client->get($url, $options);
    });
    if ($limit > 0 && $milliseconds > 0) {
      $helper->throttle($url, $limit, $milliseconds, function ($wait, OutputStyle $io) {
        if ($this->isVerbose()) {
          $io->text("Throttle engaged, waiting $wait milliseconds...");
        }
      });
    }
    $helper->tolerateFaults(
      $maxAttempts,
      max(500, $milliseconds),
      2,
      function (\Exception $exception, $attempt, $maxAttempts, $waitTime) use ($io, $url) {
        $io->warning("Attempt $attempt of $maxAttempts to call $url failed ({$exception->getMessage()}), retrying in {$waitTime}ms...");
      }
    );
    $result = $helper->execute();
    if ($isVerbose) {
      $io->text("Got response from $url.");
    }
    return $result;
  }

  protected function sftpRequest($host, $port, $user, $password, $path, $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    $io = $this->io();
    $isVerbose = $io->isVerbose();
    if ($maxAttempts < 1) {
      throw new \InvalidArgumentException('$maxAttempts must be greater than or equal to 1');
    }
    $helper = new ReliabilityHelper(function() use ($host, $port, $user, $path, $password, $io, $isVerbose) {
      $sftp = new SFTP($host, $port);
      if (!$sftp->login($user, $password)) {
        throw new \Exception('Unable to log in to SFTP server.');
      }
      if ($isVerbose) {
        $io->text('Logged in to SFTP server.');
      }
      $tempFilename = 'temporary://' . basename($path);
      if (!$sftp->get($path, $tempFilename)) {
        throw new \Exception('Unable to download file.');
      }
      return $tempFilename;
    });
    if ($limit > 0 && $milliseconds > 0) {
      $helper->throttle("$host-$port-$user-$path", $limit, $milliseconds, function ($wait, OutputStyle $io) {
        if ($this->isVerbose()) {
          $io->text("Throttle engaged, waiting $wait milliseconds...");
        }
      });
    }
    $helper->tolerateFaults(
      $maxAttempts,
      max(500, $milliseconds),
      2,
      function (\Exception $exception, $attempt, $maxAttempts, $waitTime) use ($io, $path) {
        $io->warning("Attempt $attempt of $maxAttempts to download file $path failed ({$exception->getMessage()}), retrying in {$waitTime}ms...");
      }
    );
    return $helper->execute();
  }

  protected function conditionallyCreateTaxonomyTerm($vid, $termName) : ?TermInterface {
    $io = $this->io();
    $isVerbose = $this->isVerbose();
    $termCache = &drupal_static(__FUNCTION__);
    if (!$termCache) {
      $termCache = [];
    }
    if (isset($termCache[$vid][$termName])) {
      if ($isVerbose) {
        $io->text("Existing term ($termName) found in cache, skipping.");
      }
      return $termCache[$vid][$termName];
    }
    $existing = $this->termStorage->loadByProperties([
      'vid' => $vid,
      'name' => $termName,
    ]);
    if (count($existing) > 1) {
      $warning = ["Multiple terms with matching names found, skipping."];
      /** @var TermInterface $term */
      foreach ($this->termStorage->loadMultiple($existing) as $term) {
        array_push($warning, "TID: {$term->id()}, Name: {$term->getName()}");
      }
      $io->warning($warning);
      return NULL;
    }
    $term = reset($existing);
    if ($term instanceof TermInterface) {
      if ($isVerbose) {
        $io->text("Existing term found in database: $termName");
      }
    } else {
      if ($isVerbose) {
        $io->text("Creating new term: $termName");
      }
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $this->termStorage->create([
        'vid' => $vid,
      ]);
      $term->setName($termName);
      $term->save();
      if ($isVerbose) {
        $io->text("Successfully created new term: $termName");
      }
    }
    $termCache[$vid][$termName] = $term;
    return $term;
  }

  protected function generateHash($data) : string {
    return Crypt::hashBase64(static::VERSION . serialize($data));
  }

  protected function getNewOrExisting(
    $data,
    $bundle,
    $idField,
    $id
  ) : array {
    $io = $this->io();
    $isVerbose = $io->isVerbose();
    $hash = $this->generateHash($data);
    $nodes = $this->nodeStorage->loadByProperties([
      'type' => $bundle,
      $idField => $id,
    ]);
    $node = reset($nodes);
    if (!$node instanceof NodeInterface) {
      $node = $this->nodeStorage->create([
        'type' => $bundle,
        $idField => $id,
      ]);
    }
    if (!$node->isNew() && $hash === $node->get('field_hash')->value) {
      if ($isVerbose) {
        $io->text("Existing node without changes detected.");
      }
      return [$node, static::EXISTING_UNCHANGED];
    }
    $node->set('field_hash', $hash);
    if ($isVerbose) {
      $io->text($node->isNew() ? "No existing node detected, creating new node." : "Existing node with changes detected.");
    }
    return [$node, $node->isNew() ? static::NEW : static::EXISTING_CHANGED];
  }

}
