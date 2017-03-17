<?php

namespace Drupal\ctek_common\Annotation;

/**
 * @Annotation
 */
abstract class AnnotationBase {

  public function __construct($options) {
    if (isset($options['value'])) {
      $this->name = $options['value'];
      unset($options['value']);
    }
    foreach ($options as $key => $value) {
      if (!property_exists($this, $key)) {
        throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
      }

      $this->$key = $value;
    }
  }

}
