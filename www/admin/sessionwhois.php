<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
$conn = connection::getInstance();
if(!$conn)
	die();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	if(!$session->admin)
	{
		phrasea::headers(403);
	}
}
else{
	phrasea::headers(403);
}

phrasea::headers();

	
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head> 
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<style type="text/css">
		BODY
		{
		}
		TD
		{
		    FONT-SIZE: 10px;
		}
		TABLE
		{
		}
		.colTitle
		{
		    FONT-SIZE: 12px;
		    TEXT-ALIGN: center;
		    font-weight:700;
		}
		
		.total
		{
		} 
		.rollexp
		{
		}
		.rollexp TD
		{
		}
		.noborder
		{
		    BORDER-RIGHT: medium none;
		    BORDER-TOP: medium none;
		    BORDER-LEFT: medium none;
		    BORDER-BOTTOM: medium none
		}
		#tooltip{
			position:absolute;
		}
		</style>
		<script type="text/javascript">
			var p4 = {};
			bodySize = {x:0,y:0};
		</script>
		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jquery.tooltip.js"></script>
		<script type="text/javascript">
		$(document).ready(
			function(){
				$(window).bind('resize',function(){resize();});
				$('.usrTips').tooltip();
				resize();
			}
		);
		function resize()
		{
			bodySize.y = $('body').height();
			bodySize.x = $('body').width();	
		};
		</script>
	</head>
	<body>  
	<div style="margin:20px 0;text-align:center;">
		<div style="font-size:14px"><u><b><?php echo _('admin::utilisateurs: utilisateurs connectes')?> - <?php echo date("G:i:s")?></b></u></div>
	</div>
	
<?php

	
			 $appLaunched = array(
			 									'0'=>0
			 									,'1'=>0
			 									,'2'=>0
			 									,'3'=>0
			 									,'4'=>0
			 									,'5'=>0
			 									,'6'=>0
			 									,'7'=>0
			 									,'8'=>0
			 									);
	
$out = "";
$date_obj = new DateTime('-90 min');

$sql = "SELECT session_id,app,appinf FROM cache WHERE lastaccess>'".date("Y-m-d H:i:s", $date_obj->format('U') )."'";



if($rs = $conn->query($sql))
{	
	$out.="<center>\n";
	
	$out.="<table style=\"table-layout:fixed;border:#000000 1px solid\"  id=\"dwnldtable\" >\n";
	$out.="	<tr>\n";
	$out.="		<td class=\"colTitle\"  nowrap style=\"width:140px;\" >"._('admin::monitor: utilisateur')."</td>\n";
	$out.="		<td class=\"colTitle\"  nowrap style=\"width:100px;\" >"._('admin::monitor: modules')."</td> \n";
	$out.="		<td class=\"colTitle\"  nowrap style=\"width:120px;\" >"._('phraseanet:: adresse')."</td>\n";
	$out.="		<td class=\"colTitle\" style=\"width:140px;\" >"._('admin::monitor: heure de connexion')."</td>\n";	
	$out.="	</tr>\n";

	
	while(($row = $conn->fetch_assoc($rs)) )
	{
		$aConnect =NULL ; 
		if($row["appinf"]!="" && $row["appinf"]!=NULL)
			$aConnect = unserialize($row["appinf"]);
		$displayDb=null;
		if($aConnect!=NULL)
		{
			$aConnectDb = $aConnect["db"];
			for($i=0;$i<count($aConnectDb); $i++)
			{
				$displayDb[]= phrasea::sbas_names($aConnectDb[$i]);
			}
		}
		$onedetail ="<span style=\"position:relative; top:0px;left:0px;\">";
		$onedetail.="	<table cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout:fixed;width:300px; border:#000000 1px solid;\" id=\"tabledescexp\" >";
		$onedetail.="		<tr class=\"noborder\" style=\"border:0px\">";
		$onedetail.="			<td class=\"noborder\" style=\"border:0px;width:160px;\" valign=\"center\" />";
		$onedetail.="			<td class=\"noborder\" style=\"border:0px;width:200px;\" valign=\"center\" />";
		$onedetail.="		</tr>";				
		$onedetail.="		<tr style=\"border:0px\">";				
		$onedetail.="			<td  colspan=\"2\" class=\"noborder\" style=\"height:20px;text-align:center;background-color:#666666; color:#FFFFFF;font-size:12px\" valign=\"center\" ><b>".$aConnect["usrid"]."</b></td>";			
		$onedetail.="		</tr>";			

		$onedetail.="		<tr style=\"border:0px\">";
		$onedetail.="			<td   class=\"noborder\" style=\"border:0px;\" valign=\"top\" />";
		$onedetail.="				<table class=\"noborder\" valign=\"top\" >";
		if(isset($aConnect["usr"]["usr_nom"])&& trim($aConnect["usr"]["usr_nom"])!="")
		{
			$onedetail.="					<tr  class=\"noborder\" >";
			$onedetail.="						<td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >".$aConnect["usr"]["usr_nom"]."</td>";
			$onedetail.="					</tr>";	
		}
		if(isset($aConnect["usr"]["usr_prenom"])&& trim($aConnect["usr"]["usr_prenom"])!="")
		{
			$onedetail.="					<tr  class=\"noborder\" >";
			$onedetail.="						<td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >".$aConnect["usr"]["usr_prenom"]."</td>";
			$onedetail.="					</tr>";	
		}
		if(isset($aConnect["usr"]["societe"])&& trim($aConnect["usr"]["societe"])!="")
		{
			$onedetail.="					<tr  class=\"noborder\" >";
			$onedetail.="						<td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >".$aConnect["usr"]["societe"]."</td>";
			$onedetail.="					</tr>";	
		}
		if(isset($aConnect["usr"]["tel"])&& trim($aConnect["usr"]["tel"])!="")
		{
			$onedetail.="					<tr  class=\"noborder\" >";
			$onedetail.="						<td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >Tel :".$aConnect["usr"]["tel"]."</td>";
			$onedetail.="					</tr>";	
		}
		if(isset($aConnect["usr"]["usr_mail"])&& trim($aConnect["usr"]["usr_mail"])!="")
		{
			$onedetail.="					<tr  class=\"noborder\" >";
			$onedetail.="						<td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >".$aConnect["usr"]["usr_mail"]."</td>";
			$onedetail.="					</tr>";	
		}		
		$onedetail.="				</table>"; 
		$onedetail.="			</td>";
		
		$onedetail.="			<td  style=\"border:0px;width:160px;border-left:#cccccc 1px solid;\" valign=\"top\" />";
		$onedetail.="				<table class=\"noborder\" valign=\"top\" >";
		$onedetail.="					<tr>";
		$onedetail.="						<td class=\"noborder\" style=\"text-align:left\" >"._('admin::monitor: bases sur lesquelles l\'utilisateur est connecte : ')."</td>";
		$onedetail.="					</tr>";
		for($i=0;$i<count($displayDb); $i++)
		{
			$onedetail.="					<tr>";
			$onedetail.="						<td class=\"noborder\" style=\"text-align:left;width:160px;overflow:hidden;\"  >&nbsp;&nbsp;".$displayDb[$i]."</td>";
			$onedetail.="					</tr>";
		}
		$onedetail.="				</table>"; 
		$onedetail.="			</td>";
		$onedetail.="		</tr>";	
					
		$onedetail.="		<tr style=\"border:0px\">";				
		$onedetail.="			<td  colspan=\"2\" style=\"height:20px;text-align:center;background-color:#666666; color:#FFFFFF\" valign=\"center\" >".$aConnect["info"]."</td>";	
		$onedetail.="		</tr>";		
				
		$onedetail.="	</table>";
		$onedetail.="</span>";
		
		
		
		$out.="<tr title=\"".str_replace('"','&quot;',$onedetail)."\" class='usrTips' id=\"TREXP_".$row["session_id"]."\">";	

		if($displayDb!=null)
		{
			// cette ligne est montrable, car appartient au moins a une base
			
			$sql = 'SELECT CONCAT_WS(" ", usr_prenom, usr_nom) as nom, usr_mail, invite FROM usr WHERE usr_id="'.$conn->escape_string($aConnect["usrid"]).'"';
			$name = 'user '.$aConnect["usrid"];
			if($rsN = $conn->query($sql))
			{
				if($rowN = $conn->fetch_assoc($rsN))
				{
					if($rowN['invite'] == '1')
						$name = 'Guest access';
					elseif(trim($rowN['nom']) != '')
						$name = $rowN['nom'];
					elseif(trim($rowN['usr_mail']) != '')
						$name = $rowN['usr_mail'];
				}
			}
			
			if($row["session_id"]==$ses_id)
				$out.=sprintf("<td style=\"color:#ff0000\"><i>".$name."</i></td>\n");
			else 
				$out.=sprintf("<td>".$name."</td>\n");
			 
			 $appRef = array(
			 									'0'=>_('admin::monitor: module inconnu')
			 									,'1'=>_('admin::monitor: module production')
			 									,'2'=>_('admin::monitor: module client')
			 									,'3'=>_('admin::monitor: module admin')
			 									,'4'=>_('admin::monitor: module report')
			 									,'5'=>_('admin::monitor: module thesaurus')
			 									,'6'=>_('admin::monitor: module comparateur')
			 									,'7'=>_('admin::monitor: module validation')
			 									,'8'=>_('admin::monitor: module upload')
			 									);
			 
			 
			 $row["app"] = unserialize($row["app"]);

			 $out.= "<td>";
				foreach($row["app"] as $app)
				{
					if(isset($appLaunched[$app]))
						$appLaunched[$app]++;
					if($app == '0')
						continue;
					$out .= (isset($appRef[$app])?$appRef[$app]:$appRef[0]).'<br>';
				}
				$out .= "</td>\n";

			$out.=sprintf("<td>".$aConnect["ip"]."</td>\n");
			$out.=sprintf("<td>".$aConnect["date"]."</td>\n");
		}

		$out.="</tr>\n";
	}
	$out.="</table>\n";
}


echo "<center>";

echo "<table style=\"table-layout:fixed;border:#000000 1px solid\">";

echo "<tr>";
echo "		<td class=\"colTitle\"  nowrap style=\"width:120px;text-align:left;\" >"._('admin::monitor: module production')."</td>";
echo "		<td class=\"noborder\"  nowrap style=\"width:60px;text-align:right\" >".$appLaunched[1]."</td>";
echo "</tr>";	 
echo "<tr  class=\"noborder\">";
echo "	<td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "		<td class=\"colTitle\"  nowrap style=\"width:120px;text-align:left;\" >"._('admin::monitor: module client')."</td>";
echo "	<td  class=\"noborder\" style=\"text-align:right\">".$appLaunched[2]."</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"colTitle\" style=\"text-align:left\">"._('admin::monitor: module admin')."</td>";
echo "	<td  class=\"noborder\" style=\"text-align:right\">".$appLaunched[3]."</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"colTitle\" style=\"text-align:left\">"._('admin::monitor: module report')."</td>";
echo "	<td  class=\"noborder\" style=\"text-align:right\">".$appLaunched[4]."</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"colTitle\" style=\"text-align:left\">"._('admin::monitor: module thesaurus')."</td>";
echo "	<td  class=\"noborder\" style=\"text-align:right\">".$appLaunched[5]."</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"colTitle\" style=\"text-align:left\">"._('admin::monitor: module comparateur')."</td>";
echo "	<td  class=\"noborder\" style=\"text-align:right\">".$appLaunched[6]."</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"colTitle\" style=\"text-align:left\">"._('admin::monitor: module validation')."</td>";
echo "	<td  class=\"noborder\" style=\"text-align:right\">".$appLaunched[7]."</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "	<td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

if($appLaunched[0]>0)
{

echo "<tr  class=\"noborder\">";
echo "<td  class=\"noborder\" colspan=\"2\"/>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "<td  class=\"colTitle\" style=\"text-align:left\">"._('admin::monitor: total des utilisateurs uniques : ');
echo "	<td  class=\"noborder\" style=\"text-align:right\">".$appLaunched[0]."</td>";
echo "</tr>";	
}


echo "</table>";
echo "</center>";


echo "<br><br><hr><br><br>";
echo $out;
?>

	<form method="post" action="sessionwhois.php?>" style="display:none; visibility:hidden" target="_self">
			<input type="submit">
	</form>
		
	<script type="text/javascript">
	function view()
	{
		document.forms[0].submit();
	}
	window.setTimeout("view();",60000);
	</script>

				
	</body>
</html>