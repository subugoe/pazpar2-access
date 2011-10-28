<?php
/**
 * Script to serve as an intermediary for pazpar2 init commands.
 * Its configuration and the reply of GBV’s 'iport_request' service determine 
 * whether additional (access-restricted) databases will be activated in a pazpar2 session.
 *
 * Please refer to the Readme or github page for further information on the configuration.
 * https://github.com/ssp/pazpar2-access
 *
 * 2011 by Sven-S. Porst, SUB Göttingen <porst@sub.uni-goettingen.de>
 */



// Load configuration which defines the $serviceConfig and $GBVDatabaseMapping Arrays.
include('./pazpar2-access-configuration.php');

// Local pazpar2 URL.
define('pazpar2URL', 'http://localhost:9004/search.pz2');

$output = Null;

if (array_key_exists('command', $_GET)) {
	$command = $_GET['command'];
	if ($command === 'init') {
		$output = run();
	}
	else {
		$output = errorXML(50000, 'pazpar2-access.php can only process init commands');
	}
}
else {
	$output = errorXML(2, 'Missing parameter', 'command');
}

header('Content-type: text/xml');
echo $output->saveXML();




/**
 * Main action:
 * 1. initialise pazpar2
 * 2. determine configuration
 * 3. load access permissions from GBV
 * 4. activate additional databases
 *
 * @return DOMDocument
 */
function run () {
	// Initialise pazpar2.
	$service = getServiceName();
	$initResult = initialisePazpar2($service);
	$result = $initResult['initXML'];
	if ($result && $initResult['status'] === 'OK' && $initResult['sessionID'] != '') {
		global $serviceConfig;
		if (array_key_exists($service, $serviceConfig)) {
			// We have a configuration for this service name, use it.
			
			// Load access permissions from GBV and add institution name to init response.
			$accessRights = getGBVAccessInfo();
			if ($accessRights) {
				$institutionElement = $result->createElement('institution');
				$institutionElement->appendChild($result->createTextNode($accessRights['institutionName']));
				$result->firstChild->appendChild($institutionElement);
					
				// Activate additional databases and add vague information about the result to init response.
				$activationResult = activateDatabasesForSession($serviceConfig[$service]['databases'], $accessRights['permittedDatabases'], $initResult['sessionID']);
				$allServers = $result->createElement('allServers');
				$allServers->appendChild($result->createTextNode($activationResult['usingAllServers']));
				$result->firstChild->appendChild($allServers);
			}
		}
	}
	else {
		$result = errorXML(50001, 'pazpar2-access.php could not initialise the pazpar2 service');
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
	$result = Array();
	$initURL = pazpar2URL . '?command=init';
	if ($service != '') {
		$initURL .= '&service=' . $service;
	}
	$initXML = loadXMLFromURL($initURL);
	$initXPath = new DOMXpath($initXML);
	$initElements = $initXPath->query('/init');
	if ($initElements->length > 0) {
		$initElement = $initElements->item(0);
		$result['status'] = $initXPath->query('/init/status')->item(0)->textContent;
		$result['sessionID'] = $initXPath->query('/init/session')->item(0)->textContent;
		$result['initXML'] = $initXML;
	}
	
	return $result;
}



/**
 * Returns information about the GBV databases the user may access. Result is an array containing:
 * - permittedDatabases: Array of strings with Pica Database IDs (Array)
 * - institutionName: GBV’s name for the user’s access group (string)
 * The data for this is provided by GBV’s 'iport_request' service.
 *
 * @return Array
 */
function getGBVAccessInfo () {
	$result = Array();
	$GBVQueryURL = 'http://gso.gbv.de/login/iport_request?IPaddress=' . clientIPAddress();
	$GBVResponseXML = loadXMLFromURL($GBVQueryURL);

	if ($GBVResponseXML) {
		$GBVResponseXPath = new DOMXpath($GBVResponseXML);
		$GBVDatabaseElements = $GBVResponseXPath->query('/result/userInfo/group');
		$GBVDatabaseElementCount = $GBVDatabaseElements->length;
		$GBVDatabaseNames = Array();
		for ($i = 0; $i < $GBVDatabaseElementCount; $i++) {
			$GBVDatabaseNames[] = $GBVDatabaseElements->item($i)->textContent;
		}
		$result['permittedDatabases'] = $GBVDatabaseNames;
		
		$institutionElements = $GBVResponseXPath->query('/result/userInfo/cn');
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
	$configurationURL = pazpar2URL . '?command=settings&session=' . $session;
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
 * Helper function: Returns XML loaded from the given $URL
 * - times out after 2 seconds
 * - ignores 'error' status codes (pazpar2 returns 417 for wrong queries, which leads to 
 *   an empty result rather than the error XML with the default setting); This requires
 *   PHP 5.3 or above, so we’re in for FAIL on SLES 11.
 * 
 * @param $URL string
 * @return DOMDocument
 */
function loadXMLFromURL ($URL) {
	$httpOptions = Array('timeout' => 2, 'ignore_errors' => True);
	$loadContext = stream_context_create(Array('http' => $httpOptions));
	$URLContent = file_get_contents($URL, Null, $loadContext);
	$XML = new DOMDocument();
	$XML->loadXML($URLContent);
	return $XML;
}



/**
 * Helper function: Create <error> XML Document in pazpar2 style.
 *
 * @param $code string error code
 * @param $message string error message
 * @param $content string content of the error XML element
 * @return DOMDocument
 */
function errorXML ($code, $message, $content = '') {
	$DI = new DOMImplementation();
	$doc = $DI->createDocument();
	$error = $doc->createElement('error');
	$error->setAttribute('code', $code);
	$error->setAttribute('msg', $message);
	$error->appendChild($doc->createTextNode($content));
	$doc->appendChild($error);
	return $doc;
}

?>
