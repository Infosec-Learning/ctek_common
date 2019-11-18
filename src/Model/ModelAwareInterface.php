<?php

namespace Drupal\ctek_common\Model;

interface ModelAwareInterface {

  /**
   * @return \Drupal\ctek_common\Model\ModelInterface|null
   */
  public function getModel() : ?ModelInterface;

}
