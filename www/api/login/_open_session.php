<?php

//SPECIAL ZINO
ini_set('display_errors','off');
ini_set('display_startup_errors','off');
ini_set('log_errors','off');
//SPECIAL ZINO
$session = session::getInstance();

$login = (string)($sxParms->login);
$pwd = (string)($sxParms->pwd);

$lm = p4::signOnAPI($login, $pwd);
if($lm['error']) // || !$lm['admin'])
{
	err($lm['error']);
}
else
{
	$sessid = $session->ses_id;
	$usrid = $session->usr_id;

	if( ($ph_session = phrasea_open_session($sessid, $usrid)) )
	{

		$result->appendChild($dom->createElement('ses_id'))->appendChild($dom->createTextNode((string)$sessid));
		$result->appendChild($dom->createElement('usr_id'))->appendChild($dom->createTextNode((string)$usrid));
		$xbases = $result->appendChild($dom->createElement('bases'));
		foreach($ph_session['bases'] as $base)
		{
			$xbase = $xbases->appendChild($dom->createElement('base'));
			$xbase->appendChild($dom->createElement('name'))->appendChild($dom->createTextNode($base['viewname']));
			$xcolls = $xbase->appendChild($dom->createElement('collections'));
			foreach($base['collections'] as $coll)
			{
				$xcoll = $xcolls->appendChild($dom->createElement('collection'));
				$xcoll->setAttribute('id', (string)$coll['base_id']);
				$xcoll->appendChild($dom->createElement('name'))->appendChild($dom->createTextNode($coll['name']));
			}
			$xstats = $xbase->appendChild($dom->createElement('statusbits'));
												
			if($sxe = simplexml_load_string($base['xmlstruct']))
			{
				if($sxe->statbits->bit)
				{
					foreach($sxe->statbits->bit as $sb)
					{
						$xstat = $xstats->appendChild($dom->createElement('statusbit'));
						$xstat->setAttribute('name',(string)$sb);
						$xstat->setAttribute('index',(string)$sb['n']);
						$xstat0 = $xstat->appendChild($dom->createElement('label_0'))->appendChild($dom->createTextNode((string)$sb['labelOff']));
						$xstat1 = $xstat->appendChild($dom->createElement('label_1'))->appendChild($dom->createTextNode((string)$sb['labelOn']));
					}
				}
			}
		}
	}
}

?>