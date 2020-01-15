<?php

namespace Drupal\ctek_common\Model;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ctek_common\Menu\MenuHelper;
use Drupal\node\NodeInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class NodeModelBase extends ModelBase implements
  NodeModelInterface,
  ContainerFactoryPluginInterface
{

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pathauto.alias_cleaner'),
      $container->get('ctek_common.menu_helper')
    );
  }

  protected $aliasCleaner;

  protected $menuHelper;

  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    AliasCleanerInterface $aliasCleaner,
    MenuHelper $menuHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasCleaner = $aliasCleaner;
    $this->menuHelper = $menuHelper;
  }

  public function getNode() : NodeInterface {
    return $this->entity;
  }

  public function getTitle() : string {
    return $this->entity->getTitle();
  }

  public function created() : int {
    return $this->entity->getCreatedTime();
  }

  public function changed() : int {
    return $this->entity->getChangedTime();
  }

  public function isPromoted() : bool {
    return $this->entity->isPromoted();
  }

  public function isSticky() : bool {
    return $this->entity->isSticky();
  }

  public function getAlias(array $options) : string {
    return $this->aliasCleaner->cleanString($this->getTitle(), $options);
  }

}
