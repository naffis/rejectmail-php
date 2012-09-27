<?
// Class content
// v 3.0.

class Tfile {
	var $file_name,			// name of file description $FILE_NAME
		$field_name,		// name of field in data defenition
		$file,				// array of file description
		$path,				// file path
		$id,				// file id
		$original_name,		// original file name
		$max_size,			// max size of the file, bytes
		$full_name,			// full name - path and file name

		$ftp_name;			// name of FTP file that was setted to insert/update

	/**
	* @return void
	* @param Data|Template $object	Object that contain information for initializtion image.
	* @param int $id 				ID of image.
	* @desc Initialize image.
	*/
	function init(&$object, $value = null) {
		$this->file_name	= $object->element_type_info;
		$this->field_name	= $object->element_name;
		$this->file			= $GLOBALS['FILE_'.strtoupper($this->file_name)];
		$this->path			= SYS_ROOT.FILE_PATH.$this->file['path'];
		$this->max_size		= $this->file['max_size']*1024;
		preg_match('/^([0-9]+)\_(.*)$/',$value,$file_info);
		if ($file_info[1]!='') {
			$this->id			= $file_info[1];
		}
		if ($file_info[2]!='') {
			$this->original_name = $file_info[2];
		}
		$this->full_name = $this->path.'/'.$this->id.'_'.$this->original_name;
	}

	function delete(&$values) {
		GLOBAL $data;
		
		if ($values) foreach ($values as $value) { // if we have several records to delete
			$this->init(&$data,$value['id'].'_'.$value[$data->element_name]);

			$this->deleteFile();
		}
	}
	
	function insert(&$values) {
		GLOBAL $data;
		GLOBAL $db;

		$new_id = $db->getAutoIncrement($data->table);
		$this->id=$new_id;

		$this->init($data, $new_id);
		
		$new_fn = $this->saveFile();
		if ($new_fn!='') {
			$old_file_name = $db->get('id='.$this->id,$this->field_name,$data->table);
			$old_fn = $this->path.'/'.$this->id.'_'.$old_file_name;
			deleteFile($old_fn);
			return $new_fn;
		} else {
			return false;
		}		

		return false;		
	}

	/**
	* @return boolean True -- image new, no file(s) uploaded; false -- otherwise
	* @desc Check whether file new or exists
	*/
	function isNew() {
		$new = true; // flag - file insert OR update
		if (isset($this->id) && isset($this->original_name)) {
			$new = !file_exists($this->full_name);
		}
		return $new;
	}

	function update($values) {
		GLOBAL $data;
		GLOBAL $db;

		$this->init($data,$values['id'].'_'.$values[$data->element_name]);

		// set file name that was choosed from server's /upload/ folder
		$this->ftp_name = $values['ftp_'.$this->field_name];

		if ($values[$this->field_name.'_delete'] == 1) {
			// delete
			$this->original_name = $db->get('id='.$this->id,$this->field_name,$data->table);
			$this->deleteFile();
			return '';
		} else {
			// update
			$new_fn = $this->saveFile();
			if ($new_fn!='') {
				$old_file_name = $db->get('id='.$this->id,$this->field_name,$data->table);
				$old_fn = $this->path.'/'.$this->id.'_'.$old_file_name;
				deleteFile($old_fn);
				return $new_fn;
			} else {
				return false;
			}
		}
	}

	function makeFormElement($value=null) {
		GLOBAL $parser;

		$tpl = &$parser->tpl;

		$this->init($parser, $value);

		if ($this->isNew()) {
			$tpl->setCurrentBlock('new');
			$tpl->setVariable(array(
				'NAME'					=> $this->field_name,
				'MAX_SIZE'				=> $this->max_size,
				'MAX_SIZE_FORMATTED'	=> formatFileSize($this->max_size)
			));
			$tpl->parseCurrentBlock();

		} else {
			$fn = $this->path.'/'.$this->id.'_'.$this->original_name;
			$tpl->setCurrentBlock('update');
			$tpl->setVariable(array(
				'NAME'					=> $this->field_name,
				'MAX_SIZE_FORMATTED'	=> formatFileSize($this->max_size),
				'FILE_NAME'				=> FILE_PATH.$this->file[path].$this->id.'_'.$this->original_name,//$this->full_name,
				'FILE_PATH'				=> ROOT_PATH.FILE_PATH.$this->file[path].$this->id.'_'.$this->original_name,//$this->full_name,
				'FILE_TYPE'				=> (MIME_MODULE)?mime_content_type($fn):null,
				'FILE_SIZE'				=> formatFileSize(filesize($fn)),
				'FILE_DELETE'			=> $this->field_name.'_delete',
				'EDITORS_PATH'			=> 'editors/'
			));
			$tpl->parseCurrentBlock();

		}

		return trim($tpl->get());

	}

	function makeElementView($value) {
		GLOBAL $parser;
		$this->init(&$parser,$value);
		
		$file_size = (file_exists($this->full_name))?formatFileSize(filesize($this->full_name)):'';
		$file_link = HTTP_ROOT.FILE_PATH.$this->file['path'].$this->id.'_'.$this->original_name;
		
		if ($file_size!='') {
			$tpl = new template(TPL_PATH);
			$tpl_name=$parser->getSystemTemplate('type/file');
			$tpl->loadTemplatefile($tpl_name,true,true);
			$tpl->parseVariable(array(
				'file_name'	=> $this->original_name,
				'id'		=> $this->id,
				'link'		=> $file_link,
				'size'		=> $file_size,
				'data_name'	=> $this->file_name		
			),'view');
			$view = $tpl->get();
		}
		
		if ($parser->buffer=='dynamic_view') {
			if (file_exists($this->full_name)) {
				return $view;
			} else {
				return ' ';	
			}
		}		
		
		$to_parse='FILE_'.strtoupper($this->file_name);
		
		$return[$to_parse] = $file_link;
		$return[$to_parse.'_NAME'] = $this->original_name;
		$return[$to_parse.'_SRC'] = $file_link;
		$return[$to_parse.'_SIZE'] = $file_size;
		$return[$this->field_name] = $view;
		return $return;
	}


	function saveFile() {
		GLOBAL $app;

		if (isset($this->ftp_name) && $this->ftp_name!='') {
		// WORK WITH FTP UPLOAD FILE
			$server_file_name = basename($this->ftp_name);
			$server_file = SYS_ROOT.$this->ftp_name;

			// check file
			if (!file_exists($server_file)) {
				$app->setError('File you selected was not found on ftp. Please check it.');
				return '';
			}

			// create folder(s) if not exist(s)
			if (!file_exists($this->path)) {
				createDir($this->path);
			}

			if (!copyFile($server_file, $this->path.'/'.$this->id.'_'.$server_file_name, '0777')) {
				return '';
			}

			return $server_file_name;

		} else {
		// WORK WITH UPLOADED FROM USER'S COMPUTER FILE
			if (!isset($_FILES[$this->field_name]['name']) && !isset($_FILES[$this->field_name]['tmp_name'])) {
				return '';	// no file where uploaded
			}

			$real_name = $_FILES[$this->field_name]['name'];
			$tmp_name = $_FILES[$this->field_name]['tmp_name'];

			$bad_format = false;
			if ($real_name != '' && !is_uploaded_file($tmp_name)) {
				$app->setError('File is not received');
				return '';
			}

			if (filesize($tmp_name)>$this->max_size) {
				$app->setError('File is too large.');
				return '';
			}

			// create folder(s) if not exist(s)
			if (!file_exists($this->path)) {
				createDir($this->path);
			}

			if (!copyFile($tmp_name, $this->path.'/'.$this->id.'_'.$real_name, '0777')) {
				return '';
			}

			return $real_name;
		}
	}

	function deleteFile() {
		$fn = $this->path.'/'.$this->id.'_'.$this->original_name;
		deleteFile($fn);
	}


}



?>