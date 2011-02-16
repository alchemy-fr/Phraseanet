<?php
define('__DIR__', dirname(__FILE__));
define('__CUSTOMDIR__', dirname(dirname(__FILE__)).'/config' );

function phrasea_autoload($class_name)
{
	if(file_exists(__CUSTOMDIR__ . '/classes/'.str_replace('_','/',$class_name).'.class.php' ))
	{
		require_once __CUSTOMDIR__ . '/classes/'.str_replace('_','/',$class_name).'.class.php';
	}
	elseif(file_exists(__DIR__ . '/classes/'.str_replace('_','/',$class_name).'.class.php'))
	{
		require_once __DIR__ . '/classes/'.str_replace('_','/',$class_name).'.class.php';
	}	
}

require_once __DIR__ . '/version.inc';
require_once __DIR__ . '/../config/_GV.php';

spl_autoload_register('phrasea_autoload');
phrasea::start();
$session = session::getInstance();

if(defined('GV_timezone'))
	date_default_timezone_set(GV_timezone);

define('USE_MINIFY_CSS', true);
define('USE_MINIFY_JS', true);

define('JETON_MAKE_SUBDEF', 0x01);
define('JETON_WRITE_META_DOC', 0x02);
define('JETON_WRITE_META_SUBDEF', 0x04);
define('JETON_WRITE_META', 0x06);
define('JETON_READ_META_DOC', 0x08);
define('JETON_READ_META_DOC_MAKE_SUBDEF', 0x10);
	

mb_internal_encoding("UTF-8");
phrasea::use_i18n();
