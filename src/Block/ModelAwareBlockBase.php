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

  public function build() : array {
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

}
