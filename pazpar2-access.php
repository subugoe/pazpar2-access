<?php

$output = Null;

if (array_key_exists('command', $_GET)) {
	$command = $_GET['command'];
	if ($command === 'init') {
		$output = init();
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



function init () {
	$pazpar2URL = 'http://localhost/pazpar2/search.pz2';
	$initURL = $pazpar2URL . '?command=init';
	$serviceName = '';
	if (array_key_exists('service', $_GET)) {
		$serviceName = $_GET['service'];
		$initURL .= '&service=' . $serviceName;
	}
	
	$httpOptions = Array('timeout' => 2, 'ignore_errors' => True);
	$loadContext = stream_context_create(Array('http' => $httpOptions));
	$initText = file_get_contents($initURL, Null, $loadContext);
	$initXML = new DOMDocument();
	if ($initXML->loadXML($initText)) {
		// load configuration which defines the $serviceConfig variable
		include('pazpar2-access-configuration.php');

		if (array_key_exists($serviceName, $serviceConfig)) {
			// We have a configuration for this service name, apply it.
			
			// Determine which databases the user’s IP may access using GBV information:
			$GBVAccessURL = 'http://gso.gbv.de/login/iport_request';
			$GBVAccessURL .= '?IPaddress=' . $_SERVER['REMOTE_ADDR'];
			$GBVAccessRightsText = file_get_contents($GBVAccessURL, Null, $loadContext);
			$GBVAccessRightsXML = new DOMDocument();

			if ($GBVAccessRightsXML->loadXML($GBVAccessRightsText)) {
				$GBVAccessRightsXPath = new DOMXpath($GBVAccessRightsXML);
			
				$GBVDatabaseElements = $GBVAccessRightsXPath->query('/result/userInfo/group');
				$GBVDatabaseElementCount = $GBVDatabaseElements->length;
				$GBVDatabaseNames = Array();
				for ($i = 0; $i < $GBVDatabaseElementCount; $i++) {
					$GBVDatabaseNames[] = $GBVDatabaseElements->item($i)->textContent;
				}
				
				$initXPath = new DOMXpath($initXML);
				$initElements = $initXPath->query('/init');
				if ($initElements->length > 0) {
					$initElement = $initElements->item(0);
					$status = $initXPath->query('/init/status')->item(0)->textContent;
					$sessionID = $initXPath->query('/init/session')->item(0)->textContent;
					
					if ($status === 'OK' && $sessionID != '') {

						$institutionElements = $GBVAccessRightsXPath->query('/result/userInfo/cn');
						if ($institutionElements->length > 0) {
							$institutionName = $institutionElements->item(0)->textContent;
							$institutionElement = $initXML->createElement('institution');
							$institutionElement->appendChild($initXML->createTextNode($institutionName));
							$initElement->appendChild($institutionElement);
						}
	
						$serviceDatabases = $serviceConfig[$serviceName]['databases'];
						$configurationURL = $pazpar2URL . '?command=settings&session=' . $sessionID;
											
						$usingAllServers = 1;
						foreach ($serviceDatabases as $database) {
							if (array_key_exists($database, $GBVDatabaseMapping)) {
								if (in_array($GBVDatabaseMapping[$database], $GBVDatabaseNames)) {
									$configurationURL .= '&pz:allow[' . $database . ']=1';
								}
								else {
									$usingAllServers = 0;
								}
							}
						}
						
						$configurationText = file_get_contents($configurationURL);
						$configurationXML = new DOMDocument();
						if ($configurationXML->loadXML($configurationText)) {
							$configurationXPath = new DOMXpath($configurationXML);
							$status = $configurationXPath->query('status')->item(0)->textContent;
							if ($status === 'OK') {
								$allServers = $initXML->createElement('allServers');
								$allServers->appendChild($initXML->createTextNode($usingAllServers));
								$initElement->appendChild($allServers);
							}
						}
					}
				}
			}
		}

		$output = $initXML;
	}
	else {
		$output = errorXML(50001, 'pazpar2-access.php could not access the pazpar2 service');
	}
	
	return $output;
}

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
