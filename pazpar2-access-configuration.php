<?php

$serviceConfig = Array(
	'AAC' => Array (
		'databases' => Array('sru.gbv.de/zdb-1-pio')
	),
	'SUB' => Array(
		'databases' => Array('sru.gbv.de/olc')
	),
	'all' => Array (
		'databases' => Array(
			'sru.gbv.de/olc',
			'sru.gbv.de/olcssg-ang',
			'sru.gbv.de/olcssg-his',
			'sru.gbv.de/olcssg-mat',
			'sru.gbv.de/olcssg-ggo',
			'sru.gbv.de/olcssg-ast',
			'sru.gbv.de/zdb-1-pio'
		)
	)
);



/*
 *	Translation between GBV SRU URLs and the corresponding Pica Database IDs.
 *  From: http://uri.gbv.de/database/
 */
$GBVDatabaseMapping = Array(
	'sru.gbv.de/olc' => '2.3',
	'sru.gbv.de/olcssg-ang' => '2.75',
	'sru.gbv.de/olcssg-ast' => '2.43',
	'sru.gbv.de/olcssg-ggo' => '2.38',
	'sru.gbv.de/olcssg-his' => '2.35',
	'sru.gbv.de/olcssg-mat' => '2.77',
	'sru.gbv.de/zdb-1-pio' => '5.55'
);

?>
