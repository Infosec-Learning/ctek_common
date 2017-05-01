<?php
namespace Drupal\ctek_common\Plugin\Field\FieldWidget;

use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "address_geolocation",
 *   label = @Translation("Address form with geolocation"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressGeolocationWidget extends AddressDefaultWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['geolocate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Geolocate'),
    ];
    return $element;
  }

  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $fieldName = $this->fieldDefinition->getName();
    $addressField = $form_state->getValue($fieldName);
    if ($addressField) {
      $address = $addressField[0]['address'];
      $shouldGeolocate = $addressField[0]['geolocate'];
      if ($shouldGeolocate) {
        $addressParts = [
          $address['address_line1'],
          $address['address_line2'],
          $address['locality'],
          $address['administrative_area'],
          $address['postal_code'],
        ];
        $formattedAddress = array_filter($addressParts);
        /** @var \Drupal\geolocation\GeocoderManager $geocoderManager */
        $geocoderManager = \Drupal::service('plugin.manager.geolocation.geocoder');
        $geocode = $geocoderManager->getGeocoder('google_geocoding_api')
          ->geocode(join(' ', $formattedAddress));
        if ($geocode) {
          $form_state->setValue('field_geolocation', [
            [
              'lat' => $geocode['location']['lat'],
              'lng' => $geocode['location']['lng'],
            ]
          ]);
        }
      }
    }
    return parent::extractFormValues($items, $form, $form_state); // TODO: Change the autogenerated stub
  }

}