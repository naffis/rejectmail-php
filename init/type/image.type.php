<?
// Class content
// v 3.0.

class Timage {
	var $image,				// image info array
		$image_name,		// image name $IMAGE_name
		$field_name,		// field name in fields array
		$path,				// image path
		$http_path,			// http path
		$no_image,			// boolean true if no image feature is enabled
		$root_path;			// image path from project root

	/**
	* @return void
	* @param Data|Template $object	Object that contain information for initializtion image.
	* @param int $id 				ID of image.
	* @desc Initialize image.
	*/
	function init(&$object, $id = null) {
		$this->image_name	= $object->element_type_info;
		$this->field_name	= $object->element_name;
		$this->image		= $GLOBALS['IMAGE_'.strtoupper($this->image_name)];
		$this->path			= SYS_ROOT.FILE_PATH.$this->image[path];
		$this->http_path	= HTTP_ROOT.FILE_PATH.$this->image[path];
		$this->root_path	= FILE_PATH.$this->image[path];
		$this->no_image		= ($this->image['no_image']==1)?true:false;
		$this->id			= $id;
	}

	function delete(&$values) {
		GLOBAL $data;

		if ($values) foreach ($values as $value) { // if we have several records to delete
			$this->init($data,$value['id']);

			foreach ($this->image['images'] as $key=>$image) {
				deleteFile($this->path.$value['id'].'_'.$key);
			}
			deleteFile($this->path.$value['id'].'_t');
		}

	}

	/**
	* @return boolean True -- image new, no file(s) uploaded; false -- otherwise
	* @desc Check whether image new (image file(s) presence)
	*/
	function isNew() {
		$new = true; // flag - image insert OR update
		if (isset($this->id)) {
			$file_name=$this->path.$this->id.'_0';
			$new = !file_exists($file_name);
		}
		return $new;
	}

	function insert($values) {
		GLOBAL $data;
		GLOBAL $db;

		$new_id = $db->getAutoIncrement($data->table);

		$this->init($data,$new_id);

		foreach ($this->image['images'] as $key=>$params) {
			$is_saved = $this->saveUploadedFile($key);
			if ($is_saved && $key==0) {
				$this->createThumbnail($key);
			}
			if (!$is_saved && $key!=0) {
				$this->createThumbnail($key);
			}
		}

		return false;
	}

	/**
	* @return void
	* @param array $values Array of new values
	* @desc Update image information, upload new image or delete old -- depends on parameters.
	*/
	function update($values) {
		GLOBAL $data;

		$this->init($data,$values['id']);
		
		if ($this->isNew()) {
			// insert image
			foreach ($this->image['images'] as $key=>$params) {
				$is_saved = $this->saveUploadedFile($key);
				if ($is_saved && $key==0) {
					$this->createThumbnail($key);
				}
				if (!$is_saved && $key!=0) {
					$this->createThumbnail($key);
				}
			}
		} else {
			//update images

			if ($values['is_delete']) {  //delete all images
			    if ($values['apply_to_thumbnails']) {
					foreach ($this->image['images'] as $key=>$image) {
						unlink($this->path.$values['id'].'_'.$key);
					}
			    }

				deleteFile($this->path.$values[id].'_t');
			}

			//save uploaded file
			$is_saved = $this->saveUploadedFile(0);

			if ($is_saved) { //main image uploaded
				$this->createThumbnail(0);  //make original thumbnail
				if ($values['apply_to_thumbnails']) { //apply changes to thumbnails
					foreach ($this->image['images'] as $key=>$params) {
						if ($key!=0) $this->createThumbnail($key);
					}
				}
			}

		}

		return false;
	}

	/**
	* @return string 	HTML code for image form element.
	* @param int 		$id Image ID.
	* @param array 		$element_params
	* @desc Make image form element
	*/
	function makeFormElement($id=null, $element_params=null) {
		GLOBAL $parser;
		GLOBAL $db;
		
		$tpl = &$parser->tpl;

		$this->init($parser,$id);

		if ($this->isNew()) {
			// create thumbnails
			foreach ($this->image['images'] as $key=>$params) {
				if ($key!=0) {
					$tpl->setCurrentBlock('new_thumbnail');
					$tpl->setVariable(array(
						'NAME'				=> $this->field_name.'_'.$key,
						'THUMBNAIL_NAME'	=> $params['name'],
						'THUMBNAIL_WIDTH'	=> $params['width'],
						'THUMBNAIL_HEIGHT'	=> $params['height']
					));
					$tpl->parseCurrentBlock();
				}
			}
			// create original
			$tpl->setCurrentBlock('new');
			$tpl->setVariable(array(
				'NAME'		=> $this->field_name.'_0',
				'IMG_NAME' => $this->field_name
			));
			$tpl->parseCurrentBlock();

		} else {

			// create thumbnails
			foreach ($this->image[images] as $key=>$params) {
				if ($key!=0) {
					$file_name = $this->path.$this->id.'_'.$key;
					$http_file_name = HTTP_ROOT.$this->root_path.$this->id.'_'.$key;
					if (file_exists($file_name)) {
						// if thumbnail exists
						$thumbnail_info = getimagesize($file_name);
						$tpl->setCurrentBlock('update_thumbnail');
						$tpl->setVariable(array(
							'THUMBNAIL_NAME'		=> $params[name],
							'THUMBNAIL_W'			=> $thumbnail_info[0],
							'THUMBNAIL_H'			=> $thumbnail_info[1],
							'THUMBNAIL_FILE_NAME'	=> $http_file_name,
							'EDITORS_PATH'			=> 'editors/',
							'ID' => $this->id,
							'KEY' => $key,
							'IMAGE_NAME' => $this->image_name
						));
						$tpl->parseCurrentBlock();
					} else {
						// if thumbnail NOT exists

					}
				}
			}

			$thumbnail_file_name = $this->path.$this->id.'_t';
			$thumbnail_http_file_name = HTTP_ROOT.$this->root_path.$this->id.'_t';
			$original_thumbnail_info = getimagesize($thumbnail_file_name);
			$original_file_name = $this->path.$this->id.'_0';
			$original_info = getimagesize($original_file_name);
			$file_size = filesize($original_file_name);
			$tpl->parseVariable(array(
			    'NAME'						=> $this->field_name.'_0',
			    'IMAGE_FILE_DELETE'			=> 'is_delete',
			    'APPLY_TO_THUMBNAILS'		=> 'apply_to_thumbnails',

				'ORIGINAL_THUMBNAIL_SRC'	=> $thumbnail_http_file_name,
				'ORIGINAL_THUMBNAIL_INFO'	=> $original_thumbnail_info[3],
				'TIMESTAMP'				=> time(),
				'IMAGE_SRC'					=> $this->path.$this->id,
				'IMAGE_TYPE'				=> strtolower(imageGetTypeName($original_info[2])),
				'IMAGE_W'					=> $original_info[0],
				'IMAGE_H'					=> $original_info[1],
				'IMAGE_FILE_SIZE'			=> formatFileSize($file_size),
				'IMG_NAME' => $this->field_name,
				'EDITORS_PATH'			=> 'editors/',
				'ID' => $this->id,
				'KEY' => '0',
				'IMAGE_NAME' => $this->image_name
//				'FILENAME' => str_replace(SYS_ROOT,'',$file_name)


			),'update');

		}

		return trim($tpl->get());
	}

	function makeElementView($value=null) {
		GLOBAL $parser;

		// check is value = ID ?
		if (!isset($value)) {
			return '';
		}

		$this->init($parser, $value);
		$return = array();

		foreach ($this->image['images'] as $key=>$params) {
			
			if (!file_exists($this->path.$this->id.'_'.$key)) {
				if ($this->no_image) {				
					$temp_id='no_image';
				} else {
					continue;	
				}
			} else {
				$temp_id=$this->id;	
			}
			
			$file_name=$this->path.$temp_id.'_'.$key;
			$file_http_name = $this->http_path.$temp_id.'_'.$key;			
			
			$size = getimagesize($file_name);
			$width = $size[0];
			$height = $size[1];

			$prefix='';
			if ($key!=0) {
				$prefix = '_'.strtoupper($params['name']);
			}

			if ($key == 0) {
				$z_src=	$file_http_name;
				$z_w = $width;
				$z_h = $height;
			}
			
			$to_parse='IMAGE_'.strtoupper($this->image_name).$prefix;

			$return[$to_parse]			= '<img src="'.$file_http_name.'" width="'.$width.'" height="'.$height.'" border="0">';
			$return[$to_parse.'_SRC']	= $file_http_name;
			$return[$to_parse.'_HEIGHT']= $height;
			$return[$to_parse.'_WIDTH']	= $width;
			$return[$to_parse.'_SIZE']	= ' width="'.$width.'" height="'.$height.'" ';
			$return[$to_parse.'_INFO']	= ' src="'.$file_http_name.'" width="'.$width.'" height="'.$height.'" ';
		}

		// add system
		$return['ZOOM'] = "javascript: doZoom('$z_src','$z_h','$z_w');";
		$file_name=$this->path.$temp_id.'_t';
		$file_http_name = $this->http_path.$temp_id.'_t';
		
		if ($parser->buffer=='dynamic_view') {
			if (file_exists($file_name)) {
				return '<a href="'.HTTP_ROOT.$this->root_path.$temp_id.'_0'.'" target="_blank"><img src="'.HTTP_ROOT.$this->root_path.$this->id.'_t'.'?'.time().'" '.$original_thumbnail_info[3].' border="0"></a>';
			} else {
				return ' ';	
			}
		} else {
			return $return;
		}
	}

	/**
	* @return boolean True -- if file succesfully uploaded, false -- otherwise.
	* @param int $key Type of thumbnail
	* @desc Save uploaded image file
	*/
	function saveUploadedFile($key) {
		GLOBAL $app;

		$form_field_name = $this->field_name.'_'.$key;

		//$form_name=$img_key."_".$key;
		$params = $this->image['images'][$key];

		$client_side_file_name = $_FILES[$form_field_name]['name']; // real name
		$tmp_name = $_FILES[$form_field_name]['tmp_name'];

		if (!is_uploaded_file($tmp_name)) {
			// if file wasn't uploaded
			return false;
		} else {
			// check file type - is Image ?
			$file_info = getimagesize($tmp_name);
			$file_type = sprintf("%d",@$file_info[2]);
			if ($file_type == 0) {
				$app->setError("file $client_side_file_name is not image");
				return false;
			}
		}

		// create folder(s) if not exist(s)
		if (!file_exists($this->path)) {
			createDir($this->path);
		}

		$suffix='_'.$key;
		$file_name=$this->path.$this->id.$suffix;
		imageResize($tmp_name, $file_name, $params['width'], $params['height']);
		return true;
	}

	/**
	* @return void
	* @param int $key Thumbnail type (1, 2, 3 etc.)
	* @desc Create specified thumbnail for image.
	*/
	function createThumbnail($key) {
		$params = $this->image['images'][$key];
		$original_file = $this->path.$this->id.'_0';

		if ($key==0) {
			// create image default thumbnail
			list($original_thumb_w,$original_thumb_h) = split('x',ORIGINAL_THUMBNAIL_SIZE);
			$thumbnail_file = $this->path.$this->id.'_t';
			if (file_exists($original_file)) {
				imageResize($original_file, $thumbnail_file, $original_thumb_w, $original_thumb_h);
			}
		} else {
			// create image thumbnails
			$thumbnail_file = $this->path.$this->id.'_'.$key;
			if (file_exists($original_file)) {
				imageResize($original_file, $thumbnail_file, $params['width'], $params['height']);
			}
		}


	}


}



?>