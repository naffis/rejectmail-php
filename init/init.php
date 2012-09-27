<?php 
/*
    Wed Nov 17 13:33:01 EET 2004 syntax fix
*/

define('INIT_NAME',		'main'); // name of site partition (main, cp, etc...)

if (!defined('ROOT_PATH')) define('ROOT_PATH', './');

define('DEBUG',			0);
define('STAT_ACTION',	0);
define('STAT_VISIT',	0);

require_once(ROOT_PATH.'init/cfg/project.cfg.php');
require_once(SYS_ROOT.INIT_PATH.CLASS_PATH.'app.class.php');

// GENERAL 	LOAD
$app->load('data');
$app->load('list','cfg');
$app->load('db','class');
$app->load('tpl','class');
$app->load('cp','class');
$app->load('parser','class');
$app->load('filesys','inc');
$app->load('blocks','func');
$app->load('image', 'inc');
$app->load('utils','func');


// MODULES LOAD
$app->load('mail','mod');
$app->load('faq','mod');
$app->load('check','mod');

?>