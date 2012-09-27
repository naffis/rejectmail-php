<?
$DATA_BANNER = array(
	'table'		=>	'banners',
	'order'		=>	'id',
	'fields'	=>	array(
		'id'		=>	'hidden',
		'link'		=>	'input',		
		'alt'		=>	'input',
		'image'		=>	'image_banner'
		)
	);
$IMAGE_BANNER = array(
	'images'	=> array (
		0	=> array (
			'name'		=> 'original',
			'height'	=> 0,
			'width'		=> 0),
		1	=> array (
			'name'		=> 'small',
			'height'	=> 100,
			'width'		=> 100),
		2	=> array (
			'name'		=> 'big',
			'height'	=> 60,
			'width'		=> 0)
	),
	'no_image'	=> 1,
	'path'		=> 'banners/'
);
$DATA_TEXT_BLOCKS = array(
	'table'			=> 'text_blocks',
	'order'			=> 'id',
	'fields'		=> array(
				'id'		=> 'hidden',
				'text'		=>	'text',								
				
		)
	);
	
$DATA_ARTICLES = array(
	'table'	=>	'articles',
	'order'	=>	'id',
	'fields'=>	array(
					'title'	=>	'input',
					'article'	=>  'text',
					'link'	=>	'input',
					'link_name'=> 	'input'
				)
	);


$LIST_CHILDREN = array(
	1	=> 'one',
	2	=> 'two',
	3	=> 'three',
	4	=> 'four',
	5	=> 'more...'
);

$LIST_COLOR = array(
	1	=> 'green',
	2	=> 'red',
	3	=> 'blue',
	4	=> 'black'
);

$LIST_SEX = array(
	1	=> 'male',
	2	=>	'female'
);


$TREE_BOOK = array(
	'name'		=> 'Book Tree',
	'max_level'	=> 3,
	
	'node'		=> 'books_directory',
	'point'		=> 'items'
);

$DATA_ITEMS = array(
	'table' => 'items',
	'order' => 'last_changed',
	'fields'	=> array(
		'id'		=>'hidden',
		'parent_id'	=>'category_book',
		'parents'	=>'hidden',
		'name'		=>'input',
		'small_desc'=>'input',
		'description'=>'text',
		'price'		=>'input',
		'add_date'	=>'date'
	),
	'list_fields'	=> 'name,price'

);

$DATA_BOOKS_DIRECTORY = array(
	'table'		=> 'books_directories',
	'order'		=> 'oder',
	'fields'	=> array (
		'id'		=> 'hidden',
		'oder'		=> 'hidden',
		'parent_id'	=> 'category_book',
		'parents'	=> 'hidden',
		'name'		=> 'input'
	),
	'list_fields'	=> 'name',
	'redefine_main'	=> array (
		'parent_id'		=> 'hidden'
	)
);



$DATA_TEST_ITEM = array(
	'table'	=> 'swapz_items',
	'order'	=> 'name',
	
	'fields'	=> array(
		'id'						=> 'hidden',
		'user_id'					=> 'hidden',
		'parent_id'					=> 'category_test',
		'parents'					=> 'hidden',
		'name'						=> array(
			'type'						=> 'input',
			'check_msg'					=> 'Please fill in Swap Title',
			'check'						=> ''
		),
		'price'						=> array(
			'type'						=> 'input',
			'check'						=> '[0-9\.\,]+'
		),
		'description'				=> 'text',
		'exchange_item_description'	=> 'text',
		'picture'					=> 'image_swap_picture',
		'days_to_swap'				=> 'list_def_item_time',
		'creation_time'				=> 'hidden',
		'status'					=> array(
			'type'						=> 'radio_def_item_status',
			'name'						=> 'status'
		),
		'is_open'					=> 'flag',
		'country_area'				=> 'list_def_country_area',
		'location'					=> array(
			'type'						=> 'input',
			'check'						=> ''
		),
		'stat'						=> 'input'
	),
	'list_fields'	=> 'name',
	'redefine_my'	=> array(
		'creation_time'	=> 'date',
		'user_id'		=> array(
			'type'			=> 'list_data_user',
			'cross_field'	=> 'login'
		)
	)
);

$IMAGE_TEST = array (
	'images'	=> array (
		0	=> array (
			'name'		=> 'original',
			'height'	=> 0,
			'width'		=> 0),
		1	=> array (
			'name'		=> 'small',
			'height'	=> 100,
			'width'		=> 100),
		2	=> array (
			'name'		=> 'big',
			'height'	=> 350,
			'width'		=> 350)
	),
	'no_image'	=> 1,
	'path'		=> 'test/'
);


/************************ DEFAULT DO NOT REMOVE *******************************/

$DATA_EMAIL = array(
	'table'		=> 'email',
	'order'		=> 'id',
	'fields'	=> array(
		'id'					=> 'hidden',
		'name'					=> 'input',
		'recipients'			=> 'text_small',
		'subject'				=> 'input',
		'body'					=> 'text',
		'from_header'			=> 'input',
		'content_type_header'	=> 'radio_def_content_type'
	)
);

$DATA_CONTENT = array (
	'table'	=> 'contents',
	'order'	=> 'id',
	'fields'	=> array (
		'id'		=> 'hidden',
		'name'		=> 'input',
		'content'	=> 'text'
	)
);

$DATA_USER_CP	= array (
	'table'	=> 'users_cp',
	'order'	=> 'login',
	'fields'	=> array (
		'id'		=> 'hidden',
		'login'		=> 'input',
		'password'	=> array(
			'type'		=> 'password',
			'view'		=> '***'
		),
		'sid'		=> 'hidden',
		'level'		=> array (
			'type'		=> 'hidden',
			'def_value'	=> 1
		),
		'lastdate'	=> 'hidden',
		'lastlogin'	=> array (
				'type'	=> 'datetime',
				'name'	=> 'Last login time'
		)	
	)
);


?>