<?php

namespace Drupal\ctek_common\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * @RenderElement("glossary")
 */
class Glossary extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'glossary',
      '#letters' => [],
      '#url' => NULL,
      '#showCount' => FALSE,
      '#pre_render' => [
        [$class, 'preRender'],
      ],
    ];
  }

  public static function preRender($element) {
    $url = $element['#url'];
    if (!$url instanceof Url) {
      $url = Url::fromRoute('<current>', \Drupal::request()->query->all());
    }
    foreach ($element['#letters'] as $letter => &$count) {
      $count = [
        'value' => Markup::create($letter),
        'count' => Markup::create($count),
        'url' => Markup::create($url->mergeOptions(['query' => ['letter' => $letter, 'page' => 0]])->toString()),
      ];
    }
    return $element;
  }

}
