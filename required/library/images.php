<?php 

class images  {

	/**
	 * Scales image sizes to maximum proportions height and width
	 * @param Integer $newWidth  new maximum width of image
	 * @param Integer $newHeight new maximum height of image
	 * @param Array   $imgsize   current image sizes
	 * @param bool    $crop
	 * @return Array Array with new height and width values
	 */
    private static function getNewSizes($newWidth, $newHeight, $imgsize, $crop = false) {


        if ($imgsize[0] > $newWidth || $imgsize[1] > $newHeight) {

            # 0 is width, 1 is height
            if ($imgsize[0] / $newWidth > $imgsize[1] / $newHeight) {
            
                # In this case scaled width is more than height
            	if($crop) {
	                $maxLen["width"]  = $imgsize[0] * ($newHeight / $imgsize[1]);
	                $maxLen["height"] = $newHeight;
            	} else {
	                $maxLen["width"]  = $newWidth;
	                $maxLen["height"] = $imgsize[1] * ($newWidth / $imgsize[0]);
                }
                
            } else {
            
            	
				if($crop) {
	                $maxLen["width"]  = $newWidth;
	                $maxLen["height"] = $imgsize[1] * ($newWidth / $imgsize[0]);
            	} else {
	                $maxLen["width"]  = $imgsize[0] * ($newHeight / $imgsize[1]);
	                $maxLen["height"] = $newHeight;
               	}
            
            }
        } else {
            $imgsize["width"] = $imgsize[0];
            $imgsize["height"] = $imgsize[1];
            return $imgsize;
        }
        return $maxLen;
    }

	/**
	 * Generates image from file path
	 * @param string $extensions Extension of Image
	 * @param string $path       Path if image
	 * @throws userErrorException
	 * @throws systemErrorException
	 * @return resource
	 */
    private static function createFromPath($extensions, $path) {
        switch ($extensions) {
            case 'jpg':
            case 'jpeg':
                if (!$image = imagecreatefromjpeg($path))
                    throw new systemErrorException("Невозможно создать изображение из файла $path"); break;
            case 'gif':
                if (!$image = imagecreatefromgif($path))
                    throw new systemErrorException("Невозможно создать изображение из файла $path"); break;
            case 'png':
                if (!$image = imagecreatefrompng($path))
                    throw new systemErrorException("Невозможно создать изображение из файла $path"); break;
            default:
                throw new userErrorException("Неизвестное расширение изображения: \"$extensions\"");
                break;
        }
        return $image;
    }

	/**
	 * Creates new image with new sizes and transparent according old one
	 * @param resource $oldImage   Old image
	 * @param Array    $sizes      Array with "width" and "height" indexes
	 * @param String   $extensions Extension of image
	 * @throws systemErrorException
	 * @return resource New blank image
	 */
    private static function createNew($oldImage, $sizes, $extensions) {
    
        if (!($image_p = imagecreatetruecolor($sizes["width"], $sizes["height"])))
            throw new systemErrorException("");


        if ($extensions == "png" || $extensions == "gif") {
            $trnprt_indx = imagecolortransparent($oldImage);

            # If we have a specific transparent color
            if ($trnprt_indx >= 0) {

                # Get the original image's transparent color's RGB values
                $trnprt_color = imagecolorsforindex($oldImage, $trnprt_indx);

                # Allocate the same color in the new image resource
                $trnprt_indx = imagecolorallocate($image_p, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);

                # Completely fill the background of the new image with allocated color.
                imagefill($image_p, 0, 0, $trnprt_indx);

                # Set the background color for new image to transparent
                imagecolortransparent($image_p, $trnprt_indx);
            }
            # Always make a transparent background color for PNGs that don't have one allocated already
            elseif ($extensions == "png") {

                # Turn off transparency blending (temporarily)
                imagealphablending($image_p, false);

                # Create a new transparent color for image
                $color = imagecolorallocatealpha($image_p, 0, 0, 0, 127);

                # Completely fill the background of the new image with allocated color.
                imagefill($image_p, 0, 0, $color);

                # Restore transparency blending
                imagesavealpha($image_p, true);
            }
        }

        return $image_p;
    }

	/**
	 * Outputs image to file
	 * @param resource $image      Resource if image to output
	 * @param string   $extensions Extension of outputted file
	 * @param string   $filename   Name of outputted file
	 * @throws systemErrorException
	 */
    private static function toFile($image, $extensions, $filename) {
        switch ($extensions) {
            case 'jpg':
                if (!imagejpeg($image, $filename, 100))
                    throw new systemErrorException("Невозможно создать файл $filename"); break;
            case 'jpeg':
                if (!imagejpeg($image, $filename, 100))
                    throw new systemErrorException("Невозможно создать файл $filename"); break;
            case 'gif':
                if (!imagegif($image, $filename))
                    throw new systemErrorException("Невозможно создать файл $filename"); break;
            case 'png':
                if (!imagepng($image, $filename))
                    throw new systemErrorException("Невозможно создать файл $filename"); break;
            default:
                throw new systemErrorException("Неизвестное расширение изображения: \"$extensions\"");
                break;
        }
    }

	/**
	 * Generates resize images by that was sended in $files and gathered by files::uploadFiles
	 * @param array       $files       Array of files for which one we should make small copies.
	 * @param string      $directory   Path of dir to put images to
	 * @param integer     $newWidth    New max width for images.
	 * @param integer     $newHeight   New max height for images.
	 * @param bool|string $nameType    New images naming type(may be <b>random</b>, <b>numbered</b>, <b>false</b> ).
	 * @param mixed       $prefix      Prefix which is used for new images names.
	 * @param bool        $crop
	 * @throws systemNoticeException
	 * @internal param String $uploadDir Directory in which resize images will be saved.
	 */
    public static function makeSmallFromFiles(&$files, $directory, $newWidth, $newHeight, $nameType = false, $prefix = 0, $crop = false) {

        # Variable check
        if (!is_array($files))
            throw new systemNoticeException("No images were resized, because they are not in array format");


        foreach ($files as &$file) {
        
        
            # Try to resize image
            if(!$directory) $directory = $file["directory"];
        
        
			# Make filename
	        $filename = files::makeName($directory, $nameType, $prefix, $file['name'], $file['extension']);


            # Try to create miniature
            try {
				$sizes = self::resizeToFile($file['fileLocation'], $filename, $newWidth, $newHeight, $crop); 			
			} catch(Exception $e) {
                info::error("Во время создания миниатюры для файла " . basename($filename) . " произошла ошибка. Возможно файл поврежден.");
				continue;
            }


            # Adds image data
            $file += array(
				'hasSmall'			=> true,
				'smallDirectory'	=> $directory,
				'smallFileLocation'	=> $filename,
				'smallFileName'		=> files::getName($filename) .'.'. $file['extension'],
				"width"				=> $sizes["oldWidth"],
				"height"			=> $sizes["oldHeight"]
			);
			
        }
    }

	/**
	 * Resize image file and save its resize copy to new file via IMagic
	 * @param string  $oldFile   Path to old image file
	 * @param string  $newFile   Path to new image file
	 * @param integer $newWidth  Max width of new image
	 * @param integer $newHeight Max height of new image file
	 * @param bool    $crop      Identifies if image should be cropped if not  has proper proportions
	 * @throws systemNoticeException
	 * @throws systemErrorException
	 * @return bool return TRUE if file properly created and FALSE if any error occupied
	 */
    private static function resizeToFileIMagick($oldFile, $newFile, $newWidth, $newHeight, $crop = false) {

		try {

			# Create new image from path
			$image = new Imagick($oldFile);


			# Get image sizes
			$sizes = array_values($image->getImageGeometry());


			# Generates new image sizes
			$newSizes = self::getNewSizes($newWidth, $newHeight, $sizes, $crop);


			# Create new image from old one
			$newImage = clone $image;


			# Resize image
			$newImage->resizeImage($newSizes["width"], $newSizes["height"], imagick::FILTER_LANCZOS, 0.9);


			# Crop
			if($crop)
				$newImage->cropImage($newWidth, $newHeight,
					(int)($newSizes["width"] - $newWidth)/2,
					(int)($newSizes["height"]- $newHeight)/2);


			# Frite to file
			$newImage->writeImage($newFile);


			# Return sizes array
			return array(
				"oldWidth"	=> $sizes[0],
				"oldHeight"	=> $sizes[1],
				"newWidth"	=> $newSizes["width"],
				"newHeight"	=> $newSizes["height"]
			);


		} catch(ImagickException $e) {
			throw new systemErrorException("Imagick error: " . $e->getMessage());
		}
    
    }

	/**
	 * Resize part of image to file
	 * @param string $oldFile   Path to image file
	 * @param string $newFile   Path to new image file
	 * @param int    $left      Left offset in original image
	 * @param int    $top       Top offset in original image
	 * @param int    $width     Original area width
	 * @param int    $height    Original area height
	 * @param int    $newWidth  New image width
	 * @param int    $newHeight New image height
	 * @throws systemNoticeException
	 */
	public static function resizePartToFile($oldFile, $newFile, $left, $top, $width, $height, $newWidth, $newHeight) {

		if(!file_exists($oldFile))
			throw new systemNoticeException("Файл не сществует: $oldFile");


		# Gathers old image sizes
		if(!($sizes = GetImageSize($oldFile)))
			throw new systemNoticeException("Невозможно получить размеры изображения $oldFile");


		# Correct left
		if($sizes[0] < $left + $width)
			$left = $sizes[0] - $width;


		# If width grater than max
		if($left < 0) {
			$left = 0;
			$width = $sizes[0];
		}


		# Correct top
		if($sizes[1] < $top + $height)
			$top = $sizes[1] - $height;


		# If height grater than max
		if($top < 0) {
			$top = 0;
			$height = $sizes[1];
		}


		# Get image extension
		$extension = files::getExtension($oldFile);


		# Creates image object from path
		$oldImage = self::createFromPath($extension, $oldFile);


		# Create new image
		$newImage = self::createNew($oldImage, array("width" => $newWidth, "height" => $newHeight), $extension);


		# Make  mini
		if(!imagecopyresampled($newImage, $oldImage, 0, 0, $left, $top, $newWidth, $newHeight, $width, $height))
			throw new systemNoticeException("Невозможно создать миниатрю для изображения");


		# Write new image to file
		self::toFile($newImage, $extension, $newFile);


		# Destroy temporary stuff
		imagedestroy($oldImage);
		imagedestroy($newImage);


	}

	/**
	 * Resize image file and save its resize copy to new file
	 * @param string  $oldFile   Path to old image file
	 * @param String  $newFile   Path to new image file
	 * @param Integer $newWidth  Max width of new image
	 * @param Integer $newHeight Max height of new image file
	 * @param Bool    $crop      Identifies if image should be cropped if not  has proper proportions
	 * @throws systemNoticeException
	 * @return Boolean return TRUE if file properly created and FALSE if any error occupied
	 */
    public static function resizeToFile($oldFile, $newFile, $newWidth, $newHeight, $crop = false) {


		# Checks if file exists
		if(!file_exists($oldFile))
			throw new systemNoticeException("Изображения " . $oldFile . " не существует");


		# Try to do work via IMagick
		if(class_exists("Imagick", false))
			return self::resizeToFileIMagick($oldFile, $newFile, $newWidth, $newHeight, $crop);


		# Get image extension
		$extension = files::getExtension($oldFile);


		# Gathers old image sizes
		if(!($imgsize = GetImageSize($oldFile)))
			throw new systemNoticeException("Невозможно получить размеры изображения $oldFile");


		# Generates new image sizes
		$newSizes = self::getNewSizes($newWidth, $newHeight, $imgsize, $crop);


		# Creates image object from path
		$oldImage = self::createFromPath($extension, $oldFile);


		# Creates new image object
		if($crop)	$newImage = self::createNew($oldImage, array("width" => $newWidth, "height" => $newHeight), $extension);
		else 		$newImage = self::createNew($oldImage, $newSizes, $extension);


		# Reseze image
		if($crop && !imagecopyresampled(
							$newImage, 
							$oldImage, 
							0, 0, 
							(int)($imgsize[0] -  $imgsize[0] * ($newWidth/$newSizes["width"]))/2, 
							(int)($imgsize[1] -  $imgsize[1] * ($newHeight/$newSizes["height"]))/2, 
							$newWidth, 
							$newHeight, 
							$imgsize[0] - ($imgsize[0] -  $imgsize[0] * ($newWidth/$newSizes["width"])), 
							$imgsize[1] - ($imgsize[1] -  $imgsize[1] * ($newHeight/$newSizes["height"])))) {

			throw new systemNoticeException("Невозможно создать миниатрю для файла $oldFile");

		}
		elseif(!$crop && !imagecopyresampled($newImage, $oldImage, 0, 0, 0, 0, $newSizes["width"], $newSizes["height"], $imgsize[0], $imgsize[1]))
			throw new systemNoticeException("Невозможно создать миниатрю для файла $oldFile");


		# Write new image to file
		self::toFile($newImage, $extension, $newFile);


		# Destroy temporary stuff
		imagedestroy($oldImage);
		imagedestroy($newImage);

		
		# Return sizes array
		return array(
			"oldWidth"	=> $imgsize[0],
			"oldHeight"	=> $imgsize[1],
			"newWidth"	=> $newWidth,
			"newHeight"	=> $newHeight
		);

    }

	/**
	 * Gets image file information 
	 */
	public static function getImageInfo($path) {


		# Get EXIF data from image
		$exif = @exif_read_data($path, 0, true);


		# If no exif data gained
		if($exif === false)
			throw new systemErrorException("Can't read exif data from image: $path");


		# If no data
		if(empty($exif['GPS']))
			throw new systemErrorException("No GPS data persists");


		# If wrong data
		if(empty($exif['GPS']['GPSLatitudeRef']) ||
		   empty($exif['GPS']['GPSLatitude']) ||
		   empty($exif['GPS']['GPSLongitudeRef']) ||
		   empty($exif['GPS']['GPSLongitude']))
			throw new systemErrorException("Wrong GPS data for $path: ".var_export($exif, true));


		# Get reference and latitude
		$reference = $exif['GPS']['GPSLatitudeRef'];
		$latitude  = $exif['GPS']['GPSLatitude'];


		# Count parts
		list($num, $dec) = explode('/', $latitude[0]);
		$seconds = $num / $dec;
		list($num, $dec) = explode('/', $latitude[1]);
		$minutes = $num / $dec;
		list($num, $dec) = explode('/', $latitude[2]);
		$degrees = $num / $dec;


		# Recount
		$latitude = ($seconds + $minutes / 60 + $degrees / 3600) * ($reference == "S" ? -1 : 1);


		# Get reference and longitude
		$reference  = $exif['GPS']['GPSLongitudeRef'];
		$longitude = $exif['GPS']['GPSLongitude'];


		# Count parts
		list($num, $dec) = explode('/', $longitude[0]);
		$seconds = $num / $dec;
		list($num, $dec) = explode('/', $longitude[1]);
		$minutes = $num / $dec;
		list($num, $dec) = explode('/', $longitude[2]);
		$degrees = $num / $dec;


		# Recount
		$longitude = ($seconds + $minutes / 60 + $degrees / 3600) * ($reference == "W" ? -1 : 1);


		# Return
		return array("gps" => array($latitude, $longitude, "latitude" => $latitude, "longitude" => $longitude));

	}

}
