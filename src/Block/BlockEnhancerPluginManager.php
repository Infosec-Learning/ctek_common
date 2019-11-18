<?php

namespace Drupal\ctek_common\Block;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * BlockEnhancer plugin manager.
 */
class BlockEnhancerPluginManager extends DefaultPluginManager {

  /**
   * Constructs BlockEnhancerPluginManager object.
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
      'Plugin/BlockEnhancer',
      $namespaces,
      $module_handler,
      'Drupal\ctek_common\Block\BlockEnhancerInterface',
      'Drupal\ctek_common\Annotation\BlockEnhancer'
    );
    $this->alterInfo('block_enhancer_info');
    $this->setCacheBackend($cache_backend, 'block_enhancer_plugins');
  }

  public function enhanceBlock(array &$vars) : void {
    foreach ($this->getDefinitions() as $definition) {
      if ($definition['blockId'] === $vars['plugin_id']) {
        /** @var \Drupal\ctek_common\Block\BlockEnhancerInterface $blockEnhancer */
        $blockEnhancer = $this->createInstance($definition['id']);
        $blockEnhancer->enhanceBlock($vars);
      }
    }
  }

}
