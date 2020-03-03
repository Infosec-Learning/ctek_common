<?php

namespace Drupal\ctek_common\Commands;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ctek_common\ReliabilityHelper;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerInterface;
use phpseclib\Net\SFTP;
use Psr\Log\LogLevel;
use Stiphle\Throttle\TimeWindow;
use STS\Backoff\Backoff;
use STS\Backoff\Strategies\AbstractStrategy;
use STS\Backoff\Strategies\PolynomialStrategy;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Terminal;

abstract class ImportCommand extends DrushCommands implements ContainerAwareInterface {
  use ContainerAwareTrait;

  const VERSION = 0;

  const DATETIME_FORMAT = 'Y-m-d h:i:s A T';

  const NEW = 1;
  const EXISTING_CHANGED = 2;
  const EXISTING_UNCHANGED = 3;

  const IMPORT_NOTIFYEE_ROLE = 'import_notifyees';

  const CONTENT_TYPE_IMAGE = ['image/jpeg', 'image/gif', 'image/png'];

  const STATUS_COMPLETED = 'completed';
  const STATUS_COMPLETED_WITH_WARNINGS = 'completed with warnings';
  const STATUS_FAILED = 'failed';

  protected $nodeStorage;
  protected $termStorage;
  protected $userStorage;
  protected $fileStorage;
  protected $renderer;
  protected $httpClient;
  protected $database;
  protected $timeWindow;
  protected $fileSystem;
  protected $mailManager;
  protected $state;
  protected $config;
  protected $timezone;
  protected $terminal;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    RendererInterface $renderer,
    ClientInterface $httpClient,
    Connection $database,
    FileSystemInterface $fileSystem,
    MailManagerInterface $mailManager,
    StateInterface $state,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct();
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->renderer = $renderer;
    $this->httpClient = $httpClient;
    $this->database = $database;
    $this->timeWindow = new TimeWindow();
    $this->fileSystem = $fileSystem;
    $this->mailManager = $mailManager;
    $this->state = $state;
    $this->timezone = new \DateTimeZone($configFactory->get('system.date')->get('timezone.default'));
    $this->terminal = new Terminal();
  }

  public function setContainer(ContainerInterface $container = NULL) {
    if ($container instanceof Container) {
      $container->share('logger', Logger::class)
        ->withArgument('output')
        ->withMethodCall('setLogOutputStyler', [new LogStyle()]);
      $this->container = $container;
    }
  }

  protected function logger() {
    return $this->container->get('logger');
  }

  protected function getStateKey($name) {
    return 'ctek_common.import.' . strtolower(str_replace(' ', '_', $name));
  }

  /**
   * @TODO optional email
   *
   * @param string $name
   * @param callable $callback
   *
   * @throws \Exception
   */
  protected function doImport(string $name, callable $callback) {
    $logger = $this->logger();
    $logger->notice('Beginning import {importname}', ['importname' => $name]);
    $stateKey = $this->getStateKey($name);
    $previous = $this->state->get($stateKey);
    if ($previous) {
      $timestamp = (new \DateTime())
        ->setTimestamp($previous['timestamp'])
        ->setTimezone($this->timezone)
        ->format(LogStyle::DATETIME_FORMAT);
      $logger->info('Last run at {previoustimestamp} with status: {previousstatus}.', [
        'previoustimestamp' => $timestamp,
        'previousstatus' => $previous['status'],
      ]);
    } else {
      $logger->info('No previous run on record.');
    }
    $result = [
      'timestamp' => time(),
      'status' => NULL
    ];
    $transaction = $this->database->startTransaction();
    try {
      $this->renderer->executeInRenderContext(new RenderContext(), $callback);
      $logger->notice("Import complete.");
      $result['status'] = self::STATUS_COMPLETED;
      if ($logger instanceof Logger && $logger->hasLogsForEmail()) {
        $result['status'] = self::STATUS_COMPLETED_WITH_WARNINGS;
        $this->mail($name. ' Import Warnings', $this->formatLogsForMail($logger->getLogsForEmail()));
      }
    } catch (\Throwable $e) {
      $result['status'] = self::STATUS_FAILED;
      $logger->emergency('Uncaught Exception');
      $body = array_merge(
        $this->formatExceptionForMail($e),
        $this->formatLogsForMail($logger->getLogsForEmail())
      );
      $this->mail($name . ' Import Failed', $body);
      $this->doRenderException($e, $this->io());
      $transaction->rollBack();
      return;
    }
    $this->state->set($stateKey, $result);
  }

  protected function logException(\Throwable $e) {
    // If the key is "message", interpolation does weird things...
    $message = '{file}::{line} - {mesage}';
    $context = [
      'mesage' => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
    ];
    $this->logger()->error($message, $context);
  }

  protected function formatExceptionForMail(\Throwable $e) {
    return [
      Markup::create("<h1>Uncaught Exception</h1>"),
      Markup::create("<h3>{$e->getMessage()}</h3>"),
      Markup::create("<pre>{$e->getTraceAsString()}</pre>"),
    ];
  }

  protected function formatLogsForMail(array $logs) {
    $body = [
      Markup::create("<h1>Logs</h1>"),
    ];
    $table = [
      '#type' => 'table',
      '#header' => [
        'Timestamp',
        'Type',
        'Message',
        'Memory',
      ],
      '#rows' => [],
    ];
    foreach ($logs as $log) {
      $row = [
        'data' => [
          $log['timestamp'],
          $log['type'],
          $log['message'],
          number_format(intval($log['memory']) / 1024 / 1024, 2) . ' MiB',
        ],
      ];
      switch ($log['type']) {
        case LogLevel::WARNING:
          $row['style'] = 'background:#FFFF00;font-weight:bold;';
          break;
        case LogLevel::ERROR:
        case LogLevel::ALERT:
        case LogLevel::CRITICAL:
        case LogLevel::EMERGENCY:
          $row['style'] = 'background:#FF0000;color:#FFFFFF;font-weight:bold;';
          break;
        default:
          $row['style'] = 'background:#EEEEEE;color:#888888;';
          break;
      }
      $table['#rows'][] = $row;
    }
    $body[] = (\Drupal::service('renderer'))->renderRoot($table);
    return $body;
  }

  protected function mail(string $subject, array $body) {
    $notifyees = $this->userStorage->loadByProperties([
      'status' => 1,
      'roles' => static::IMPORT_NOTIFYEE_ROLE,
    ]);
    if (count($notifyees) === 0) {
      return;
    }
    $langCode = \Drupal::languageManager()
      ->getDefaultLanguage()
      ->getId();
    $notifyees = array_map(function(UserInterface $user){
      return $user->getEmail();
    }, $notifyees);
    $this->mailManager->mail('ctek_common', 'import_exception', '', $langCode, [
      'notifyees' => $notifyees,
      'subject' => $subject,
      'body' => $body,
    ]);
  }

  /**
   * Shamelessly stolen from \Symfony\Component\Console\Application::doRenderException
   *
   * @param \Throwable $e
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function doRenderException(\Throwable $e, OutputInterface $output) {
    do {
      $message = trim($e->getMessage());
      if ('' === $message || OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
        $title = sprintf('  [%s%s]  ', \get_class($e), 0 !== ($code = $e->getCode()) ? ' ('.$code.')' : '');
        $len = Helper::strlen($title);
      } else {
        $len = 0;
      }

      $width = $this->terminal->getWidth() ? $this->terminal->getWidth() - 1 : PHP_INT_MAX;
      // HHVM only accepts 32 bits integer in str_split, even when PHP_INT_MAX is a 64 bit integer: https://github.com/facebook/hhvm/issues/1327
      if (\defined('HHVM_VERSION') && $width > 1 << 31) {
        $width = 1 << 31;
      }
      $lines = [];
      foreach ('' !== $message ? preg_split('/\r?\n/', $message) : [] as $line) {
        foreach ($this->splitStringByWidth($line, $width - 4) as $line) {
          // pre-format lines to get the right string length
          $lineLength = Helper::strlen($line) + 4;
          $lines[] = [$line, $lineLength];

          $len = max($lineLength, $len);
        }
      }

      $messages = [];
      if (!$e instanceof ExceptionInterface || OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
        $messages[] = sprintf('<comment>%s</comment>', OutputFormatter::escape(sprintf('In %s line %s:', basename($e->getFile()) ?: 'n/a', $e->getLine() ?: 'n/a')));
      }
      $messages[] = $emptyLine = sprintf('<error>%s</error>', str_repeat(' ', $len));
      if ('' === $message || OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
        $messages[] = sprintf('<error>%s%s</error>', $title, str_repeat(' ', max(0, $len - Helper::strlen($title))));
      }
      foreach ($lines as $line) {
        $messages[] = sprintf('<error>  %s  %s</error>', OutputFormatter::escape($line[0]), str_repeat(' ', $len - $line[1]));
      }
      $messages[] = $emptyLine;
      $messages[] = '';

      $output->writeln($messages, OutputInterface::VERBOSITY_QUIET);

      if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
        $output->writeln('<comment>Exception trace:</comment>', OutputInterface::VERBOSITY_QUIET);

        // exception related properties
        $trace = $e->getTrace();

        array_unshift($trace, [
          'function' => '',
          'file' => $e->getFile() ?: 'n/a',
          'line' => $e->getLine() ?: 'n/a',
          'args' => [],
        ]);

        for ($i = 0, $count = \count($trace); $i < $count; ++$i) {
          $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
          $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
          $function = $trace[$i]['function'];
          $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
          $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

          $output->writeln(sprintf(' %s%s%s() at <info>%s:%s</info>', $class, $type, $function, $file, $line), OutputInterface::VERBOSITY_QUIET);
        }

        $output->writeln('', OutputInterface::VERBOSITY_QUIET);
      }
    } while ($e = $e->getPrevious());
  }

  /**
   * Shamelessly stolen from \Symfony\Component\Console\Application::splitStringByWidth
   * @param $string
   * @param $width
   *
   * @return array
   */
  private function splitStringByWidth($string, $width) {
    // str_split is not suitable for multi-byte characters, we should use preg_split to get char array properly.
    // additionally, array_slice() is not enough as some character has doubled width.
    // we need a function to split string not by character count but by string width
    if (false === $encoding = mb_detect_encoding($string, null, true)) {
      return str_split($string, $width);
    }

    $utf8String = mb_convert_encoding($string, 'utf8', $encoding);
    $lines = [];
    $line = '';
    foreach (preg_split('//u', $utf8String) as $char) {
      // test if $char could be appended to current line
      if (mb_strwidth($line.$char, 'utf8') <= $width) {
        $line .= $char;
        continue;
      }
      // if not, push current line to array and make new line
      $lines[] = str_pad($line, $width);
      $line = $char;
    }

    $lines[] = \count($lines) ? str_pad($line, $width) : $line;

    mb_convert_variables($encoding, 'utf8', $lines);

    return $lines;
  }

  protected function httpRequest($verb, $url, array $options = [], $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    $logger = $this->logger();
    $client = \Drupal::httpClient();
    $logger->debug("Calling $url...");
    $result = (new ReliabilityHelper(function() use ($verb, $client, $url, $options) {
      return $client->{$verb}($url, $options);
    }))
      ->throttle($url, $limit, $milliseconds)
      ->tolerateFaults(
        $maxAttempts,
        max(500, $milliseconds),
        2,
        function(\Exception $exception, $attempt, $maxAttempts, AbstractStrategy $backoffStrategy) use ($logger, $url) {
          $logger->warning("Attempt $attempt of $maxAttempts to call $url failed ({$exception->getMessage()}), retrying in {$backoffStrategy->getWaitTime($attempt)}ms...");
        }
      )
      ->execute();
    $logger->debug("Got response from $url.");
    return $result;
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
  protected function httpGetReliably($url, array $options = [], $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    return $this->httpRequest('get', $url, $options, $maxAttempts, $limit, $milliseconds);
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
  protected function httpPostReliably($url, array $options = [], $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    return $this->httpRequest('post', $url, $options, $maxAttempts, $limit, $milliseconds);
  }

  protected function conditionallyCreateTaxonomyTerm($vid, $termName) {
    $logger = $this->logger();
    $termCache = &drupal_static(__FUNCTION__);
    if (!$termCache) {
      $termCache = [];
    }
    if (isset($termCache[$vid][$termName])) {
      $logger->debug("Existing term ($termName) found in cache, skipping.");
      return $termCache[$vid][$termName];
    }
    $existing = $this->termStorage->loadByProperties([
      'vid' => $vid,
      'name' => $termName,
    ]);
    if (count($existing) > 1) {
      $logger->warning("Multiple terms with matching names found, skipping.");
      /** @var TermInterface $term */
      foreach ($this->termStorage->loadMultiple($existing) as $term) {
        $logger->warning("TID: {$term->id()}, Name: {$term->getName()}");
      }
      return NULL;
    }
    $term = reset($existing);
    if ($term instanceof TermInterface) {
      $logger->debug("Existing term found in database: $termName");
    } else {
      $logger->debug("Creating new term: $termName");
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $this->termStorage->create([
        'vid' => $vid,
      ]);
      $term->setName($termName);
      $term->save();
      $logger->debug("Successfully created new term: $termName");
    }
    $termCache[$vid][$termName] = $term;
    return $term;
  }

  protected function generateHash($data) {
    return Crypt::hashBase64(static::VERSION . serialize($data));
  }

  protected function getNewOrExisting(
    $data,
    $bundle,
    $idField,
    $id
  ) {
    $logger = $this->logger();
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
      $logger->debug("Existing node without changes detected.");
      return [$node, static::EXISTING_UNCHANGED];
    }
    $node->set('field_hash', $hash);
    $logger->debug($node->isNew() ? "No existing node detected, creating new node." : "Existing node with changes detected.");
    return [$node, $node->isNew() ? static::NEW : static::EXISTING_CHANGED];
  }

  protected function ensureDestination($destination) {
    $logger = $this->logger();
    if (is_dir($this->fileSystem->realpath($destination))) {
      $logger->info('Destination directory exists.');
    } else {
      $logger->info('Creating destination directory.');
      if (!$this->fileSystem->mkdir($destination, NULL, TRUE)) {
        throw new \Exception("Unable to create destination directory: $destination");
      }
    }
  }

  protected function saveDownloadedFile($url, $destination, $contentTypes = []) {
    $tempFilename = $this->fileSystem->tempnam('temporary://', 'import-downloads');
    $temp = fopen($tempFilename, 'w');
    $response = $this->httpGetReliably($url);
    if (count($contentTypes) > 0 && count(array_intersect($response->getHeader('Content-Type'), $contentTypes)) === 0) {
      throw new \Exception('Got unexpected content type(s): ' . join(', ', $response->getHeader('Content-Type')));
    }
    $body = $response->getBody();
    while ($body->isReadable() && !$body->eof()) {
      fwrite($temp, $response->getBody()->read(8192));
    }
    fclose($temp);
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->fileStorage->create([
      'uri' => $tempFilename,
      'status' => 1,
    ]);
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    $this->ensureDestination($destination);
    $file = file_copy($file, $destination . '/' . $file->getFilename() . '.' . $extension, FileSystemInterface::EXISTS_REPLACE);
    if (!$file) {
      throw new \Exception('Unable to save file.');
    }
    return $file;
  }

  protected function unpublishMissing(callable $alterQuery, array $importedNids = []) {
    $logger = $this->logger();
    $query = $this->nodeStorage->getQuery();
    $query->condition('status', NodeInterface::PUBLISHED);
    $alterQuery($query);
    $query->condition('nid', $importedNids, 'NOT IN');
    foreach ($query->execute() as $nid) {
      $node = $this->nodeStorage->load($nid);
      if ($node instanceof NodeInterface) {
        $logger->debug('Unpublishing location {title}', ['title' => $node->getTitle()]);
        $node->setUnpublished();
        try {
          $node->save();
        } catch (\Exception $e) {
          $this->logException($e);
        }
      }
    }
  }

}
