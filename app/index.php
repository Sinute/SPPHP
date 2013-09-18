<?php
$s = microtime(true);
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
define('SP_ROOT', '/home/sinute/workspace/PHP/spphp');
define('APP_ROOT', '/home/sinute/workspace/PHP/spphp/app');

defined('SP_DEBUG') or define('SP_DEBUG',true);

if(PHP_SAPI == 'cli')
{
	$GLOBALS['argc'] = $argc;
	$GLOBALS['argv'] = $argv;
}

require SP_ROOT.DS.'SP.php';
SP::init()->run();
var_dump(microtime(true)-$s);
