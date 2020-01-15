<?php

namespace Drupal\ctek_common\Model;

interface NodeModelInterface extends ModelInterface, UrlAliasProviderInterface {

  public function getTitle() : string;

  public function created() : int;

  public function changed() : int;

  public function isPromoted() : bool;

  public function isSticky() : bool;

}
