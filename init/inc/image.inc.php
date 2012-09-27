<?
/**
 * @return unknown
 * @param string $src Source image file
 * @param string $dst Destination image file
 * @param int $dst_w Destination image width
 * @param int $dst_h Destination image height
 * @param boolean $only_minimize If true, then image will not be enlarged.
 * @desc Resize image.
*/
function imageResize($src, $dst, $dst_w, $dst_h, $only_minimize=true) {
	// 3.0 - updated
	if ($dst_w < 1 || $dst_h < 1) {
		copyFile($src, $dst, '0777');
		return true;
	}
	
	$file_info = getimagesize($src);
	$src_w = $file_info[0];
	$src_h = $file_info[1];
	$src_type = $file_info[2]; // file type
	
	list ($dst_w, $dst_h) = imageMath($src_w, $src_h, $dst_w, $dst_h);
	
	// only minimize 
	if ($only_minimize && $dst_w > $src_w && $dst_h > $src_h) {
		copyFile($src, $dst, '0777');
		return true;
	}
	
	if (IMAGE_LIB_TYPE == 1) {
		// if Gif is unsupported just copy
		if (!imageCheckType($src_type)) {
			copyFile($src, $dst, '0777');
			return false;
		}
		
		$src_im = imageCreateFromFile($src, $src_type);
		$dst_im = imagecreate($dst_w,$dst_h);
		imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
		imageFile($dst_im, $dst, $src_type);
		changeMode($dst);
	}
	
	if (IMAGE_LIB_TYPE == 2) {
		if (!imageCheckType($src_type)) {
			copyFile($src, $dst, '0777');
			return false;
		}
		$src_im = imageCreateFromFile($src, $src_type);
		$dst_im = imagecreatetruecolor($dst_w,$dst_h);
		imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
		imageFile($dst_im, $dst, $src_type);
		changeMode($dst);
	}
	
	if (IMAGE_LIB_TYPE == 3) {
		copyFile($src, $dst, '0777');
		$cmd = IMAGE_MAGICK_PATH."mogrify -resize ".$dst_w."x".$dst_h." ".$dst;
		$res = `$cmd 2>&1`;
//		print $cmd." - ".$res.BR;
	}
	
	return true;
}


function imageCut($name1, $name2, $left, $top, $width, $height) {
	if ($name1 == $name2) return true;
	
	if ($left == 0 && $top == 0 && $width == '' && $height == '') {
		copy($name1, $name2);
		return true;
	}
	
	$file_info = getimagesize($name1);
	$file_type = $file_info[2];
	
	$srcX = $left;
	$srcY = $top;
	$dstX = 0;
	$dstY = 0;
	$srcW = $width;
	$srcH = $height;
	$dstW = $width;
	$dstH = $height;
	
	if (JPEG_LIB == 1) {
		if (!imageCheckType($file_type)) {
			copy($name1, $name2);
			return false;
		}
		$src_im = imageCreateFromFile($name1, $file_type);
		$dst_im = imagecreate($dstW, $dstH);
		imagecopyresized($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
		imageFile($dst_im, $name2, $file_type);
		chmod($name2, 0666);
	}
	
	if (JPEG_LIB == 2) {
		if (!imageCheckType($file_type)) {
			copy($name1, $name2);
			return false;
		}
		$src_im = imageCreateFromFile($name1, $file_type);
		$dst_im = imagecreatetruecolor($dstW, $dstH);
		imagecopyresized($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
		imageFile($dst_im, $name2, $file_type);
		chmod($name2, 0666);
	}
	
	if (JPEG_LIB == 3) {
		/*copy($name1, $name2);
		chmod($name2, 0666);
		$cmd = JPEG_UTIL_PATH."mogrify -crop ".$dstW."x".$dstH." $srcX $srcY ".$name2;
		$res = `$cmd 2>&1`;
		//print $cmd." - ".$res.BR;*/
		if (!imageCheckType($file_type)) {
			copy($name1, $name2);
			return false;
		}
		$src_im = imageCreateFromFile($name1, $file_type);
		$dst_im = imagecreate($dstW, $dstH);
		imagecopyresized($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
		imageFile($dst_im, $name2, $file_type);
		chmod($name2, 0666);
	}
	
	return true;
}


function imageQuality($name1, $name2, $quality) {
	if ($name1 == $name2) return true;
	
	$file_info = getimagesize($name1);
	$srcW = $file_info[0];
	$srcH = $file_info[1];
	$file_type = $file_info[2];
	
	if ($file_type != 2) {
		return false;
	}
	
	if (JPEG_LIB == 1) {
		$src_im = imagecreatefromjpeg($name1);
		$dst_im = imagecreate($srcW,$srcH);
		imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $srcW, $srcH, $srcW, $srcH);
		imagejpeg($dst_im, $name2, $quality);
		chmod($name2, 0666);
	}
	
	if (JPEG_LIB == 2) {
		$src_im = imagecreatefromjpeg($name1);
		$dst_im = imagecreatetruecolor($srcW,$srcH);
		imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $srcW, $srcH, $srcW, $srcH);
		imagejpeg($dst_im, $name2, $quality);
		chmod($name2, 0666);
	}
	
	if (JPEG_LIB == 3) {
		copy($name1, $name2);
		chmod($name2, 0666);
		//$cmd = JPEG_UTIL_PATH."mogrify -resize ".$srcW."x".$srcH." -quality $quality ".$name2;
		$cmd = JPEG_UTIL_PATH."mogrify -resize ".$srcW."x".$srcH." ".$name2;
		$res = `$cmd 2>&1`;
		//print $cmd." - ".$res;
	}
	
	return true;
}


function imageMath($width1, $height1, $width2, $height2) {
	// 3.0 updated
	$k1 = $width1/$height1;
	$k2 = $width2/$height2;
	
	$q = $k1/$k2;
	
	if ($k1 >= 1) {
		
		if ($q >= 1) {
			$width = $width2;
			$height = $width/$k1;
		} else {
			$width = $width2*$q;
			$height = $height2;
		}
		
	} else {
		
		if ($q >= 1) {
			$height = $height2/$q;
			$width = $height*$k1;
		} else {
			$height = $height2;
			$width = $height*$k1;
		}
		
	}
	
	return array(round($width), round($height));
}


function imageCreateFromFile($file, $type) {
	switch ($type) {
		case 1:		return imagecreatefromgif($file); break; //GIF
		case 2:		return imagecreatefromjpeg($file); break; //JPG
		case 3:		return imagecreatefrompng($file); break; //PNG
		case 4:		break; //SWF
		case 5:		break; //PSD
		case 6:		break; //BMP
		case 7:		break; //TIFF(intel byte order)
		case 8:		break; //TIFF(motorola byte order)
		case 9:		break; //JPC
		case 10:	break; //JP2
		case 11:	break; //JPX
		case 12:	break; //JB2
		case 13:	break; //SWC
		case 14:	break; //IFF
	}
	
	//return imagecreatefromjpeg($file);
}


function imageFile($im, $file, $type) {
	switch ($type) {
		case 1:		imagegif($im, $file); break; //GIF
		case 2:		imagejpeg($im, $file); break; //JPG
		case 3:		imagepng($im, $file); break; //PNG
		case 4:		break; //SWF
		case 5:		break; //PSD
		case 6:		break; //BMP
		case 7:		break; //TIFF(intel byte order)
		case 8:		break; //TIFF(motorola byte order)
		case 9:		break; //JPC
		case 10:	break; //JP2
		case 11:	break; //JPX
		case 12:	break; //JB2
		case 13:	break; //SWC
		case 14:	break; //IFF
	}
	
	//imagejpeg($im, $file);
}


/**
 * @return boolean True - if given image type supported
 * @param int $type Image type
 * @desc Check if given image type supported by image library.
*/
function imageCheckType($type) {
	// 3.0 updated
	switch ($type) {
		case 1:		return (IMAGE_LIB_TYPE == 3 || (@imagetypes() & IMG_GIF)); //GIF
		case 2:		return (IMAGE_LIB_TYPE == 3 || (@imagetypes() & IMG_JPG)); //JPG
		case 3:		return (IMAGE_LIB_TYPE == 3 || (@imagetypes() & IMG_PNG)); //PNG
		/*case 4:		break; //SWF
		case 5:		break; //PSD
		case 6:		break; //BMP
		case 7:		break; //TIFF(intel byte order)
		case 8:		break; //TIFF(motorola byte order)
		case 9:		break; //JPC
		case 10:	break; //JP2
		case 11:	break; //JPX
		case 12:	break; //JB2
		case 13:	break; //SWC
		case 14:	break; //IFF*/
	}
	return false;
}


/**
 * @return string Image type name (GIF, JPG, PNG etc.)
 * @param int $type Image type.
 * @desc Get image type description.
*/
function imageGetTypeName($type) {
	switch ($type) {
		case 1:		return 'GIF'; break; //GIF
		case 2:		return 'JPG'; break; //JPG
		case 3:		return 'PNG'; break; //PNG
		case 4:		return 'SWF'; break; //SWF
		case 5:		return 'PSD'; break; //PSD
		case 6:		return 'BMP'; break; //BMP
		case 7:		return 'TIFF'; break; //TIFF(intel byte order)
		case 8:		return 'TIFF'; break; //TIFF(motorola byte order)
		case 9:		break; //JPC
		case 10:	break; //JP2
		case 11:	break; //JPX
		case 12:	break; //JB2
		case 13:	break; //SWC
		case 14:	break; //IFF
	}
	
	return '';
}

?>
