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


$request = httpRequest::getInstance();
$parm = $request->get_parms("act", "p0","p1", 'flush_cache', 'sudo', 'admins', 'email');



if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
{
	phrasea::headers(403);
}

$user = user::getInstance($session->usr_id);
if(!$user->is_admin)
{
	phrasea::headers(403);
}


$cache_flushed = false;
if($parm['flush_cache'] && GV_use_cache)
{
	$cache = cache::getInstance();
	
	if($cache->is_ok())
	{
		if($cache->flush() === true)
			$cache_flushed = true;
	}
}
?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
		<script type="text/javascript" src="/include/jslibs/jquery-1.4.4.js"></script>
		<style type="text/css">
		body
		{
		}
		A,  A:link, A:visited, A:active
		{
			
			color : #000000; 
			padding-bottom : 15px;
			text-decoration:none;
		}
		
		A:hover
		{ 
			COLOR : #ba36bf;
			text-decoration:underline;
		}
		
		
			h1{
				position:relative;
				float:left;
				width:100%;
			}
			ul{
				position:relative;
				float:left;
				width:360px;
				list-style-type:none;
				padding:0 0 0 40px;
				margin:5px 0;
			}
			li{
				margin:5px 0;
				padding:5px 5px 5px 30px;
				border:1px solid #404040;
				background-image:url(/skins/icons/ok.png);
				background-repeat:no-repeat;
				background-position:5px center;
			}
			li.non-blocker{
				background-image:url(/skins/icons/alert.png);
			}
			li.blocker{
				background-image:url(/skins/icons/delete.png);
			}
			tr.even{
				background-color:#CCCCCC;
			}
		</style>
	</head>
	<body>
<?php

if($parm['sudo'])
{
	if($parm['sudo'] == '1')
	{
		user::reset_sys_admins_rights();
	}
}

if($parm['admins'])
{
	$admins = array();
	
	foreach($parm['admins'] as $a)
	{
		if(trim($a) == '')
			continue;
		
		$admins[] = $a;
	}
	
	if(!in_array($session->usr_id,$admins))
		$admins[] = $session->usr_id;
	
	if($admins > 0)
	{
		user::set_sys_admins($admins);
		user::reset_sys_admins_rights();
	}
}

if($cache_flushed)
{
?>
<div>
	<?php echo _('admin::Le serveur memcached a ete flushe');?>
</div>
<?php 
}
?>
<div>
<h1><?php echo _('setup:: administrateurs de l\'application') ?></h1>
<form action="sitestruct.php" method="post">
	<?php 
	
	$admins = user::get_sys_admins();
	
	foreach($admins as $usr_id=>$usr_login)
	{
		?>
		<div><input name="admins[]" type="checkbox" value="<?php echo $usr_id?>" id="adm_<?php echo $usr_id?>" checked /><label for="adm_<?php echo $usr_id?>"><?php echo $usr_login;?></label></div>
		<?php
	}
	?>
	<div><?php echo _('setup:: ajouter un administrateur de l\'application') ?></div>
	
	<?php 
	
	$elligible = user::get_simple_users_list();
	
	?>
	<select name="admins[]">
		<option value=""><?php echo _('choisir');?></option>
		<?php
		foreach($elligible as $usr_id=>$usr_login)
		{
			?>
			<option value="<?php echo $usr_id?>"><?php echo $usr_login;?></option>
			<?php
		}
		?>
	</select>
	<input type="submit" value="<?php echo _('boutton::valider') ?>" />
</form>
<h1><?php echo _('setup:: Reinitialisation des droits admins') ?></h1>

<form action="sitestruct.php" method="post">
	<input type="hidden" name="sudo" value="1" />
	<input type="submit" value="<?php echo _('boutton::reinitialiser') ?>" />
</form>
</div>
<h1><?php echo _('setup:: Reglages generaux') ?></h1>
<br>
<a href="paramchu.php?p0=<?php echo $parm['p0']?>&p1=<?php echo $parm['p1']?>" target="_self"><?php echo _('admin:: modifier les parametres de publication des paniers')?></a>
<br>
<?php 
try{
	$invite = new user('invite');
}
catch(Exception $e)
{
	$invite = user::create_special('invite');
}

	?>
<div><a href="editusr.php?ord=asc&p2=<?php echo $invite->id ?>"><?php echo _('Reglages:: reglages d acces guest');?></a></div>
	<?php 

try{
	$autoregister = new user('autoregister');
}
catch(Exception $e)
{
	$autoregister = user::create_special('autoregister');
}
if($autoregister !== false)
{
	?>
<div><a href="editusr.php?ord=asc&p2=<?php echo $autoregister->id ?>"><?php echo _('Reglages:: reglages d inscitpition automatisee');?></a></div>
	<?php 
}

?>
<h2><?php echo _('setup::Votre configuration')?></h2>
<div>
	<div style="position:relative;float:left;width:400px;">
		<?php 
		setup::check_mail_form();
		
		if($parm['email'])
		{
			echo 'result : ' ; var_dump(mail::mail_test($parm['email']));
		}
		?>
		<?php 
		setup::check_php_version();
		?>
		<?php 
		setup::check_writability();
		?>
		<?php 
		setup::check_binaries();
		?>
		<?php 
		setup::check_php_extension();
		?>
	</div>
	<div style="position:relative;float:left;width:400px;margin-left:25px;">
		<?php 
		setup::check_phrasea();
		?>
		<?php 
		setup::check_apache();
		?>
		<?php 
		setup::check_mod_auth_token();
		?>
		<?php 
		setup::check_cache_opcode();
		?>
		<?php 
		setup::check_cache_memcache();
		
		if(GV_use_cache)
		{
			$cache = cache::getInstance();
			
			if($cache->is_ok())
			{
				?>
				<form method="post" action="sitestruct.php">
					<input type="hidden" name="flush_cache" value="1"/>
					<input type="submit" value="Flush Memcached"/>
				</form>
				<?php 
			}
		}
		?>
		<?php 
		setup::check_php_configuration();
		?>
	</div>
</div>
</body>
</html>
