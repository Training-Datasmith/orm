<?php

define('DOCROOT', realpath(__DIR__).DIRECTORY_SEPARATOR);
define('APPPATH', DOCROOT.'app'.DIRECTORY_SEPARATOR);
define('PKGPATH', DOCROOT.'fuel/packages'.DIRECTORY_SEPARATOR);
define('COREPATH', DOCROOT.'fuel/core'.DIRECTORY_SEPARATOR);
define('VENDORPATH', dirname(DOCROOT).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR);

if ( ! is_dir(PKGPATH))
{
	mkdir(PKGPATH, 0777, true);
}

$packageLinks = array(
	PKGPATH.'orm' => '../../../',
	PKGPATH.'auth' => '../auth',
);

foreach ($packageLinks as $link => $target)
{
	if (is_link($link))
	{
		$resolved = realpath(dirname($link).DIRECTORY_SEPARATOR.$target);
		if ($resolved === false || realpath($link) !== $resolved)
		{
			unlink($link);
		}
	}
	if ( ! file_exists($link))
	{
		symlink($target, $link);
	}
}

require VENDORPATH.'autoload.php';
require COREPATH.'classes'.DIRECTORY_SEPARATOR.'autoloader.php';
class_alias('Fuel\\Core\\Autoloader', 'Autoloader');
require COREPATH.'bootstrap.php';
\Autoloader::register();

\Fuel::$env = \Fuel::TEST;
ini_set('session.use_cookies', '0');
if (PHP_SAPI === 'cli')
{
	empty($_SERVER['REQUEST_URI']) and $_SERVER['REQUEST_URI'] = '/';
	empty($_SERVER['HTTP_HOST']) and $_SERVER['HTTP_HOST'] = 'localhost';
}
\Fuel::init('config.php');
\Package::load('orm');
\Package::load('auth');
