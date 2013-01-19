<?php

$path = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
$dir = $path[count($path) - 1];

define('REPORTS_DIR', $dir);