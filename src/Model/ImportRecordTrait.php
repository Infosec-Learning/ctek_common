<?php

namespace Drupal\ctek_common\Model;

use Drupal\Component\Utility\Crypt;

trait ImportRecordTrait {

  public function computeHash($salt = '') {
    return Crypt::hashBase64($salt . serialize($this));
  }

}
