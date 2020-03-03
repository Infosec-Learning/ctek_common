<?php

namespace Drupal\ctek_common\Model;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\ctek_common\ReliabilityHelper;
use Drupal\taxonomy\TermInterface;
use STS\Backoff\Strategies\AbstractStrategy;

trait ImportableModelTrait {

  /**
   * Run via hook_rebuild(). Ensures that changes to annotations for model
   * classes that change the importability of a model will update the tracking
   * fields that are added via \ctek_common_entity_base_field_info_alter.
   *
   * @throws \Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException
   */
  public static function onRebuild() {
    parent::onRebuild();
    /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $lastInstalledSchemaRepository */
    $lastInstalledSchemaRepository = \Drupal::service('entity.last_installed_schema.repository');
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    /** @var \Drupal\Core\Field\FieldStorageDefinitionListenerInterface $fieldStorageDefinitionListener */
    $fieldStorageDefinitionListener = \Drupal::service('field_storage_definition.listener');
    /** @var \Drupal\ctek_common\Model\ModelPluginManager $modelPluginManager */
    $modelPluginManager = \Drupal::service('plugin.manager.model');
    $definitions = $modelPluginManager->getDefinitions();
    $changeList = \Drupal::entityDefinitionUpdateManager()->getChangeList();
    if (count($changeList) === 0) {
      return;
    }
    foreach ($definitions as $definition) {
      $entityTypeId = $definition['entityType'];
      if (
        $definition['class'] === static::class
        && isset($changeList[$entityTypeId])
      ) {
        $changes = $changeList[$definition['entityType']];
        $fieldStorageDefinitions = $entityFieldManager->getFieldStorageDefinitions($entityTypeId);
        $originalFieldStorageDefinitions = $lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entityTypeId);
        if (isset($changes['field_storage_definitions'])) {
          foreach ($changes['field_storage_definitions'] as $fieldName => $change) {
            if (in_array($fieldName, ImportableModelInterface::IMPORT_TRACKING_FIELDS)) {
              $fieldStorageDefinition = isset($fieldStorageDefinitions[$fieldName]) ? $fieldStorageDefinitions[$fieldName] : NULL;
              $originalFieldStorageDefinition = isset($originalFieldStorageDefinitions[$fieldName]) ? $originalFieldStorageDefinitions[$fieldName] : NULL;
              switch ($change) {
                case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
                  $fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($fieldStorageDefinition);
                  break;
                case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
                  $fieldStorageDefinitionListener->onFieldStorageDefinitionUpdate($fieldStorageDefinition, $originalFieldStorageDefinition);
                  break;
                case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
                  $fieldStorageDefinitionListener->onFieldStorageDefinitionDelete($originalFieldStorageDefinition);
                  break;
              }
            }
          }
        }
      }
    }
  }

  public static function getNewOrExisting(
    ImportableModelInterface $plugin,
    ImportRecordInterface $importData
  ) {
    $entityType = $plugin->getPluginDefinition()['entityType'];
    $bundle = $plugin->getPluginDefinition()['bundle'];
    $storage = \Drupal::entityTypeManager()->getStorage($entityType);
    $definition = \Drupal::entityTypeManager()->getDefinition($entityType);
    $properties = [
      $definition->getKey('bundle') => $bundle,
      ImportableModelInterface::IMPORT_TRACKING_ID_FIELD => $importData->getImportId(),
    ];
    $entities = $storage->loadByProperties($properties);
    if (count($entities) > 1) {
      throw new \LogicException();
    }
    $entity = reset($entities);
    if (!$entity instanceof ContentEntityInterface) {
      $entity = $storage->create($properties);
    }
    $hash = $importData->computeHash(static::getVersion());
    if (!$entity->isNew() && $hash === $entity->get(ImportableModelInterface::IMPORT_TRACKING_HASH_FIELD)->value) {
      return [$entity, ImportableModelInterface::EXISTING_UNCHANGED];
    }
    $entity->set(ImportableModelInterface::IMPORT_TRACKING_HASH_FIELD, $hash);
    return [$entity, $entity->isNew() ? ImportableModelInterface::NEW : ImportableModelInterface::EXISTING_CHANGED];
  }

  private static function logger() {
    return \Drupal::logger('import');
  }

  protected static function conditionallyCreateTaxonomyTerm($vid, $termName) {
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $logger = static::logger();
    $termCache = &drupal_static(__FUNCTION__);
    if (!$termCache) {
      $termCache = [];
    }
    if (isset($termCache[$vid][$termName])) {
      $logger->debug("Existing term ($termName) found in cache, skipping.");
      return $termCache[$vid][$termName];
    }
    $existing = $termStorage->loadByProperties([
      'vid' => $vid,
      'name' => $termName,
    ]);
    if (count($existing) > 1) {
      $logger->warning("Multiple terms with matching names found, skipping.");
      /** @var TermInterface $term */
      foreach ($termStorage->loadMultiple($existing) as $term) {
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
      $term = $termStorage->create([
        'vid' => $vid,
      ]);
      $term->setName($termName);
      $term->save();
      $logger->debug("Successfully created new term: $termName");
    }
    $termCache[$vid][$termName] = $term;
    return $term;
  }

  protected static function saveDownloadedFile($url, $destination, $contentTypes = []) {
    $fileSystem = \Drupal::service('file_system');
    $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
    $tempFilename = $fileSystem->tempnam('temporary://', 'import-downloads');
    $temp = fopen($tempFilename, 'w');
    $response = static::httpGetReliably($url);
    if (count($contentTypes) > 0 && count(array_intersect($response->getHeader('Content-Type'), $contentTypes)) === 0) {
      throw new \Exception('Got unexpected content type(s): ' . join(', ', $response->getHeader('Content-Type')));
    }
    $body = $response->getBody();
    while ($body->isReadable() && !$body->eof()) {
      fwrite($temp, $response->getBody()->read(8192));
    }
    fclose($temp);
    /** @var \Drupal\file\Entity\File $file */
    $file = $fileStorage->create([
      'uri' => $tempFilename,
      'status' => 1,
    ]);
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    static::ensureDestination($destination);
    $file = file_copy($file, $destination . '/' . $file->getFilename() . '.' . $extension, FileSystemInterface::EXISTS_REPLACE);
    if (!$file) {
      throw new \Exception('Unable to save file.');
    }
    return $file;
  }

  protected static function ensureDestination($destination) {
    $logger = static::logger();
    $fileSystem = \Drupal::service('file_system');
    if (is_dir($fileSystem->realpath($destination))) {
      $logger->info('Destination directory exists.');
    } else {
      $logger->info('Creating destination directory.');
      if (!$fileSystem->mkdir($destination, NULL, TRUE)) {
        throw new \Exception("Unable to create destination directory: $destination");
      }
    }
  }

  protected static function httpRequest($verb, $url, array $options = [], $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    $logger = static::logger();
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
  protected static function httpGetReliably($url, array $options = [], $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    return static::httpRequest('get', $url, $options, $maxAttempts, $limit, $milliseconds);
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
  protected static function httpPostReliably($url, array $options = [], $maxAttempts = 5, $limit = 0, $milliseconds = 0) {
    return static::httpRequest('post', $url, $options, $maxAttempts, $limit, $milliseconds);
  }

}
