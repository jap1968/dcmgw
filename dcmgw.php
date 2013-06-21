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

define('DCMGW_INCLUDE', './');

include_once(DCMGW_INCLUDE.'dcmgwPacs.php');
include_once(DCMGW_INCLUDE.'dcmgwConfig.php');

// *****************************************************************************

// operation: cfind | dicomfields | wado | binarydata
$operation = isset($_GET['operation']) ? $_GET['operation'] : 'cfind';

if ($operation == 'cfind') {
  if (isset($_GET['studyUID'])) {
    $studyUID = $_GET['studyUID'];
    dicomQRStudy($pacs, $studyUID);
  }
  else {
    dicomQR($pacs);
  }
}
else if ($operation == 'dicomfields') {
  $studyUID = $_GET['studyUID'];
  $seriesUID = $_GET['seriesUID'];
  $objectUID = $_GET['objectUID'];
  getDicomWadoFields($pacs, $studyUID, $seriesUID, $objectUID);
}
else if ($operation == 'binarydata') {
  $studyUID = $_GET['studyUID'];
  $seriesUID = $_GET['seriesUID'];
  $objectUID = $_GET['objectUID'];
  $len = $_GET['len'];
  $src = $_GET['src'];
  getBinaryData($pacs, $studyUID, $seriesUID, $objectUID, $len, $src);
}
else {
// define('TAM_MAX_THUMB', 120);
  $studyUID = $_GET['studyUID'];
  $seriesUID = $_GET['seriesUID'];
  $objectUID = $_GET['objectUID'];
  getWado($pacs, $studyUID, $seriesUID, $objectUID);

/*
  $uriWado = $pacs->getUriWado($studyUID, $seriesUID, $objectUID);
  // To get thumbnails:
  if (isset($_GET['rows']) && isset($_GET['cols'])) {
    $uriWado .= "&rows={$_GET['rows']}&cols={$_GET['cols']}";
  }
//  header("Cache-Control: public");
//  header('Expires: '.gmdate('D, d M Y H:i:s', strtotime('+1 day')).' GMT');
  header("Content-Type: image/jpeg");
  readfile($uriWado);
*/
}

// *****************************************************************************
// *****************************************************************************

/**
 * The Query/Retrieve answer is processed
 * outputRes: Q/R answer string
 */

function processResponse($outputRes, $encoding) {

  $pattern = "/^((?:[0|1][0-9]|2[0-3])(?::[0-5][0-9]){2},[0-9]{3})\s([A-Z]+)\s+-\s(.+)$/";
  $dicom = array();
  
  foreach ($outputRes as $numLine => $strLine) {
    $matches = array();
    $numMatches = preg_match($pattern, $strLine, $matches);
    if ($numMatches == 1) {
      // $matches[1]: time (hh:mm:ss,mil)
      // $matches[2]: INFO|ERROR|???
      $outputType = $matches[2];
      // $matches[3]: info string
      $outputStr = $matches[3];
      if (DEBUG_LEVEL >= DEBUG_DUMP) {
        // Mensajes de tipo INFO / ERROR
        echo "<div style='color:red; margin:2em 0.5em;'>Patr&oacute;n reconocido (I)</div>";
        echo "<pre>";
        print_r($matches);
        echo "</pre>";
      }
// 20120120: ToDo: Dealing with errors. Inform the client about the error
      if ($outputType == 'ERROR') {
        echo "ERROR";
        if (DEBUG_LEVEL >= DEBUG_INFO) {
          echo ": $outputStr";
        }
        echo "<br>\n";
        return false;
      }

      if ($element = identifyPattern($outputStr)) {
        $lineNum = $numLine + 1;
        $xmlString = '';
        while(strlen($outputRes[$lineNum]) > 0) {
          if (DEBUG_LEVEL >= DEBUG_INFO) {
            echo $outputRes[$lineNum]."<br>\n";
          }
          if ($df = processDicomField($outputRes[$lineNum], $encoding)) {
            $xmlString .= $df['xmlString']."\n";
          }
          $lineNum++;
        }
        $element['xmlString'] = $element['xmlPre'].$xmlString.$element['xmlPost'];
        if (DEBUG_LEVEL >= DEBUG_DUMP) {
          // print_r($dicomHeaders);
          echo $element['xmlString'];
        }
        array_push($dicom, $element);
      }
    }
  }


  if (DEBUG_LEVEL >= DEBUG_INFO) {
    echo "<pre>";
//    print_r($result);
    echo "</pre>";
  }
  elseif (DEBUG_LEVEL == DEBUG_NONE) {
    header('Content-type: text/xml; charset='.XML_ENCODING);
    echo "<?xml version=\"1.0\" encoding=\"".XML_ENCODING."\"?>\n";

    $fechaAhora = strftime("%Y%m%d%H%M%S%z");
    echo "<dicom datetime=\"$fechaAhora\">\n";
    foreach ($dicom as $response) {
      echo $response['xmlString'];
    }
    echo "</dicom>\n";
  }
} // function processResponse(...)

// *****************************************************************************

function identifyPattern($testStr) {

  $patterns = array();
  $element = false;

  if (SHOW_REQUEST) {
    // Send Query Request using 1.2.840.10008.5.1.4.1.2.2.1/Study Root Query/Retrieve Information Model - FIND:
    $patterns[QUERY_REQUEST_ROOT] = "/^Send Query Request using ([0-9]+(?:\.[0-9]+)+)\/([[:alpha:][:space:]\/\-]+):$/";

    // Send Query Request #1/3 using 1.2.840.10008.5.1.4.1.2.2.1/Study Root Query/Retrieve Information Model - FIND:
    $patterns[QUERY_REQUEST] = "/^Send Query Request #([1-9][0-9]*)\/([1-9][0-9]*) using ([0-9]+(?:\.[0-9]+)+)\/([[:alpha:][:space:]\/\-]+):$/";
  }

  // Query Response #1:
  $patterns[QUERY_RESPONSE_ROOT] = "/^Query Response #([1-9][0-9]*):$/";

  // Query Response #1 for Query Request #1/3:
  $patterns[QUERY_RESPONSE] = "/^Query Response #([1-9][0-9]*) for Query Request #([1-9][0-9]*)\/([1-9][0-9]*):$/";
  $matches = array();

  $numMatches = 0;
  foreach ($patterns as $type => $pattern) {
    if (DEBUG_LEVEL >= DEBUG_INFO) {
      echo "Testing $testStr<br>against pattern: $pattern<br>";
    }
    $numMatches = preg_match($pattern, $testStr, $matches);
    if($numMatches == 1) {
      $element = array();
      $element['xmlPre'] = "<!--{$matches[0]}-->\n";
      $element['type'] = $type;
      switch ($type) {
        case QUERY_REQUEST_ROOT:
          $element['tag'] = 'request';
          $element['xmlPre'] .= "<{$element['tag']} qrim='{$matches[1]}'>\n";
          break;
        case QUERY_RESPONSE_ROOT:
          $element['tag'] = 'response';
          $element['xmlPre'] .= "<{$element['tag']} number='{$matches[1]}'>\n";
          break;
        case QUERY_REQUEST:
          $element['tag'] = 'qrequest';
          $element['xmlPre'] .= "<{$element['tag']} number='{$matches[1]}' qrim='{$matches[3]}'>\n";
          break;
        case QUERY_RESPONSE:
          $element['tag'] = 'qresponse';
          $element['xmlPre'] .= "<{$element['tag']} number='{$matches[1]}' qrequest='{$matches[2]}'>\n";
          break;
        default:
          $element['tag'] = 'dummy';
          $element['xmlPre'] .= "<{$element['tag']}>\n";
      }
      $element['xmlPost'] = "</{$element['tag']}>\n";
      break; // break 2 ???
    }
  }
  
  return $element;
}


// *****************************************************************************

// 20120112: Parameters are obtained directly from $_GET
// ToDo: Dealing with errors (returning error information) in the case of incorrect parameters
function dicomQR ($pacs) {

  // Nivel Q/R | | -P | -S | -I
  $extraFields = '-r 00080061 -r 00081030 -r 00100010 -r 00100021 -r 00100030 -r 00100040 -r 00201206 -r 00201208';
  // (0008,0061) Modalities in Study
  // (0008,1030) Study Description

  // (0010,0010) Patient's Name
  // (0010,0021) Issuer of Patient ID
  // (0010,0030) Patient's Birth Date
  // (0010,0040) Patient's Sex

  // (0020,1206) Number of Study Related Series
  // (0020,1208) Number of Study Related Instances

  // (0020,000D) Study IUID


  // ToDo: Take also into account PatientIdIssuer
  $qPatId = isset($_GET['patId']) ? " -q 00100020='" . $_GET['patId'] . "'" : "";
  $qStudyDate = isset($_GET['studyDate']) ? " -q StudyDate={$_GET['studyDate']}" : ""; // AAAAMMDD ???
  $qFilter = $qPatId . $qStudyDate . " ";


  //  $command = 'LANG='.$pacs->getLocale().' '.PATH_BASE_DCM4CHE2.'dcmqr -device '.AETITLE_GATEWAY.' '.$pacs->getDicomServer()." -q 00100020='$patientId' $extraFields";

  if (strlen($qFilter) > 1) {
    $command = 'LANG='.$pacs->getLocale().' '.PATH_BASE_DCM4CHE2.'dcmqr -device '.AETITLE_GATEWAY.' '.$pacs->getDicomServer() . $qFilter . $extraFields;

  // echo $command;

  $outputRes = array();
  exec($command, $outputRes);

  if (DEBUG_LEVEL >= DEBUG_DUMP) {
    echo "<pre>";
    print_r($outputRes);
    echo "</pre>";
  }

  processResponse($outputRes, $pacs->encoding);
}

/*
Example of a Q/R answer (at study level)
14:28:31,609 INFO   - Query Response #4:
(0008,0005) CS #10 [ISO_IR 100] Specific Character Set
(0008,0020) DA #8 [20080410] Study Date
(0008,0030) TM #14 [103025.000000] Study Time
(0008,0050) SH #0 [] Accession Number
(0008,0052) CS #6 [STUDY] Query/Retrieve Level
(0008,0054) AE #8 [PACSECO] Retrieve AE Title
(0008,0056) CS #6 [ONLINE] Instance Availability
(0010,0020) LO #6 [72471] Patient ID
(0020,000D) UI #62 [1.2.840.113543.6.6.3.4.617968937028517893191307041671843345256] Study Instance U
(0020,0010) SH #4 [3379] Study ID
(0020,1206) IS #2 [1] Number of Study Related Series
(0020,1208) IS #2 [2] Number of Study Related Instances
(0088,0130) SH #0 [] Storage Media File-set ID
(0088,0140) UI #0 [] Storage Media File-set UID
*/

}

// *****************************************************************************

/**
 * Performa a QR operation to get series and instances from a given study
 */
function dicomQRStudy ($pacs, $study_IUID) {

  // Nivel Q/R | | -P | -S | -I
  // Problema: Algun campo extra lo pide en la subquery de instancias, pero no en la query principal de series
  $extraFields = '-r 00080060 -r 0008103E -r 00200011 -r 00201209';
  // (0008,0060) Modality
  // (0008,103E) Series Description  // Solo se envia en las subquery
  // (0020,0011) Series Number
  // (0020,1209) Number of Series Related Instances  // Solo se envia en las subquery

  $command = 'LANG='.$pacs->getLocale().' '.PATH_BASE_DCM4CHE2.'dcmqr -device '.AETITLE_GATEWAY.' -I '.$pacs->getDicomServer()." -q 0020000D=$study_IUID $extraFields";

  $outputRes = array();
  exec($command, $outputRes);

  if (DEBUG_LEVEL >= DEBUG_DUMP) {
    echo "<pre>";
    print_r($outputRes);
    echo "</pre>";
  }

  processResponse($outputRes, $pacs->encoding);
}

// *****************************************************************************
// *****************************************************************************

function processDicomField($dcmString, $encoding) {
  /*
    // (0008,0020) DA #8 [20080410] Study Date
    // Returned values (string):
    tagGroup = 0008
    tagElement = 0020
    value = 20080410
    tagName = Study Date
    valueRepr = DA
    valueLength = 8 (int)
  */

    if (DEBUG_LEVEL >= DEBUG_INFO) {
      echo "processDicomField($dcmString)<br>";
    }
    $matches = array();
    $pattern = "/^\(([0-9A-F]{4}),([0-9A-F]{4})\)\s([A-Z]{2})\s#([0-9]+)\s\[([^\]]*)\]\s(.*)$/";
    $numMatches = preg_match($pattern, $dcmString, $matches);
    if($numMatches == 1) {
      $d = array();
      if (DEBUG_LEVEL >= DEBUG_DUMP) {
        echo "<pre>";
        print_r($matches);
        echo "</pre>";
      }
      $d['tagGroup'] = $matches[1];
      $d['tagElement'] = $matches[2];
      $d['valueRepr'] = $matches[3];
      $d['valueLength'] = $matches[4];
      $d['value'] = $matches[5];
      $d['tagName'] = $matches[6];

      // Characters to convert to UTF-8: Elements with VR=PN, SH, LO, ST, LT, UT in the Data Set.
      $vr = $d['valueRepr'];
      if ($vr == 'PN' || $vr == 'SH' || $vr == 'LO' || $vr == 'ST' || $vr == 'LT' || $vr == 'UT') {
        $d['value'] = iconv($encoding, XML_ENCODING, $d['value']);
        $d['value'] = htmlspecialchars($d['value'], ENT_QUOTES); // Convert non valid XML characters
      }

      $xmlString = "<!--{$d['tagName']}-->\n";
      $xmlString .= "<attr tag=\"{$d['tagGroup']}{$d['tagElement']}\" vr=\"{$d['valueRepr']}\" len=\"{$d['valueLength']}\">";
      $xmlString .= "{$d['value']}</attr>";
      $d['xmlString'] = $xmlString;
    }
    else {
      if (DEBUG_LEVEL >= DEBUG_DUMP) {
        echo "NO match!!!<br>";
      }
      $d = false;
    }
    return $d;
}

// *****************************************************************************

/**
 * Recovers a Dicom object via WADO
 */
function getWado($pacs, $studyUID, $seriesUID, $objectUID) {

//  $tStart = microtime(true);
  
  $uriWado = $pacs->getUriWado($studyUID, $seriesUID, $objectUID);
  if (isset($_GET['contentType']) && $_GET['contentType'] == 'application/dicom') {
    $header = "Content-Type: application/dicom";
    $uriWado .= "&contentType=application%2Fdicom";
    // 20130502: Force transfer syntax to Explicit VR little endian
    $uriWado .= "&transferSyntax=1.2.840.10008.1.2.1"; 
  }
  else {
    error_log("ERROR jpeg/WADO files should not be used anymore");
    $header = "Content-Type: image/jpeg";
  }

  // To get thumbnails:
  if (isset($_GET['rows']) && isset($_GET['cols'])) {
//    $uriWado .= "&rows={$_GET['rows']}&cols={$_GET['cols']}";
    $uriWado .= "&rows=".THUMBNAIL_SIZE."&cols=".THUMBNAIL_SIZE;
  }

  if (RETRIEVE_LOCAL) {
    // Store a local copy of the Dicom file to obtain its size.
    $tmpFWado = tempnam('/tmp', 'dicom_');
//    $tmpFWado = tempnam('/dev/shm/dicom', 'dicom_');
    if ($getOk = getLocalWado($uriWado, $tmpFWado)) {
      $fp = fopen($tmpFWado, 'rb');
      header("Content-Type: application/dicom");
      header("Content-Length: " . filesize($tmpFWado));
      header("Content-Disposition: inline");
      fpassthru($fp);
      fclose($fp);
    }
    else {
      error_log("Error retrieving WADO/Dicom object");
    }
    unlink($tmpFWado);
  }
  else {
//  header("Cache-Control: public");
//  header('Expires: '.gmdate('D, d M Y H:i:s', strtotime('+1 day')).' GMT');
    header($header);
    readfile($uriWado);
  }

/*
  $tEnd = microtime(true);
  $tDelta = round(1000 * ($tEnd - $tStart));
  $logMsg = "getWado: $tDelta ms";
  error_log($logMsg);
*/
}

// ******* ********* ********* ********* ********* ********* ********* *********

function getLocalWado($uriWado, $tmpFWado) {
  $getOk = false;
  $numTry = 0;
  $maxTry = 3;
  $delayTry = 1;

  while (!$getOk && $numTry < $maxTry) {
    if ($numTry > 0) {
      sleep($delayTry);
    }
    $retrieveCommand = PATH_WGET." '$uriWado' -O $tmpFWado --server-response 2> /dev/stdout | grep Content-Type | awk -F\"Content-Type: \" '{print $2}'";

    // error_log($retrieveCommand);
    $outputCommand = array();
    exec($retrieveCommand, $outputCommand);
    // Verification: The returned contents is a dicom object
    $getOk = $outputCommand[0] == 'application/dicom';
    $numTry++;
  }
  return $getOk;
}

// ******* ********* ********* ********* ********* ********* ********* *********

?>
