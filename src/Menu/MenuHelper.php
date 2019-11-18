<?php

namespace Drupal\ctek_common\Menu;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;

class MenuHelper {

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MenuHelper constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menuActiveTrail
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    MenuLinkManagerInterface $menuLinkManager,
    MenuActiveTrailInterface $menuActiveTrail,
    MenuLinkTreeInterface $menuLinkTree,
    EntityTypeManagerInterface $entityTypeManager) {
    $this->menuLinkManager = $menuLinkManager;
    $this->menuActiveTrail = $menuActiveTrail;
    $this->menuLinkTree = $menuLinkTree;
    $this->entityTypeManager = $entityTypeManager;
  }

  public function getActiveLink($menu = NULL) : ?MenuLinkInterface {
    return $this->menuActiveTrail->getActiveLink($menu);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   */
  public function getLinksForEntity(EntityInterface $entity) : array {
    $links = $this->menuLinkManager->loadLinksByRoute(
      'entity.' . $entity->getEntityTypeId() . '.canonical',
      [$entity->getEntityTypeId() => $entity->id()]
    );
    return $links;
  }

  /**
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   * @param callable|NULL $filter
   * @return \Drupal\Core\Menu\MenuLinkInterface[]
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getParents(MenuLinkInterface $link, callable $filter = NULL) : array {
    $parents = [];
    while ($parent = $link->getParent()) {
      $link = $this->menuLinkManager->createInstance($parent);
      $parents[$link->getPluginId()] = $link;
    }
    if ($filter) {
      $parents = array_filter($parents, $filter);
    }
    return $parents;
  }

  public function hasChildren(MenuLinkInterface $link) : bool {
    return count($this->menuLinkManager->getChildIds($link->getPluginId())) > 0;
  }

  public function getMenuByRoot(
    MenuLinkInterface $root = NULL,
    $minDepth = 0,
    $maxDepth = 0,
    $expand = FALSE,
    $ensureParents = FALSE,
    callable $transformCallback = NULL,
    $includeContextualLinks = TRUE
  ) : array {
    $activeLink = $this->getActiveLink();
    if (!$root) {
      $root = $activeLink;
    } elseif (is_string($root)) {
      try {
        $root = $this->menuLinkManager->createInstance($root);
      } catch (\Exception $e) {
        return NULL;
      }
    }
    if (!$root) {
      return NULL;
    }
    $menuName = $root->getMenuName();
    $params = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($menuName);
    $params->setRoot($root->getPluginId());
    if ($minDepth) {
      $params->setMinDepth($minDepth);
    }
    if ($maxDepth) {
      $params->setMaxDepth($maxDepth);
    }
    if ($expand) {
      $params->expandedParents = [];
    }
    $tree = $this->menuLinkTree->load($menuName, $params);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    if ($ensureParents) {
      array_push($manipulators, [
        'callable' => [$this, 'ensureParent'],
        'args' => [$root],
      ]);
    }
    if (is_callable($transformCallback)) {
      array_push($manipulators, [
        'callable' => $transformCallback,
        'args' => [$root],
      ]);
    }
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $contextualLinks = [
      'menu' => [
        'route_parameters' => ['menu' => $menuName],
      ],
    ];
    if ($includeContextualLinks) {
      $build = [
        '#type' => 'container',
        '#attributes' => ['class' => ['contextual-region']],
        [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($contextualLinks),
        ],
      ];
    } else {
      $build = [];
    }
    if ($tree) {
      $build['menu'] = $this->menuLinkTree->build($tree);
    } else {
      $build = [];
    }
    $build['#cache']['contexts'][] = 'route.menu_active_trails:' . $menuName;
    return $build;
  }

  public function getMenuByName(
    $menuName,
    $minDepth = 0,
    $maxDepth = 0,
    $expand = FALSE,
    callable $transformCallback = NULL,
    $includeContextualLinks = TRUE
  ) : array {
    $params = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($menuName);
    if ($minDepth) {
      $params->setMinDepth($minDepth);
    }
    if ($maxDepth) {
      $params->setMaxDepth($maxDepth);
    }
    if ($expand) {
      $params->expandedParents = [];
    }
    $tree = $this->menuLinkTree->load($menuName, $params);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    if (is_callable($transformCallback)) {
      array_push($manipulators, [
        'callable' => $transformCallback,
      ]);
    }
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $contextualLinks = [
      'menu' => [
        'route_parameters' => ['menu' => $menuName],
      ],
    ];
    if ($includeContextualLinks) {
      $build = [
        '#type' => 'container',
        '#attributes' => ['class' => ['contextual-region']],
        [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($contextualLinks),
        ],
      ];
    } else {
      $build = [];
    }
    if ($tree) {
      $build['menu'] = $this->menuLinkTree->build($tree);
    } else {
      $build = [];
    }
    $build['#cache']['contexts'][] = 'route.menu_active_trails:' . $menuName;
    return $build;
  }

}
