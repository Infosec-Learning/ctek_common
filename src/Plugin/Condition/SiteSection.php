<?php

namespace Drupal\ctek_common\Plugin\Condition;

use Drupal\Component\Utility\Html;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\Annotation\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ctek_common\SiteSection\SiteSectionPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Condition(
 *   id="site_section",
 *   label=@Translation("Site Section")
 * )
 */
class SiteSection extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  const SITE_SECTION_NONE = 'none';

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.site-section')
    );
  }

  protected $siteSectionPluginManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SiteSectionPluginManager $siteSectionPluginManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->siteSectionPluginManager = $siteSectionPluginManager;
  }

  public function defaultConfiguration() {
    return [
      'site_section' => [],
      ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['site_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site Sections'),
      'none' => [
        '#type' => 'checkbox',
        '#title' => $this->t('None'),
        '#default_value' => in_array(static::SITE_SECTION_NONE, $this->configuration['site_section']),
      ],
    ];
    foreach ($this->siteSectionPluginManager->getDefinitions() as $definition) {
      $form['site_section'][$definition['id']] = [
        '#type' => 'checkbox',
        '#title' => $definition['label'],
        '#default_value' => in_array($definition['id'], $this->configuration['site_section']),
        '#states' => [
          'enabled' => [
            '[name="visibility[site_section][site_section][none]"]' => ['checked' => FALSE],
          ],
        ],
      ];
    }
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['negate']['#access'] = FALSE;
    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['site_section'] = array_keys(array_filter($form_state->getValue('site_section')));
    parent::submitConfigurationForm($form, $form_state);
  }

  public function execute() {
    return parent::execute(); // TODO: Change the autogenerated stub
  }

  public function evaluate() {
    $currentSections = $this->siteSectionPluginManager->getCurrentSiteSections();
    if (in_array(static::SITE_SECTION_NONE, $this->configuration['site_section'])) {
      return count($currentSections) === 0;
    } else {
      return count($currentSections) > 0;
    }
  }

  public function summary() {
    if (in_array(static::SITE_SECTION_NONE, $this->configuration['site_section'])) {
      return $this->t('Current page belongs to no section of the site.');
    } else {
      $sections = [];
      foreach ($this->configuration['site_section'] as $id) {
        $definition = $this->siteSectionPluginManager->getDefinition($id);
        if ($definition) {
          $sections[] = $definition['label'];
        }
      }
      $sections = join(', ', $sections);
      return $this->t('Current page belongs to one or more of the following sections: @sections', ['@sections' => $sections]);
    }
  }

  public function getCacheTags() {
    return Cache::mergeTags($this->siteSectionPluginManager->getCacheTags(), parent::getCacheTags());
  }

  public function getCacheContexts() {
    return Cache::mergeContexts($this->siteSectionPluginManager->getCacheContexts(), parent::getCacheContexts());
  }

  public function getCacheMaxAge() {
    return Cache::mergeMaxAges($this->siteSectionPluginManager->getCacheMaxAge(), parent::getCacheMaxAge());
  }

}
