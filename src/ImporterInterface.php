<?php
namespace Drupal\ctek_common;

interface ImporterInterface {

  public function test($host, $path, $username, $password);

  public function import();

}