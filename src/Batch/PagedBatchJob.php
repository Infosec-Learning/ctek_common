<?php

namespace Drupal\ctek_common\Batch;

class PagedBatchJob extends ManagedBatchJobBase {

  const PAGE_INDEX_0 = 0;
  const PAGE_INDEX_1 = 1;

  const DEFAULT_PAGE_SIZE = 50;

  protected $pageIndex;

  protected $page;

  protected $pageSize;

  protected $total;

  public function __construct(callable $callback) {
    parent::__construct($callback);
    $this->setPageIndex(static::PAGE_INDEX_0);
    $this->setPage(static::PAGE_INDEX_0);
    $this->setPageSize(static::DEFAULT_PAGE_SIZE);
    $this->setTotal(0);
  }

  public function setPage(int $page) : PagedBatchJob {
    $this->page = $page;
    return $this;
  }

  public function getPage() {
    return $this->page;
  }

  public function setPageIndex($pageIndex) : PagedBatchJob {
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
   * @return PagedBatchJob
   */
  public function setPageSize(int $pageSize) : PagedBatchJob {
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
   * @return PagedBatchJob
   */
  public function setTotal(int $total): PagedBatchJob {
    $this->total = $total;
    return $this;
  }

  public function createOperations(ManagedBatch $batch, callable $wrapper = NULL) {
    $totalPages = ceil($this->getTotal() / $this->getPageSize());
    for ($i = 0; $i < $totalPages; $i++) {
      $batch->addOperation(
        $wrapper,
        $this->callback,
        new PagedBatchOperation(
          $this,
          $i + $this->getPageIndex(),
          $totalPages
        )
      );
    }
  }

}
