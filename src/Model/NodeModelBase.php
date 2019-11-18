<?php

namespace Drupal\ctek_common\Model;

use Drupal\node\NodeInterface;

abstract class NodeModelBase extends ModelBase implements NodeModelInterface {

  public function getNode() : NodeInterface {
    return $this->entity;
  }

  public function getTitle() : string {
    return $this->entity->getTitle();
  }

  public function created() : int {
    return $this->entity->getCreatedTime();
  }

  public function changed() : int {
    return $this->entity->getChangedTime();
  }

  public function isPromoted() : bool {
    return $this->entity->isPromoted();
  }

  public function isSticky() : bool {
    return $this->entity->isSticky();
  }

}
