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
require_once __DIR__ . '/classes/Twig/Autoloader.php';

spl_autoload_register('phrasea_autoload');
Twig_Autoloader::register();
session::getInstance();
phrasea::start();
user::detectlanguage();
$session = session::getInstance();

if(!isset($session->locale) || !$session->isset_cookie('locale') || $session->get_cookie('locale') !== $session->locale)
{
	$avLanguages = user::detectlanguage($session->isset_cookie('locale') ? $session->get_cookie('locale') : null);
}
if(defined('GV_timezone'))
	date_default_timezone_set(GV_timezone);
	
if(isset($session->ses_id) && isset($session->usr_id))
{
	phrasea_open_session($session->ses_id, $session->usr_id);
}

mb_internal_encoding("UTF-8");

define('USE_MINIFY_CSS', true);
define('USE_MINIFY_JS', true);

define('JETON_MAKE_SUBDEF', 0x01);
define('JETON_WRITE_META_DOC', 0x02);
define('JETON_WRITE_META_SUBDEF', 0x04);
define('JETON_WRITE_META', 0x06);
define('JETON_READ_META_DOC', 0x08);
define('JETON_READ_META_DOC_MAKE_SUBDEF', 0x10);


phrasea::use_i18n();
phrasea::load_events();

