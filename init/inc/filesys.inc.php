<?
function deleteDir($dir) {
	$dir = (substr($dir,0,-1)!='/')?$dir.'/':$dir;

	if (OS == 'unix') {

	} else {
		if (file_exists($dir)) {
			if ($handle = @opendir($dir)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != '.' && $file != '..') {
						if (is_dir($dir.$file)) {
							deleteDir($dir.$file.'/');
							@rmdir($dir.$file);
						}
						if (is_file($dir.$file)) {
							deleteFile($dir.$file);
						}
					}
				}
				closedir($handle);
			}
			rmdir($dir);
		}
	}
}

function fileSysDebug($action,$params='',$rez='') {
	if (DEBUG==1) {
		$GLOBALS[FILESYS_DEBUG][]=array(
			'action'	=> $action,
			'params'	=> $params,
			'rez'		=> $rez
		);
	}
}

/**
 * @return boolean true -- if shell command was executed successfully, false -- otherwise
 * @param string $command command to execute
 * @desc Executes shell command.
*/
function sysExec($command) {
	$arr=array();
//	exec($command,$arr,$error);
	$res = `$command 2>&1`;
	if ($res) {
		print_r($command.BR.$res);
	}
	return $error===0;
}

/**
 * @return boolean true - if dir was succesfully created and chmoded, false -- otherwise
 * @param string $path directory path
 * @param string $mode directory mode
 * @desc Creates directory.
*/
function createDir($path,$mode='0777') {

	$path = (substr($path,0,-1)!='/')?$path.'/':$path;

	if (OS == 'unix') {
		if (!sysExec("mkdir $path")) return false;
		if (!changeMode($path, $mode, true)) return false;

		return true;
	} else {
		$new_path_start=strpos($path,'files/');
		$new_path=substr($path,$new_path_start+6,-1);
		$basic_path=substr($path,0,$new_path_start+6);
		$new_dirs=split('/',$new_path);

		foreach ($new_dirs as $new_dir) {
			$create_dir=$basic_path.$new_dir.'/';
			if (!file_exists($create_dir)) mkdir($create_dir);
			$basic_path.=$new_dir.'/';
		}

		return true;
	}
}

/**
 * @return boolean true -- if file was successfully copied, false -- otherwise
 * @param string $src source file
 * @param string $dst destination file
 * @param string $mode destination file mode
 * @desc Copies file.
*/
function copyFile($src,$dst,$mode=null) {
	if (OS == 'unix') {
		if (!sysExec("cp $src $dst")) return false;
		if (isset($mode)) {
			if (!changeMode($dst, $mode)) return false;
		}
		return true;
	} else {
		return copy($src, $dst);
	}
}

/**
 * @return boolean true -- if mode was succesfully changed, false -- otherwise
 * @param string $name file name (path)
 * @param string $mode new mode
 * @param boolean $recursive is mode change resursively
 * @desc Change file mode.
*/
function changeMode($name, $mode='0777',$recursive = false) {
	if (OS == 'unix') {
		$rec_flag = $recursive?'-R':'';
		return sysExec("chmod $mode $rec_flag $dst");
	} else return true;
}

function deleteFile($name) {

	if (file_exists($name)) {
		return unlink($name);
	} else {
		return true;
	}
}


function copyDirR($src,$dst,$mode='0777') {
	//recursive copy of directory

	if (substr($src,0,-1)!='/') $src.='/';
	if (substr($dst,0,-1)!='/') $dst.='/';

		$d = dir($src);
		while($entry=readdir($d->handle)) {
			if(is_dir($d->path.$entry)) {

				if(strlen($entry)>2)
				{
					createDir($dst.$entry.'/',$mode);
					copyDirR($d->path.$entry,$dst.$entry);
				}
				else
				{
					if($entry[0]!='.')
					{
						createDir($dst.$entry,$mode);
						copyDirR($d->path.$entry,$dst.$entry);
					}
					else
					{
						if(strlen($entry)>1&&$entry[1]!='.')
						{
							createDir($dst.'/'.$entry,$mode);
							copyDirR($d->path.$entry,$dst.$entry);
						};
					};
				};
			} else {
				copy($d->path.$entry,$dst.$entry);
			};
		};
		$d->close();

};

function deleteDirR($src) {
	//recursive copy of directory

	if (substr($src,0,-1)!='/') $src.='/';

		$d = dir($src);
		while($entry=readdir($d->handle)) {
			if(is_dir($d->path.$entry)) {
				if(strlen($entry)>2) {
					deleteDirR($d->path.$entry);
				} else {
					if($entry[0]!='.') {
						deleteDirR($d->path.$entry,$dst.$entry);
					} else {
						if(strlen($entry)>1&&$entry[1]!='.') {
							deleteDirR($d->path.$entry,$dst.$entry);
						}
					}
				}

				if ($entry!='.' && $entry!='..') {
					rmdir($d->path.$entry);
				}
			} else {
				unlink($d->path.$entry);
			}
		}
		$d->close();
}
?>