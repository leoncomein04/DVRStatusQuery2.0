<?php
require_once 'driverWeb.php';

$file = realpath('../app/pvrMessage1.xml');
$d = new driverWeb ();
$d->driver_process ($file);

/*
http://localhost:85/pvrMessage1.php?c=pvrMessage1&p=TC_HDEdit_001&m=MPEG2_moved&path=%5C%5CSsehgdlaptop%5Cncc_test%5CStorageMPG%5CMPEG2%5C20150521%5CTRAIN_20150521_230001-010000_TEST-MidnightCrossing_
*/
