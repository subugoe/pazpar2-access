<?php

$goettingenIPs = Array('134.76.*');
$freibergIPs = Array('139.20.*');
$hannoverIPs = Array('130.75.*','194.95.11[2345].*', '194.95.15[6789].*');


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
	),
	'GEO-LEO-Themen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'Math' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
		'sru.gbv.de/gvk-tib' => 'default',
	),
	'Math-Neuerwerbungen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
	),
	'Math-Themen' => Array(
		'sru.gbv.de/opac-de-7' => 'default',
		'sru.gbv.de/gvk-tib' => 'default',
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
				'condition' => $goettingenIPs,
				'value' => 'https://opac.sub.uni-goettingen.de/DB=1/PPNSET?PPN=',
			),
		),
		'pz:name' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => $goettingenIPs,
				'value' => 'SUB Göttingen',
			),
		),
	),
	'sru.gbv.de/gvk-tib' => Array(
		'catalogueURLHintPrefix' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => $hannoverIPs,
				'value' => 'http://opac.tib.uni-hannover.de/DB=1/PPNSET?PPN='
			),
		),
		'pz:name' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => $hannoverIPs,
				'value' => 'TIB Hannover'
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
				'condition' => $freibergIPs,
				'value' => 'http://webopac.ub.tu-freiberg.de/libero/WebopacOpenURL.cls?ACTION=DISPLAY&sid=Libero:TUF&RID=',
			),
		),
		'pz:name' => Array(
			Array(
				'conditionType' => 'IP',
				'condition' => $freibergIPs,
				'value' => 'Freiberg',
			),
		),
	),
);

?>
