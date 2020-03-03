<?php

namespace Drupal\ctek_common\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class GenericImportRecordBase extends ParameterBag implements ImportRecordInterface {
  use ImportRecordTrait;

}
