<?php

namespace Drupal\ctek_common\Logger;

use Drupal\Component\Serialization\Json;

class BatchLog implements \Iterator {

  protected $file;

  public function __construct(\SplFileObject $file) {
    $this->file = $file;
  }

  public function valid() {
    return $this->file->valid();
  }

  public function key() {
    return $this->file->key();
  }

  public function current() {
    return Json::decode($this->file->current());
  }

  public function next() {
    $this->file->next();
  }

  public function rewind() {
    $this->file->rewind();
  }

}
