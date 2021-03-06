<?php
/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser Public License for more details.

    You should have received a copy of the GNU Lesser Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

ini_set( "memory_limit", "200M" );
error_reporting(E_ALL);
ini_set('display_errors', '1');

include("FaceDetection.php");
class FaceDetector implements FaceDetection
{
	private $classifierSize;
	private $stages;
	private $image;
	private $width;
	private $height;
	private $foundRects;
	
	private $tmpImg;
	private $ratio;
	
	/**
	 * Constructor
	 * @param string Path to classifier file, otherwise default classifier will be used
	 * @return FaceDetector 
	 */	
	public function __construct($classifierFile = null)
	{		
		$this->initClassifier(is_null($classifierFile) ? dirname(__FILE__)."/haarcascade_frontalface_default.xml" : $classifierFile);
	}
	
	private function initClassifier($classifierFile)
	{
		$xmls = file_get_contents($classifierFile);
		$xmls = preg_replace("/<!--[\S|\s]*?-->/", "", $xmls);
		$xml = simplexml_load_string($xmls);
				
		$this->classifierSize = explode(" ", strval($xml->children()->children()->size));
		$this->stages = array();
		
		$stagesNode = $xml->children()->children()->stages;
		
		foreach($stagesNode->children() as $stageNode)
		{
			$stage = new Stage(floatval($stageNode->stage_threshold));
				
			foreach($stageNode->trees->children() as $treeNode)
			{
				$feature = new Feature(floatval($treeNode->_->threshold), floatval($treeNode->_->left_val), floatval($treeNode->_->right_val), $this->classifierSize);
				
				foreach($treeNode->_->feature->rects->_ as $r)
				{
					$feature->add(Rect::fromString(strval($r)));
				}
				
				$stage->features[] = $feature;
			}
			
			$this->stages[] = $stage;
		}		
	}

	/**
	 * Detect faces in given image 
	 * 
	 * @param string path of image file
	 * @throws Exception
	 */	
	 
	
	public function scanImage($imageFile)
	{
		$imageInfo = getimagesize($imageFile);
		$image = null;
		if(!$imageInfo)
		{
			echo("Could not open file: ".$imageFile);
			throw new Exception("Could not open file: ".$imageFile);
		}
		
		$this->width = $imageInfo[0];
		$this->height = $imageInfo[1];
		$imageType = $imageInfo[2];
		
		if( $imageType == IMAGETYPE_JPEG )
		{
			$image = imagecreatefromjpeg($imageFile);
		}
		elseif( $imageType == IMAGETYPE_GIF )
		{
			$image = imagecreatefromgif($imageFile);
		}
		elseif( $imageType == IMAGETYPE_PNG )
		{
			$image = imagecreatefrompng($imageFile);
		}
		else
		{
			throw new Exception("Unknown Fileformat: ".$imageType.", ".$imageFile);
		}
		
		return $image;
	}
	
	
	/**
	 * Returnes array of found faces. 
	 *
	 * Each face is represented by an associative array with the keys x, y, width and hight. 
	 * 
	 * @param bool desire more confidence what a face is, gives less results
	 * @return array found faces
	 */	
	public function faceDetec($image)
	{
		$this->image = $image;
		$this->resize();

		$this->foundRects = array();
		
		$maxScale = min($this->width/$this->classifierSize[0], $this->height/$this->classifierSize[1]);
		$grayImage = array_fill(0, $this->width, array_fill(0, $this->height, null));
		$img = array_fill(0, $this->width, array_fill(0, $this->height, null));
		$squares = array_fill(0, $this->width, array_fill(0, $this->height, null));
		
		for($i = 0; $i < $this->width; $i++)
		{
			$col=0;
			$col2=0;
			for($j = 0; $j < $this->height; $j++)
			{
				$colors = imagecolorsforindex($this->image, imagecolorat($this->image, $i, $j));
		
				$value = (30*$colors['red'] +59*$colors['green'] +11*$colors['blue'])/100;
				$img[$i][$j] = $value;
				$grayImage[$i][$j] = ($i > 0 ? $grayImage[$i-1][$j] : 0) + $col + $value;
				$squares[$i][$j]=($i > 0 ? $squares[$i-1][$j] : 0) + $col2 + $value*$value;
				$col += $value;
				$col2 += $value*$value;
			}
		}
		
		$baseScale = 2;
		$scale_inc = 1.1;
		$increment = 0.1;
		$min_neighbors = 3;
		
		for($scale = $baseScale; $scale < $maxScale; $scale *= $scale_inc)
		{
			$step = (int)($scale*24*$increment);
			$size = (int)($scale*24);
			
			for($i = 0; $i < $this->width-$size; $i += $step)
			{
				for($j = 0; $j < $this->height-$size; $j += $step)
				{
					$pass = true;
					$k = 0;
					foreach($this->stages as $s)
					{
						
						if(!$s->pass($grayImage, $squares, $i, $j, $scale))
						{
							$pass = false;
							//echo $k."\n";
							break;
						}
						$k++;
					}
					if($pass)
					{
						$this->foundRects[]= array("x" => $i, "y" => $j, "width" => $size, "height" => $size);
					}
				}
			}
		}
		
		
		return $this->merge($this->foundRects, 2 + intval(false));
		
	}
	
	public function getImage($faces, $image)
	{
		foreach($faces as $face)
		{
			$color = imagecolorallocate($image, 255, 0, 0);
			$face_x = $face['x'];
			$face_y = $face['y'];
			$face_width = $face['width'];
			$face_height = $face['height'];
			imageline($image, $face_x, $face_y, ($face_x + $face_width), $face_y, $color);
			imageline($image, ($face_x + $face_width), $face_y, ($face_x + $face_width), ($face_y + $face_height), $color);
			imageline($image, ($face_x + $face_width), ($face_y + $face_height), $face_x, ($face_y + $face_height), $color);
			imageline($image, $face_x, $face_y, $face_x, ($face_y + $face_height), $color);
		}
		
		return $image;
	}
	 

	
	
	
	private function merge($rects, $min_neighbors)
	{
		$retour = array();
		$ret = array();
		$nb_classes = 0;
		
		for($i = 0; $i < count($rects); $i++)
		{
			$found = false;
			for($j = 0; $j < $i; $j++)
			{
				if($this->equals($rects[$j], $rects[$i]))
				{
					$found = true;
					$ret[$i] = $ret[$j];
				}
			}
			
			if(!$found)
			{
				$ret[$i] = $nb_classes;
				$nb_classes++;
			}
		}
	
		
		$neighbors = array();
		$rect = array();
		for($i = 0; $i < $nb_classes; $i++)
		{
			$neighbors[$i] = 0;
			$rect[$i] = array("x" => 0, "y" => 0, "width" => 0, "height" => 0);
		}
		
		for($i = 0; $i < count($rects); $i++)
		{
			$neighbors[$ret[$i]]++;
			$rect[$ret[$i]]['x'] += $rects[$i]['x'];
			$rect[$ret[$i]]['y'] += $rects[$i]['y'];
			$rect[$ret[$i]]['width'] += $rects[$i]['width'];
			$rect[$ret[$i]]['height'] += $rects[$i]['height'];
		}
		
		for($i = 0; $i < $nb_classes; $i++ )
		{
			$n = $neighbors[$i];
			if( $n >= $min_neighbors)
			{
				$r = array("x" => 0, "y" => 0, "width" => 0, "height" => 0);
				$r['x'] = round(($rect[$i]['x']*2 + $n)/(2*$n)*$this->ratio);
				$r['y'] = round(($rect[$i]['y']*2 + $n)/(2*$n)*$this->ratio);
				$r['width'] = round((($rect[$i]['width']*2 + $n)/(2*$n))*$this->ratio);
				$r['height'] = round((($rect[$i]['height']*2 + $n)/(2*$n))*$this->ratio);
				
				$retour[] = $r;
			}
		}
		return $retour;
	}
	
	private function equals($r1, $r2)
	{
		$distance = (int)($r1['width']*0.2);
		
		if(	$r2['x'] <= $r1['x'] + $distance &&
			$r2['x'] >= $r1['x'] - $distance &&
			$r2['y'] <= $r1['y'] + $distance &&
			$r2['y'] >= $r1['y'] - $distance &&
			$r2['width'] <= (int)( $r1['width'] * 1.2 ) &&
			(int)( $r2['width'] * 1.2 ) >= $r1['width'] )
		{
			return true;
		}
		
		if( $r1['x'] >= $r2['x'] &&
			$r1['x'] + $r1['width'] <= $r2['x'] + $r2['width'] &&
			$r1['y'] >= $r2['y'] &&
			$r1['y'] + $r1['height'] <= $r2['y'] + $r2['height'] )
		{
			return true;
		}
		
		return false;
	}	
	
	/**
	 *	resize a image
	 */
	private function resize(){
		$this->ratio = 1;
		if($this->width*$this->height >140000){
			$this->ratio = ceil(sqrt(($this->width*$this->height)/140000));
			$newWidth = round($this->width/$this->ratio);
			$newHeight = round($this->height/$this->ratio);
			$image_p = imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($image_p, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height);
			$this->width = $newWidth;
			$this->height = $newHeight;
			$this->tmpImg = $this->image;
			$this->image = $image_p;
		}
		else
			$this->tmpImg = $this->image;
		
	}
	
}

class Rect
{
	public $x1;
	public $x2;
	public $y1;
	public $y2;
	public $weight;
	
	public function __construct($x1, $x2, $y1, $y2, $weight)
	{
		$this->x1 = $x1;
		$this->x2 = $x2;
		$this->y1 = $y1;
		$this->y2 = $y2;
		$this->weight = $weight;
	}
	
	public static function fromString($text)
	{
		$tab = explode(" ", $text);
		$x1 = intval($tab[0]);
		$x2 = intval($tab[1]);
		$y1 = intval($tab[2]);
		$y2 = intval($tab[3]);
		$f = floatval($tab[4]);
		
		return new Rect($x1, $x2, $y1, $y2, $f);
	}

}

class Feature
{

	public $rects;
	public $threshold;
	public $left_val;
	public $right_val;
	public $size;
	
	public function __construct( $threshold, $left_val, $right_val, $size)
	{

		$this->rects = array();
		$this->threshold = $threshold;
		$this->left_val = $left_val;
		$this->right_val = $right_val;
		$this->size = $size;
	}


	public function add(Rect $r)
	{
		$this->rects[] = $r;
	}
	
	public function getVal($grayImage, $squares, $i, $j, $scale)
	{
		$w = (int)($scale*$this->size[0]);
		$h = (int)($scale*$this->size[1]);
		$inv_area = 1/($w*$h);

		$total_x = $grayImage[$i+$w][$j+$h] + $grayImage[$i][$j] - $grayImage[$i][$j+$h] - $grayImage[$i+$w][$j];
		$total_x2 = $squares[$i+$w][$j+$h] + $squares[$i][$j] - $squares[$i][$j+$h] - $squares[$i+$w][$j];
		
		$moy = $total_x*$inv_area;
		$vnorm = $total_x2*$inv_area-$moy*$moy;
		$vnorm = ($vnorm>1) ? sqrt($vnorm) : 1;
		
		$rect_sum = 0;
		for($k = 0; $k < count($this->rects); $k++)
		{
			$r = $this->rects[$k];
			$rx1 = $i+(int)($scale*$r->x1);
			$rx2 = $i+(int)($scale*($r->x1 + $r->y1));
			$ry1 = $j+(int)($scale*$r->x2);
			$ry2 = $j+(int)($scale*($r->x2 + $r->y2));

			$rect_sum += (int)(($grayImage[$rx2][$ry2]-$grayImage[$rx1][$ry2]-$grayImage[$rx2][$ry1]+$grayImage[$rx1][$ry1])*$r->weight);
		}

		$rect_sum2 = $rect_sum*$inv_area;
		
		return ($rect_sum2 < $this->threshold*$vnorm ? $this->left_val : $this->right_val);
	}	
	
}

class Stage
{
	public $features;
	public $threshold;
	
	public function __construct($threshold)
	{
		$this->threshold = floatval($threshold);
		$this->features = array();
	}
	
	public function pass($grayImage, $squares, $i, $j, $scale)
	{
		$sum = 0;
		foreach($this->features as $f)
		{
			$sum += $f->getVal($grayImage, $squares, $i, $j, $scale);
		}

		return $sum > $this->threshold;
	}

}
?>
