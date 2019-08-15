<?php

namespace Drupal\ctek_common\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase as BaseParagraphsBehaviorBase;

abstract class ParagraphsBehaviorBase extends BaseParagraphsBehaviorBase {

  public function getImageStyle(
    ParagraphInterface $paragraph,
    array $field,
    ParagraphInterface $parent,
    EntityViewDisplayInterface $display,
    $view_mode
  ) {
    return NULL;
  }

  public function getResponsiveImageStyle(
    ParagraphInterface $paragraph,
    array $field,
    ParagraphInterface $parent,
    EntityViewDisplayInterface $display,
    $view_mode
  ) {
    return NULL;
  }

  public static function isApplicable(ParagraphsType $paragraphs_type) {
    /** @var \Drupal\paragraphs\ParagraphsBehaviorManager $manager */
    $manager = \Drupal::service('plugin.manager.paragraphs.behavior');
    $definitions = $manager->getDefinitions();
    $staticClass = get_called_class();
    foreach ($definitions as $definition) {
      if ($definition['class'] === $staticClass) {
        return $definition['id'] === $paragraphs_type->id();
      }
    }
  }

}
