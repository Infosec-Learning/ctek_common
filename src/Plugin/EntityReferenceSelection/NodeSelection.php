<?php

namespace Drupal\ctek_common\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Annotation\EntityReferenceSelection;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection as BaseNodeSelection;

/**
 * Increases number of results returned, and sorts exact matches before partial.
 *
 * @EntityReferenceSelection(
 *   id = "ctek_common:node",
 *   label = @Translation("User selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 2
 * )
 */
class NodeSelection extends BaseNodeSelection {

  protected $match;

  protected $matchOperator;

  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') : QueryInterface {
    $this->match = $match;
    $this->matchOperator = $match_operator;
    return parent::buildEntityQuery($match, $match_operator);
  }

  public function entityQueryAlter(SelectInterface $query) : void {
    $query->range(0, 20);
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

}
