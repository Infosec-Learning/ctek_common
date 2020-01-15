<?php

namespace Drupal\ctek_common\SiteSection;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\ctek_common\Annotation\SiteSection;

class SiteSectionPluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/SiteSection',
      $namespaces,
      $module_handler,
      SiteSectionInterface::class,
      SiteSection::class
    );
    $this->alterInfo('site_section_info');
    $this->setCacheBackend($cache_backend, 'site_section_plugins');
  }

  public function getCurrentSiteSections() {
    return array_filter($this->getDefinitions(), function($definition) {
      $plugin = $this->createInstance($definition['id']);
      if ($plugin instanceof SiteSectionInterface) {
        return $plugin->evaluate();
      }
      return FALSE;
    });
  }

  public function getCacheTags() {
    return array_reduce(
      $this->getDefinitions(),
      function($previous, $definition) {
        $plugin = $this->createInstance($definition['id']);
        $tags = [];
        if ($plugin instanceof CacheableDependencyInterface) {
          $tags = $plugin->getCacheTags();
        }
        return Cache::mergeTags($tags, $previous);
      },
      parent::getCacheTags()
    );
  }

}
