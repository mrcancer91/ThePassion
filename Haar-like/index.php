<?php
header("Content-type: image/png");
ini_set( "memory_limit", "200M" );
error_reporting(E_ALL);
ini_set('display_errors', '1');


//$time_start = microtime(true);

include("FaceDetector.php");
$detector = new FaceDetector();
$detector->scan("demo.jpg");
$faces = $detector->getFaces();
//print_r($faces);
imagepng($faces);
imagedestroy($faces);

//$time_end = microtime(true);
//$time = $time_end - $time_start;
//echo 'Script took '.$time.' seconds to execute';
?>
