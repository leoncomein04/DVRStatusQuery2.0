<?php
class inHandler {
    protected $driverobj;
	protected $inDirIter;
	protected $inDirFile;
	protected $useDirIterCurrent = false;
	
    public function IH_init(&$driverobj) {} 
    public function IH_process() {}
    public function IH_exit() {}
	
	protected function writeLog ($text) {
		return $this->driverobj->writeLog ($text);
	}
	protected function formatSQLErrors ($title,$errors) {
		return $this->driverobj->formatSQLErrors ($title,$errors);
	}

	protected function startProcessDirectoryFiles ($path, $file) {
		$this->inDirIter = new DirectoryIterator ($path);
		$this->inDirFile = $file; // dir iter does not use this, may need to test manully for input directory with mixed files
		
		// see if there any files to process
		$found = false;
		while (!$found) {
			if (!$this->inDirIter->valid()) {
				return false; // no files to process
			}
			$iterFile = $this->inDirIter->current();
			//$name = $iterFile->getFilename();
			if (!$iterFile->isDot()) {
				//if (!$iterFile->getFilename() == null && $iterFile->getFilename() !== '') {
					$found = true;
					$this->useDirIterCurrent = true;
					break;
				//}
			}
			$this->inDirIter->next(); // advance the pointer
		}
		return $found;		
	}
	protected function getDirectoryFile () {
		if (!$this->useDirIterCurrent) {
			$this->inDirIter->next(); // advance the pointer
		}
		$this->useDirIterCurrent = false;
		$found = false;
		while (!$found) {
			if (!$this->inDirIter->valid()) {
				return false; // finished
			}
			$iterFile = $this->inDirIter->current();
			//$name = $iterFile->getFilename();
			if (!$iterFile->isDot()) {
				return $iterFile;
			}
		}
	}
}
