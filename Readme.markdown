# pazpar2-access
A mechanism to catch [pazpar2](http://www.indexdata.com/pazpar2) init commands to activate special databases if the user’s IP is determined to be authorised by [GBV](http://www.gbv.de/)’s [GSO login service](http://gso.gbv.de/login/XML=1.0/AUTH?IP=134.76.1.1).

2011 by [Sven-S. Porst](http://earthlingsoft.net/ssp/), [SUB Göttingen](http://www.sub.uni-goettingen.de/) <[porst@sub.uni-goettingen.de](mailto:porst@sub.uni-goettingen.de?subject=pazpar2-access)>

Repository [available at github](https://github.com/ssp/pazpar2-access).


## Files

### pazpar2-access.php
The script doing the actual work.

#### Prerequisites:
* Some pazpar2 services are set up with GBV databases which are disabled by default
* The configuration file contains service names with those special databases listed (see below)
* The system is set up to redirect pazpar2 'init' and 'settings' commands to this script (see below)
* The system is set up to block the pazpar2 port from outside requests

#### What it does:
The script processes pazpar2 'init' commands and blocks all others.

* In case they are for a service that is listed in the configuration file, GBV’s GSO login service is queried to check whether those databases are available for the user’s IP address. If so,
	1. a pazpar2 session is initialised,
	2. the appropriate databases are activated for the pazpar2 session, and
	3. the init command’s response is returned after augmenting it by the tags <institution> containing the name of the user’s institution and <allServers> containing '1' if all databases could be activated and '0' otherwise.
* In case they are for other services, the request is forwarded to pazpar2 and its reply is returned.

### pazpar2-access-configuration.php
Contains the configuration for the script in two PHP arrays.

#### $serviceConfig
An array with pazpar2 service names as keys. Each key has an array as its value containing the key 'databases' with an array of strings that are pazpar2 database IDs as its value. E.g.:

	$serviceConfig = Array(
		'AAC' => Array (
			'databases' => Array('sru.gbv.de/zdb-1-pio')
		)
	);

#### $GBVDatabaseMapping
GBV use various names to refer to the same database in different contexts.

We have to use their [unAPI-names](http://uri.gbv.de/database/) to access the SRU server and receive Pica database IDs by the GSO login service. This array maps pazpar2 database names to Pica database IDs. E.g.:

	$GBVDatabaseMapping = Array(
		'sru.gbv.de/zdb-1-pio' => '5.55'
	);

### pazpar2-access.conf
apache2 configuration file that uses mod_rewrite to proxy requests for /pazpar2/search.pz2. By default it redirects all commands to the /pazpar2-access/pazpar2-access.php path for further processing. Just the commands 

* to /pazpar2-access/pazpar2-access.php if the query contains 'command=init' and
* to localhost:9004/search.pz2 where the pazpar2 daemon is expected to run otherwise.


