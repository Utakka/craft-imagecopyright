<?php
/**
 * Image Copyright
 *
 * Copyrightangaben von Bildern (Metadaten) beim Import auslesen
 *
 * @link      https://stockwerk2.de
 * @copyright Copyright (c) 2019 Stockwerk2
 */

namespace stockwerk2\imagecopyright;

use Craft;
use craft\base\Plugin;
use craft\services\Elements;
use craft\elements\Asset;
use yii\base\Event;


class Imagecopyright extends Plugin {


  public function init() {

    parent::init();

    Event::on(
      Elements::class,
      Elements::EVENT_BEFORE_SAVE_ELEMENT,
      function(Event $event) {

        if(
          $event->element instanceof craft\elements\Asset &&         // only assets
          isset( $event->element->tempFilePath ) &&                  // only with tmp upload file (new upload)
          preg_match('/jp(e?)g$/i', $event->element->filename) &&    // only jpg images
          $this->_hasEmptyFields( $event->element )                  // only when copyright fields are empty
        ) {

          $this->_handleAssetUpload( $event->element );

        }

      }
    );

  }


  private function _hasEmptyFields( $asset ) {

    return
      in_array("copyright", $asset->fields()) &&
      in_array("quelle", $asset->fields()) &&
      !$asset->getFieldValue("copyright") &&
      !$asset->getFieldValue("quelle");

  }


  private function _handleAssetUpload( $asset ) {

    $importCopyright = "";
    $importQuelle = "";

    // exif
    if( $exif = exif_read_data( $asset->tempFilePath, 'IFD0', true ) ) {

      if( isset( $exif["IFD0"]["Copyright"] ) ) {
        $importCopyright = trim($exif["IFD0"]["Copyright"]);
      }

    }

    // iptc
    if( getimagesize($asset->tempFilePath, $info) && isset($info['APP13']) ) {

      $iptc = iptcparse($info['APP13']);

      if( empty($importCopyright) && isset($iptc["2#116"][0]) ) {
        $importCopyright = $iptc["2#116"][0];
      }

      if( isset($iptc["2#115"][0]) ) {
        $importQuelle = $iptc["2#115"][0];
      }

    }

    // set data
    $asset->setFieldValue( "copyright", trim(strip_tags($importCopyright)) );
    $asset->setFieldValue( "quelle", trim(strip_tags($importQuelle)) );

  }


}
