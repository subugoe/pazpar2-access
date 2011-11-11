<?php
/**
 * Script to serve as an intermediary for pazpar2 commands.
 * - processes init commands
 * - blocks settings commands
 * - passes all other commands on to pazpar2
 *
 * Its configuration and the reply of GBV’s GSO login service determine 
 * whether additional (access-restricted) databases will be activated in a pazpar2 session.
 *
 * Please refer to the Readme or github page for further information on the configuration.
 * https://github.com/ssp/pazpar2-access
 *
 * 2011 by Sven-S. Porst, SUB Göttingen <porst@sub.uni-goettingen.de>
 */



// Load configuration which defines the $serviceConfig and $GBVDatabaseMapping arrays.
include('./pazpar2-access-configuration.php');

// Local pazpar2 URL.
define('pazpar2URL', 'http://localhost:9004/search.pz2?');

$result = Array();

if (array_key_exists('command', $_GET)) {
	$command = $_GET['command'];
	if ($command === 'init') {
		// handle init commands ourselves
		$result = runInit();
	}
	else if ($command === 'settings') {
		// block settings commands
		$result['httpStatus'] = 403;
		$result['content'] = errorXML(50002, 'Command not permitted by pazpar2-access.php', 'settings');
	}
	else {
		// pass all other commands on to pazpar2
		$commandURL = pazpar2URL . http_build_query($_GET);
		$result = loadURL($commandURL);
	}
}
else {
	$result['httpStatus'] = 417;
	$result['content'] = errorXML(2, 'Missing parameter', 'command');
}

header('HTTP/1.1 ' . $result['httpStatus']);
header('Content-type: text/xml');
echo $result['content'];




/**
 * 1. load access permissions from GBV
 * 2. determine configuration
 * 3. initialise pazpar2 with that configuration
 * 4. augment init reply with vauge information about the configuration
 *
 * @return Array containing at least 'httpStatus' and 'content' fields
 */
function runInit () {
	$configurationParameters = '';
	$usingAllServers = -1;

	$serviceName = '';
	if (array_key_exists('service', $_GET)) {
		$serviceName = $_GET['service'];
	}
	global $serviceConfig;
	if (array_key_exists($serviceName, $serviceConfig)) {
		// We have a configuration for this service name, use it.
		$accessRights = getGBVAccessInfo();
		if ($accessRights) {
			$usingAllServers = 1;
			foreach ($serviceConfig[$serviceName]['databases'] as $wantDatabase) {
				global $GBVDatabaseMapping;
				$wantDatabaseID = $GBVDatabaseMapping[$wantDatabase];
				if (in_array($wantDatabaseID, $accessRights['permittedDatabases'])) {
					$configurationParameters .= '&pz:allow' . urlencode('[' . $wantDatabase . ']') . '=1';
				}
				else {
					$usingAllServers = 0;
				}
			}
		}
	}
	
	$result = initialisePazpar2($serviceName, $configurationParameters);
	
	if ($result['httpStatus'] == 200 && array_key_exists($serviceName, $serviceConfig)) {
		$initXML = $result['initXML'];
		$accessRightsElement = $initXML->createElement('accessRights');
		$initXML->firstChild->appendChild($accessRightsElement);
		
		$institutionElement = $initXML->createElement('institutionName');
		$accessRightsElement->appendChild($institutionElement);
		$institutionElement->appendChild($initXML->createTextNode($accessRights['institutionName']));

		
		$allServers = $initXML->createElement('allTargetsActive');
		$allServers->appendChild($initXML->createTextNode($usingAllServers));
		$accessRightsElement->appendChild($allServers);

		$result['content'] = $initXML->saveXML();		
	}

	return $result;
}



/**
 * Initialises pazpar2 with the service name and parameters passed and returns an array with the fields:
 * - httpStatus: the http status code (int)
 * - content: the content sent by pazpar2 (string)
 * if initialisation succeeded, these fields are added:
 * - status: pazpar2 status message (string)
 * - sessionID: pazpar2 session ID (string)
 * - initXML: pazpar2’s reply as XML (DOMDocument)
 * A blank service name initialises pazpar2 without the service parameter.
 *
 * @param $service string pazpar2 service name
 * @param $parameters string additional parameters [optional]
 * @return Array
 */
function initialisePazpar2 ($service, $parameters = '') {
	$initURL = pazpar2URL . 'command=init';
	if ($service != '') {
		$initURL .= '&service=' . $service;
	}
	$initURL .= $parameters;

	$result = loadURL($initURL);
	if ($result['httpStatus'] == 200) {
		$initXML = new DOMDocument();
		$initXML->loadXML($result['content']);
	
		$initXPath = new DOMXpath($initXML);
		$initElements = $initXPath->query('/init');
		if ($initElements->length > 0) {
			$initElement = $initElements->item(0);
			$result['status'] = $initXPath->query('/init/status')->item(0)->textContent;
			$result['sessionID'] = $initXPath->query('/init/session')->item(0)->textContent;
			$result['initXML'] = $initXML;
		}
	}

	return $result;
}



/**
 * Returns information about the GBV databases the user may access. Result is an array containing:
 * - permittedDatabases: Array of strings with Pica Database IDs (Array)
 * - institutionName: GBV’s name for the user’s access group (string)
 * The data for this is provided by GBV’s GSO login service.
 *
 * @return Array
 */
function getGBVAccessInfo () {
	$result = Array();
	$GBVQueryURL = 'http://gso.gbv.de/login/XML=1.0/AUTH?IP=' . clientIPAddress();
	$GBVResponse = loadURL($GBVQueryURL);
	$GBVResponseString = $GBVResponse['content'];
	$GBVResponseXML = new DOMDocument($GBVResponseString);
	$GBVResponseXML->loadXML($GBVResponseString);

	if ($GBVResponseXML) {
		$GBVResponseXPath = new DOMXpath($GBVResponseXML);
		$GBVDatabaseElements = $GBVResponseXPath->query('/result/database');
		$GBVDatabaseElementCount = $GBVDatabaseElements->length;
		$GBVDatabaseNames = Array();
		for ($i = 0; $i < $GBVDatabaseElementCount; $i++) {
			$GBVDatabaseNames[] = $GBVDatabaseElements->item($i)->textContent;
		}
		$result['permittedDatabases'] = $GBVDatabaseNames;
		
		$institutionElements = $GBVResponseXPath->query('/result/library_name');
		$institutionName = 'GAST';
		if ($institutionElements->length > 0) {
			 $institutionName = $institutionElements->item(0)->textContent;
			 $institutionName = str_replace('FLplus ', '', $institutionName);
		}
		$result['institutionName'] = $institutionName;
	}
	
	return $result;
}



/**
 * Returns the client’s presumed IP address from the HTTP_X_FORWARDED_FOR header
 * as our service is presumed to be accessed from a proxy on localhost.
 *
 * @return string client’s IP address
 */
function clientIPAddress () {
	$result = $_SERVER['REMOTE_ADDR'];
	
	if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
		$result = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	
	return $result;
}



/**
 * Helper function: loads the given URL and returns the content as text as well as the status code
 *
 * @param $URL string
 * @return Array containing a string 'content' and the status code 'httpStatus'
 */
function loadURL ($URL) {
	$connection = curl_init($URL);
	curl_setopt($connection, CURLOPT_RETURNTRANSFER, True);
	$URLContent = curl_exec($connection);
	$httpStatus = curl_getinfo($connection, CURLINFO_HTTP_CODE);
	curl_close($connection);

	return Array('content' => $URLContent, 'httpStatus' => $httpStatus);
}



/**
 * Helper function: Create <error> XML Document in pazpar2 style.
 *
 * @param $code string error code
 * @param $message string error message
 * @param $content string content of the error XML element
 * @return string
 */
function errorXML ($code, $message, $content = '') {
	$DI = new DOMImplementation();
	$doc = $DI->createDocument();
	$error = $doc->createElement('error');
	$error->setAttribute('code', $code);
	$error->setAttribute('msg', $message);
	$error->appendChild($doc->createTextNode($content));
	$doc->appendChild($error);
	return $doc->saveXML();
}

?>
