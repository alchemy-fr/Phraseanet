<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"sbid"
					, "id"
					, "piv"
					, "acf"		// si TH, verifier si on accepte les candidats en provenance de ce champ
					, "debug"
				);


if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}
				
$json = Array();		
				
if($parm["sbid"] !== null)
{
	$loaded = false;
	$connbas = connection::getInstance($parm['sbid']);
	
	if($connbas)
	{
		$sql = "SELECT p1.value AS struct, p2.value AS xml FROM pref p1, pref p2 WHERE p1.prop='structure' AND p2.prop='thesaurus'";
		if($rsbas = $connbas->query($sql))
		{
			if($rowbas = $connbas->fetch_assoc($rsbas))
			{
				$xml = trim($rowbas["xml"]);
				
				if(($dom = @DOMDocument::loadXML($xml)))
				{
					$xpath = new DOMXPath($dom);

					$json['cfield'] = $parm["acf"];
					
					// on doit verifier si le terme demande est accessible e partir de ce champ acf
					if($parm["acf"] == '*')
					{
						// le champ "*" est la corbeille, il est toujours accepte
						$json['acceptable'] = true;
					}
					else
					{
						// le champ est teste d'apres son tbranch
						$sxstruct = simplexml_load_string($rowbas["struct"]);
						if($sxstruct && ($sxfld = $sxstruct->description->{$parm["acf"]}) && ($tbranch = $sxfld["tbranch"]))
						{
							$q = "(".$tbranch.")/descendant-or-self::te[@id='".$parm["id"]."']";
							
							if($parm["debug"])
								printf("tbranch-q = \" $q \" <br/>\n");
								
							$nodes = $xpath->query($q);
							
							$json['acceptable'] = ($nodes->length > 0) ? true : false;
						}
					}
					
					
					if($parm["id"] == "T")
					{
						$q = "/thesaurus";
					}
					else
					{
						$q = "/thesaurus//te[@id='".$parm["id"]."']";
					}
					if($parm["debug"])
						print("q:".$q."<br/>\n");
						
					$nodes = $xpath->query($q);
					$json['found'] = $nodes->length;

					if($nodes->length > 0)
					{
						$fullpath_html = $fullpath = "";
						for($depth=0, $n=$nodes->item(0); $n; $n=$n->parentNode, $depth--)
						{
							if($n->nodeName=="te")
							{
								if($parm["debug"])
									printf("parent:%s<br/>\n", $n->nodeName);
								$firstsy = $goodsy = null;
								for($n2=$n->firstChild; $n2; $n2=$n2->nextSibling)
								{
									if($n2->nodeName=="sy")
									{
										$sy = $n2->getAttribute("v");
										if(!$firstsy)
										{
											$firstsy = $sy;
											if($parm["debug"])
												printf("fullpath : firstsy='%s' in %s<br/>\n", $firstsy, $n2->getAttribute("lng"));
										}
										if($n2->getAttribute("lng") == $parm["piv"])
										{
											if($parm["debug"])
												printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
											$goodsy = $sy;
											break;
										}
									}
								}
								if(!$goodsy)
									$goodsy = $firstsy;
								$fullpath = " / " . $goodsy . $fullpath;
								if($depth==0)
									$fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $goodsy . "</span>" . $fullpath_html;
								else
									$fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
							}
						}
						if($fullpath == "")
						{
							$fullpath = "/";
							$fullpath_html = "<span class='path_separator'> / </span>";
						}
						$json['fullpath'] = $fullpath;
						$json['fullpath_html'] = $fullpath_html;
					}
				}
			}
			$connbas->free_result($rsbas);
		}
	}
}
print(p4string::jsonencode($json));
?>