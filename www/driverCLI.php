<?php
require_once 'driver.php';

class driverCLI extends driver {
    public $configXML;
    public $configParams;
    public $basePath = '';
    public $projectPath = '';
    public $unique = 0;
    public $processType;
    public $tempFilename;
    public $handlerFilePath;
    public $args;
	public $tempH = null;

	/*
command line format:
php C:\test\driver.php C:\test\driver_rss.xml type1 -i1 test1 -o1 test1
args[0] = C:\test\driver.php		this program
args[1] = C:\test\driver_rss.xml	config xml file for this app
args[2] = <basePath>
args[3] = <projectPath>
*/
    public function driverCLI ($args) {
        $this->args = $args;
        parent::__construct();
    }

    public function driver_process() {
        try {
            if(count($this->args) < 2){
                echo 'lacking parameters provided...',"\n";
                $this->writeLog ('lacking parameters provided...');
				$this->closeLog ();
                exit();
            }      
			if(count($this->args) > 2){
				$this->basePath = $this->args[2];
				if ($this->basePath === '-') {
					$this->basePath = '';
				}
			}
			if(count($this->args) > 3){
				$this->projectPath = $this->args[3];
				if ($this->projectPath === '-') {
					$this->projectPath = '';
				}
			}
			// open config file
            $strValue = $this->args[1];
            if (!file_exists($strValue)){
				$this->writeLog ('config file(', $strValue, ') not found...');
			} else {
				if ($this->driver_openConfig ($strValue)) {
					parent::driver_process ();
				}
			}
        } catch (Exception $ex) {
			$this->writeLog ($ex->getMessage());
        }
		$this->closeLog ();
		exit();
    }
}