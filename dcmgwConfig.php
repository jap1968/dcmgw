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
// Configuration: Change values to fit your environment
// ******* ********* ********* ********* ********* ********* ********* *********

// You should end up having a variable $pacs pointing to your PACS server.
// You can find below some configuration samples for public servers
// Please, for testing purposes use your own server (if possible) to avoid overloading the public servers

// http://www.dicomserver.co.uk/
// http://wado.medicalconnections.co.uk/WADO/WADO.asp
$pacsMedicalConnections = new PACS('TEST', 'www.dicomserver.co.uk', '11112');
$pacsMedicalConnections->setWado('http', 'www.dicomserver.co.uk', '80', 'wado/WADO.asp');

// DC's public PixelMed PACS.
// No WADO?
$pacsPixelMed = new PACS('AWSPIXELMEDPUB', '184.73.255.26', '11112');

// Public JVSdicom Server. Not sure if this one has any contents.
// No WADO ?
// More information: http://153.1.200.58/?q=dicom_images
$pacsJVSdicom = new PACS('JVSDICOM', '153.1.200.58', '104');

// My own PACS
$pacsHome = new DCM4CHEE('DCMTEST', '192.168.1.44');


$pacs = $pacsHome;

// ******* ********* ********* ********* ********* ********* ********* *********

// This script makes use of dcmqr, from the dcm4che2 Dicom toolkit
// dcm4che2 Dicom toolkit: http://www.dcm4che.org/confluence/display/d2/dcm4che2+DICOM+Toolkit
// dcmqr: http://www.dcm4che.org/confluence/display/d2/dcmqr
define ('PATH_BASE_DCM4CHE2', '/usr/local/dcm4che/dcm4che2/bin/');
define ('PATH_BASE_DICOM_CACHE', '/tmp/dicom/');

define ('PATH_WGET', '/usr/bin/wget');
define ('RETRIEVE_LOCAL', true);

define('SHOW_REQUEST', false);
define('XML_ENCODING', 'UTF-8');

define ('DEBUG_NONE', 0);
define ('DEBUG_INFO', 5);
define ('DEBUG_DUMP', 9);
define ('DEBUG_LEVEL', DEBUG_NONE);

define ('AETITLE_GATEWAY', 'DCMGW');
define ('THUMBNAIL_SIZE', '200');

// ******* ********* ********* ********* ********* ********* ********* *********

define('QUERY_REQUEST_ROOT', '0');
define('QUERY_RESPONSE_ROOT', '1');
define('QUERY_REQUEST', '2');
define('QUERY_RESPONSE', '3');

// ******* ********* ********* ********* ********* ********* ********* *********

?>
