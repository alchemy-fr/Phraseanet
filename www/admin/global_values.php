<?php 
require_once("../../lib/bootstrap.php");
require(GV_RootPath."lib/countries.php");

$session = session::getInstance();

	
$lng =  !isset($session->locale)?GV_default_lng:$session->locale;



if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	$user = user::getInstance($usr_id);

	if(!$user->is_admin)
	{
		phrasea::headers(403);
	}
	
}
else{
		phrasea::headers(403);
}

require(dirname(__FILE__)."/../../lib/conf.d/_GV_template.inc");

$request = httpRequest::getInstance();

if($request->has_post_datas())
{
	if(setup::create_global_values($request->get_post_datas()))
	{
		header('Location: global_values.php');
		exit;
	}
}

	
	
phrasea::start();
	
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<script type="text/javascript" src="/include/jslibs/jquery-1.4.4.js"></script>
		<script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.6/jquery-ui-1.8.6.js"></script>
		<script type="text/javascript" src="/include/jslibs/jquery.validate.js"></script>
		<script type="text/javascript" src="/include/jslibs/jquery.validate.password.js"></script>
		<link href="/skins/common/main.css" type="text/css" rel="stylesheet"/>
		<style type="text/css">
			.error{
				color:red;
				font-weight:bold;
			}
			h1{
				font-weight:bold;
				color:#404040;
				font-size:16px;
				margin:5px 0;
			}
			body{
				overflow:auto;
			}
			.NEW{color:red;}
			

			#form div{
				position:relative;
				float:left;
				width:100%;
			}		
			ul{
				position:relative;
				float:left;
				width:100%;
				list-style-type:none;
				width:100%;
			}
			li{
				position:relative;
				float:left;
				width:100%;
			}
			#form li div.input
			{
				width:200px;
			}
			#form li div.input input,
			#form li div.input textarea,
			#form li div.input select
			{
				width:180px;
			}
			#form li div.input input.checkbox
			{
				width:auto;
			}
			#form li div.label
			{
				width:350px;
			}
		
		</style>
	</head>
	<body>
<?php



$rules = array();



echo '<form id="form" method="post" action = "global_values.php">';

foreach($GV as $section)
{
	echo '<div style="">';
	echo '<h1>'.$section['section'].'</h1>';
	echo '<ul>';
	foreach($section['vars'] as $value)
	{
			$readonly = false;
			if(isset($value['readonly']) && $value['readonly'] === true)
				$readonly = true;
				
			$currentValue = '';
			$input = '';
			eval("\$currentValue = defined('".$value['name']."') ? ".$value['name']." : (isset(\$value['default']) ? \$value['default'] : null) ;");
					
			switch($value['type'])
			{
				
				case 'boolean':
					$input = '
					<input class="checkbox" '.($readonly ? 'readonly="readonly"' : '').' '.( $currentValue === false	? 'checked="selected"' : '' ).' type="radio"  name="'.$value['name'].'" value="False" id="id_'.$value['name'].'_no" /><label for="id_'.$value['name'].'_no">False</label>
					<input class="checkbox" '.($readonly ? 'readonly="readonly"' : '').' '.( $currentValue === true	? 'checked="checked"' : '' ).' type="radio"  name="'.$value['name'].'" value="True" id="id_'.$value['name'].'_yes" /><label for="id_'.$value['name'].'_yes">True</label>
					';
					break;
				case 'string':
					$input = '<input '.($readonly ? 'readonly="readonly"' : '').' name="'.$value['name'].'" id="id_'.$value['name'].'" type="text" value="'.str_replace('"','\"',stripslashes($currentValue)).'"/>';
					break;
				case 'text':
					$input = '<textarea '.($readonly ? 'readonly="readonly"' : '').' name="'.$value['name'].'" id="id_'.$value['name'].'">'.str_replace('"','\"',stripslashes($currentValue)).'</textarea>';
					break;
				case 'enum':
					$input = '<select '.($readonly ? 'readonly="readonly"' : '').' name="'.$value['name'].'" id="id_'.$value['name'].'">';
						if(isset($value['avalaible']) && is_array($value['avalaible']))	
						{
							foreach($value['avalaible'] as $k=>$v)
								$input .= '<option value="'.$k.'" '.( $currentValue === $k	? 'selected="selected"' : '' ).'>'.$v.'</option>';
						}
						else
						{
							echo '<p style="color:red;">erreur avec la valeur '.$value['name'].'</p>';
						}
					$input .= '</select>';
					break;
				case 'list':
					
					break;
				case 'integer':
					$input .= '<input '.($readonly ? 'readonly="readonly"' : '').' name="'.$value['name'].'" id="id_'.$value['name'].'" type="text" value="'. $currentValue .'"/>';
					break;
				case 'password':
					$input .= '<input '.($readonly ? 'readonly="readonly"' : '').' name="'.$value['name'].'" id="id_'.$value['name'].'" type="password" value="'.str_replace('"','\"',stripslashes($currentValue)).'"/>';
					break;
				case 'timezone':
					if(trim($currentValue) === '')
					{
						$datetime = new DateTime();
						$currentValue = $datetime->getTimezone()->getName();
					}
					$input .= timezone::getForm(array('name'=>$value['name'],'id'=>'id_'.$value['name']),$currentValue);
					break;
				default:
					break;
				
				
				
				
			}
			
			eval('$isnew = defined("'.$value['name'].'");');
			echo '	<li>
						<div class="input">'.$input.'</div>
						<div class="label"><span class="NEW">'.($isnew===false?'NEW':'').'</span><label for="id_'.$value['name'].'">'.$value['comment'].'</label></div>
					</li>';
			if(isset($value['required']))
			{
				$rules[$value['name']]		= array('required'=>$value['required']);
				$messages[$value['name']]	= array('required'=>'Ce champ est requis !');
			}
		
	}
	
	echo '</ul>';

	if(isset($section['javascript']))
	{
		echo "<div><input type='button' onclick='".$section['javascript']."(this);' value='Tester'/></div>";
	}
	
	echo '</div>';
	
}


$JS = '$(document).ready(function() {
	// validate signup form on keyup and submit
	$("#form").validate({
		rules: '.p4string::jsonencode($rules).',
		messages: '.p4string::jsonencode($messages).',
		errorPlacement: function(error, element) {
		error.prependTo( element.parent().next() );
		}
	});
	});
';


?>
<input type="submit" value="<?php echo _('boutton::valider')?>"/>
</form>
<script type='text/javascript'>
	<?php echo $JS?>
</script>
</body>
</html>

