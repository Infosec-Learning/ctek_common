<?php

namespace Drupal\ctek_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "link_with_default_title",
 *   label = @Translation("Link with default title"),
 *   field_types = {
 *     "link",
 *   }
 * )
 */
class LinkWithDefaultTitleFormatter extends LinkFormatter {

  public static function defaultSettings() {
    return [
      'default_title' => '',
    ] + parent::defaultSettings();
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    /** @var LinkItemInterface $item */
    foreach ($items as $delta => $item) {
      if (empty($item->get('title')->getString())) {
        $elements[$delta]['#title'] = $this->getSetting('default_title');
      }
    }
    return $elements;
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['default_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default title'),
    ];
    return $form;
  }

  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $defaultTitle = $this->getSetting('default_title');
    if ($defaultTitle) {
      $summary[] = $this->t('Default title: <strong>@default_title</strong>', [
        '@default_title' => $defaultTitle,
      ]);
    }
    return $summary;
  }

}
