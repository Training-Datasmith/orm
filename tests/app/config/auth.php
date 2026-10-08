<?php

return array(
	'driver' => 'Simpleauth',
	'verify_multiple_logins' => false,
	'salt' => 'orm-test-auth-salt',
	'groups' => array(),
	'login_hash_salt' => 'orm-test-login-hash',
	'iterations' => 10000,
);
