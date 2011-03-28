<?php
class lazaretFile
{
	protected $storage = array();
	
	function __construct($lazaret_id)
	{
		$conn = connection::getInstance();
		
		if(!$conn)
			throw new Exception ('Impossible detablir la connection a la base de donnee');
		
		$sql = 'SELECT filename, filepath, base_id, uuid, sha256, status, created_on, usr_id FROM lazaret WHERE id="'.$conn->escape_string($lazaret_id).'"';
		
		$id = false;
		
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$id = $lazaret_id;
				$this->id 			= $lazaret_id;
				$this->filename 	= $row['filename'];
				$this->filepath 	= $row['filepath'];
				$this->status 		= $row['status'];
				$this->base_id 		= $row['base_id'];
				$this->uuid       = $row['uuid'];
				$this->sha256       = $row['sha256'];
				$this->created_on 	= new DateTime($row['created_on']);
				$this->usr_id 		= $row['usr_id'];
			}
			$conn->free_result($rs);
		}
		if(!$id)
		{
			throw new Exception (_('L\'element n\'existe pas ou plus'));
		}
	}
	
	public function add_to_base()
	{
		$conn = connection::getInstance();
		
		$file = GV_RootPath.'tmp/lazaret/'.$this->filepath;
		
		if(($record_id = p4file::archiveFile($file, $this->base_id, false, $this->filename, $this->sha256)) === false)
			throw new Exception (_('Impossible dajouter le fichier a la base'));
		
		$sbas_id = phrasea::sbasFromBas($this->base_id);
		$connbas = connection::getInstance($sbas_id);
		if($connbas)
		{
			$sql = 'UPDATE record SET status = (status | '.$this->status.') WHERE record_id="'.$connbas->escape_string($record_id).'"';
			$connbas->query($sql);
		}	
		
		$this->delete();
		
		return $this;
	}
	
	public function delete()
	{
		$conn = connection::getInstance();
		
		$sql = 'DELETE FROM lazaret WHERE id="'.$conn->escape_string($this->id).'"';
		if($conn->query($sql))
		{
			$file = GV_RootPath.'tmp/lazaret/'.$this->filepath;
			$thumbnail = $file.'_thumbnail.jpg';
	
			@unlink($thumbnail);
			@unlink($file);
		}
		
		return $this;
	}
	
	public function substitute($lazaret_id, $record_id)
	{
		$conn = connection::getInstance();
		
		$sbas_id = phrasea::sbasFromBas($this->base_id);
		$connbas = connection::getInstance($sbas_id);
		
		$base_id = false;
		
		$sql = 'SELECT coll_id FROM record WHERE record_id ="'.$connbas->escape_string($record_id).'"';
		if($rs = $connbas->query($sql))
		{
			if($row = $connbas->fetch_assoc($rs))
				$base_id = phrasea::baseFromColl($sbas_id, $row['coll_id']);
			$connbas->free_result($rs);
		}
		
		if(!$base_id)
			throw new Exception(_('Impossible de trouver la base'));
		
		$pathfile = GV_RootPath.'tmp/lazaret/'.$this->filepath;	
			
		try{
			p4file::substitute($base_id, $record_id, $pathfile, $this->filename, false);
			$this->delete();
		}
		catch(Exception $e)
		{
			throw new Exception ($e->getMessage());
		}		
		return $this;
	}
	
	public static function move_uploaded_to_lazaret($tmp_name, $base_id, $filename, $uuid, $sha256, $errors='', $status=false)
	{
		$conn = connection::getInstance();
		$system = p4utils::getSystem();

		if(!$conn)
			return false;

		if(!$status)
			$status = '0';
		
		$session = session::getInstance();

		$usr_id = isset($session->usr_id) ? $session->usr_id : false; 
		
		$lazaret_root = GV_RootPath.'tmp/lazaret/';
		$pathinfo = pathinfo($filename);
		
		$tmp_filename = $filename;
		
		$n = 1;
		while(file_exists($lazaret_root.$tmp_filename))
		{
			$tmp_filename = $pathinfo['filename'].'-'.$n.'.'.$pathinfo['extension'];
			$n++;
		}
		
		$pathfile = $lazaret_root.$tmp_filename;
		
		rename($tmp_name, $pathfile);
		p4::chmod($pathfile);
		
		$sql = 'INSERT INTO lazaret (id, filename, filepath, base_id, uuid, sha256, errors, status, created_on, usr_id)
				VALUES (null, "'.$conn->escape_string($filename).'", "'.$conn->escape_string($tmp_filename).'"
				, "'.$conn->escape_string($base_id).'", "'.$conn->escape_string($uuid).'", "'.$conn->escape_string($sha256).'",
        "'.$conn->escape_string($errors).'"
				, '.$conn->escape_string('0b'.$status).', NOW(), '.($usr_id ? '"'.$conn->escape_string($usr_id).'"' : 'NULL').')';
		
		
		//create thumbnail

		$infos = exiftool::get_fields($pathfile, array('Image Width', 'Image Height', 'Orientation', 'MIME Type'));
		$sdsize = 300;

		if($infos['Image Width']>0 && $infos['Image Height']>0 && $infos['Image Width']<$sdsize && $infos['Image Height']<$sdsize)
		{
			$sdsize = max($infos['Image Width'], $infos['Image Height']);
		}

		if($system == 'WINDOWS')
			$cmd = 'start /B /WAIT /LOW ' . GV_imagick;
		else
			$cmd = GV_imagick;
			
		$cmd .= ' -colorspace RGB -flatten -alpha Off -quiet';

		$cmd .= ' -quality 75 -resize ' . $sdsize . 'x' . $sdsize;

		$cmd .= ' -density 72x72 -units PixelsPerInch';

		if(isset($infos['Orientation']))
		{
			switch($infos['Orientation'])
			{
				case 'Rotate 180':		// -90 trigo pour corriger
					$cmd .= ' -rotate 180';
					break;
				case 'Rotate 90 CW':		// -90 trigo pour corriger
					$cmd .= ' -rotate 90';
					break;
				case 'Rotate 270 CW':		// 90 trigo pour corriger
					$cmd .= ' -rotate -90';
					break;
			}
		}

		// attention, au cas ou il y aurait des espaces dans le path, il faut des quotes
		// windows n'accepte pas les simple quotes
		// pour mac les quotes pour les noms de fichiers sont indispensables car si il y a un espace -> ca plante
		$array = array('application/pdf','image/psd','image/vnd.adobe.photoshop','image/photoshop','image/ai','image/illustrator','image/vnd.adobe.illustrator');
		
		if( in_array($infos['MIME Type'], $array ) )
			$cmd .= ' "'.$pathfile .'[0]" "'. $pathfile .'_thumbnail.jpg"';
		else
			$cmd .= ' "'.$pathfile .'" "'. $pathfile .'_thumbnail.jpg"';
		
		$res = exec($cmd);
		
		if($conn->query($sql))
			return true;
			
		return false;
	}
	
	public static function stream_thumbnail($id)
	{
		$conn = connection::getInstance();
		$sql = "SELECT filepath FROM lazaret WHERE id='".$conn->escape_string($id)."'";
		
		$pathfile = false;
		
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$pathfile = GV_RootPath.'tmp/lazaret/'.$row['filepath'].'_thumbnail.jpg';
			}
			$conn->free_result($rs);
		}

		export::stream_file($pathfile, basename($pathfile), 'image/jpeg', 'inline');
	}
}