<?php

/*

    Copyright (C) 2013  Jose Antonio Perez
    [ http://goo.gl/lW17d ]

    This file is part of dcmgw (Dicom gateway)
    [ https://github.com/jap1968/dcmgw ]

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/licenses/gpl.html
    
*/

// ******* ********* ********* ********* ********* ********* ********* *********

/**
 * Avoids direct execution of the script
 */
  if (!defined('DCMGW_INCLUDE')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    die;
  }

// ******* ********* ********* ********* ********* ********* ********* *********
// Configuration: Generic Dicom Storage SCP
// ******* ********* ********* ********* ********* ********* ********* *********

class PACS
{
  // DICOM setup
  public $AETitle;
  public $host;
  public $port;
  
  // WADO setup
  public $wadoProtocol;
  public $wadoHost;
  public $wadoPort;
  public $wadoScript;

  public $lang = 'en_US'; // No need to change
  public $encoding = 'iso-8859-1'; // May need to change to UTF-8

  function __construct($AETitle, $host, $port) {
    $this->AETitle = $AETitle;
    $this->host = $host;
    $this->port = $port;

    $this->wadoProtocol = 'http';
    $this->wadoHost = $host;
    $this->wadoPort = $port;
  }

  public function getDicomServer() {
    $dicomServer = $this->AETitle.'@'.$this->host.':'.$this->port;
    return $dicomServer;
  }

  /**
   * Force Dicom Q/R execution under a given locale to deal properly with non-ascii characters
   */
  public function getLocale() {
    return $this->lang.'.'.$this->encoding;
  }

  /**
   * In some environments Wado server runs independently of Dicom server
   */
  public function setWado($wadoProtocol, $wadoHost, $wadoPort, $wadoScript) {
    $this->wadoProtocol = $wadoProtocol;
    $this->wadoHost = $wadoHost;
    $this->wadoPort = $wadoPort;
    $this->wadoScript = $wadoScript;
  }

  public function setWadoScript($wadoScript) {
    $this->wadoScript = $wadoScript;
  }

  public function getUriWado($studyUID, $seriesUID, $objectUID) {
// Additional parameters ???
  // contentType
  // transferSyntax (UID)

    $codeBase = "{$this->wadoProtocol}://{$this->wadoHost}:{$this->wadoPort}/{$this->wadoScript}?";
    $queryString = "requestType=WADO&studyUID={$studyUID}&seriesUID={$seriesUID}&objectUID={$objectUID}";
    $uriWado = $codeBase.$queryString;
    return $uriWado;
  }
}

// ******* ********* ********* ********* ********* ********* ********* *********

// Specific subclasses for some popular Dicom servers

class DCM4CHEE extends PACS {
  function __construct($AETitle, $host) {
    $port = '11112';
    parent::__construct($AETitle, $host, $port);
    $this->wadoPort = '8080';
    $this->setWadoScript('wado');
  }
}

class CONQUEST extends PACS {
  function __construct($AETitle, $host, $port) {
    parent::__construct($AETitle, $host, $port);
    $this->setWadoScript('cgi-bin/dgate.exe');
  }
}

class CLEARCANVAS extends PACS {
  function __construct($AETitle, $host, $port) {
    parent::__construct($AETitle, $host, $port);
    $this->wadoPort = '1000';
    $this->setWadoScript('wado/'.$AETitle);
  }
}

// ******* ********* ********* ********* ********* ********* ********* *********

?>
