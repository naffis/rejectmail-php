<?
if ($app->paging['cur_page']>0) {
	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>0))	
	),'first');
	
	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>($app->paging['cur_page']-1)))
	),'prev');	
} else {
	$tpl->touchBlock('first_empty');
	$tpl->touchBlock('prev_empty');	
}

if (($app->paging['cur_page']+1)<$app->paging['max_pages']/2) {
	$start_page=1;
	$end_page=$app->paging['max_pages'];		
} else if (($app->paging['cur_page']+1) > ($app->paging['pages']-$app->paging['max_pages']/2)) {
	$start_page=$app->paging['pages']-$app->paging['max_pages']+1;
	$end_page=$app->paging['pages'];
} else {
	$start_page=$app->paging['cur_page']+1-$app->paging['max_pages']/2+1;
	$end_page=$app->paging['cur_page']+1+$app->paging['max_pages']/2;
}		



for ($i=$start_page;$i<=$end_page;$i++) {
	if ($i!=($app->paging['cur_page']+1)) {
		$tpl->parseVariable(array(
			'NUMBER'	=> $i,
			'HREF'		=> $app->addGetVariable(array('pcp'=>$i-1))
		),'page');	
	} else {
		$tpl->parseVariable(array(
			'NUMBER'	=> $i
		),'cur_page');			
	}
	
	$tpl->setCurrentBlock('pages');
	$tpl->parseCurrentBlock();
}


if ($app->paging['cur_page']<($app->paging['pages']-1) && $app->paging['pages']>1) {
	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>($app->paging['cur_page']+1)))	
	),'next');
	
	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>($app->paging['pages']-1)))
	),'last');
} else {
	$tpl->touchBlock('next_empty');
	$tpl->touchBlock('last_empty');		
}


$tpl->setCurrentBlock('paging');
$tpl->parseCurrentBlock();



?>