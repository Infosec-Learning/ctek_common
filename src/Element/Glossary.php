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
    $showCount = $element['#showCount'];
    if (!$url instanceof Url) {
      $url = Url::fromRoute('<current>', \Drupal::request()->query->all());
    }
    foreach ($element['#letters'] as $letter => &$count) {
      $count = [
        'value' => Markup::create($letter),
        'count' => Markup::create($count),
        'link' => [
          '#type' => 'link',
          '#title' => $letter . ($showCount ? "($count)" : ''),
          '#url' => (clone $url)->mergeOptions([
            // '0' must be a string because dumb reasons in active-link.js.
            'query' => ['letter' => $letter, 'page' => '0'],
            'set_active_class' => TRUE,
          ])
        ],
      ];
    }
    return $element;
  }

}
