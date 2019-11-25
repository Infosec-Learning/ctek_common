<?php

namespace Drupal\ctek_common\Breadcrumb;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class PathBasedBreadcrumbWithTitle extends PathBasedBreadcrumbBuilder {

  protected $requestStack;

  public function __construct(
    RequestStack $requestStack,
    RequestContext $context,
    AccessManagerInterface $access_manager,
    RequestMatcherInterface $router,
    InboundPathProcessorInterface $path_processor,
    ConfigFactoryInterface $config_factory,
    TitleResolverInterface $title_resolver,
    AccountInterface $current_user,
    CurrentPathStack $current_path,
    PathMatcherInterface $path_matcher = NULL
  ) {
    parent::__construct(
      $context,
      $access_manager,
      $router,
      $path_processor,
      $config_factory,
      $title_resolver,
      $current_user,
      $current_path,
      $path_matcher
    );
    $this->requestStack = $requestStack;
  }

  public function build(RouteMatchInterface $route_match) {
    $parentBreadcrumb = parent::build($route_match);
    $breadcrumb = new Breadcrumb();
    foreach ($parentBreadcrumb->getLinks() as $link) {
      $breadcrumb->addLink($link);
    }
    $request = $this->requestStack->getCurrentRequest();
    $title = $this->titleResolver->getTitle($request,  $route_match->getRouteObject());
    $breadcrumb->addLink(new Link($title, Url::fromRoute('<nolink>')));
    return $breadcrumb;
  }

}
