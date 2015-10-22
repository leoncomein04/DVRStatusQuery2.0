<?php

class outHandlerRS extends outHandlerWebDB {
	protected	$tblRS1;
	protected	$tblRS2;
	protected	$tblRS3;
	protected	$tblRS4;
	protected	$tblRS5;
	protected	$tblRS6;
	protected	$tblRS7;
	
	public function __construct() {}

    public function OH_init(&$driverobj) 
    {
		parent::OH_init($driverobj);
	}

    public function OH_process() 
    {
	}

    public function OH_exit() 
    {
		return parent::OH_exit ();
	}

	public function getByKey ($i, $db, $keyName, $keyValue, $keyComp=null) {
		$tbl = $this->openRS ($i);
		return $tbl->getByKey ($db, $keyName, $keyValue, $keyComp);
	}

    public function openRS ($i) {
       	$tblClass = 'tblRS' . strval ($i);
		if (isset($this->$tblClass)) {
			return $this->$tblClass;
		}
       	$tblClassFileName = $this->driverobj->protectedFilePath . '/' . $tblClass . '.php';             
       	require_once $tblClassFileName;
	   	$this->$tblClass = new $tblClass($this->driverobj);
	   	$this->$tblClass->tblName = 'dbo.tblRS' . strval ($i);
		return $this->$tblClass;
	}
    public function closeRS ($i) {
       	$tblClass = 'tblRS' . strval ($i);
		if (isset($this->$tblClass)) {
			$this->$tblClass->close ();
			unset ($this->$tblClass);
		}
		return true;
	}

	public function getDOWColName ($i) {
		switch ($i) {
			case 0: return 'StartDOWSun';
			case 1: return 'StartDOWMon';
			case 2: return 'StartDOWTue';
			case 3: return 'StartDOWWed';
			case 4: return 'StartDOWThu';
			case 5: return 'StartDOWFri';
			case 6: return 'StartDOWSat';
		}
		return '';
	}
}