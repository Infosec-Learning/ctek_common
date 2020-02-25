<?php

namespace Drupal\ctek_common\Commands;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Schema\EntityStorageSchemaInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drush\Commands\DrushCommands;

class EntityUpdateCommand extends DrushCommands {

  protected $entityDefinitionUpdateManager;
  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $fieldStorageDefinitionListener;
  protected $lastInstalledSchemaRepository;
  protected $entityTypeListener;

  public function __construct(
    EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    FieldStorageDefinitionListenerInterface $fieldStorageDefinitionListener,
    EntityLastInstalledSchemaRepositoryInterface $lastInstalledSchemaRepository,
    EntityTypeListenerInterface $entityTypeListener
  ) {
    parent::__construct();
    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldStorageDefinitionListener = $fieldStorageDefinitionListener;
    $this->lastInstalledSchemaRepository = $lastInstalledSchemaRepository;
    $this->entityTypeListener = $entityTypeListener;
  }

  /**
   * @command ctek_common:entity-updates
   */
  public function entityUpdates() {
    $changeList = $this->entityDefinitionUpdateManager->getChangeList();
    if (!$changeList) {
      return;
    }
    $this->entityTypeManager->clearCachedDefinitions();
    $this->entityFieldManager->clearCachedFieldDefinitions();
    foreach ($changeList as $entityTypeId => $changes) {
      $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
      if (!empty($changes['entity_type'])) {
        switch ($changes['entity_type']) {
          case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
            $this->entityTypeListener->onEntityTypeCreate($entityType);
            break;
          case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
            $original = $this->lastInstalledSchemaRepository->getLastInstalledDefinition($entityTypeId);
            $storage = $this->entityTypeManager->getStorage($entityType->id());
            if ($storage instanceof EntityStorageSchemaInterface && $storage->requiresEntityDataMigration($entityType, $original)) {
              throw new \InvalidArgumentException('The entity schema update for the ' . $entityType->id() . ' entity type requires a data migration.');
            }
            $fieldStorageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityTypeId);
            $originalFieldStorageDefinitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entityTypeId);
            $this->entityTypeListener->onFieldableEntityTypeUpdate($entityType, $original, $fieldStorageDefinitions, $originalFieldStorageDefinitions);
            break;
        }
      }
      if (!empty($changes['field_storage_definitions'])) {
        $fieldStorageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityTypeId);
        $originalFieldStorageDefinitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entityTypeId);
        foreach ($changes['field_storage_definitions'] as $fieldName => $change) {
          $fieldStorageDefinition = isset($fieldStorageDefinitions[$fieldName]) ? $fieldStorageDefinitions[$fieldName] : NULL;
          $originalFieldStorageDefinition = isset($originalFieldStorageDefinitions[$fieldName]) ? $originalFieldStorageDefinitions[$fieldName] : NULL;
          switch ($change) {
            case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
              $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($fieldStorageDefinition);
              break;
            case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
              $this->fieldStorageDefinitionListener->onFieldStorageDefinitionUpdate($fieldStorageDefinition, $originalFieldStorageDefinition);
              break;
            case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
              $this->fieldStorageDefinitionListener->onFieldStorageDefinitionDelete($originalFieldStorageDefinition);
              break;
          }
        }
      }
    }
  }

}
