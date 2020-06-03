<?php

namespace Drupal\ctek_common\Plugin\EntityReferenceSelection;

  use Drupal\Core\Database\Query\SelectInterface;
  use Drupal\Core\Entity\Annotation\EntityReferenceSelection;
  use Drupal\Core\Entity\Query\QueryInterface;
  use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection as BaseNodeSelection;

class NodeSelection extends BaseNodeSelection {

  public const QUERY_TAG_DONT_SET_RANGE = 'dont_set_range';

  protected $match;

  protected $matchOperator;

  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS'): QueryInterface {
    $this->match = $match;
    $this->matchOperator = $match_operator;
    return parent::buildEntityQuery($match, $match_operator);
  }

  public function entityQueryAlter(SelectInterface $query): void {
    if (!$query->hasTag(static::QUERY_TAG_DONT_SET_RANGE)) {
      $query->range(0, 20);
    }
    if ($this->matchOperator === 'CONTAINS') {
      $sort = <<<EOSQL
CASE WHEN LOWER(node_field_data.title) = LOWER(:keywords) THEN 1
ELSE 2
END
EOSQL;
      $query->addExpression($sort, 'exactMatchSort', [':keywords' => $this->match]);
      $query->orderBy('exactMatchSort');
      $query->groupBy('exactMatchSort');
      $query->orderBy('node_field_data.title');
      $query->groupBy('node_field_data.title');
      $query->groupBy('base_table.vid');
      $query->groupBy('base_table.nid');
    }
    parent::entityQueryAlter($query);
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $query = $this->buildEntityQuery();
      $query->addTag(static::QUERY_TAG_DONT_SET_RANGE);
      $result = $query
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }

    return $result;
  }

}
