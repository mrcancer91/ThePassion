<?php
	$timeStart = microtime( true );
	//Face Detect program
	//
	$timeEnd = microtime( true );
	
	// giá trị được tính bằng số khuôn mặt phát hiện được đúng chia cho tổng số khuôn mặt có trong ảnh (tính theo đơn vị phần trăm)
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
				$count = 0; $countTrue = 0;
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
						$pixelW = $widthImage * 5 / 100;
						$pixelH = $heightImage * 5 / 100;
						
						//Compare face position
						if( abs( $x - $xData ) <= $pixelW && abs( $y - $yData ) <= $pixelH)
							if( abs( $width - $widthData ) <= $pixelW && abs( $height - $heightData ) <= $pixelH)
								$countTrue++;
					}
					$count++;
				}
			}
		}
		
		$per = ($countTrue / $count * 100);
		return $per;
	}
	
	//$face['x'] = 200;
	//$face['y'] = 140;
	//$face['width'] = 160;
	//$face['height'] = 167;
	//$list[0] = $face;
	
	echo "Độ chính xác: " . percent( , , , , );
	
	// Processing time is calculated by milliseconds
	echo "<br />Processing time: " . ($timeEnd - $timeStart)*1000;
?>