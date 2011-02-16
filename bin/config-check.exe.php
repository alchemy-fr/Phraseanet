<?php

require_once dirname( __FILE__ ) . '/../lib/conf.d/_GV_template.inc';
require_once dirname( __FILE__ ) . '/../lib/classes/phrasea.class.php';
require_once dirname( __FILE__ ) . '/../lib/classes/setup.class.php';
require_once dirname( __FILE__ ) . '/../lib/classes/session.class.php';


error_reporting(E_ALL);
ini_set('display_errors','on');
ini_set('display_startup_errors','on');

$error = false;
if(version_compare(PHP_VERSION,'5.2.4','<'))
{
	echo "\nYour PHP version is too old. PHP 5.2.4 is required\n";
	$error = true;
}

$error = setup::check_php_extension_console();
				
if($error)
	exit( "\n\nPlese set up correct configuration before install\n\nPlease be sure to use the correct php.ini for this test\n\n");
else
	echo "\n\nConfiguration is OK you can now update or install\n\n";