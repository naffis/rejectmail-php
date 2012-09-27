<?php

$AUTH_MAIN = array(
    'table'					=> 'users', /* authorization table */
    'default'				=> 0, /* default authorization level */           
    'login_page'			=> 'login.php',
    'logged_page'			=> 'home.php', /* page where to jump on login */  
    'jump_to_logged_page'	=> 0, /* on login: 0 - jump to requested page, 1 - jump to $logged_page */
    'remove_auto_login'		=> 0, /* remove cookies on logout */      
    'sid_name'				=> 'sid_main', /* $_SESSION[$sid_name] */
    'pages'					=> array(  /* custom auth levels per page */
    	
    )
);

$AUTH_CP = array(
    'table'					=> 'users_cp',           
    'default'				=> 1,                  
    'login_page'			=> 'login.php',
    'logged_page'			=> 'index.htm',   
    'jump_to_logged_page'	=> 1,           
    'remove_auto_login'		=> 0,       
    'sid_name'				=> 'sid_cp',
    'pages'					=> array()
);
$AUTH_NO_AUTH = array(
    'table'					=> 'users', /* authorization table */
    'default'				=> 0, /* default authorization level */           
    'login_page'			=> 'login.php',
    'logged_page'			=> 'home.php', /* page where to jump on login */  
    'jump_to_logged_page'	=> 0, /* on login: 0 - jump to requested page, 1 - jump to $logged_page */
    'remove_auto_login'		=> 0, /* remove cookies on logout */      
    'sid_name'				=> 'sid_main', /* $_SESSION[$sid_name] */
    'pages'					=> array(  /* custom auth levels per page */
    	
    )
);
?>