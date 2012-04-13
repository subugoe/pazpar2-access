<?php

$serviceConfig = Array(
	'AAC' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
		'sru.gbv.de/zdb-1-pio' => 'default',
	),
	'AAC-Neuerwerbungen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'AAC-Hist-Themen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'AAC-Lit-Themen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'GEO-LEO' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
		'z3950.bsz-bw.de:20215/swb' => 'default',
	),
	'GEO-LEO-Themen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
		'z3950.bsz-bw.de:20215/swb' => 'default',
	),
	'Math' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'Math-Neuerwerbungen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'Math-Themen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'Neuerwerbungen' => Array(
		'sru.gbv.de/opac-de-7' => Array(
			'catalogueURLHintPrefix' => Array(
				Array(
					'value' => 'https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=',
				),
			),
		),
	),
	'SUB' => Array(
		'sru.gbv.de/olc' => 'default',
	),
	'all' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
		'sru.gbv.de/zdb-1-pio' => 'default',
		'sru.gbv.de/olc' => 'default',
		'sru.gbv.de/olcssg-ang' => 'default',
		'sru.gbv.de/olcssg-his' => 'default',
		'sru.gbv.de/olcssg-mat' => 'default',
		'sru.gbv.de/olcssg-ggo' => 'default',
		'sru.gbv.de/olcssg-ast' => 'default',
	)
);


$databaseDefaults = Array(
	'sru.gbv.de/opac-de-7' => Array(
		'catalogueURLHintPrefix' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => Array('134.76.*'),
				'value' => 'https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=',
			),
		),
		'pz:name' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => Array('134.76.*'),
				'value' => 'SUB Göttingen',
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
	'z3950.bsz-bw.de:20215/swb' => Array(
		'catalogueURLHintPrefix' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => Array('139.20.*'),
				'value' => 'http://webopac.ub.tu-freiberg.de/libero/WebopacOpenURL.cls?ACTION=DISPLAY&amp;sid=Libero:TUF&amp;RID=',
			),
		),
		'pz:name' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => Array('139.20.*'),
				'value' => 'Freiberg',
			),
		),
	),
);

?>
