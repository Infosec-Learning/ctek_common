<?php

namespace Drupal\ctek_common\Model;

interface UrlAliasProviderInterface {

  public function getAlias(array $options) : string;

}
