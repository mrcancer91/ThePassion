<?php
Interface FaceDetection
{
	// Input: image 's path
	// Return: an image
	public function scanImage( $pathImage );
	
	// Input: an image
	// Return: list faces 's info
	public function faceDetec( $image );
	
	//Input: list faces 's info and an image
	// Return: an image is marked face 's position
	public function getImage( $listFace, $image );
}