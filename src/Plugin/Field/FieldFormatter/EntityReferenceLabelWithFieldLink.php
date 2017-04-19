<?php
namespace Drupal\ctek_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * @FieldFormatter(
 *   id = "entity_reference_label_with_field_link",
 *   label = @Translation("Label with field link"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceLabelWithFieldLink extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
        'fields' => [],
      ) + parent::defaultSettings();
  }

  protected function getBundles() {
    return array_keys($this->fieldDefinition->getSetting('handler_settings')['target_bundles']);;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = ['fields' => []];
    $entity_type = explode(':', $this->fieldDefinition->getSetting('handler'))[1];
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $manager */
    $manager = \Drupal::service('entity_field.manager');
    foreach ($this->getBundles() as $bundle) {
      $elements['fields'][$bundle] = [
        '#title' => t('Link Field (%bundle)', ['%bundle' => \Drupal::entityTypeManager()
          ->getStorage('node_type')
          ->load($bundle)
          ->label()]),
        '#type' => 'select',
        '#default_value' => isset($this->getSetting('fields')[$bundle]) ? $this->getSetting('fields')[$bundle] : NULL,
        '#options' => [],
        '#empty_option' => t('Use Default URL'),
        '#empty_value' => NULL,
      ];
      $definitions = $manager->getFieldDefinitions($entity_type, $bundle);
      foreach ($definitions as $definition) {
        if ($definition->getType() === 'link') {
          $elements['fields'][$bundle]['#options'][$definition->getName()] = $definition->getLabel();
        }
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    foreach ($this->getBundles() as $bundle) {
      $field = isset($this->getSetting('fields')[$bundle]) ? $this->getSetting('fields')[$bundle] : NULL;
      $summary[] = $field ? $field : t('Link to the referenced entity');
    }
    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      if (!$entity->isNew()) {
        try {
          $settings = $this->getSetting('fields');
          if (isset($settings[$entity->bundle()]) && !empty($settings[$entity->bundle()])) {
            $uri = Url::fromUri($entity->{$settings[$entity->bundle()]}->uri);
          } else {
            $uri = $entity->toUrl();
          }
        }
        catch (UndefinedLinkTemplateException $e) {
        }
      }
      if (isset($uri) && !$entity->isNew()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => $uri,
          '#options' => $uri->getOptions(),
        ];
        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += array('attributes' => array());
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = array('#plain_text' => $label);
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

}