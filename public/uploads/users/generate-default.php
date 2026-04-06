<?php
// Generate a simple default avatar using PHP GD

$size = 200;
$image = imagecreatetruecolor($size, $size);

// Colors (forest green shade)
$bgColor = imagecolorallocate($image, 30, 58, 47); // #1E3A2F
$textColor = imagecolorallocate($image, 255, 255, 255); // white

// Fill background
imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);

// Draw circle for avatar
$centerX = $size / 2;
$centerY = $size / 2;
$radius = $size / 2.5;

// Draw a slightly lighter circle for contrast
$lighterColor = imagecolorallocate($image, 61, 107, 79); // #3D6B4F
imagefilledarc($image, $centerX, $centerY, $radius * 2, $radius * 2, 0, 360, $lighterColor, IMG_ARC_PIE);

// Save as JPEG
header('Content-Type: image/jpeg');
imagejpeg($image, null, 90);
imagedestroy($image);
?>
