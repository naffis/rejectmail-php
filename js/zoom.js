function doZoom(what,h,w) {
	var scrol=0;
	if (w>screen.width) {
		w=screen.width;
		scrol=1;
	}
	if (h>screen.height) {
		h=screen.height;
		scrol=1;
	}
	var opt='toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars='+scrol+',height='+h+',width='+w+',top=10,left=10';
	//window.child.zoom.close();
	var wnd= window.open('zoom.php?name='+what,'',opt);
	//wnd.resizeTo(w+4,h+20);
	wnd.focus();
}