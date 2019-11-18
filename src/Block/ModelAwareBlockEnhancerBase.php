<?php

namespace Drupal\ctek_common\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\ctek_common\Block\BlockEnhancerPluginBase;
use Drupal\ctek_common\Model\ModelAwareInterface;
use Drupal\ctek_common\Model\ModelAwareTrait;

abstract class ModelAwareBlockEnhancerBase extends BlockEnhancerPluginBase implements ModelAwareInterface {

  use ModelAwareTrait;

  public function enhanceBlock(array &$vars) : void {
    $vars['content']['#access'] = $this->access();
    if (!isset($vars['#cache']['contexts'])) {
      $vars['#cache']['contexts'] = [];
    }
    if (!isset($vars['#cache']['tags'])) {
      $vars['#cache']['tags'] = [];
    }
    $vars['#cache']['contexts'] = Cache::mergeContexts($vars['#cache']['contexts'], ['route']);
    $model = $this->getModel();
    if ($model) {
      $vars['#cache']['tags'] = Cache::mergeTags($vars['#cache']['tags'], $model->getEntity()->getCacheTags());
    }
  }

  public function access() : AccessResultInterface {
    $model = $this->getModel();
    if (!$model) {
      return AccessResult::forbidden();
    }
    return $model->getEntity()->access('view', \Drupal::currentUser(), TRUE);
  }

}
