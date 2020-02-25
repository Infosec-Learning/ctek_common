<?php

namespace Drupal\ctek_common\Model;

use Drupal\ctek_gathercontent\Api\Model\ItemDetails;

interface ImportableModelInterface extends ModelInterface {

  const NEW = 1;
  const EXISTING_CHANGED = 2;
  const EXISTING_UNCHANGED = 3;

  const IMPORT_TRACKING_ID_FIELD = 'import_id';
  const IMPORT_TRACKING_HASH_FIELD = 'import_hash';

  const IMPORT_TRACKING_FIELDS = [
    self::IMPORT_TRACKING_ID_FIELD,
    self::IMPORT_TRACKING_HASH_FIELD,
  ];

  public static function getNewOrExisting(
    self $plugin,
    ImportRecordInterface $importData
  );

  public static function upsert(ItemDetails $itemDetails) : ImportableModelInterface;

  public static function getVersion() : int;

}
