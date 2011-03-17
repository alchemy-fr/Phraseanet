<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

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

$request = httpRequest::getInstance();

$conn = connection::getInstance();

if($request->has_post_datas())
{
	$parm = $request->get_parms('name', 'type', 'desc', 'lst', 'coll');	
	
	if(trim($parm['name']) != '')
	{
    $parm['desc'] = strip_tags($parm['desc']);
		if(is_null($parm['type']) || $parm['type']=="CHU")
		{
			try
			{
				$basket = new basket();
				$basket->name = $parm['name'];
				$basket->desc = $parm['desc'];
				$basket->save();

				$ssel_id = $basket->ssel_id;
				if(trim($parm['lst']) != '')
				{
					$basket->push_list($parm['lst'],false);
				}
			}
			catch (Exception $e)
			{
				
			}
			echo "<script type='text/javascript'>
				parent.refreshBaskets('".$ssel_id."');
				parent.hideDwnl();
				</script>";
			exit();
		}
		elseif ($parm['type']=="REG")
		{            
		    try{
				$story = new basket();
				$story->name = $parm['name'];
				$story->desc = strip_tags(str_replace('<br>',"\n",$parm['desc']));
				$story->is_grouping = true;
				$story->base_id = $parm['coll'];
				$story->save();
				if($parm['lst']!='')
					$story->push_list($parm['lst'], false);
		    }
		    catch(Exception $e)
		    {
				?>
					<script type="text/javascript">
					alert("<?php echo _('panier:: erreur en creant le reportage')?>");
					parent.hideDwnl();
					</script>
				<?php
		    }
			?>
			<script type="text/javascript">
				<?php 
				if(isset($add['error']) && trim($add['error']) !== '')
				{
					?>alert("<?php echo str_replace('"','&quot;',$add['error']);?>");<?php 
				}
				?>
				parent.hideDwnl();
				parent.refreshBaskets(<?php echo $story->ssel_id?>);	
			</script>
			<?php
		}
			 	
	}
	
}

	
$usrRight 	= NULL;
$allCollName = NULL ;

	foreach($ph_session["bases"] as $onebase)
	{			
		foreach($onebase["collections"] as $onecoll)
			$allCollName[$onebase["sbas_id"]][$onecoll["base_id"]] = $onecoll["name"];
	}
	
	
	$sql = "SELECT basusr.base_id,canaddrecord 
			FROM (usr INNER JOIN basusr ON (usr.usr_id=basusr.usr_id AND usr.usr_id='".$conn->escape_string($usr_id) ."' AND canaddrecord=1)) "; 
	
	
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["base_id"]] = $row["canaddrecord"];
		}
		$conn->free_result($rs); 
	}
	$html = "";	 
			$bas_order = phrasea::getBasesOrder();
	foreach ($allCollName as $sbas_id=>$colls)		
	{
		$title = '<optgroup label="'.phrasea::sbas_names($sbas_id).'">';
		$collections = array();
		foreach($colls as $oneCollid=>$acollname)
		{
			if(isset($usrRight[$oneCollid]) && $usrRight[$oneCollid]=="1")
			{
				$collections[$oneCollid] = '<option  value="'.$oneCollid.'">'.$acollname.'</option>';
			}
		}	
		foreach($bas_order as $coll)
		{
			if(isset($collections[$coll['base_id']]))
			{
				if($title)
					$html .= $title;
				$title = false;
				$html .= $collections[$coll['base_id']];
			}
		}
	}
	
$parm = $request->get_parms('type');
?>
<html lang="<?php echo $session->usr_i18n;?>">
<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
<title><?php echo _('action:: nouveau panier')?></title>
<head>


	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
	<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />
<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.8.6/jquery-ui-1.8.6.js"></script>
<script type="text/javascript"> 


function chgMode(val)
{
	if(val)
		$('#sbaspopup').slideUp();
	else
		$('#sbaspopup').slideDown();
		
}
$(document).ready(function(){
	$('#tabs').tabs();
	if(parent.p4.sel.length > 0)
	{
		$('#sel_adder').show();
		$('#add_sel').val(parent.p4.sel.join(';'));
	}
	$('input.input-button').hover(
				function(){parent.$(this).addClass('hover')},
				function(){parent.$(this).removeClass('hover')}
		);

<?php 

$basket_coll = new basketCollection();
$baskets = $basket_coll->baskets;

$n = count($baskets['baskets']) + count($baskets['recept']);

$limit_bask = 150;

if($n > $limit_bask)
{
	?>
	alert('<?php echo str_replace("'","\'",sprintf(_('panier::Votre zone de panier ne peux contenir plus de %d paniers, supprimez-en pour en creer un nouveau'),$limit_bask))?>');
	parent.hideDwnl();
	<?php 
}

?>
	
});

</script>

</head>
 

	<body class="bodyprofile"> 	
		<div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></div>
		<div id="tabs">
			<ul>
				<li><a href="#baskets"><?php echo _('panier::nouveau')?></a></li>
			</ul>
			<div id="baskets">
				<form name="myform" method="post" action="newtemporary.php">
					<table border="0" cellpadding="0" cellspacing="0" style="text-align:left;margin:0 auto;" >
						<?php 
						if($html != '')
						{
						?>
						
						<tr>
							<td colspan="2">
								<?php echo _('Quel type de panier souhaitez vous creer ?');?> :
							</td>
						</tr>
						<tr>
							<td colspan="2" style="height:5px">
							</td>
						</tr>
						
						<tr>
							<td style="width:25px"></td>
							<td>
								<input type="radio" name="type" id="typeTemp0" onClick="chgMode(true);" <?php echo ($parm['type']=='CHU' ? 'checked' : '')?> value="CHU"><label for="typeTemp0"><?php echo _('phraseanet:: panier')?></label>
							</td>
						</tr>
						<?php 
						}
						?>
			
			<?php		
		?>			
						
						<tr>
							<td style="width:25px"></td>
							<td>
		<?php
								if($html!="")
								{
		?>						
								<input type="radio" name="type" id="typeTemp1" onClick="chgMode(false);" <?php echo ($parm['type']=='REG' ? 'checked' : '')?> value="REG"><label for="typeTemp1"><?php echo _('phraseanet::type:: reportages')?></label>
		<?php
								}
		?>				
							</td>
						</tr>
					
						<tr>
							<td></td>
							<td style="text-align:right">
		<?php
								if($html!="")
								{
									?>						
										<select name="coll" id="sbaspopup" style="display:none;"><?php echo $html?></select>
									<?php
								}
		?>					
							</td>
						</tr>
						<tr style="height:40px;display:none;" id="sel_adder">
							<td style="width:25px"></td>
							<td>
								<input type="checkbox" id="add_sel" name="lst" value=""/><label for="add_sel"><?php echo _('Ajouter ma selection courrante')?></label>
							</td>
						</tr>
						
						<tr>
							<td colspan="2">
								<table border="0" style="width:100%" >
									<tr>
										<td style="text-align:right"><?php echo _('Nom du nouveau panier')?>&nbsp;:</td>
										<td><input type="text" name="name" id="IdName" style="width:175px" ></td>
									</tr>
									<tr>
										<td style="text-align:right"><?php echo _('paniers::description du nouveau panier')?>&nbsp;:</td>
										<td><textarea name="desc" id="IdDesc" cols="20" rows="10"></textarea></td>
									</tr>
									<tr>
										<td colspan="2" style="height:20px"></td> 
									</tr>
								</table>
							</td>
						</tr>
					
					</table>
					<div style="text-align:center;">
						<input type="submit" value="<?php echo _('boutton::valider');?>" class="input-button" /> 
						<input type="button" value="<?php echo _('boutton::annuler');?>" class="input-button" onclick="parent.hideDwnl();" />
					</div>
				</form>
			</div>
		</div>
		 	
	</body>
</html>