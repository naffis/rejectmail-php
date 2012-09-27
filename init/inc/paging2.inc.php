<?

//var_dump($app->paging);

if ($app->paging['cur_page']>0) {
	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>0))
	),'first');

	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>($app->paging['cur_page']-1)))
	),'prev');
} else {
	if ($app->paging['pages'] > 1) {
		$tpl->touchBlock('first_empty');
		$tpl->touchBlock('prev_empty');
	}
}


if ($app->paging['pages'] > 1) {
	$current = $app->paging['cur_page']+1;
	$pages_blocks = ceil($app->paging['pages']/$app->paging['max_pages']);
	$current_block = ceil($current/$app->paging['max_pages']);
	$start = $current_block*$app->paging['max_pages']-$app->paging['max_pages']+1;
	$end = $current_block*$app->paging['max_pages'];

	if ($end > $app->paging['pages'])
		$end = $app->paging['pages'];

	if ($current_block==$pages_blocks) {
		$lim = $app->paging['max_pages']-abs($end-$start+1);
		$start -= $lim;
	}

	if ($start<1)
		$start=1;

	for ($i=$start;$i<=$end;$i++) {
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
}

if ($app->paging['cur_page']<($app->paging['pages']-1) && $app->paging['pages']>1) {
	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>($app->paging['cur_page']+1)))
	),'next');

	$tpl->parseVariable(array(
		'href'	=> $app->addGetVariable(array('pcp'=>($app->paging['pages']-1)))
	),'last');
} else {
	if ($app->paging['pages'] > 1) {
		$tpl->touchBlock('next_empty');
		$tpl->touchBlock('last_empty');
	}
}

$tpl->setCurrentBlock('paging');
$tpl->parseCurrentBlock();
?>