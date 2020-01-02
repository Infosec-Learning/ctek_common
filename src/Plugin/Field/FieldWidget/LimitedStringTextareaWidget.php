<?php


namespace Drupal\ctek_common\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_textarea' widget.
 *
 * @FieldWidget(
 *   id = "string_textarea_limited",
 *   label = @Translation("Text area (multiple rows, limited)"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class LimitedStringTextareaWidget extends StringTextareaWidget {

  public static function defaultSettings() {
    return [
        'max-length' => '',
      ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['max-length'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Length'),
      '#default_value' => $this->getSetting('max-length'),
      '#min' => 1,
      '#step' => 1,
    ];
    return $form;
  }

  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $maxLength = $this->getSetting('max-length');
    if ($maxLength) {
      $summary[] = $this->t('Limited to @max-length characters.', [
        '@max-length' => $maxLength,
      ]);
    } else {
      $summary[] = $this->t('No character limit.');
    }
    return $summary;
  }

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $maxLength = $this->getSetting('max-length');
    if ($maxLength) {
      $element['value']['#attributes']['maxlength'] = $maxLength;
    }
    return $element;
  }

}
