<?php

namespace Drupal\ctek_common\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctek_search\SolrModelPluginManager;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ImportFormBase extends FormBase {

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.solr-model')
    );
  }

  protected $fileStorage;

  protected $solrModelPluginManager;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    SolrModelPluginManager $solrModelPluginManager
  ) {
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->solrModelPluginManager = $solrModelPluginManager;
  }

  abstract protected function getFileLabel();

  abstract protected function import(FileInterface $file);

  abstract protected static function getFileTypes() : array;

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->getFileLabel(),
      '#upload_validators' => [
        'file_validate_extensions' => [
          join(' ', static::getFileTypes()),
        ],
      ],
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    ];
    return $form;
  }

  protected function getFile(FormStateInterface $formState) {
    $files = $formState->getValue('file');
    return $this->fileStorage->load(reset($files));
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = $this->getFile($form_state);
    if (!$file instanceof FileInterface) {
      \Drupal::messenger()->addError('Unknown error occurred.');
    }
    $this->import($file);
  }

}
