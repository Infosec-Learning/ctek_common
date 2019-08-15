<?php

namespace Drupal\ctek_common;

use Drupal\node\NodeInterface;

trait NodeAwareTrait {

  /** @var NodeInterface */
  protected $node;

  /**
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\node\NodeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNode() {
    if (!$this->node) {
      $request = \Drupal::request();
      $node = $request->attributes->get('node');
      if ($node) {
        if (!is_object($node)) {
          $node = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->load($node);
        }
        if ($node instanceof NodeInterface) {
          $this->node = $node;
        }
      }
    }
    return $this->node;
  }

}
