<?php

return array(
	'default' => array(
		'type' => 'pdo',
		'connection' => array(
			'dsn' => 'sqlite::memory:',
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
