<?php

namespace Drupal\ctek_common\Routing\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\ctek_common\Model\ModelInterface;
use Symfony\Component\HttpFoundation\Request;

class ModelRouteEnhancer implements EnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    /** @var \Drupal\ctek_common\Model\ModelPluginManager $modelManager */
    $modelManager = \Drupal::service('plugin.manager.model');
    if ($model = $modelManager->wrapFromRouteDefaults($defaults)) {
      $defaults[ModelInterface::ROUTE_PARAMETER] = $model;
    }
    return $defaults;
  }

}
