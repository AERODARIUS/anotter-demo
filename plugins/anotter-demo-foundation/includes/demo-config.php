<?php
/** Single source of initial M03 sample values; never official carrier tariffs. */
defined( 'ABSPATH' ) || exit;
return array(
	'shipping' => array(
		'montevideo' => array( 'name' => 'Montevideo — DEMO', 'location' => 'UY:UY-MO', 'type' => 'state', 'order' => 0, 'dac' => '180', 'ues' => '210' ),
		'interior' => array( 'name' => 'Resto de Uruguay — DEMO', 'location' => 'UY', 'type' => 'country', 'order' => 10, 'dac' => '260', 'ues' => '290' ),
	),
	'hold_minutes' => 30,
	'low_stock' => 2,
	'no_stock' => 0,
);
