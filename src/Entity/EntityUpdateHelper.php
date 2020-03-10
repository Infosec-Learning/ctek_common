<?php

namespace Drupal\ctek_common\Entity;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

class EntityUpdateHelper {

  protected $lastInstalledSchemaRepository;
  protected $entityFieldManager;
  protected $fieldStorageDefinitionListener;
  protected $entityDefinitionUpdateManager;

  public function __construct(
    EntityLastInstalledSchemaRepositoryInterface $lastInstalledSchemaRepository,
    EntityFieldManagerInterface $entityFieldManager,
    FieldStorageDefinitionListenerInterface $fieldStorageDefinitionListener,
    EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager
  ) {
    $this->lastInstalledSchemaRepository = $lastInstalledSchemaRepository;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldStorageDefinitionListener = $fieldStorageDefinitionListener;
    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
  }

  public function update($entityTypeId, $fieldName) {
    $changeList = $this->entityDefinitionUpdateManager->getChangeList();
    if (!isset($changeList[$entityTypeId])) {
      return;
    }
    $changes = $changeList[$entityTypeId];
    if (!isset($changes['field_storage_definitions'])) {
      return;
    }
    if (!isset($changes['field_storage_definitions'][$fieldName])) {
      return;
    }
    $fieldStorageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityTypeId);
    $originalFieldStorageDefinitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entityTypeId);
    $fieldStorageDefinition = isset($fieldStorageDefinitions[$fieldName]) ? $fieldStorageDefinitions[$fieldName] : NULL;
    $originalFieldStorageDefinition = isset($originalFieldStorageDefinitions[$fieldName]) ? $originalFieldStorageDefinitions[$fieldName] : NULL;
    switch ($changes['field_storage_definitions'][$fieldName]) {
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
