<?php
/*
$Header: /cvs_repository/lisk/engine/init/class/tpl.class.php,v 1.2 2005/02/14 11:25:50 andrew Exp $

CLASS tpl
v 3.0
*/

class IT_Error {
	function IT_Error($msg, $file = __FILE__, $line = __LINE__) {

		//echo $msg.BR;

	} // end func IT_Error
} // end class IT_Error

/*********************************************************/

class IntegratedTemplate {

	/**
    * Contains the error objects
    * @var      array
    * @access   public
    * @see      halt(), $printError, $haltOnError
    */
	var $err = array();

	/**
    * Clear cache on get()? 
    * @var      boolean
    */ 
	var $clearCache = false;

	/**
    * First character of a variable placeholder ( _{_VARIABLE} ).
    * @var      string
    * @access   public
    * @see      $closingDelimiter, $blocknameRegExp, $variablenameRegExp
    */
	var $openingDelimiter = "{";

	/**
    * Last character of a variable placeholder ( {VARIABLE_}_ ).
    * @var      string
    * @access   public
    * @see      $openingDelimiter, $blocknameRegExp, $variablenameRegExp
    */
	var $closingDelimiter     = "}";

	/**
    * RegExp matching a block in the template. 
    * Per default "sm" is used as the regexp modifier, "i" is missing.
    * That means a case sensitive search is done.
    * @var      string
    * @access   public
    * @see      $variablenameRegExp, $openingDelimiter, $closingDelimiter
    */
	var $blocknameRegExp    = "[0-9A-Za-z_-]+";

	/**
    * RegExp matching a variable placeholder in the template.
    * Per default "sm" is used as the regexp modifier, "i" is missing.
    * That means a case sensitive search is done.
    * @var      string    
    * @access   public
    * @see      $blocknameRegExp, $openingDelimiter, $closingDelimiter
    */
	var $variablenameRegExp    = "[0-9A-Za-z_-]+";

	/**
    * RegExp used to find variable placeholder, filled by the constructor.
    * @var      string    Looks somewhat like @(delimiter varname delimiter)@
    * @access   public
    * @see      IntegratedTemplate()
    */
	var $variablesRegExp = "";

	/**
    * RegExp used to strip unused variable placeholder.
    * @brother  $variablesRegExp
    */
	var $removeVariablesRegExp = "";

	/**
    * Controls the handling of unknown variables, default is remove.
    * @var      boolean
    * @access   public
    */
	var $removeUnknownVariables = true;

	/**
    * Controls the handling of empty blocks, default is remove.
    * @var      boolean
    * @access   public
    */
	var $removeEmptyBlocks = true;

	/**
    * RegExp used to find blocks an their content, filled by the constructor.
    * @var      string
    * @see      IntegratedTemplate()
    */
	var $blockRegExp = "";

	/**
    * Name of the current block.
    * @var      string
    */
	var $currentBlock = "__global__";

	/**
    * Content of the template.
    * @var      string
    */    
	var $template = "";

	/**
    * Array of all blocks and their content.
    * 
    * @var      array
    * @see      findBlocks()
    */    
	var $blocklist = array();

	/**
    * Array with the parsed content of a block.
    *
    * @var      array
    */
	var $blockdata = array();

	/**
    * Array of variables in a block.
    * @var      array
    */
	var $blockvariables = array();

	/**
    * Array of inner blocks of a block.
    * @var      array
    */    
	var $blockinner         = array();


	var $touchedBlocks = array();

	var $variableCache = array();

	var $clearCacheOnParse = false;

	var $fileRoot = "";

	var $flagBlocktrouble = false;

	var $flagGlobalParsed = false;

	var $flagCacheTemplatefile = true;

	var $lastTemplatefile = "";

	/**
	* @return void
	* @param string $root
	* @desc constructor
	*/
	function IntegratedTemplate($root = "") {

		$this->variablesRegExp = "@" . $this->openingDelimiter . "(" . $this->variablenameRegExp . ")" . $this->closingDelimiter . "@sm";
		$this->removeVariablesRegExp = "@" . $this->openingDelimiter . "\s*(" . $this->variablenameRegExp . ")\s*" . $this->closingDelimiter . "@sm";

		$this->blockRegExp = '@<!--\s+BEGIN\s+(' . $this->blocknameRegExp . ')\s+-->(.*)<!--\s+END\s+\1\s+-->@sm';

		$this->setRoot($root);
	} // end constructor

	/**
	* @return void
	* @param string $block
	* @desc Print a certain block with all replacements done.
	* @see get()
	*/
	function show($block = "__global__") {
		print $this->get($block);
	} // end func show

	/**
    * Returns a block with all replacements done.
    * 
    * @param    string     name of the block
    * @return   string
    * @throws   IT_Error
    * @access   public
    * @see      show()
    */
	function get($block = "__global__") {
		if ("__global__" == $block && !$this->flagGlobalParsed)
		$this->parse("__global__");

		if (!isset($this->blocklist[$block])) {
			new IT_Error("The block '$block' was not found in the template.", __FILE__, __LINE__);
			return "";
		}

		if ($this->clearCache) {

			$data = (isset($this->blockdata[$block])) ? $this->blockdata[$block] : "";
			unset($this->blockdata[$block]);
			return $data;

		} else {

			return (isset($this->blockdata[$block])) ? $this->blockdata[$block] : "";

		}

	} // end func get()

	/**
    * Parses the given block.
    *    
    * @param    string    name of the block to be parsed
    * @access   public
    * @see      parseCurrentBlock()
    * @throws   IT_Error
    */
	function parse($block = "__global__", $flag_recursion = false, $touched=false) {

		if (!isset($this->blocklist[$block])) {
			return new IT_Error("The block '$block' was not found in the template.", __FILE__, __LINE__);
			return false;
		}

		if ("__global__" == $block)
		$this->flagGlobalParsed = true;

		$regs = array();
		$values = array();

		if ($this->clearCacheOnParse) {

			foreach ($this->variableCache as $name => $value) {
				$regs[] = "@" . $this->openingDelimiter . $name . $this->closingDelimiter . "@";
				$values[] = addcslashes($value,'\\$');
				//                $values[] = $value;
			}
			$this->variableCache = array();

		} else {

			foreach ($this->blockvariables[$block] as $allowedvar => $v) {

				if (isset($this->variableCache[$allowedvar])) {
					$regs[]   = "@".$this->openingDelimiter . $allowedvar . $this->closingDelimiter . "@";

					$values[] =addcslashes($this->variableCache[$allowedvar],'\\$');
					//                   $values[] = $this->variableCache[$allowedvar];
					unset($this->variableCache[$allowedvar]);
				}

			}

		}

		$outer = (0 == count($regs)) ? $this->blocklist[$block] : preg_replace($regs, $values, $this->blocklist[$block]);
		$empty = (0 == count($values)) ? true : false;

		if (isset($this->blockinner[$block])) {

			foreach ($this->blockinner[$block] as $k => $innerblock) {

				$this->parse($innerblock, true);
				if ("" != $this->blockdata[$innerblock])
				$empty = false;

				$placeholder = $this->openingDelimiter . "__" . $innerblock . "__" . $this->closingDelimiter;
				$outer = str_replace($placeholder, $this->blockdata[$innerblock], $outer);
				$this->blockdata[$innerblock] = "";
			}

		}

		if ($this->removeUnknownVariables)
		$outer = preg_replace($this->removeVariablesRegExp, "", $outer);

		if ($empty) {

			if (!$this->removeEmptyBlocks) {

				$this->blockdata[$block ].= $outer;

			} else {

				// if block is touched - it parsed anywhere
				// --------------------------------------------------------
				if ($touched)
				$this->blockdata[$block] .= $outer;
				// --------------------------------------------------------

			}

		} else {

			$this->blockdata[$block] .= $outer;

		}

		// added variables clean after tpl parsing
		// next(external) tpl parsing is going without OLD variables
		// --------------------------------------------------------
		if ($flag_recursion==false) {
			$this->variableCache=array();
		}
		// --------------------------------------------------------

		return $empty;
	} // end func parse

	/**
    * Parses the current block
    * @see      parse(), setCurrentBlock(), $currentBlock
    * @access   public
    */
	function parseCurrentBlock() {
		return $this->parse($this->currentBlock);
	} // end func parseCurrentBlock

	/**
    * Sets a variable value.
    * 
    * The function can be used eighter like setVariable( "varname", "value")
    * or with one array $variables["varname"] = "value" given setVariable($variables)
    * quite like phplib templates set_var().
    * 
    * @param    mixed     string with the variable name or an array %variables["varname"] = "value"
    * @param    string    value of the variable or empty if $variable is an array.
    * @param    string    prefix for variable names
    * @access   public
    */    
	function setVariable($variable, $value = "") {

		if (is_array($variable)) {

			$this->variableCache = array_merge($this->variableCache, $variable);

		} else {

			$this->variableCache[$variable] = $value;

		}

	} // end func setVariable

	/**
    * Sets the name of the current block that is the block where variables are added.
    *
    * @param    string      name of the block 
    * @return   boolean     false on failure, otherwise true
    * @throws   IT_Error
    * @access   public
    */
	function setCurrentBlock($block = "__global__") {

		if (!isset($this->blocklist[$block])) {
			return new IT_Error("Can't find the block '$block' in the template.", __FILE__, __LINE__);
		} else {
			$this->currentBlock = $block;
			return true;
		}

	} // end func setCurrentBlock

	/**
    * Preserves an empty block even if removeEmptyBlocks is true.
    *
    * @param    string      name of the block
    * @return   boolean     false on false, otherwise true
    * @throws   IT_Error    
    * @access   public
    * @see      $removeEmptyBlocks
    */
	function touchBlock($block) {

		if (!isset($this->blocklist[$block]))
		return new IT_Error("Can't find the block '$block' in the template.", __FILE__, __LINE__);

		//        $this->touchedBlocks[$block] = true;
		//
		$this->parse($block,false,true);


		return true;
	} // end func touchBlock

	/**
    * Clears all datafields of the object and rebuild the internal blocklist
    * 
    * LoadTemplatefile() and setTemplate() automatically call this function 
    * when a new template is given. Don't use this function 
    * unless you know what you're doing.
    *
    * @access   public
    * @see      free()
    */
	function init() {

		$this->free();
		$this->findBlocks($this->template);
		$this->buildBlockvariablelist();

	} // end func init

	/**
    * Clears all datafields of the object.
    * 
    * Don't use this function unless you know what you're doing.
    *
    * @access   public
    * @see      init()
    */
	function free() {

		$this->err = array();

		$this->currentBlock = "__global__";

		$this->variableCache    = array();
		$this->blocklookup      = array();
		$this->touchedBlocks    = array();

		$this->flagBlocktrouble = false;
		$this->flagGlobalParsed = false;

	} // end func free

	/**
    * Sets the template.
    *  
    * You can eighter load a template file from disk with LoadTemplatefile() or set the
    * template manually using this function.
    * 
    * @param        string      template content
    * @param        boolean     remove unknown/unused variables?
    * @param        boolean     remove empty blocks?
    * @see          LoadTemplatefile(), $template
    * @access       public
    */
	function setTemplate($template, $removeUnknownVariables = true, $removeEmptyBlocks = true) {

		$this->removeUnknownVariables = $removeUnknownVariables;
		$this->removeEmptyBlocks = $removeEmptyBlocks;

		if ("" == $template && $this->flagCacheTemplatefile) {

			$this->variableCache = array();
			$this->blockdata = array();
			$this->touchedBlocks = array();
			$this->currentBlock = "__global__";

		} else {

			$this->template = '<!-- BEGIN __global__ -->' . $template . '<!-- END __global__ -->';
			$this->init();

		}

		if ($this->flagBlocktrouble)
		return false;

		return true;
	} // end func setTemplate

	/**
    * Reads a template file from the disk.
    *
    * @param    string      name of the template file
    * @param    bool        how to handle unknown variables.
    * @param    bool        how to handle empty blocks. 
    * @access   public
    * @return   boolean    false on failure, otherwise true
    * @see      $template, setTemplate(), $removeUnknownVariables, $removeEmptyBlocks
    */
	function loadTemplatefile($filename, $removeUnknownVariables = true, $removeEmptyBlocks = true) {
		$template = "";
		if (!$this->flagCacheTemplatefile || $this->lastTemplatefile != $filename)
		$template = $this->getfile($filename);		
		$this->lastTemplatefile = $filename;

		return $this->setTemplate($template, $removeUnknownVariables, $removeEmptyBlocks, true);
	} // end func LoadTemplatefile

	/**
    * Sets the file root. The file root gets prefixed to all filenames passed to the object.
    * 
    * Make sure that you override this function when using the class
    * on windows.
    * 
    * @param    string
    * @see      IntegratedTemplate()
    * @access   public
    */
	function setRoot($root) {

		if ("" != $root && "/" != substr($root, -1))
		$root .= "/";

		$this->fileRoot = $root;

	} // end func setRoot

	/**
    * Build a list of all variables within of a block
    */    
	function buildBlockvariablelist() {

		foreach ($this->blocklist as $name => $content) {
			preg_match_all( $this->variablesRegExp, $content, $regs );

			if (0 != count($regs[1])) {

				foreach ($regs[1] as $k => $var)
				$this->blockvariables[$name][$var] = true;

			} else {

				$this->blockvariables[$name] = array();

			}

		}

	} // end func buildBlockvariablelist

	/**
    * Returns a list of all 
    */
	function getGlobalvariables() {

		$regs   = array();
		$values = array();

		foreach ($this->blockvariables["__global__"] as $allowedvar => $v) {

			if (isset($this->variableCache[$allowedvar])) {
				$regs[]   = "@" . $this->openingDelimiter . $allowedvar . $this->closingDelimiter."@";
				$values[] = $this->variableCache[$allowedvar];
				unset($this->variableCache[$allowedvar]);
			}

		}

		return array($regs, $values);
	} // end func getGlobalvariables

	/**
    * Recusively builds a list of all blocks within the template.
    *
    * @param    string    string that gets scanned
    * @see      $blocklist
    */    
	function findBlocks($string) {

		$blocklist = array();

		if (preg_match_all($this->blockRegExp, $string, $regs, PREG_SET_ORDER)) {

			foreach ($regs as $k => $match) {

				$blockname         = $match[1];
				$blockcontent = $match[2];

				if (isset($this->blocklist[$blockname])) {
					new IT_Error("The name of a block must be unique within a template. Found '$blockname' twice. Unpredictable results may appear.", __FILE__, __LINE__);
					$this->flagBlocktrouble = true;
				}

				$this->blocklist[$blockname] = $blockcontent;
				$this->blockdata[$blockname] = "";

				$blocklist[] = $blockname;

				$inner = $this->findBlocks($blockcontent);
				foreach ($inner as $k => $name) {

					$pattern = sprintf('@<!--\s+BEGIN\s+%s\s+-->(.*)<!--\s+END\s+%s\s+-->@sm',
					$name,
					$name
					);

					$this->blocklist[$blockname] = preg_replace(    $pattern,
					$this->openingDelimiter . "__" . $name . "__" . $this->closingDelimiter,
					$this->blocklist[$blockname]
					);
					$this->blockinner[$blockname][] = $name;
					$this->blockparents[$name] = $blockname;

				}

			}

		}

		return $blocklist;
	} // end func findBlocks

	/**
    * Reads a file from disk and returns its content.
    * @param    string    Filename
    * @return   string    Filecontent
    */    
	function getFile($filename) {

		if ("/" == $filename{0} && "/" == substr($this->fileRoot, -1))
		$filename = substr($filename, 1);
		
		$fh = @fopen($filename, "r");
		$content = fread($fh, filesize($filename));
		fclose($fh);

		return $content;
	} // end func getFile

} // end class IntegratedTemplate

class template extends IntegratedTemplate {

	/**
    * Constructor
    * @param string $root
    */
	function template($root) {
		return parent::IntegratedTemplate($root);
	}

	/**
    * Load template file
    * @param string $filename
    * @param bool $removeUnknownVariables
    * @param bool $removeEmptyBlocks
    */
	function loadTemplatefile($filename, $removeUnknownVariables=true, $removeEmptyBlocks=true) {
		$filename = TPL_PATH.$filename.'.'.TPL_EXT;	
		//PRINT $filename;
		if (!file_exists($filename)) {
			GLOBAL $app;
			$app->raiseError("template file <b>$tpl_file</b> is not found");
		}
		return parent::loadTemplatefile($filename, $removeUnknownVariables, $removeEmptyBlocks);
	}

	/**
    * Load template file with name of script executing
    * @param bool $removeUnknownVariables
    * @param bool $removeEmptyBlocks
    * @see loadTemplatefile
    */
	function load($tpl_name=true,$removeUnknownVariables=true, $removeEmptyBlocks=true) {
		if (is_bool($tpl_name)) {
			$removeEmptyBlocks = $removeUnknownVariables;
			$removeUnknownVariables = $tpl_name;
			$tpl_name = substr(basename($_SERVER['PHP_SELF']),0,-4);
		}
		$this->loadTemplatefile($tpl_name,$removeUnknownVariables, $removeEmptyBlocks);
	}

	/**
    * set variable into template block
    * @param mixed $placeholder
    * @param string $variable
    */
	function setVariable($placeholder, $variable='') {
		if (is_array($placeholder)) {
			$hash = array();
			foreach ($placeholder as $key=>$val) {
				$hash[strtoupper($key)] = $val;
			}
			return parent::setVariable($hash);
		} else {
			$placeholder = strtoupper($placeholder);
			return parent::setVariable($placeholder, $variable);
		}
	}

	/**
    * Parse variable into template block
    * @param array $arr
    * @param string $block_name
    * @see setVariable()
    */ 
	function parseVariable($arr,$block_name) {
		if ($this->setCurrentBlock($block_name)) {
			$this->setVariable($arr);
			$this->parseCurrentBlock();
		}
	}

	/**
    * Reads a file from disk and returns its content.
    * @param string $page
    * @return string - parsed template
    */ 
	function show($page='') {
		if (isset($page) && $page!='') {
			echo preg_replace_callback(EVAL_REG_EXP, 'tpl_eval', $page);
		} else {
			parent::show();
		}

		echo $this->makeDebug();
	}
	/*------------------------DEBUG------------------------------*/

	/**
    * Reads a file from disk and returns its content.
    * 
    */ 
	function makeDebug() {

		//set timer OFF for global execute time
		/*$exec_time = -1;
		$try = 0;
		while ($exec_time < 0 && $try++<999) {
		$exec_time = microtime() - $GLOBALS['start_time'];
		}*/

		$GLOBALS['exec_time'] = $exec_time = getmicrotime() - $GLOBALS['start_time'];

		if ((defined('DEBUG'))and(DEBUG == 1)) {
			$this->free();

			$debug_array = array(
			'GET'		=> $_GET,
			'POST'		=> $_POST,
			'SESSION'	=> $_SESSION,
			'COOKIE'	=> $_COOKIE
			);

			$colors = array(
			'GENERAL'	=> '8495BB',
			'GET'		=> 'FFCC66',
			'POST'		=> '00FF66',
			'SESSION'	=> '9999FF',
			'COOKIE'	=> 'CC99FF',
			'SQL'		=> 'FF9999',
			'DATA'		=> 'FCFC00',
			'FILESYS'	=> 'FCFC00',
			'AUTH'		=> 'CCCCCC',
			'EMAIL'		=> 'CCCCCC',
			'DEBUG'		=> 'FFFF66'
			);


			$tpl_name_debug='/'.SYS_ROOT.TPL_PATH.'/global/debug.htm';

			parent::loadTemplatefile($tpl_name_debug, true, true);
			
			// GENERAL
			$name = 'GENERAL';
			$time = 0;
			$this->setCurrentBlock('variable_item');

			$this->setVariable(array(
			'NAME'	=> 'Script name:',
			'VALUE'	=> $_SERVER['PHP_SELF']
			));
			$this->parseCurrentBlock();
			
			//AUTH info
			GLOBAL $app;
			if (is_array($app->user)) {
				$auth_info='id: '.$app->user['id'].BR.'login: '.$app->user['login'];
			} else {
				$auth_info='False';
			}

			$this->setVariable(array(
			'NAME'	=> 'Authorization:',
			'VALUE'	=> $auth_info
			));
			$this->parseCurrentBlock();


			$this->setCurrentBlock('variable_category');
			$this->setVariable(array(
			'NAME'	=> $name,
			'COLOR'	=> $colors[$name]
			));
			$this->parseCurrentBlock();
			$this->setCurrentBlock('variable');
			$this->parseCurrentBlock();

			// END GENERAL


			if (is_array(@$GLOBALS['DEBUG'])) {
				$name = 'DEBUG';
				$time = 0;
				$this->setCurrentBlock('debug_item');
				foreach ($GLOBALS['DEBUG'] as $item) {
					ob_start();
					print_r($item['value']);
					$val_r = ob_get_contents();
					ob_end_clean();
					$this->setVariable(array(
					'NAME'	=> $item['name'],
					'LINE'	=> $item['line'],
					'VALUE'	=> nl2br($val_r)
					));
					$this->parseCurrentBlock();
				}
				$this->setCurrentBlock('debug_category');
				$this->setVariable(array(
				'NAME'	=> $name,
				'COLOR'	=> $colors[$name]
				));
				$this->parseCurrentBlock();
				$this->setCurrentBlock('debug');
				$this->parseCurrentBlock();
			}


			// SQL DEBUG
			if (is_array($GLOBALS['SQLS'])) {
				$name = 'SQL';
				$time = 0;
				foreach ($GLOBALS['SQLS'] as $sql) {
					if ((isset($sql['error']))and($sql['error'] != '')) {
						$this->setCurrentBlock('sql_error');
						$this->setVariable(array(
						'ERROR'	=> $sql['error']
						));
						$this->parseCurrentBlock();
					}
					$this->setCurrentBlock('sql_item');
					$this->setVariable(array(
					'NAME'	=> $sql['sql'],
					'VALUE'	=> $sql['time']
					));
					$this->parseCurrentBlock();
					$time += $sql['time'];
				}
				$this->setVariable(array(
				'NAME'	=> 'TOTAL',
				'VALUE'	=> "<b>$time</b>"
				));
				$this->parseCurrentBlock();
				$this->setCurrentBlock('sql_category');
				$this->setVariable(array(
				'NAME'	=> $name,
				'COLOR'	=> $colors[$name]
				));
				$this->parseCurrentBlock();
				$this->setCurrentBlock('sql');
				$this->parseCurrentBlock();
			}


			// DATA DEBUG
			if (is_array(@$GLOBALS['DATA_DEBUG'])) {
				$name = 'DATA';
				$time = 0;
				foreach ($GLOBALS['DATA_DEBUG'] as $data) {
					$this->setCurrentBlock('data_item');
					$this->setVariable(array(
					'ACTION'	=> $data['action'],
					'PARAMS'	=> $data['params'],
					'REZ'		=> $data['rez']
					));
					$this->parseCurrentBlock();
				}


				$this->setCurrentBlock('data_category');
				$this->setVariable(array(
				'NAME'	=> $name,
				'COLOR'	=> $colors[$name]
				));
				$this->parseCurrentBlock();
				$this->setCurrentBlock('data');
				$this->parseCurrentBlock();
			}

			// FILESYS DEBUG
			if (is_array(@$GLOBALS['FILESYS_DEBUG'])) {
				$name = 'FILESYS';
				$time = 0;
				foreach ($GLOBALS['FILESYS_DEBUG'] as $data) {
					$this->setCurrentBlock('data_item');
					$this->setVariable(array(
					'ACTION'	=> $data['action'],
					'PARAMS'	=> $data['params'],
					'REZ'		=> $data['rez']
					));
					$this->parseCurrentBlock();
				}


				$this->setCurrentBlock('data_category');
				$this->setVariable(array(
				'NAME'	=> $name,
				'COLOR'	=> $colors[$name]
				));
				$this->parseCurrentBlock();
				$this->setCurrentBlock('data');
				$this->parseCurrentBlock();
			}


			// AUTH DEBUG
			if (is_array(@$GLOBALS['AUTH_DEBUG'])) {
				$name = 'AUTH';
				$time = 0;
				foreach ($GLOBALS['AUTH_DEBUG'] as $data) {
					$this->setCurrentBlock('data_item');
					$this->setVariable(array(
					'ACTION'	=> $data['action'],
					'PARAMS'	=> $data['params'],
					'REZ'		=> formatDebug($data['rez'])
					));
					$this->parseCurrentBlock();
				}


				$this->setCurrentBlock('data_category');
				$this->setVariable(array(
				'NAME'	=> $name,
				'COLOR'	=> $colors[$name]
				));
				$this->parseCurrentBlock();
				$this->setCurrentBlock('data');
				$this->parseCurrentBlock();
			}

			// Email debug
			$emails = @$GLOBALS['email_debug'];
			if (isset($emails) && is_array($emails) and (sizeof($emails) > 0) ) {
				$this->setCurrentBlock('variable_item');
				foreach ($emails as $email_name=>$email_info) {
					$this->setVariable(array(
					'NAME'	=> $email_name,
					'VALUE'	=> '<b>recipients: </b>'.$email_info['recipients'].HR.
					'<b>subject: </b>'.$email_info['subject'].HR.
					'<b>header: </b>'.$email_info['header'].HR.
					'<b>body: </b>'.$email_info['body']
					));
					$this->parseCurrentBlock();
				}
				$this->setCurrentBlock('variable_category');
				$this->setVariable(array(
				'NAME'	=> 'Email send',
				'COLOR'	=> $colors['EMAIL']
				));
				$this->parseCurrentBlock();
			}

			$printed = false;
			foreach ($debug_array as $name=>$array) {
				if (is_array($array) and (sizeof($array) > 0) ) {
					$printed = true;
					$this->setCurrentBlock('variable_item');
					foreach ($array as $key=>$val) {
						//don't include previous $_SESSION[old_debug] data
						if($key!='old_debug') {
							ob_start();
							print_r($val);
							$val_r = ob_get_contents();
							ob_end_clean();
							$this->setVariable(array(
							'NAME'	=> $key,
							'VALUE'	=> nl2br($val_r)
							));
							$this->parseCurrentBlock();
						}
					}
					$this->setCurrentBlock('variable_category');
					$this->setVariable(array(
					'NAME'	=> $name,
					'COLOR'	=> $colors[$name]
					));
					$this->parseCurrentBlock();
				}
			}
			if ($printed) {
				$this->setCurrentBlock('variable');
				$this->parseCurrentBlock();
			}

			//get previuos debug data
			$this->setCurrentBlock('old_debug');
			$this->setVariable(array(
			'OLD_DEBUG'	=> @$_SESSION['old_debug']
			));
			$this->parseCurrentBlock();
			//

			$this->setCurrentBlock('win');
			$this->setVariable('EXEC_TIME', $GLOBALS['exec_time']);
			$this->parseCurrentBlock();
			$text = $this->get('win');

			$this->setCurrentBlock('js');

			$search = array('/[\n|\r]*/', "/'/");
			$replace = array('', "&#39;");
			$text_r = preg_replace($search, $replace, $text);

			$this->setVariable(array(
			'TEXT'	=> $text_r
			));
			$this->parseCurrentBlock();

			//set this debug data to $_SESSION[old_debug]
			$output=$this->get('win');

			preg_match("/<dontdelete>(.*)<dontdelete>/ms",$output,$matches);
			$_SESSION['old_debug'] = $matches[1];

			return $this->get('js');
		}
	}
	/*------------------------DEBUG------------------------------*/
}

/* ----------------------------------------------------------------------------------------------------- */

$GLOBALS['tpl'] = $tpl = new template(TPL_PATH);
?>