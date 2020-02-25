<?php

namespace Drupal\ctek_common\Model;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;

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

}
