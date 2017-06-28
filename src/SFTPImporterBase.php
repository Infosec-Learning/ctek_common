<?php
namespace Drupal\ctek_common;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use phpseclib\Net\SFTP;
use Psr\Log\LoggerInterface;

abstract class SFTPImporterBase implements ImporterInterface {

  protected $state;
  protected $config;
  protected $fileSystem;
  protected $file;
  protected $log;

  abstract public function getKey();

  public function __construct(ConfigFactoryInterface $configFactory, LoggerInterface $log, StateInterface $state, FileSystemInterface $fileSystem) {
    $this->config = $configFactory->get($this->getKey() . '.settings');
    $this->log = $log;
    $this->state = $state;
    $this->fileSystem = $fileSystem;
  }

  public function test($host, $path, $username, $password) {
    $sftp = new SFTP($host);
    $this->log->info('Logging in to SFTP server.');
    if (!$sftp->login($username, $password)) {
      throw new \Exception('Unable to log in to SFTP server.');
    }
    $this->log->info('Logged in to SFTP server.');
    $this->log->info('Attempting to stat file.');
    if (!$sftp->stat($path)) {
      throw new \Exception('Unable to stat file.');
    }
    $this->log->info('File stat successful.');
    return TRUE;
  }

  public function import() {
    $config = $this->config;
    $path = $config->get('path');
    $tempFilename = 'temporary://' . basename($path);
    $sftp = new SFTP($config->get('host'));
    $this->log->info('Logging in to SFTP server.');
    if (!$sftp->login($config->get('username'), $config->get('password'))) {
      throw new \Exception('Unable to log in to SFTP server.');
    }
    $this->log->info('Downloading file.');
    if (!$sftp->get($path, $tempFilename)) {
      throw new \Exception('Unable to download file.');
    }
    $this->log->info('File downloaded.');
    $this->file = $tempFilename;
  }

}