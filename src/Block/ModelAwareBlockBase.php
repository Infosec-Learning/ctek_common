<?php

namespace Drupal\ctek_common\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctek_common\Model\ModelAwareTrait;
use Drupal\ctek_common\Model\ModelInterface;
use Drupal\ctek_common\NodeAwareTrait;
use Drupal\node\NodeInterface;

abstract class ModelAwareBlockBase extends BlockBase {
  use ModelAwareTrait;

  public function build() : ?array {
    $model = $this->getModel();
    if (!$model instanceof ModelInterface) {
      return NULL;
    }
    $build['#model'] = $model;
    return $build;
  }

  protected function blockAccess(AccountInterface $account) : AccessResultInterface {
    return AccessResult::allowedIf($this->getModel() instanceof ModelInterface);
  }

  public function getCacheContexts() {
    $model = $this->getModel();
    if (!$model) {
      return parent::getCacheContexts();
    }
    $contexts = Cache::mergeContexts(['url.path'], $model->getCacheContexts());
    return Cache::mergeContexts($contexts, parent::getCacheContexts());
  }

  public function getCacheTags() {
    $model = $this->getModel();
    if (!$model) {
      return parent::getCacheTags();
    }
    return Cache::mergeTags($model->getCacheTags(), parent::getCacheTags());
  }

  public function getCacheMaxAge() {
    $model = $this->getModel();
    if (!$model) {
      return parent::getCacheMaxAge();
    }
    return Cache::mergeMaxAges($model->getCacheMaxAge(), parent::getCacheMaxAge());
  }

}
