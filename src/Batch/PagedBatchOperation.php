<?php

namespace Drupal\ctek_common\Batch;

class PagedBatchOperation {

  protected $page;

  protected $totalPages;

  protected $job;

  public function __construct(
    PagedBatchJob $job,
    int $page,
    int $totalPages
  ) {
    $this->page = $page;
    $this->totalPages = $totalPages;
    $this->job = $job;
  }

  /**
   * @return callable
   */
  public function getCallback() : callable {
    return $this->job->getCallback();
  }

  /**
   * @return int
   */
  public function getPage(): int {
    return $this->page;
  }

  /**
   * @return int
   */
  public function getPageSize(): int {
    return $this->job->getPageSize();
  }

  /**
   * @return int
   */
  public function getTotal(): int {
    return $this->job->getTotal();
  }

  /**
   * @return int
   */
  public function getTotalPages() {
    return $this->totalPages;
  }


  /**
   * @return \Drupal\ctek_common\Batch\ManagedBatchJobInterface
   */
  public function getJob() : ManagedBatchJobInterface {
    return $this->job;
  }

}
