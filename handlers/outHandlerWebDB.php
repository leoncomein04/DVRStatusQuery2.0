<?php
require_once 'handlerBase.php';

class outHandlerWebDB extends handlerBase {
	
    public function __construct () {
        parent::__construct();
    }
	
    public function OH_init(&$driverobj) 
    {
		parent::OH_init($driverobj);
	}
    public function OH_process() {} 
    public function OH_exit() {} 
}
