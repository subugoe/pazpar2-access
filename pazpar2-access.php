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
 * 1. initialise pazpar2
 * 2. determine configuration
 * 3. load access permissions from GBV
 * 4. activate additional databases
 *
 * @return DOMDocument
 */
function runInit () {
	$service = getServiceName();
	$result = initialisePazpar2($service);
	$initXML = $result['initXML'];
	if ($initXML && $result['status'] === 'OK' && $result['sessionID'] != '') {
		global $serviceConfig;
		if (array_key_exists($service, $serviceConfig)) {
			// We have a configuration for this service name, use it.
			
			// Load access permissions from GBV and add institution name to init response.
			$accessRights = getGBVAccessInfo();
			if ($accessRights) {
				$institutionElement = $initXML->createElement('institution');
				$institutionElement->appendChild($initXML->createTextNode($accessRights['institutionName']));
				$initXML->firstChild->appendChild($institutionElement);
					
				// Activate additional databases and add vague information about the result to init response.
				$activationResult = activateDatabasesForSession($serviceConfig[$service]['databases'], $accessRights['permittedDatabases'], $result['sessionID']);
				$allServers = $initXML->createElement('allServers');
				$allServers->appendChild($initXML->createTextNode($activationResult['usingAllServers']));
				$initXML->firstChild->appendChild($allServers);
			}
			$result['content'] = $initXML->saveXML();
		}
	}
	
	return $result;
}



/**
 * Returns the service name used in the GET request.
 *
 * @return string service name from GET request’s 'service' parameter, empty string if there is none
 */
function getServiceName () {
	$serviceName = '';
	if (array_key_exists('service', $_GET)) {
		$serviceName = $_GET['service'];
	}

	return $serviceName;
}



/**
 * Initialises pazpar2 with the service name passed and returns an array with the fields:
 * - status: status code (string)
 * - sessionID: pazpar2 session ID (string)
 * - initXML: pazpar2’s reply (DOMDocument)
 * A blank service name, initialises pazpar2 without the service parameter.
 *
 * @param $service string pazpar2 service name
 * @return Array
 */
function initialisePazpar2 ($service) {
	$initURL = pazpar2URL . 'command=init';
	if ($service != '') {
		$initURL .= '&service=' . $service;
	}

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
	$GBVResponseXML = loadXMLFromURL($GBVQueryURL);

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
		if ($institutionElements->length > 0) {
			$result['institutionName'] = $institutionElements->item(0)->textContent;
		}
	}
	
	return $result;
}



/**
 * Returns the client’s presumed IP address from the HTTP_X_FORWARDED_FOR header
 * as our services is presumed to be accessed from a proxy on localhost.
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
 * Configures pazpar2 to activate the databases which
 * 1. need to activated (according to the configuration file) and
 * 2. the user may access (according to the $allowedDatabases array)
 * This requires the $GBVDatabaseMapping Array to provide a translation between GBV SRU database names
 * and GBV Pica Database IDs.
 *
 * Returns an array with:
 * - usingAllServers: 1/0, depending on whether all possible servers are used
 * - configurationSuccess: True/False, reflecting the result of setting pazpar2’s configuration
 *
 * @param $wantDatabases Array of strings with GBV SRU database names
 * @param $allowedDatabases Array of strings with Pica IDs of GBV databases the user’s IP may access
 * @param $session string pazpar2 session ID
 * @result Array
 */
function activateDatabasesForSession ($wantDatabases, $allowedDatabases, $session) {
	$result = Array();
	$configurationURL = pazpar2URL . 'command=settings&session=' . $session;
	$configurationParameters = '';
	$configurationSuccess = False;
	$usingAllServers = 1;

	foreach ($wantDatabases as $wantDatabase) {
		global $GBVDatabaseMapping;
		$wantDatabaseID = $GBVDatabaseMapping[$wantDatabase];
		if (in_array($wantDatabaseID, $allowedDatabases)) {
			$configurationParameters .= '&pz:allow' . urlencode('[' . $wantDatabase . ']') . '=1';
		}
		else {
			$usingAllServers = 0;
		}
	}
	
	if ($configurationParameters !== '') {
		$configurationURL .= $configurationParameters;
		$configurationResult = loadXMLFromURL($configurationURL);
		if ($configurationResult) {
			$configurationXPath = new DOMXpath($configurationResult);
			$status = $configurationXPath->query('status')->item(0)->textContent;
			$configurationSuccess = ($status === 'OK');
		}
	}
	
	$result['usingAllServers'] = $usingAllServers;
	$result['configurationSuccess'] = $configurationSuccess;
	return $result;
}



/**
 * Helper function: Returns XML loaded from the given URL
 * 
 * @param $URL string
 * @return DOMDocument
 */
function loadXMLFromURL ($URL) {
	$download = loadURL($URL);
	$XMLString = $download['content'];
	$XML = new DOMDocument($URL);
	$XML->loadXML($XMLString);
	return $XML;
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
