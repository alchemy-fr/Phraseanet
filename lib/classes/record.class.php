<?php
class record
{
	
	public function __construct($bas,$rec)
	{
		
	}
	
	public function __destruct()
	{
		
	}

	public static function watermark($bas, $rec)
	{
		
	}

	public static function rebuild_subdef($lst)
	{
		if(!is_array($lst))
			$lst = explode(';',$lst);
		
		$conn = connection::getInstance();
		foreach($lst as $basrec)
		{
			if(trim($basrec) == '')
				continue;
				
			$basrec = explode('_',$basrec);
			$bas = $basrec[0];
			$rec = $basrec[1];
			$sbas_id = phrasea::sbasFromBas($bas);
			$connbas = connection::getInstance($sbas_id);
			
			if($connbas)
			{
				$sql = 'UPDATE record SET jeton=(jeton | '.JETON_MAKE_SUBDEF.') WHERE record_id="'.$connbas->escape_string($rec).'"';
				$connbas->query($sql);
			}
		}
		return true;
		
	}
	
	public static function stamp($bas, $rec)
	{
		
	}
	
	public function embed_preview()
	{
		
	}
	
	public function binary_datas($type,$bas,$rec,$usr=false,$ses=false)
	{
		
		return false;
	}
	
}