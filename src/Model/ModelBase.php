<?php

namespace Drupal\ctek_common\Model;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Base class for model plugins.
 */
abstract class ModelBase extends PluginBase implements ModelInterface {

  public static function getCache() : CacheBackendInterface {
    return \Drupal::cache(static::CACHE_BIN);
  }

  public static function getCacheId($functionName, ...$params) : string {
    return get_called_class() . '::' . $functionName . '(' . join(', ', $params) . ')';
  }

  /** @var \Drupal\Core\Entity\ContentEntityInterface */
  protected $entity;

  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity = $configuration['entity'];
  }

  public function getEntity() : ContentEntityInterface {
    return $this->entity;
  }

  public function getId() : int {
    $id = intval($this->entity->id());
    if ($id === 0) {
      throw new \LogicException('Invalid id.');
    }
    return $id;
  }

  public function getBundle() : string {
    return $this->entity->bundle();
  }

  public function getEntityTypeId() : string {
    return $this->entity->getEntityTypeId();
  }

  public function getCacheMaxAge() : int {
    return $this->entity->getCacheMaxAge();
  }

  public function getCacheContexts() : array {
    return $this->entity->getCacheContexts();
  }

  public function getCacheTags() : array {
    return $this->entity->getCacheTags();
  }

  protected function extractComplexData(TypedDataInterface $data, $mapping, callable $transformCallback = NULL) : ?array {
    if (!$data) {
      return NULL;
    }
    $data = $data->toArray();
    $data = array_combine($mapping, array_map(function($key) use ($data, $transformCallback) {
      if (is_callable($transformCallback)) {
        return $transformCallback($data[$key]);
      }
      return $data[$key];
    }, $mapping));
    return $data;
  }

  protected function getOne(
    string $field,
    callable $valueCallback = NULL,
    $defaultValue = NULL
  ) {
    $entity = $this->getEntity();
    if (!$entity->hasField($field)) {
      return $defaultValue;
    }
    $field = $entity->get($field);
    if ($field->count() === 0) {
      return $defaultValue;
    }
    $value = $field->first();
    return $valueCallback ? $valueCallback($value) : $value->value;
  }

  protected function getAll(
    string $field,
    callable $valueCallback = NULL,
    $defaultValue = []
  ) : array {
    $values = [];
    $entity = $this->getEntity();
    if (!$entity->hasField($field)) {
      return $defaultValue;
    }
    $field = $entity->get($field);
    if ($field->count() === 0) {
      return $defaultValue;
    }
    /** @var \Drupal\Core\Field\FieldItemInterface $value */
    foreach ($field as $value) {
      $values[] = $valueCallback ? $valueCallback($value) : $value->value;
    }
    return $values;
  }

  protected function getReferencedEntity(
    EntityReferenceItem $item,
    callable $valueCallback = NULL,
    $defaultValue = NULL
  ) {
    $entity = $item->entity;
    if ($entity === NULL) {
      return $defaultValue;
    }
    return $valueCallback ? $valueCallback($entity) : $entity;
  }

}
