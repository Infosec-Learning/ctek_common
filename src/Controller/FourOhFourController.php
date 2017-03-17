<?php

namespace Drupal\ctek_common\Controller;

use Drupal\Core\Controller\ControllerBase;

class FourOhFourController extends ControllerBase {

  public function index() {
    return [
      '#theme' => 'four_oh_four'
    ];
  }

}