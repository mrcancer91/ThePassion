<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        
        <?php
        ini_set("memory_limit", "200M");
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
//        include ("FaceDetector.php");
//        $detector = new FaceDetector();
//        $detector->scan("demo.jpg");
//        $faces = $detector->getFaces();
//        foreach ($faces as $face) {
//            echo "Face found at x: {$face['x']}, y: {$face['y']}, width: {$face['width']}, height: {$face['height']}<br />\n";
//        }
        //header("Content-type: image/jpeg");
        //68717379583
         $start = microtime(true);
        $mynumber =68717379583;
        echo pow(2,36);
       
        include("LBP.php");
        $obj = new LBP();
        $img = $obj->scan("demo.jpg");
        imagejpeg($img, "result.jpg");
        $end  =  microtime(true);
        echo 'Time: '.($end-$start);
        ?>
        <br/>

    </body>
</html>
