<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
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
    position:relative; 
    top:10px;
}
TABLE
{
}
INPUT
{
}

SELECT
{
    position:relative;
    top:3px;
    width:190px
}
A, A:link, A:visited, A:active, A:hover
{
    color:#000000;
    text-decoration : none;
}
</style>

<script type="text/javascript"> 

var oMyObject = window.dialogArguments;
var myOpener  = oMyObject.myOpener;

function createTemp()
{	
	if(document.forms["myform"].typeExp[0].checked)
		myOpener.exportlist2('SYLK');
	else
		myOpener.exportlist2('TXT');
	self.close();		
	return (false);
}
</script>


 
</head>
	<body> 		
		<center>
		<FORM name="myform">
		<table border="0" cellpadding="0" cellspacing="0" style="text-align:left"" >
			<tr>
				<td colspan="2" style="text-align:center">
					<h3><?php echo _('admin::user:export: format d\'export')?><h3>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="height:5px">
				<br>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="height:5px">
				<br>
				</td>
			</tr>
			<tr>
				<td style="width:25px"></td>
				<td>
					<input type="radio" name="typeExp" id="typeExp0" checked value="XLS"><label for="typeExp0"><?php echo _('admin::user:export:format: export excel')?> (.csv)</label>
				</td>
			</tr>
			
			<tr>
				<td colspan="2" style="height:5px">
				<br>
				</td>
			</tr>
			
			<tr>
				<td style="width:25px"></td>
				<td>
					<input type="radio" name="typeExp" id="typeExp1" value="CSV"><label for="typeExp1"><?php echo _('admin::user:export:format: export ascii')?> (.txt)</label>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="height:5px">
				<br>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="height:5px">
				<br>
				</td>
			</tr><tr>
				<td colspan="2" style="height:5px">
				<br>
				</td>
			</tr>
 
			
			<tr>
				<td colspan="2" style="text-align:center"><a href="javascript:void();return(false);" onClick="createTemp();return(false);"><?php echo _('boutton::valider')?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void();return(false);" onClick="self.close();return(false);"><?php echo _('boutton::annuler')?></a></td> 
			</tr> 
		
		</table>
		</FORM>
		</center> 
		
		 	
	</body>
</html>