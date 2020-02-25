<?php

namespace Drupal\ctek_common\Model;

interface ImportRecordInterface {

  public function getImportId();

  public function computeHash($salt = '');

}
