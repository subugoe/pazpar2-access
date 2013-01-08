# pazpar2-access
A mechanism to catch [pazpar2](http://www.indexdata.com/pazpar2) init commands to and add additional settings to them (e.g. for activating additional databases or changing XSL parameters) based on the user’s IP address and [GBV](http://www.gbv.de/)’s [GSO login service](http://gso.gbv.de/login/XML=1.0/AUTH?IP=134.76.1.1).

2011-2013 by [Sven-S. Porst](http://earthlingsoft.net/ssp/), [SUB Göttingen](http://www.sub.uni-goettingen.de/) <[porst@sub.uni-goettingen.de](mailto:porst@sub.uni-goettingen.de?subject=pazpar2-access)>

Repository [available at github](https://github.com/ssp/pazpar2-access).


## Files

### pazpar2-access.php
The script doing the actual work.


#### Prerequisites:

* Some pazpar2 services are set up with GBV databases which have to be disabled by default as they may only be accessed by authorised users
* The configuration file contains service names with those special databases listed (see below)
* pazpar2 is running on localhost:9004 which is not accessible from the outside


#### What it does:

1. process pazpar2 `init` commands:
	* based on the service name passed to the 'init' command, additional settings can be added to that command by pazpar2-access. Each of those parameters can be equipped with a condition based on which it will be added or not. There is support for two types of conditions:
		* IP addresses: Matches the user’s IP address against an array of regular expression
		* GBV GSO login service: Determines whether the user’s IP address may access a specific GBV database. When the GBV GSO login service is used <institution> (containing the institution name for the IP address return ed by the GBV GSO login service) and <allServers> (containing 1 if all databases were activated and 0 otherwise) tags are added to the init reply to make the results available to the client. 
	* In case an init command is for other services, the request is forwarded to pazpar2 and its reply is returned.
2. block pazpar2 `settings` commands
3. forward all other commands to pazpar2


### pazpar2-access-configuration.php
Contains the configuration for the script in PHP arrays $serviceConfig and $databaseDefaults.

#### database configuration
The main component is per-database configuration. It is a PHP array with pazpar2 settings names as keys and arrays as their values.

The array contains arrays, each of which can contain three keys:

* `conditionType`: a string giving the type of the condition to be checked. Supported values are `IP` and `GBV`. If the `conditionType` key is missing, the value is used unconditionally.
* `condition`: spelling out the condition
	* for conditionType `IP` this is an array of strings; if any of those strings matches the user’s IP address, the condition is satisfied
	* for conditionType `GBV` this is a string; if it matches the ID of a database in the list of allowed databases for the user’s IP, the condition is satisfied
* `value`: the string that the pazpar2 setting is set to
		
_Example_ database configuration which sets `catalogueURLPrefix` for database ID `sru.gbv.de/opac-de-7` to `https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=` if the user’s IP address matches `^134.76.*` as a regular expression:

	'sru.gbv.de/opac-de-7' => Array(
		'catalogueURLPrefix' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => Array('134.76.*'),
				'value' => 'https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=',
			),
		),
	)

Technically this will result in the pazpar2 init query with an added `catalogueURLPrefix[sru.gbv.de/opac-de-7]=https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=` parameter.

_Example_ database configuration which sets `pz:allow` for database ID `sru.gbv.de/zdb-1-pio` to 1 if the user’s IP address is allowed to access GBV database ID `5.55`:

	'sru.gbv.de/zdb-1-pio' => Array(
		'pz:allow' => Array(
			Array(
				'conditionType' => 'GBV',
				'condition' => '5.55',
				'value' => '1',
			),
		),
	),

Technically this will result in the pazpar2 init query with an added `pz:allow[sru.gbv.de/zdb-1-pio]=1` parameter.



#### $serviceConfig
The $serviceConfig variable is used to configure the actions of pazpar2-access on a per-service basis. The array has pazpar2 service names as keys which have a database configuration or the string `default` as values. If the string `default` is used, the database configuration for the same key from the $databaseDefaults array will be used.

_Example_ configuration for using the default configuration of the database `sru.gbv.de/zdb-1-pio` in the `AAC` service and for unconditionally overwriting the `catalogueURLPrefix` variable on the `sru.gbv.de/opac-de-7` database of the `SUB` service:

	$serviceConfig = Array(
		'AAC' => Array (
			'sru.gbv.de/zdb-1-pio' => 'default',
		),
		'SUB' => Array(
			'sru.gbv.de/opac-de-7' => Array(
				'catalogueURLPrefix' => Array(
					'value' => 'https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=',
				),
			),
		),
	);




#### $databaseDefaults
This array has pazpar2 database IDs as keys with database configurations as values. The values set here will be loaded into $serviceConfig if the configuration given there is the string `default`.

_Example_ setup with defaults for `sru.gbv.de/opac-de-7` and `sru.gbv.de/zdb-1-pio`:

	$databaseDefaults = Array(
		'sru.gbv.de/opac-de-7' => Array(
			'catalogueURLPrefix' => Array(
				Array(
					'conditionType' => 'IP',
					'condition' => Array('134.76.*'),
					'value' => 'https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=',
				),
			),
		),
		'sru.gbv.de/zdb-1-pio' => Array(
			'pz:allow' => Array(
				Array(
					'conditionType' => 'GBV',
					'condition' => '5.55',
					'value' => '1',
				),
			),
		),
	);



### pazpar2-access.conf
apache2 configuration file that uses mod_rewrite to proxy requests for /pazpar2/search.pz2 to /pazpar2-access/pazpar2-access.php path for further processing.

