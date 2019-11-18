<?php

namespace Drupal\ctek_common\Model;

trait ModelAwareTrait {

  /**
   * @var \Drupal\ctek_common\Model\ModelInterface
   */
  protected $model;

  /**
   * @return \Drupal\ctek_common\Model\ModelInterface|null
   */
  public function getModel() : ?ModelInterface {
    if (!isset($this->model)) {
      $request = \Drupal::request();
      $model = $request->get(ModelInterface::ROUTE_PARAMETER);
      if ($model instanceof ModelInterface) {
        $this->model = $model;
      } else {
        $this->model = NULL;
      }
    }
    return $this->model;
  }

}
