<?php
define("FORMAT_REEL",   1); // #,##0.00
define("FORMAT_ENTIER", 2); // #,##0
define("FORMAT_TEXTE",  3); // @

class fileSYLK
{ 
	var $myhandler = null ;
	var $fileName = null;
	var $fieldsToExport = null;
	var $cfg_formats = null;
	
	var $nblines = null;
	var $nbrows = null;
	
	
	var $row_format = null;
	var $format = null;
	
	function fileSYLK()
	{
		$this->cfg_formats[FORMAT_ENTIER] = "FF0";
		$this->cfg_formats[FORMAT_REEL]   = "FF2";
		$this->cfg_formats[FORMAT_TEXTE]  = "FG0";
	}
	
	function setfilename($aFileName)
	{
		$pathtmp = GV_RootPath.'tmp/';
		$this->fileName = $pathtmp . $aFileName;
	}
	
	function setFieldsNamesList($arrayFields)
	{
		//$this->fieldsToExport = $arrayFields;
		foreach($arrayFields as $idx=>$a)
		{
			$this->fieldsToExport[$idx] = $arrayFields[$idx]['name'];
			
			if(isset($arrayFields[$idx]['format']))
			{
				switch($arrayFields[$idx]['format'])
				{
					case "integer":
						$this->row_format[$idx]	= FORMAT_ENTIER;   
						$this->format[$idx]		= $this->cfg_formats[$this->row_format[$idx]]."R";
					break;
					
					case "double":
						$this->row_format[$idx]	= FORMAT_REEL;   
						$this->format[$idx]		= $this->cfg_formats[$this->row_format[$idx]]."R";
					break;
					
					default:
						$this->row_format[$idx]	= FORMAT_TEXTE;   
						$this->format[$idx]		= $this->cfg_formats[$this->row_format[$idx]]."L";
					break;
				}  			
			}
			else 
			{
				$this->row_format[$idx]=FORMAT_TEXTE;   
				$this->format[$idx]= $this->cfg_formats[$this->row_format[$idx]]."L";
			}
		}
		
		$this->nbrows = count($this->fieldsToExport);
		
	
		
	}
	
	function setNbLines($nb)
	{
		$this->nblines = $nb;
	}
	
	function openHandler()
	{
		if($this->fileName)
			$this->myhandler = @fopen($this->fileName, 'a');
	}
	function releaseHandler()
	{
		if($this->myhandler)
			fclose($this->myhandler);
	}
	function release()
	{
		$this->releaseHandler();
	}
	function writeHeader()
	{
		if(!$this->myhandler )
			$this->openHandler();
		if( $this->myhandler )	
		{
			// fwrite($this->myhandler, $somecontent);
			
			fwrite($this->myhandler,"ID;APhraseanet\n"); // ID;Pappli
			fwrite($this->myhandler,"\n");
			// formats
			fwrite($this->myhandler,"P;PGeneral\n");    
			fwrite($this->myhandler,"P;P#,##0.00\n");       // P;Pformat_1 (reels)
			fwrite($this->myhandler,"P;P#,##0\n");          // P;Pformat_2 (entiers)
			fwrite($this->myhandler,"P;P@\n");              // P;Pformat_3 (textes)
			fwrite($this->myhandler,"\n");
			// polices
			fwrite($this->myhandler,"P;EArial;M200\n");
			fwrite($this->myhandler,"P;EArial;M200\n");
			fwrite($this->myhandler,"P;EArial;M200\n");
			fwrite($this->myhandler,"P;FArial;M200;SB\n");
			fwrite($this->myhandler,"\n");
			
			
			fwrite($this->myhandler,"B;Y".($this->nblines + 1));  ## <?php echo == NB de lignes qu'il y aura	avec +1 pour les titres de colone en gras
			fwrite($this->myhandler,";X".$this->nbrows."\n");  		## <?php echo == NB de colones qu'il y aura
			fwrite($this->myhandler,"\n");
			
			/*
			// largeurs des colonnes
			// pour chaque colones, on calcul la largeur
			// dans cette classe on defini ttes las col de la meme tailles
			for ($cpt = 1; $cpt <= $nbcol; $cpt++)
			{
				for($t=0;$t < count($tableau);$t++)
					$tmpo[$t]= strlen($tableau[$t][$cpt-1]);
				$taille=max($tmpo);
				// F;Wcoldeb colfin largeur
				if (strlen($tableau[0][$cpt-1]) > $taille)
					$taille=strlen($tableau[0][$cpt-1]);
				if ($taille>50)
					$taille=50;
				print(  "F;W".$cpt." ".$cpt." ".$taille."\n");
			}
			*/
			$taille=25;
			for ($cmpt = 0; $cmpt < $this->nbrows; $cmpt++)
				fwrite($this->myhandler,"F;W".($cmpt+1)." ".($cmpt+1)." ".$taille."\n");
			
			fwrite($this->myhandler,"F;W".$this->nbrows." 256 8\n"); // F;Wcoldeb colfin largeur
			fwrite($this->myhandler,"\n");
			 
			
			
			// on ecris l'en-tete des colonnes (en gras --> SDM4)
			for ($cmpt = 1; $cmpt <= $this->nbrows; $cmpt++)
			{
				fwrite($this->myhandler,"F;SDM4;FG0C;".($cmpt == 1 ? "Y1;" : "")."X".$cmpt."\n");
				fwrite($this->myhandler,"C;N;K\"".$this->fieldsToExport[$cmpt-1]."\"\n");
			}
			fwrite($this->myhandler,"\n");
		}
	}
	
	function addLine($lineNum , $arrayFields)
	{
		if(!$this->myhandler )
			$this->openHandler();
		if( $this->myhandler )	
		{
			// fwrite($this->myhandler, $somecontent);
		 		
			// parcours des champs par colone
			for ($cmpt = 0; $cmpt < $this->nbrows; $cmpt++)
			{
				
				if(!isset($arrayFields[$cmpt]))
					$arrayFields[$cmpt]='';
				$arrayFields[$cmpt] = str_replace("\n" , "\x1B\x20\x3A",$arrayFields[$cmpt]);
				// format
				fwrite($this->myhandler,"F;P".$this->row_format[$cmpt].";".$this->format[$cmpt]);
				fwrite($this->myhandler,($cmpt == 0 ? ";Y".$lineNum : "").";X".($cmpt+1)."\n");
				// valeur
				if ($this->row_format[$cmpt] == FORMAT_TEXTE)
					fwrite($this->myhandler,"C;N;K\"".str_replace(';', ';;', $arrayFields[$cmpt])."\"\n");
				else
					fwrite($this->myhandler,"C;N;K".$arrayFields[$cmpt]."\n");
			}
			fwrite($this->myhandler,"\n");
			 
		}
	}
	
	function writeEnd()
	{
		fwrite($this->myhandler, "E\n");
		$this->releaseHandler();
	}
}


