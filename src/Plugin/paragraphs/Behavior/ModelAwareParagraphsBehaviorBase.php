<?php


namespace Drupal\ctek_common\Plugin\paragraphs\Behavior;


use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\ctek_common\Model\ModelAwareInterface;
use Drupal\ctek_common\Model\ModelAwareTrait;
use Drupal\paragraphs\Entity\Paragraph;

abstract class ModelAwareParagraphsBehaviorBase extends ParagraphsBehaviorBase implements ModelAwareInterface {

  use ModelAwareTrait;

  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) : void {
    $model = $this->getModel();
    $build['#cache']['contexts'] = Cache::mergeContexts($build['#cache']['contexts'], $model->getCacheContexts());
    $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], $model->getCacheTags());
  }

}
