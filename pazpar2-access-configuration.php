<?php

$serviceConfig = Array(
	'AAC' => Array (
		'sru.gbv.de/opac-de-7' => 'default',
		'sru.gbv.de/zdb-1-pio' => 'default',
	),
	'SUB' => Array(
		'sru.gbv.de/opac-de-7' => Array(
			'catalogueURLPrefix' => Array(
				'value' => 'https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=',
			),
		),
	),
	'all' => Array (
	)
);


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
	'sru.gbv.de/olc' => Array(
		'pz:allow' => Array(
			Array(
				'conditionType' => 'GBV',
				'condition' => '2.3',
				'value' => '1',
			),
		),
	),
	'sru.gbv.de/olcssg-ang' => Array(
		'pz:allow' => Array(
			Array(
				'conditionType' => 'GBV',
				'condition' => '2.75',
				'value' => '1',
			),
		),
	),
	'sru.gbv.de/olcssg-his' => Array(
		'pz:allow' => Array(
			Array(
				'conditionType' => 'GBV',
				'condition' => '2.35',
				'value' => '1',
			),
		),
	),
	'sru.gbv.de/olcssg-mat' => Array(
		'pz:allow' => Array(
			Array(
				'conditionType' => 'GBV',
				'condition' => '2.77',
				'value' => '1',
			),
		),
	),
	'sru.gbv.de/olcssg-ggo' => Array(
		'pz:allow' => Array(
			Array(
				'conditionType' => 'GBV',
				'condition' => '2.38',
				'value' => '1',
			),
		),
	),
	'sru.gbv.de/olcssg-ast' => Array(
		'pz:allow' => Array(
			Array(
				'conditionType' => 'GBV',
				'condition' => '2.43',
				'value' => '1',
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

?>
