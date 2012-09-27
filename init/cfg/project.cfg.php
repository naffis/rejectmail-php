<?php
// Project CFG
// v 3.0
define('SYS_ROOT',		'/home/www/rejectmail/'); // system root
define('HTTP_ROOT',		'/'); // www root

define('SQL_HOST',      'localhost');	// DB host name
define('SQL_DBNAME',	'email');		// DB name
define('SQL_USER',		'rejectmail');		// user name
define('SQL_USERWORD',	'XXX');			// user password

define('IMAGE_LIB_TYPE', 2);			// image library type (1 = GD1; 2 = GD2; 3 = ImageMagick)
define('IMAGE_MAGICK_PATH', '/usr/local/bin/'); // image magick sys path

define('OS', 'windows');				// unix/windows platform

define('DATE_FORMAT', 	'd M Y');		// date format
define('TIME_FORMAT', 	'H:i');			// time format

define('TIME_LIMIT',	30);					// max executing time
define('ERROR_LEVEL',	E_ALL & ~E_NOTICE);		// error level

define('INIT_PATH',		'init/');		// LISK directory path
define('CFG_PATH',		'cfg/');		// config directory path
define('CLASS_PATH',	'class/');		// class directory path
define('INC_PATH',		'inc/');		// include directory path
define('MODULE_PATH',	'modules/');	// modules directory path
define('FUNC_PATH',		'func/');		// functions directory path
define('TGER_PATH',		'tger/');		// triggers directory path
define('TYPE_PATH',		'type/');		// types directory path
define('FILE_PATH',		'files/');		// project uploaded files path

define('ORIGINAL_THUMBNAIL_SIZE',	'100x100'); // oroginal image thumbnail width (used in makeFormElement)

define('MIME_MODULE',	false);			// is mime magic loaded - used with file type detection

define('BR', '<br>');
define('HR', '<hr>');

//TPL constant
define('TPL_PATH', 'tpl/');
define('TPL_EXT', 'htm');
//

// WarHead tpl eval
define('EVAL_REG_EXP', '@<\?\s(.+?)\s\?>@sm');


?>