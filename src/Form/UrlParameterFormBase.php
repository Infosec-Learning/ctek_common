<?php

namespace Drupal\ctek_common\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

abstract class UrlParameterFormBase extends FormBase {

  public abstract function getParametersForRoute();

  public function getRouteNameForRedirect(FormStateInterface $form_state) {
    return '<current>';
  }

  public function getRouteValuesForRedirect(array &$form, FormStateInterface $form_state, $allParams = []) {
    $routeValues = [];
    foreach ($this->getParametersForRoute() as $key) {
      $parents = [];
      if (is_array($key)) {
        ['key' => $key, 'parents' => $parents] = $key;
      }
      $parameterValue = $form_state->getValue($key);
      if (is_array($parameterValue)) {
        /**
         * Checkboxes return an array of all the options keyed with the option
         * key and with a value of (int) 0 if not checked, and then a string
         * value corresponding to the value, which could be (string) "0" which
         * is falsy, so we filter with a strict comparison. Otherwise, we end up
         * with a huge query string when we have checkboxes.
         */
        $parameterValue = array_values(array_filter($parameterValue, function($value){
          return $value !== 0;
        }));
      }
      $element = NestedArray::getValue($form, $parents + [$key]);
      if (!empty($parameterValue)) {
        $routeValues[$key] = $parameterValue;
      } elseif (isset($element['#type']) && !in_array($element['#type'], ['glossary', 'hidden'])) {
        if (isset($allParams[$key])) {
          unset($allParams[$key]);
        }
      }
    }
    $routeValues += $allParams;
    return $routeValues;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] === 'clear') {
      $form_state->setRedirect($this->getRouteNameForRedirect($form_state));
      return;
    }
    $currentRoute = \Drupal::routeMatch();
    $params = $currentRoute->getRawParameters();
    $form_state->setRedirect($this->getRouteNameForRedirect($form_state), (array)$params->getIterator() + $this->getRouteValuesForRedirect($form, $form_state));
  }

}
