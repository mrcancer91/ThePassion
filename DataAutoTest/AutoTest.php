<?php
	define( "PATH", "DataAutoTest/" );
	// Auto get image from folder
	function getImage( $foldername ){
		$file = array();
		$dir = opendir( $foldername );
		while( $r = readdir( $dir ) {
			if( eregi( "\.jpg", $r )
				array_push( $file, $r );
		}
		return $file;
	}
	//
	include("FaceDetector.php");
	
	//Test program and save result to file
	function test( $file )
	{
		$xmldoc = new DOMDocument();
		$xmldoc->load( "ResultFile.xml");
		$root = $xmldoc->getElementsByTagName( "Result" );
		
		foreach( $file as $f)
		{
			$detector = new FaceDetector();
			$image = $detector->scanImage( PATH . $f );
			$size = getimagesize( PATH . $f );
			
			$timeStart = microtime( true );
			// Face Detect program
			$faces = $detector->faceDetec();
			//
			$timeEnd = microtime( true );
			
			$count = 0;
			foreach( $faces as $face) $count++;
			
			// write
			$e = $xmldoc->createElement( "Image" );
			$a1 = $xmldoc->createAttribute( 'NameID' );
			$a1->value = $f;
			$e->appendChild( $a1 );
			$a2 = $xmldoc->createAttribute( 'Percent' );
			$a2->value = percent( $f, $size[0], $size[1], $faces, $count );
			$e->appendChild( $a2 );
			$a3 = $xmldoc->createAttribute( 'TimeProcess' );
			$a3->value = ( $timeEnd - $timeStart)*1000 );
			$e->appendChild( $a3 ;
			
			$r->appendChild( $e );
			
			$xmldoc->saveXML();
		}
	}
	// return: The percentage of correct detection
	function percent($idImage, $widthImage, $heightImage, $listface, $numberface)
	{
		$xmlDoc = new DOMDocument();
		$xmlDoc->load( 'FileData.xml' );
		
		$images = $xmlDoc->getElementsByTagName( 'Image' );
		
		foreach( $images as $image )
		{
			$id = $image->getAttribute( 'ID' );
			
			if( $id == $idImage )
			{
				$faces = $image->getElementsByTagName( 'face' );
				$count = 0; $countTrue = 0.0;
				foreach( $faces as $face )
				{
					$xData = $face->getElementsByTagName( 'x' )->item(0)->nodeValue;
					$yData = $face->getElementsByTagName( 'y' )->item(0)->nodeValue;
					$widthData = $face->getElementsByTagName( 'width' )->item(0)->nodeValue;
					$heightData = $face->getElementsByTagName( 'height' )->item(0)->nodeValue;
					
					if( $count < $numberface)
					{
						$fa = $listface[$count];
						$x = $fa['x'];
						$y = $fa['y'];
						$width = $fa['width'];
						$height = $fa['height'];
						
						//Compare face position
						if( abs( $x - $xData ) <= $widthImage * 5 / 100 && abs( $y - $yData ) <= $heightImage * 5 / 100 && abs( $width - $widthData ) <= $widthImage * 5 / 100 && abs( $height - $heightData ) <= $heightImage * 5 / 100)
							$countTrue++;
						else if( abs( $x - $xData ) <= $widthImage * 7 / 100 && abs( $y - $yData ) <= $heightImage * 7 / 100 && abs( $width - $widthData ) <= $widthImage * 7 / 100 && abs( $height - $heightData ) <= $heightImage * 7 / 100)
							$countTrue += 0.9;
						else if( abs( $x - $xData ) <= $widthImage * 10 / 100 && abs( $y - $yData ) <= $heightImage * 10 / 100 && abs( $width - $widthData ) <= $widthImage * 10 / 100 && abs( $height - $heightData ) <= $heightImage * 10 / 100)
							$countTrue += 0.8;
						else if( abs( $x - $xData ) <= $widthImage * 12 / 100 && abs( $y - $yData ) <= $heightImage * 12 / 100 && abs( $width - $widthData ) <= $widthImage * 12 / 100 && abs( $height - $heightData ) <= $heightImage * 12 / 100)
							$countTrue += 0.7;
						else if( abs( $x - $xData ) <= $widthImage * 15 / 100 && abs( $y - $yData ) <= $heightImage * 15 / 100 && abs( $width - $widthData ) <= $widthImage * 15 / 100 && abs( $height - $heightData ) <= $heightImage * 15 / 100)
							$countTrue += 0.6;
					}
					$count++;
				}
			}
		}
		
		$per = ($countTrue / $count * 100);
		return $per;
	}

?>