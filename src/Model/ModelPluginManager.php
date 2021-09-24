<?php

namespace Drupal\ctek_common\Model;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\ctek_common\Annotation\Model;

/**
 * Model plugin manager.
 */
class ModelPluginManager extends DefaultPluginManager {

  /**
   * Constructs ModelPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Model',
      $namespaces,
      $module_handler,
      ModelInterface::class,
      Model::class
    );
    $this->alterInfo('model_info');
    $this->setCacheBackend($cache_backend, 'model_plugins');
  }

  public function findDefinitions() : array {
    $definitions = parent::findDefinitions();
    $i = 0;
    $count = count($definitions);
    $weights = [];
    foreach ($definitions as $id => $definition) {
      $weight = isset($definition['weight']) ? $definition['weight'] : 0;
      $weights[$id] = floor($weight * 1000) + $i / $count;
      $i++;
    }
    asort($weights);
    foreach ($weights as $key => $weight) {
      $value = $definitions[$key];
      unset($definitions[$key]);
      $definitions[$key] = $value;
    }
    return $definitions;
  }

  public function wrap(?ContentEntityInterface $entity) : ?ModelInterface {
    if ($entity instanceof ContentEntityInterface) {
      foreach ($this->getDefinitions() as $id => $definition) {
        if ($definition['entityType'] === $entity->getEntityTypeId() && $definition['bundle'] === $entity->bundle()) {
          return $this->createInstance($id, ['entity' => $entity]);
        }
      }
    }
    return NULL;
  }

  public function wrapFromRouteDefaults($defaults) : ?ModelInterface {
    foreach ($this->getDefinitions() as $id => $definition) {
      if (isset($defaults[$definition['entityType']])) {
        $entity = $defaults[$definition['entityType']];
        if ($entity instanceof ContentEntityInterface) {
          if ($definition['entityType'] === $entity->getEntityTypeId() && $definition['bundle'] === $entity->bundle()) {
            return $this->createInstance($id, ['entity' => $entity]);
          }
        }
      }
    }
    return NULL;
  }

}
