<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LBP
 *
 * @author MrOnly
 */
class LBP {

    //put your code here
    private $classifierSize;
    private $stages;
    private $image;
    private $width;
    private $height;
    private $foundRects;

    public function __construct($classifierFile = null) {
        $this->initClassifier(is_null($classifierFile) ? dirname(__FILE__) . "/lbpcascade_frontalface.xml" : $classifierFile);
    }

    private function initClassifier($classifierFile) {
        $xmls = file_get_contents($classifierFile);
        $xmls = preg_replace("/<!--[\S|\s]*?-->/", "", $xmls);
        $xml = simplexml_load_string($xmls);
    }
    
    public function scan($imageFile) {
        $imageInfo = getimagesize($imageFile);
        if (!$imageInfo) {
            echo("Could not open file: " . $imageFile);
            throw new Exception("Could not open file: " . $imageFile);
        }
        $this->width = $imageInfo[0];
        $this->height = $imageInfo[1];
        $imageType = $imageInfo[2];
        if ($imageType == IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($imageFile);
        } elseif ($imageType == IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($imageFile);
        } elseif ($imageType == IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($imageFile);
        } else {
            throw new Exception("Unknown Fileformat: " . $imageType . ", " . $imageFile);
        }
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        $this->LBP($this->image);
        return $this->image;
    }

    public function LBP($inputImage) {
        $r = 3;

        $MAT = array_fill(0, $this->width, array_fill(0, $this->height, null));
        $Max = 0;

        for ($i = 0; $i < $this->height; $i++) {
            for ($j = 0; $j < $this->width; $j++) {
                $binArr = array();
                if (($i > $r) 
                    && ($j > $r) 
                    && ($i < ($this->height - $r)) 
                    && ($j < ($this->width - $r))
                   ) {
                    $centerPixel = (imagecolorat($inputImage, $j, $i) >> 16)& 0xFF;
                    for ($ii = $i - $r; $ii < ($i + $r); $ii++) {
                        for ($jj = $j - $r; $jj < ($j + $r); $jj++) {
                            $currentPixel = (imagecolorat($inputImage, $jj, $ii) >> 16) & 0xFF;
                            if ($currentPixel > $centerPixel) {
                                array_push($binArr, 1);
                            } else {
                                array_push($binArr, 0);
                            }
                        }
                    }
                    $decimalValue =0;
                    //get Decimal Value of pixel
                    for ($q = 0; $q < count($binArr); $q++) {
                        $decimalValue+= ($binArr[$q] * pow(2, $q));
                    }
                    $MAT[$j][$i] = $decimalValue;
                    if ($decimalValue > $Max)
                        $Max = $decimalValue;
                }
            }
        }
        echo '<br/><br/>Max value is: '.$Max.'<br/><br/><br/><br/>';
        for ($x = 0; $x < $this->height; $x++)
            for ($y = 0; $y < $this->width; $y++) {
                $val = $MAT[$y][$x]/$Max;
                $v = round($val * 255);
                //imagesetpixel($this->image, $v, $v, $v);
                $color = imagecolorallocate($this->image, $v, $v,$v);
                imagesetpixel($this->image, $y, $x, $color);
            }
        return $inputImage;
    }

}

?>
