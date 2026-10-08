<?php

return array(
	'auto_initialize' => false,
	'driver' => 'file',
	'cookie' => 'fuelcid',
	'native_emulation' => false,
	'file' => array(
		'path' => sys_get_temp_dir(),
		'gc_probability' => 0,
	),
);
