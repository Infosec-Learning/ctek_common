<?php

namespace Drupal\ctek_common\Entity;

trait EntityReferenceTrait {

  protected function getEntityReferenceNames(
    string $entityTypeId,
    string $bundle,
    callable $queryCallback = NULL,
    callable $nameCallback = NULL
  ) : array {
    $definition = \Drupal::entityTypeManager()->getDefinition($entityTypeId);
    $storage = \Drupal::entityTypeManager()->getStorage($entityTypeId);
    $entities = [];
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    if (is_callable($queryCallback)) {
      $queryCallback($query);
    }
    $query->condition($definition->getKey('bundle'), $bundle);
    foreach ($storage->loadMultiple($query->execute()) as $entity) {
      if (is_callable($nameCallback)) {
        $entities[$entity->id()] = $nameCallback($entity);
      } else {
        $entities[$entity->id()] = $entity->label();
      }
    }
    return $entities;
  }

}
