<?php
/** Merkur 5 test EXIF
 * @author Petr Coupek
 * @date 20.03.2023
 */

include_once '../lib/mlib.php';
M5::set('header','Exif test');
M5::set('debug',true);
M5::skeleton('../');

$file="d:/data/zdroje/dbhydro/files/221406/F84Fotografie_1.jpg";
$target_file="d:/data/zdroje/dbhydro/files/221406/F84Fotografie_1_rot.jpg";

$imageObject = imagecreatefromjpeg($file);

# Get exif information
$exif = exif_read_data($file);
# Add some error handling

# Get orientation
$orientation = $exif['Orientation'];

htpr($orientation );

# Manipulate image
switch ($orientation) {
    case 2:
        imageflip($imageObject, IMG_FLIP_HORIZONTAL);
        break;
    case 3:
        $imageObject = imagerotate($imageObject, 180, 0);
        break;
    case 4:
        imageflip($imageObject, IMG_FLIP_VERTICAL);
        break;
    case 5:
        $imageObject = imagerotate($imageObject, -90, 0);
        imageflip($imageObject, IMG_FLIP_HORIZONTAL);
        break;
    case 6:
        $imageObject = imagerotate($imageObject, -90, 0);
        break;
    case 7:
        $imageObject = imagerotate($imageObject, 90, 0);
        imageflip($imageObject, IMG_FLIP_HORIZONTAL);
        break;
    case 8:
        $imageObject = imagerotate($imageObject, 90, 0); 
        break;
}

# Write image
imagejpeg($imageObject, $target_file, 75);
      


M5::done();

?>