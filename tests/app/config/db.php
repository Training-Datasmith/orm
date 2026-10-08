<?php

return array(
	'default' => array(
		'type' => 'pdo',
		'connection' => array(
			'dsn' => getenv('FUEL_ORM_TEST_DSN') ? getenv('FUEL_ORM_TEST_DSN') : 'sqlite::memory:',
			'username' => null,
			'password' => null,
			'persistent' => false,
			'compress' => false,
		),
		'identifier' => '`',
		'table_prefix' => '',
		'charset' => false,
		'enable_cache' => false,
		'profiling' => false,
	),
);
