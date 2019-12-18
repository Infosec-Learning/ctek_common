<?php

namespace Drupal\ctek_common\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

abstract class UrlParameterFormBase extends FormBase {

  public abstract function getParametersForRoute();

  public function getRouteNameForRedirect() {
    $currentRoute = \Drupal::routeMatch();
    return $currentRoute->getRouteName();
  }

  public function getRouteValuesForRedirect(FormStateInterface $form_state) {
    $routeValues = [];
    foreach ($this->getParametersForRoute() as $parameter) {
      $parameterValue = $form_state->getValue($parameter);
      if (is_array($parameterValue)) {
        /**
         * Checkboxes return an array of all the options keyed with the option
         * key and with a value of (int) 0 if not checked, and then a string
         * value corresponding to the value, which could be (string) "0" which
         * is falsy, so we filter with a strict comparison. Otherwise, we end up
         * with a huge query string when we have checkboxes.
         */
        $parameterValue = array_filter($parameterValue, function($value){
          return $value !== 0;
        });
      }
      if (!empty($parameterValue)) {
        $routeValues[$parameter] = $parameterValue;
      }
    }
    return $routeValues;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] === 'clear') {
      $form_state->setRedirect('<current>');
      return;
    }
    $currentRoute = \Drupal::routeMatch();
    $params = $currentRoute->getRawParameters();
    $form_state->setRedirect($this->getRouteNameForRedirect(), (array)$params->getIterator() + $this->getRouteValuesForRedirect($form_state));
  }

}
