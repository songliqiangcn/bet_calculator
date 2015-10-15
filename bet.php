<?php

include_once('class/class_betting.php');

error_reporting(E_ALL);
set_time_limit(0);


$bet_obj = new betting();
$bet_obj->run();


