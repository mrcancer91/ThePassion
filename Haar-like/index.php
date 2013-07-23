<?php
header("Content-type: image/png");
ini_set( "memory_limit", "200M" );
error_reporting(E_ALL);
ini_set('display_errors', '1');


include("FaceDetector.php");
$detector = new FaceDetector();
$image = $detector->scanImage("demo.jpg");
$faces = $detector->faceDetec($image);
//print_r($faces);
$image = $detector->getImage($faces, $image);
imagepng($image);
imagedestroy($image);

?>
