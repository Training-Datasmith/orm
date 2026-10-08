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

$ormLink = PKGPATH.'orm';
if ( ! file_exists($ormLink))
{
	symlink(dirname(DOCROOT), $ormLink);
}

$authLink = PKGPATH.'auth';
if ( ! file_exists($authLink))
{
	symlink(DOCROOT.'fuel'.DIRECTORY_SEPARATOR.'auth', $authLink);
}

require VENDORPATH.'autoload.php';
require COREPATH.'classes'.DIRECTORY_SEPARATOR.'autoloader.php';
class_alias('Fuel\\Core\\Autoloader', 'Autoloader');
require COREPATH.'bootstrap.php';
\Autoloader::register();

\Fuel::$env = \Fuel::TEST;
\Fuel::init('config.php');
\Package::load('orm');
\Package::load('auth');
