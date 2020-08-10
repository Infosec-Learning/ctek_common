<?php

namespace Drupal\ctek_common\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctek_common\NodeAwareTrait;
use Drupal\node\NodeInterface;

abstract class NodeAwareBlockBase extends BlockBase {
  use NodeAwareTrait;

  public function build() : array {
    $node = $this->getNode();
    if (!$node instanceof NodeInterface) {
      return [];
    }
    $build['#node'] = $node;
    return $build;
  }

  protected function blockAccess(AccountInterface $account) : AccessResultInterface {
    return AccessResult::allowedIf($this->getNode() instanceof NodeInterface);
  }

  public function getCacheContexts() : array {
    return Cache::mergeContexts([
      'url.path',
    ], parent::getCacheContexts());
  }

  public function getCacheTags() : array {
    $node = $this->getNode();
    if (!$node instanceof NodeInterface) {
      return parent::getCacheTags();
    }
    return Cache::mergeTags([
      'node:' . $node->id(),
    ], parent::getCacheTags());
  }

}
