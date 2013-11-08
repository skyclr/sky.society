<?php

# Exception needed
include_once("exceptions.php");

/**
 * Class to work with files and file system
 */
class files {

	/**
	 * File groups extensions association
	 * @var array
	 */
	public static
        $filesExtensions = array(
		  "image"   => "jpeg, jpg, png, gif",
		  "audio"   => "mp3, mid, acc, midi",
		  "video"   => "avi, mpeg, mp4, mpg, flv, 3gp, mov, mkv",
		  "archive" => "zip, 7zip, 7z, tar, gz, rar",
        );

	/**
	 * Gets file extension
	 * @param String $path File name or path
	 * @return String File extension 
	 */
	public static function getExtension($path) {
		return strtolower(pathinfo($path, PATHINFO_EXTENSION));
	}
	
	/** 
	 * Gets file name
	 * @param String $path File name or path
	 * @return String file name 
	 */
	public static function getName($path) {
		return pathinfo($path, PATHINFO_FILENAME);
	}

	/**
	 * Uploads files to server
	 * @param string      $directory
	 * @param string      $nameType    Shows how to generate name
	 * @param int|string  $prefix      Special prefix for name generation
	 * @param int         $maxFiles    How much files should we download
	 * @param string      $inputName   Name of input tag
	 * @param bool|string $fileType    Type of files
	 * @throws systemFatalException
	 * @internal param string $upload Directory Directory to upload files
	 * @return array
	 */
	public static function uploadFiles($directory, $nameType = "random", $prefix = 0, $maxFiles = -1, $inputName = "userfile", $fileType = false) {

		
		# Local initialisations
		$fileNum  = 0;
		$filesData = array();

		
		# Checks of any files loaded
		if(!isset($_FILES[$inputName]))
			return false;


		# If not array
		if(!is_array($_FILES[$inputName]['tmp_name'])) {

			# Upload file
			if(!$fileData = self::uploadFile(array(
				"name"			=> $_FILES[$inputName]['name'],
				"error"			=> $_FILES[$inputName]['error'],
				"temporaryName"	=> $_FILES[$inputName]['tmp_name'],
				"size"			=> $_FILES[$inputName]['size'],
				"number"		=> $fileNum + 1
			), $directory, $nameType, $prefix, $maxFiles, $fileType)) $filesData[] = $fileData;

		} else {

			# Go through files
			foreach($_FILES[$inputName]['tmp_name'] as $number => $temporaryFile)
			{

				# Upload file
				if(!$fileData = self::uploadFile(array(
					"name"			=> $_FILES[$inputName]['name'][$number],
					"error"			=> $_FILES[$inputName]['error'][$number],
					"size"			=> $_FILES[$inputName]['size'][$number],
					"temporaryName"	=> $temporaryFile,
					"number"		=> $fileNum + 1
					), $directory, $nameType, $prefix, $maxFiles, $fileType)) continue;

				# Save file info
				$filesData[] = $fileData;

				# Update uploaded number
				$fileNum++;

			}
		}
		
		
		# Return moved files data
		return $filesData;
		
	}

	/**
	 * Uploads file according to parameters
	 * @param array  $file      File info: name, error, temporaryName, number
	 * @param string $directory Directory where file would be uploaded
	 * @param string $nameType  How to create name for new file
	 * @param mixed  $prefix    Prefix used in name creation
	 * @param int    $maxFiles  Maximum files number
	 * @param bool   $fileType  Type of file, like image, video or extension
	 * @return array|bool Array if file info or false on error
	 * @throws systemFatalException
	 */
	private static function uploadFile($file, $directory, $nameType = "random", $prefix = 0, $maxFiles = -1, $fileType = false) {


		# Upload errors proceed
		if($file['error'] != UPLOAD_ERR_OK) {

			switch($file['error']) {
				case UPLOAD_ERR_NO_FILE:
					if(empty($file['name'])) break;
					info::error("Файл <b>\"{$file['name']}\"</b> не был загружен");
					break;
				case UPLOAD_ERR_PARTIAL:
					info::error("Файла <b>\"{$file['name']}\"</b> был загружен лишь частично");
					break;
				case UPLOAD_ERR_FORM_SIZE:
				case UPLOAD_ERR_INI_SIZE:
					info::error("Файл <b>\"{$file['name']}\"</b> превышает допустимы размер");
					break;
				default: {
					info::error("Во время загрузки файла <b>\"{$file['name']}\"</b> произошла неизвестная ошибка");
					throw new systemFatalException("Can't upload file, reason code:" . $file['error'] . '$_FILES:\n'.  var_export($_FILES, true));
				}
			}

			# If any errors occupiers we'll go to next file
			return false;

		}


		# Get file extensions
		$filename  = self::getName($file['name']);
		$extension = self::getExtension($file['name']);


		# File limitation
		if($maxFiles >= 0 && sizeof($file['number']) > $maxFiles) {
			info::error("Файл $filename не загружен, так как превышен лимит загружаемых за раз файлов");
			return false;
		}


		# Check if tmp file really uploaded file
		if(!is_uploaded_file($file['temporaryName']))
			return false;


		# Check if this is proper file type
		if($fileType && !self::checkType($file['name'], $fileType)) {
			info::error("Файл $filename не загружен, так как разрешены лишь файлы типа ".$fileType);
			return false;
		}


		# Make filename
		$uploadFile = self::makeName($directory, $nameType, $prefix, $filename, $extension);


		# Download files
		if(!@move_uploaded_file($file['temporaryName'], $uploadFile))
		{
			info::error("Во время загрузки файла $filename произоша ошибка.");
			baseException::log("Cant move file '".$file['temporaryName']."' to path ".$uploadFile);
			return false;
		}


		# Change mode
		if(!chmod($uploadFile, 0666))
			info::notice("После перемещения файла не получилось изменить его права.");


		# Compile result
		$fileData = array(
			'name'			=> self::getName($filename),
			'directory'		=> $directory,
			'fileLocation'	=> $uploadFile,
			'extension'		=> $extension,
			'addDate'		=> time(),
			'fileName'		=> self::getName($uploadFile) . "." . $extension,
			'type'			=> "unknown",
			'size'			=> $file['size']
		);

		# Get file types
		foreach(self::$filesExtensions as $type => $exts) {
			$extensions = explode(", ", $exts);
			if(in_array($extension, $extensions)) $fileData['type'] = $type;
		}


		# Return file info
		return $fileData;

	}

	/**
	 * Creates new name with specified parameters
	 * @param string     $directory   Directory in which file would be moved
	 * @param string     $type        Type of name, available: 'random', 'numbered', specified name like 'new_file'
	 * @param string|int $prefix      Prefix to be used with name
	 * @param string     $realName    Current file name
	 * @param string     $extension   File extension
	 * @throws systemErrorException
	 * @return string
	 */
	public static function makeName($directory, $type, $prefix, $realName, $extension) {

		do {
			
			# Simple numbers
			if($type === 'numbered') {
				$name = $prefix; 
				$prefix++;

			# Random string
			} elseif($type === 'random')  {
				
				# Make name
				$name = substr(md5(rand().time().$realName), 0, 10);

				# Prefix add if needed
				if($prefix !== false) 
					$name = $prefix . $name;

			# If specified string
			} else {

				if(is_string($type)) $name = $type;
				else				 $name = $realName;

				# Prefix add if needed
				if($prefix > 0) {
					$name = $name . $prefix;
					$prefix++;
				}

				# Check
				if(!$prefix && file_exists($directory . $name .'.'. $extension))
					throw new systemErrorException("File '$directory . $name .'.'. $extension' already exists");

			}
		} while(file_exists($directory . $name .'.'. $extension));

		
		# Return full path
		return $directory . $name .'.'. $extension;
		
	}

	/**
	 * Checks if file has proper extension
	 * @param string $file    	Filename/path
	 * @param string $fileType	Expected file type
	 * @return bool
	 * @internal param String $fileType File format or extension
	 */
	public static function checkType($file, $fileType) {
	
		
		# Get file extension
		$fileExtensions = self::getExtension($file);
		
		
		# If this type like 'archive'
		if(in_array($fileType, array_keys(self::$filesExtensions))) {

			
			# Get extensions list
			$extensions = explode(", ", self::$filesExtensions[$fileType]);

			
			# If not valid
			if(!in_array($fileExtensions, $extensions)) return false;
			

		# If this type like 'png'
		} elseif($fileExtensions != $fileType)
			return false;
		
		
		# Get file
		return true;
		
	}

	/**
	 * Creates file from all input data, useful for XHR ajax file upload, or logging
	 * @param String      $directory Directory for file
	 * @param String      $nameType  How to make name
	 * @param Mixed       $prefix    Prefix to use in file name generation
	 * @param String      $inputName Name from $_GET that keeps original file name
	 * @param bool|String $fileType  Group or any file extension
	 * @throws userErrorException
	 * @throws systemErrorException
	 * @return array
	 */
	public static function inputToFile($directory, $nameType = "random", $prefix = 0, $inputName = "userfile", $fileType = false) {
	
		
		# Get filename
		$filename = $_GET[$inputName];
		
			
		# Get file extensions
        $extension = self::getExtension($filename);
		
		
		# Check if this is proper file type
		if($fileType && !self::checkType($filename, $fileType))
			throw new userErrorException("Файл $filename не загружен, так как разрешены лишь файлы типа ".$fileType);
		
		
		# Make filename
		$uploadFile = self::makeName($directory, $nameType, $prefix, self::getName($filename), $extension);

		
		# Open input
        if(!$input = fopen("php://input", "r"))
        	throw new systemErrorException("Cant open input stream");
        	
		
        # Open output
        if(!$target = fopen($uploadFile, "w"))
	        throw new systemErrorException("Cant open file to write data $uploadFile");
	        
		
	    # Copy stream to file
        $written = stream_copy_to_stream($input, $target);
        
		
        # Close descriptors
    	fclose($target);
        fclose($input);
		
		
		# If no bytes were written
		if(!$written) {
			self::deleteFile($uploadFile);
			throw new userErrorException("Файл пуст");
		}
		
		
		# Create return data array
		$fileData = array(
			'name' 			=> self::getName($filename),
			'directory' 	=> $directory,
			'fileLocation' 	=> $uploadFile,
			'extension' 	=> $extension,
			'addDate' 		=> time(),
			'fileName' 		=> self::getName($uploadFile) . ".$extension",
			'size'			=> $written,
			'type'			=> 'unknown'
		);
        
		
        # Define file type 
	    foreach(self::$filesExtensions as $type => $exts) {
	        $extensions = explode(", ", $exts);
	        if(in_array($extension, $extensions)) $fileData['type'] = $type;
	    }


		# Return
	    return $fileData;

	}
	
	/**
	 * Returns number of uploaded files
	 * @param String $inputName Name of file input
	 * @return int Number of uploaded files
	 */
	public static function filesUploaded($inputName = "userfile") {

		
		# If no files
        if(!isset($_FILES[$inputName]))
			return 0;
        
		
		# Number of uploaded files
		$filesUploaded = 0;


		# If many files
		if(is_array($_FILES[$inputName]['tmp_name'])) {

			# Go through files and check was ot uploaded
			foreach($_FILES[$inputName]['tmp_name'] as $temporaryFile)
			{
				if(!is_uploaded_file($temporaryFile)) continue;
				else $filesUploaded++;
			}

		# If single
		} else {
			if(!is_uploaded_file($_FILES[$inputName]['tmp_name']))
				return 0;
		}


		# Return
		return $filesUploaded;

	}
	
	
	/**
	 * Creates new directory
	 * @param String $path New directory path
 	 * @return Mixed True on create, false on already exists
	 * @throws systemException 
	 */
	public static function newDirectory($path) {
	   
		
		# Exists check
		$exists = file_exists($path);
		
		
		# If already exists but not dir
		if($exists && !is_dir($path)) 
			throw new systemException("Файл $path уже существует, и при этом не является директорией");
		
		
		# Try to create
		if(!$exists && !@mkdir($path))
			throw new systemException("Невозможно создать диретокрию \"$path\"");

	}
	
    /**
	 * Deletes directory recursively
	 * @param String $path Path of directory to delete
	 * @return boolean True on delete, false, if not exists
	 * @throws systemErrorException 
	 */
	public static function deleteDirectory($path) {
	
		
		# Check if exists
        if(!file_exists($path))
			return false;
	
		
		# Get list
        if(!$dh = glob($path . "*"))
			throw new systemErrorException("Невозможно прочитать директорию ".$path);
        
		
		# delete children
        foreach($dh as $obj) { 
            if(is_file($obj)) self::deleteFile($obj);
            else self::deleteDirectory($obj);
        } 
    
		
		# Delete dir
        if(!@rmdir($path))
			throw new systemErrorException("Невозможно удалить директорию ".$path);


		# Delete success
        return true;

	}

	/**
	 * Deletes files by name mask
	 * @see glob To find avalible masks
	 * @param string $mask Files name mask
	 */
	public static function deleteFilesByMask($mask) {

		# Delete files which find by glob
		array_map('unlink', glob($mask));

	}

	/**
	 * Deletes file
	 * @param String $filePath PAth of file to be deleted
	 * @return boolean True on delete, false if not exists
	 * @throws systemErrorException 
	 */
	public static function deleteFile($filePath) {
		
		
		# If already deleted
		if(!file_exists($filePath))
			return false;
		
		
		# Try to delete
		if(!@unlink($filePath))
			throw new systemErrorException("Невозможно удалить файл ".$filePath);
		
		
		return true;
	}
	
	/**
	 * Copies file
	 * @param String $source	Source file path
	 * @param String $destination		Destination file path
	 * @param Bool	 $overwrite	If false throws exception on file exists 
	 * @throws systemNoticeException
	 * @throws systemErrorException 
	 */
	public static function copyFile($source, $destination, $overwrite = true) {


		# If exists
		if(!$overwrite && file_exists($destination))
			throw new systemNoticeException("File already exists");


		# Copy
		$result = copy($source, $destination);


		# Exception if error occupies
		if($result === false) 
			throw new systemErrorException("Can't copy file");
		
	}

	/**
	 * Moves file
	 * @param string $source File path
 	 * @param string $destination New path
	 * @param bool $overwrite Indicates to overwrite if $destination is exists
	 * @throws systemNoticeException
	 * @throws systemErrorException
	 */
	public static function moveFile($source, $destination, $overwrite = true) {

		# If exists
		if(!$overwrite && file_exists($destination))
			throw new systemNoticeException("File already exists");


		# Copy
		$result = rename($source, $destination);


		# Exception if error occupies
		if($result === false)
			throw new systemErrorException("Can't move file");

	}

	/**
	 * Saves file data with locking
	 * @param string $file      File name
	 * @param string $string    String to write
	 * @param bool   $rewrite   Indicates if file should be rewrite
	 * @param bool   $limitSize Max file size
	 * @throws systemErrorException
	 */
	public static function saveFile($file, $string, $rewrite = false, $limitSize = false) {


		# File size is too big, could not process
		if(!$rewrite && is_numeric($limitSize) && $limitSize > 0) {
			if(file_exists($file) && filesize($file) > ($limitSize * 1024 * 1024))
				throw new systemErrorException("File is too big: $file for add/write");
		}


		# Opening file
		if(!$fp = fopen($file, ($rewrite ? "w" : "a")))
			throw new systemErrorException("Couldn't open the file: $file for add/write");


		# Locking file
		if(!flock($fp, LOCK_EX))
			throw new systemErrorException("Couldn't lock the file: $file");


		# Writing file
		if(fwrite($fp, $string) === FALSE)
			throw new systemErrorException("Couldn't write to the file: $file");


		# Unlocking file
		flock($fp, LOCK_UN);


		# Closing file
		fclose($fp);

	}

	/**
	 * Write file to output without buffering (for big files)
	 * @param string $filePath File path
	 * @throws systemErrorException
	 */
	public static function getUnBuffered($filePath) {


		# Check if file exists
		if(!file_exists($filePath))
			throw new systemErrorException("File does not exists: " . $filePath);


		# Read file and write it
		if(!$file = fopen($filePath, "r"))
			throw new systemErrorException("File is not readable: " . $filePath);


		# While we read
		while(!feof($file)) {

			# Read
			echo fread($file, 1024 * 8);

			# Flush
			flush();

			# If connection lost
			if(connection_status() != 0) {
				fclose($file);
				exit;
			}
		}

		# Close
		fclose($file);

	}
}