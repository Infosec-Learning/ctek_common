<?php

namespace Drupal\ctek_common\TwigExtension;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

class Image extends \Twig_Extension {

  public function getName() {
    return 'nebraska.image';
  }

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('image_style', [$this, 'imageStyle'], ['is_safe' => ['html']]),
    ];
  }

  public function imageStyle($fileId, $style) {
    if (!$fileId) return '';
    $file = File::load($fileId);
    if ($file && isset($file->uri->value)) {
      $imageStyle = ImageStyle::load($style);
      if ($imageStyle) {
        return $imageStyle->buildUrl($file->uri->value);
      }
    }
    return '';
  }

}