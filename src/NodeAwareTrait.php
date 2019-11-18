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
  public function getNode() : ?NodeInterface {
    if (!$this->node) {
      $request = \Drupal::request();
      $node = $request->get('node');
      if ($node instanceof NodeInterface) {
        $this->node = $node;
      } else {
        $this->node = NULL;
      }
    }
    return $this->node;
  }

}
