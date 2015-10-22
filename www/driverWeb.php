<?php
require_once 'driver.php';

class driverWeb extends driver {
	protected $requesterAddress;
	protected $htmlOut = [];
	
    public function __construct () {
        parent::__construct();
    }

    public function driver_process($configFile) {
		//$j = '{test{1{2{3}}}}';
		//$j = "{\"entries\":[{\"Src\":\"KOTV-SYN\",\"startOffset\":\"000000\",\"endOffset\":\"003200\",\"Name\":\"Dr. Phil\",\"Start\":\"230000\",\"End\":\"233000\",\"Nielsen\":\"S,0000131203,11500\"}]}";
// {"Src":"KOTV-SYN","startOffset":"003200",endOffset":"010200","Name":"Dr. Phil","Start":"233000","End":"000000","Nielsen":"S,0000131203,11530"},
// {"startOffset":"010200","endOffset":"013200","Name":"Local News at 11","Start":"000000","End":"003000"},
// {"Src":"CBS","startOffset":"013200",endOffset":"020000","Name":"CBS Late News","Start":"003000","End":"010000","Nielsen":"N,909002,11830"}]}
		
		//$this->dump_json_string($j);
		
		if (isset ($configFile)) {
			$pi = pathinfo($configFile); // dirname, basename, extension (if any), and filename
			$ext = $pi['extension'];
			$this->protectedFilePath = $pi['dirname'];
		}
		if (isset($ext) && $ext === 'xml') { // configFile is full path and file name
			$f = $pi['basename'];
		} else {
			$text = $this->getInputParam ('c', false);
			if (isset($text) && strlen(trim($text)) > 0) {
				if (!isset($configFile) || strlen($configFile) < 1) {
					$configFile = '../app/'; // path to protected files
				}
				if (!$this->endsWith ($configFile, '/')) {
					$configFile .= '/';
				}
				$configFile = realpath($configFile . $text . '.xml');
				$f = $text . '.xml';
			} else {
				$this->writeLog ('config file not supplied.');
				$this->closeLog ();
				header("HTTP/1.1 400 Failure: config file not supplied.");
				exit();
			}
		}
       	try {
            if (!file_exists($configFile)){
				$this->writeLog ('config file(', $configFile, ') not found...');
				$this->closeLog ();
				header("HTTP/1.1 400 Failure: could not find config file \'$f\'.");
				exit();
            }
			$rc = $this->driver_openConfig ($configFile);
			if (!$rc) {
				return false;
			}
			parent::driver_process ();
        } catch (Exception $ex) {
			$this->writeLog ($ex->getMessage());
			$this->closeLog ();
			header("HTTP/1.1 400 Failure: error running process.");
			exit();
        }
		$this->closeLog ();
    }

	public function getInputParam ($name, $bRequired, $default='') {
	 $strValue = filter_input(INPUT_GET,$name);    
	 if(!isset($strValue)) {
	  if ($bRequired) {
	   return false;
	  }
	  return $default;
	 } 
	 if(strlen(($strTrimmed = trim($strValue))) < 1) {
	  if ($bRequired) {
	   return false;
	  }
	  return $default;
	 } 
	 return $strTrimmed;
	}
	
	public function getRequesterAddress () {
		if (!isset ($this->requesterAddress)) {
			$this->requesterAddress = $_SERVER['REMOTE_ADDR'];
			$f = $_SERVER['HTTP_X_FORWARDED_FOR'];
			if (isset($f)) {
				$this->requesterAddress .= '-' . $f;
			}
		}
		return $this->requesterAddress;
	}
	
	public function getMessagePrefix () {
		return $this->getRequesterAddress . ' ';
	}
	
	// ---------- response ---------------------
	public function outputResponse () {
		if (!header_sent) {
			header('HTTP/1.1 200 OK');
		}
		echo implode ($this->htmlOut); 
	}
	public $responseStartString = "<!doctype html><html lang=\"en\"><head></head><body>";
	public $responseEndString = "</body></html>";
	public function responseStart () {
		$this->htmlOut [] = $responseStartString;
	}		
	public function responseEnd () {
		$this->htmlOut [] = $responseEndString;
	}		
	public function responseAdd ($html) {
		$this->htmlOut [] = $html;
	}
	public function dump_json_string($j)  {
			echo ($responseStartString);

			$lb1 = substr_count ($j, '{');	echo ('{ ' . $lb1.'<br>');
			$rb1 = substr_count ($j, '}');	echo ('} ' . $rb1.'<br>');
			$lb2 = substr_count ($j, '[');	echo ('[ ' . $lb2.'<br>');
			$rb2 = substr_count ($j, ']');	echo ('] ' . $rb2.'<br>');
			$col = substr_count ($j, ':');	echo (': ' . $col.'<br>');
			$com = substr_count ($j, ',');	echo (', ' . $com.'<br>');
			$quot= substr_count ($j, '"');	echo ('" ' . $quot.'<br>');
			$apos= substr_count ($j, '\''); echo ('\' '. $apos.'<br>');
			
			mb_internal_encoding( 'UTF-8'); 
			mb_regex_encoding( 'UTF-8');  

			$depth = 0;
			$inQuot = false;
			$hasText = false;
			$len = mb_strlen ($j); 
			for( $i=0; $i<$len; $i++) { 
				$ch = mb_substr ($j, $i, 1);
				switch ($ch) {
					case '{':	if ($hasText) { echo '<br>'; $hasText=false;} echo (str_repeat ('&nbsp;', $depth) . $ch . '<br>'); $depth++;	break;
					case '}':	if ($hasText) { echo '<br>'; $hasText=false;} $depth--; echo (str_repeat ('&nbsp;', $depth) . $ch . '<br>');	break;
					case '[':	if ($hasText) { echo '<br>'; $hasText=false;} echo (str_repeat ('&nbsp;', $depth) . $ch . '<br>'); $depth++;	break;
					case ']':	if ($hasText) { echo '<br>'; $hasText=false;} $depth--; echo (str_repeat ('&nbsp;', $depth) . $ch . '<br>');	break;
					case ':':	if (!$inQuot) { echo (str_repeat ('&nbsp;', $depth) . $ch . '<br>'); } else { echo ( $ch); }	break;
					case ',':	if (!$inQuot) { echo (str_repeat ('&nbsp;', $depth) . $ch . '<br>'); } else { echo ( $ch); }	break;
					case '"':	echo ($ch); if ($inQuot) {$inQuot=false;} else {$inQuot=true;}	break;
					case '\'':	if (!$inQuot) { echo (str_repeat ('&nbsp;', $depth) . $ch . '<br>'); }	break;
					default: if ($hasText) { echo ($ch); } else { if ($inQuot) { echo ($ch); } else { echo (str_repeat ('&nbsp;', $depth) . $ch); } $hasText = true; } break;
				}
			} 
			
			//mixed strpos ( string $haystack , mixed $needle [, int $offset = 0 ] )
/*
			$a1 = [];
			$tok = strtok($j, "{}");
			while ($tok !== false) {
				$a1 [] = $tok;
				$tok = strtok("{}");
			}

			//$a1 = explode('{',$j);
			$x  = 0;
			$xf = 0;
			$xr = count($a1)-1;
			$af = [];
			$ar = [];
			$af [] = str_repeat ('&nbsp;', $x) . '{';
			$ar [] = str_repeat ('&nbsp;', $x) . '}';
			$x++;
			while ($xf < $xr) {
				$af [] = str_repeat ('&nbsp;', $x) . $a1[$xf];
				$ar [] = str_repeat ('&nbsp;', $x) . $a1[$xr];
				$x++;
				$xf++;
				$xr--;	
				$af [] = str_repeat ('&nbsp;', $x) . '{';
				$ar [] = str_repeat ('&nbsp;', $x) . '}';
				$x++;
			}
			if ($xf === $xr) {
				$af [] = str_repeat ('&nbsp;', $x) . $a1[$xf];
			}

			$this->responseAdd (', ' . $col.'<br>');
			for ($i=0; $i<count($af); $i++){
				$this->responseAdd ($af[$i] .'<br>');			
			}
			for ($i=count($ar)-1; $i>0; $i--){
				$this->responseAdd ($ar[$i] .'<br>');			
			}
 */
			echo ($responseEndString);
			if (!header_sent) {
				header('HTTP/1.1 200 OK');
			}
	}
}
