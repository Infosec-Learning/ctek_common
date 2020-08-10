<?php

namespace Drupal\ctek_common\Plugin\Block;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ctek_common\Block\NodeAwareBlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "node_block",
 *   admin_label = @Translation("Node Block")
 * )
 */
class NodeBlock extends NodeAwareBlockBase implements ContainerFactoryPluginInterface {

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')->getViewBuilder('node'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /** @var EntityFieldManagerInterface $entityFieldManager */
  protected $entityFieldManager;

  /** @var EntityViewBuilderInterface $viewBuilder */
  protected $viewBuilder;

  protected $view_mode = 'block';

  public function __construct(
    EntityFieldManagerInterface $entityManager,
    EntityViewBuilderInterface $viewBuilder,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $this->entityFieldManager = $entityManager;
    $this->viewBuilder = $viewBuilder;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public function defaultConfiguration() {
    return [
      'theme_suggestion' => '',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    return [
      'theme_suggestion' => [
        '#type' => 'textfield',
        '#title' => $this->t('Theme Suggestion'),
        '#default_value' => $this->configuration['theme_suggestion'],
        '#description' => 'Creates a theme suggestion for block--node-block--&lt;theme suggestion&gt;.html.twig',
      ],
    ];
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['theme_suggestion'] = $form_state->getValue('theme_suggestion');
  }

  public function build() : array {
    $build = parent::build();
    if (!$build) {
      return [];
    }

    $node = $this->getNode();

    $fields = $this->entityFieldManager->getFieldDefinitions('node', $node->bundle());
    foreach ($fields as $name => $definition) {
      // Build render first and validate if empty.
      $render = $this->viewBuilder->viewField($node->get($name), $this->view_mode);
      if (!empty($render[0])) {
        $build[$name] = $render;
      }
    }

    return $build;
  }

}
