<?php
class dataObject {
    function resetObject() {
        foreach ($this as $key => $value) {
            $this->$key = null;  //set to null instead of unsetting
        }
    }
}
class customElement {
	public	$type;			// 'meta'
	public	$name;			// 'Media'
	public	$attrFormat;	// '%3$s'
	public	$valueFormat;	// '<![CDATA[%1$s %2$s]]>'
	public function __construct($type, $name, $attrFormat, $valueFormat) {
		$this->type			= $type;
		$this->name			= $name;
		$this->attrFormat	= $attrFormat;
		$this->valueFormat	= $valueFormat;
	}
}

class driver {
    public $configXML;
    public $configParams;
    public $basePath = '';
    public $handlerPath = '';
    public $projectPath = '';
    public $unique = 0;
    public $tempFilename;
    public $inHandlers = array();
    public $outHandlers = array();
    public $dataObj; // to pass data from input handlers to output handlers
	public $tempH = null;
	public $oneAndDone = 'N';
	public $dtzUTC;
	
    public function __construct () {
		$this->dataObj = new dataObject();
		$this->dtzUTC = new DateTimeZone('UTC');
    }

    public function driver_process() {

		$rc = $this->driver_startHandlers ();
		if (!$rc) {
			return false;
		}
		$rc = $this->driver_runHandlers ();
		if (!$rc) {
			return false;
		}
		$rc = $this->driver_endHandlers ();
		if (!$rc) {
			return false;
		}
		$rc = $this->driver_close ();
		if (!$rc) {
			return false;
		}
		return true;
	}
    public function driver_openConfig($configFile) {
        try {
			try {
				$this->configXML = new DOMDocument ();
				$this->configXML->Load ($configFile, LIBXML_NOENT);
			} catch (Exception $ex) {
				$this->writeLog ('xml file(', $configFile, ') load failed...');
				$this->writeLog ($ex->getMessage());
				unset ($this->configXML);
				return false;
				//closeLog ();
				//exit();
			}
			$this->configParams = $this->configXML->getElementsByTagName ('parameters')->item(0);
			           
			$this->tempFilename = $this->getTempFileName ();
        } catch (Exception $ex) {
			$this->writeLog ($ex->getMessage());
 			unset ($this->configXML);
			return false;
        }
		return true;
	}
    public function driver_startHandlers () {
        try {
			$rc = $this->startHandlers ();
        } catch (Exception $ex) {
			$this->writeLog ($ex->getMessage());
			return false;
        }
		return true;
	}
    public function driver_runHandlers () {
		$rc = true;
        try {
				while ($rc) {
				try {
					//support multiple in/out handlers
					$rc = false;
					foreach($this->inHandlers as $inHandler){
						$rci = $inHandler->IH_process ();
						if (!$rci) {
							$rc = false;
							break;							
						} else {
							$rc = true;
						}
					}
					if (!$rc) {
						break;
					}
					$rc = false;
					foreach($this->outHandlers as $outHandler){
						$rco = $outHandler->OH_process ();
						if (!$rco) {
							$rc = false;
							break;							
						} else {
							$rc = true;
						}
					}	
					if ($rc && $this->oneAndDone === 'Y') {
						$rc = false;
					}
				} catch (Exception $ex) {
					$this->writeLog ($ex->getMessage());
				}
			}
        } catch (Exception $ex) {
			$this->writeLog ($ex->getMessage());
			return false;
        }
		return true;
	}
    public function driver_endHandlers () {
		try {
			foreach($this->inHandlers as $inHandler){
				$inHandler->IH_exit ();
			}
			foreach($this->outHandlers as $outHandler){
				$outHandler->OH_exit ();
			}
        } catch (Exception $ex) {
			$this->writeLog ($ex->getMessage());
        }
		return true;
	}
    public function driver_close () {
		try {
			$this->configParams = null;
			$this->configXML = null;
			$this->closeLog ();
        } catch (Exception $ex) {
			$this->writeLog ($ex->getMessage());
			return false;
        }
		return true;
	}
	public function startHandlers(){
		$handlerFileName = '';
		$elem = $this->configXML->getElementsByTagName ('handlerFiles');
        $this->handlerPath = $elem->item(0)->getAttribute('path');
        $this->handlerPath = $this->fixPathName($this->handlerPath);
		
		// get base classes for in and out handlers
		$elemB = $this->configXML->getElementsByTagName ('inputHandlerBase');
        $handlerBase = $elemB->item(0)->nodeValue;
        $handlerFileName = $this->handlerPath . $handlerBase;             
        require $handlerFileName;
		$elemB = $this->configXML->getElementsByTagName ('outputHandlerBase');
        $handlerBase = $elemB->item(0)->nodeValue;
        $handlerFileName = $this->handlerPath . $handlerBase;             
        require $handlerFileName;
		
		try {
        	$entries = $elem->item(0)->getElementsByTagName('inputHandler');
        	foreach($entries as $entry){
        	    $handlerFileName = $this->handlerPath . $entry->nodeValue;             
        	    require $handlerFileName;
            	$fileClass = pathinfo($handlerFileName,PATHINFO_FILENAME);       
            	$inHandler = new $fileClass; 
            	$rc = $inHandler->IH_init($this);
				if (!$rc) {
					$inHandler->IH_exit();
					return false;
				}
            	$this->inHandlers[] = $inHandler; 
        	}
        	$entries = $elem->item(0)->getElementsByTagName('outputHandler');
        	foreach($entries as $entry){
        	    $handlerFileName = $this->handlerPath . $entry->nodeValue;             
        	    require $handlerFileName;
            	$fileClass = pathinfo($handlerFileName,PATHINFO_FILENAME);       
            	$outHandler = new $fileClass; 
            	$rc = $outHandler->OH_init($this);
				if (!$rc) {
					$outHandler->OH_exit();
					return false;
				}
            	$this->outHandlers [] = $outHandler; 
         	}
		} catch (Exception $e) {
			$this->writeLog ('exception starting handler:', $handlerFileName);
			exit();
		}
		return true;
    }
	public function makeTimestamp () {
        try {
			$dt = new DateTime('now', $this->dtzUTC);
        } catch (Exception $ex) {
			return false;
        }
		$a = $dt->format('YmdHis');
		$b = sprintf('%03d',0); 
		return $a . $b; 
	}

	public function makeSQLTimestamp ($dt=null) {
        try {
			if ($dt == null) {
				$dt = new DateTime('now', $this->dtzUTC);
			}
        } catch (Exception $ex) {
			return false;
        }
		$a = $dt->format('Y/m/d H:i:s');
		return $a; 
	}

	public function writeLog ($text) {
		if (($this->tempH == null) && ($text != null)) {
			$tempFileName = $this->getLogFileName();
			$this->tempH = fopen ($tempFileName, 'w');
		}
		if ($this->tempH != null) {
			fwrite ($this->tempH, $this->makeTimestamp () . $this->getMessagePrefix () . $text."\n");
			fflush ($this->tempH);
		}
	}
	protected function closeLog () {
		if ($this->tempH != null) {
			fclose ($this->tempH);
			$this->tempH = null;
		}
	}

	function formatSQLErrors ($title,$errors) {
		if ($errors == null) {
			return;
		}
		$this->writeLog ($title);
		foreach ($errors as $error) {
			  $this->writeLog ("SQLSTATE: ".$error['SQLSTATE']);
			  $this->writeLog ("Code: ".$error['code']);
			  $this->writeLog ("Message: ".$error['message']);
		}
	}
	function cleanupText ($text) {
		$out = '';
		if ($text == null) {
			return $out;
		}
		// replace &quot; &amp; &lt; &gt; &apos;	
		$pos = 0;
		$done = false;
		while (!$done) {
			if (false === (($pos = strpos ($text,'&', (int) $pos)))) {
				break; // no more & found
			} else {
				$len = strlen ($text);
				$after = $len-$pos;
				if ($after >= 6) {
					$t = substr ($text,$pos,6);
					if ($t === '&quot;') {
						$text = substr_replace($text, '"', $pos, 6);
						continue;
					} else if ($t === '&apos;') {
						$text = substr_replace($text, '\'', $pos, 6);
						continue;
					} else if ($t === '&nbsp;') {
						$text = substr_replace($text, ' ', $pos, 6);
						continue;
					}
				}
				if ($after >= 5) {
					$t = substr ($text,$pos,5);
					if ($t === '&amp;') {
						$text = substr_replace($text, '&', $pos, 5);
						$pos++;
						continue;
					}
				}
				if ($after >= 4) {
					$t = substr ($text,$pos,4);
					if ($t === '&gt;') {
						$text = substr_replace($text, '>', $pos, 4);
						continue;
					} else if ($t === '&lt;') {
						$text = substr_replace($text, '<', $pos, 4);
						continue;
					}
				}
				$pos++;
			}
		}
		if (ctype_print($text)) {
			return $text;
		}
		return $this->cleanupText2 ($text);
	}

	function cleanupText2 ($text, $removeCRLF=true) {
		$out = '';
		if (!isset($text) || $text == null) {
			return $out;
		}
		for ($i=0; $i<strlen($text); $i++) {
			$ch = $text[$i];
			$v = ord($ch);
			if ($v > 31 && $v < 127) {
				$out .= $ch;
				continue;
			}
			if ($v == 226) { // 0xe2
				$ch = $text[$i+1];
				if (ord($ch) == 128) { // 0x80 looks like a Word special character
					$ch = $text[$i+2];
					switch (ord($ch)) {
						case 147: { $out .= '-';  break; } // ndash
						case 148: { $out .= '-';  break; } // mdash
						case 152: { $out .= '\''; break; } // left single  quote 
						case 153: { $out .= '\''; break; } // right single quote
						case 156: { $out .= '"';  break; } // left double  quote
						case 157: { $out .= '"';  break; } // right double quote
					}
					$i++;
					$i++;
				} else if (ord($ch) == 150) { // 0x96 looks like a Word special character
					$ch = $text[$i+2];
					switch (ord($ch)) {
						case 160: { $out .= '.';  break; } // black square
					}
					$i++;
					$i++;
				}
			} else if ($v < 32) {
				if ($removeCRLF || ($v !== 10 && $v !== 13)) {
					$out .= ' ';
				} else {
					$out .= $ch;
				}
				continue;
			}
			// drop the character
		}
		return $out;
	}

	function fixPathName ($path) {
		$path = str_replace ('%basePath%', $this->basePath, $path);
		$path = str_replace ('%projectPath%', $this->projectPath, $path);
		return $path;
	}

    public function getLogFileName() {
        //<errorFile path="C:\errors\" name="%unique%_%datetime%_%type%.txt"/>
		$elem = $this->configXML->getElementsByTagName ('logFile');
		return $this->getFileName($elem);
	}
    public function getTempFileName() {
        //<errorFile path="C:\errors\" name="%unique%_%datetime%_%type%.txt"/>
		$elem = $this->configXML->getElementsByTagName ('tempFiles');
		return $this->getFileName($elem);
	}
	public function getOutputFileName() {
		// <outfilename path="D:\NDS\Projects\DriverPhp\FeedReader1\output\">%datetime%_%type%_%unique%.txt</outfilename>
		$elem = $this->configParams->getElementsByTagName ('outfilename');
		return $this->getFileName($elem);
	}
	public function getFileName($elem) {
		// <outfilename path="D:\NDS\Projects\DriverPhp\FeedReader1\output\">%datetime%_%type%_%unique%.txt</outfilename>
		$path = $elem->item(0)->getAttribute('path');
        $path = $this->fixPathName($path);
		$name = $elem->item(0)->getAttribute('name');
		if (!isset($name)) {
			$name = $elem->item(0)->nodeValue;
		}
		$ts = $this->makeTimestamp (); //yyyymmddhhmmssttt	
		$name = str_replace ('%datetime%', $ts, $name);         // full datetime
		$name = str_replace ('%yyyy%', substr($ts,0,4), $name); // year
		$name = str_replace ('%mm%', substr($ts,4,2), $name);   // month
		$name = str_replace ('%dd%', substr($ts,6,2), $name);   // day
		$name = str_replace ('%hh%', substr($ts,8,2), $name);   // hour
		$u = $this->unique++;
		$u = sprintf('%03d',$u); 
		$name = str_replace ('%unique%', $u, $name);
		return $path . $name;
	}

	public function dbOpen ($db_id) {
		$dbParams	= $this->configXML->getElementsByTagName ($db_id)->item(0);
		$db_server	= $dbParams->getElementsByTagName ('dbserver')->item(0)->nodeValue;
		$db_database= $dbParams->getElementsByTagName ('dbname')->item(0)->nodeValue;
		$db_user	= $dbParams->getElementsByTagName ('dblogin')->item(0)->nodeValue;
		$db_passwd	= $dbParams->getElementsByTagName ('dbpassword')->item(0)->nodeValue;
		$connectionOptions = array(
			'Database'=>$db_database
			,'UID'=>$db_user
			,'PWD'=>$db_passwd);
		return sqlsrv_connect ($db_server, $connectionOptions);
	}

	public function dbClose ($db_conn) {
		if ($db_conn != null) {
			sqlsrv_close ($db_conn);				
		}
	}

	public function dbStmtClose ($db_stmt) {
		if ($db_stmt != null) {
			sqlsrv_free_stmt ($db_stmt);				
		}
	}

	public function getMessagePrefix () {
		return '';
	}
		
	public function beginsWith( $str, $sub ) {
	   return ( substr( $str, 0, strlen( $sub ) ) === $sub );
	}
	public function endsWith( $str, $sub ) {
	   return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
	}

	//http://sourceforge.net/projects/phunction/) uses the following function to generate valid version 4 UUIDs:
	public function GUID() {
		if (function_exists('com_create_guid') === true) {
		    return trim(com_create_guid(), '{}');
		}
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
}
