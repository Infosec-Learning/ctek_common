<?php

namespace Drupal\ctek_common\Import;

class ImportJob {

  const PAGE_INDEX_0 = 0;
  const PAGE_INDEX_1 = 1;

  const DEFAULT_PAGE_SIZE = 50;

  public static function create(callable $callback) {
    return new static($callback);
  }

  protected $callback;

  protected $pageIndex;

  protected $page;

  protected $pageSize;

  protected $total;

  protected $arguments = [];

  protected function __construct(callable $callback) {
    $this->callback = $callback;
    $this->setPageIndex(static::PAGE_INDEX_0);
    $this->setPage(static::PAGE_INDEX_0);
    $this->setPageSize(static::DEFAULT_PAGE_SIZE);
    $this->setTotal(0);
  }

  public function setPage(int $page) : ImportJob {
    $this->page = $page;
    return $this;
  }

  public function getPage() {
    return $this->page;
  }

  public function setPageIndex($pageIndex) : ImportJob {
    $this->pageIndex = $pageIndex;
    $this->setPage($pageIndex);
    return $this;
  }

  public function getPageIndex() {
    return $this->pageIndex;
  }

  /**
   * @return int
   */
  public function getPageSize(): int {
    return $this->pageSize;
  }

  /**
   * @param int $pageSize
   *
   * @return ImportJob
   */
  public function setPageSize(int $pageSize) : ImportJob {
    $this->pageSize = $pageSize;
    return $this;
  }

  /**
   * @return int
   */
  public function getTotal(): int {
    return $this->total;
  }

  /**
   * @param int $total
   *
   * @return ImportJob
   */
  public function setTotal(int $total): ImportJob {
    $this->total = $total;
    return $this;
  }

  public function addArgument($name, $value) : ImportJob {
    $this->arguments[$name] = $value;
    return $this;
  }

  public function getArguments() {
    return $this->arguments;
  }

  public function getArgument($name) {
    return $this->arguments[$name];
  }

  /**
   * @return \Drupal\ctek_common\Import\ImportOperation[]
   */
  public function getOperations() : array {
    $operations = [];
    $totalPages = ceil($this->getTotal() / $this->getPageSize());
    for ($i = 0; $i < $totalPages; $i++) {
      $operations[] = new ImportOperation($this->callback, $i + $this->getPageIndex(), $totalPages, $this);
    }
    return $operations;
  }

}
