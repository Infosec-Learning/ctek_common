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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "linked_string",
 *   label = @Translation("Linked text"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class LinkedStringFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  public static function defaultSettings() {
    return [
      'link_field' => NULL,
    ];
  }

  protected static function getAvailableFields(FieldDefinitionInterface $fieldDefinition) {
    $entityType = $fieldDefinition->getTargetEntityTypeId();
    $bundle = $fieldDefinition->getTargetBundle();
    /** @var FieldDefinitionInterface[] $linkFields */
    $linkFields = array_filter(
      \Drupal::service('entity_field.manager')->getFieldDefinitions($entityType, $bundle),
      function(FieldDefinitionInterface $fieldDefinition){
        return $fieldDefinition->getType() === 'link';
      }
    );
    return $linkFields;
  }

  protected static function getAvailableFieldNames(FieldDefinitionInterface $fieldDefinition) {
    $linkFields = static::getAvailableFields($fieldDefinition);
    $fieldNames = [];
    foreach ($linkFields as $linkField) {
      $fieldNames[$linkField->getName()] = $linkField->getLabel();
    }
    return $fieldNames;
  }

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return count(static::getAvailableFields($field_definition)) > 0;
  }

  protected $entityTypeManager;
  protected $entityFieldManager;

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      $value = $item->getString();
      $linkField = $item->getEntity()->get($this->getSetting('link_field'))->first();
      if ($linkField instanceof LinkItemInterface) {
        $url = $linkField->getUrl();
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $value,
          '#url' => $url,
        ];
      } else {
        $elements[$delta] = [
          '#markup' => $value,
        ];
      }
    }
    return $elements;
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = static::getAvailableFieldNames($this->fieldDefinition);
    $form['link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#options' => $options,
    ];
    return $form;
  }

  public function settingsSummary() {
    $linkFields = static::getAvailableFieldNames($this->fieldDefinition);
    return [
      $this->t('Text linked to URI of field: <strong>@field</strong>', [
        '@field' => $linkFields[$this->getSetting('link_field')],
      ]),
    ];
  }

}
