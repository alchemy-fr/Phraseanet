<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require(GV_RootPath.'lib/geonames.php');
require_once(GV_RootPath.'lib/inscript.api.php');
$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms('code', 'token');

$lng = isset($session->locale)?$session->locale:GV_default_lng;

$conn = connection::getInstance();
if(!$conn)
{
	header('Location: /login/index.php?error=no-connection');
	exit;
}

$updated = $error = false;

if(!is_null($parm['token']))
{
	$sql = 'SELECT usr_id, datas FROM tokens WHERE value="'.$conn->escape_string($parm['token']).'"';
	if($rs = $conn->query($sql))
	{
		if($conn->num_rows($rs) == 1)
		{
			if($row = $conn->fetch_assoc($rs))	
			{
				$new_mail = $row['datas'];
				$the_usr = $row['usr_id'];
				
				$sql = 'UPDATE usr SET usr_mail="'.$conn->escape_string($new_mail).'" WHERE usr_id="'.$conn->escape_string($the_usr).'"';
				if($conn->query($sql))
				{
					$sql = 'DELETE FROM tokens WHERE value="'.$conn->escape_string($parm['token']).'"';
					$conn->query($sql);
					
					phrasea::headers();
					?>
<html lang="<?php echo $session->usr_i18n;?>">
					<head><title><?php echo GV_homeTitle?> - <?php echo _('admin::compte-utilisateur changer mon mot de passe')?></title>
					
					<link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
					<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
					<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
					<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.js"></script>
					</head>
					<body>
						<div style="width:950px;margin-left:auto;margin-right:auto;">
							<div style="margin-top:70px;height:35px;">
								<table style="width:100%;">
									<tr style="height:35px;">
										<td><span class="title-name"><?php echo GV_homeTitle?></span><span class="title-desc"><?php echo _('admin::compte-utilisateur changer mon mot de passe')?></span></td>
									</tr>
								</table>
							</div>
							<div style="height:530px;" class="tab-pane">
								<div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">
								
									<div style="margin-top:100px;"><?php echo _('admin::compte-utilisateur: L\'email a correctement ete mis a jour')?></div>
									<a href="/" target="_self"><?php echo _('accueil:: retour a l\'accueil'); ?></a>
								</div>
							</div>
						</div>
					</body>
					</html>
					<?php
					exit();
				}
			}
		}
	}
}

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

if(!isset($session->account_editor) || !$session->account_editor)
{
	
	exit();
}

$parm = $request->get_parms('form_password', 'form_email', 'form_email_confirm');

if(isset($parm['form_password']) && isset($parm['form_email']) && isset($parm['form_email_confirm']))
{
	$sql = 'SELECT usr_id FROM usr WHERE usr_id="'.$conn->escape_string($usr_id).'" AND usr_password="'.$conn->escape_string(hash('sha256',$conn->escape_string($parm["form_password"]))).'"';
	if($rs = $conn->query($sql))
	{
		if($conn->num_rows($rs) == 1)
		{
			if(str_replace(array("\r\n","\r","\n","\t"),'_',trim($parm['form_email'])) == $parm['form_email_confirm'])
			{
				if(p4string::checkMail($parm['form_email']))
				{
					if(mail::reset_email($parm['form_email'], $usr_id)===true)
						$updated = true;
					else
						$error = _('phraseanet::erreur: echec du serveur de mail');
				}
				else
					$error = _('forms::l\'email semble invalide');
			}
			else
				$error = _('forms::les emails ne correspondent pas');
		}
		else
			$error =  _('admin::compte-utilisateur:ftp: Le mot de passe est errone');
	}
}
phrasea::headers();
?>
<head>
	<title><?php echo GV_homeTitle?> - <?php echo _('admin::compte-utilisateur changer mon mot de passe')?></title>
	<link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
	<link rel="icon" type="image/png" href="/favicon.ico" />
	<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
	<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.js"></script>
	<script type="text/javascript">
	$(document).ready(function() {
		$("#mainform").validate(
				{
					rules: {
						form_password : {
							required:true
						},
						form_email : {
							required:true,
							email:true
						},
						form_email_confirm : {
							required:true,
							equalTo:'#form_email'
						}
					},
					messages: {
						form_password : {
							required :  "<?php echo str_replace('"','\"',_('forms::ce champ est requis'))?>"
						},
						form_email : {
							required :  "<?php echo str_replace('"','\"',_('forms::ce champ est requis'))?>",
							email:"<?php echo str_replace('"','\"',_('forms::l\'email semble invalide'))?>"
						},
						form_email_confirm : {
							required :  "<?php echo str_replace('"','\"',_('forms::ce champ est requis'))?>",
							equalTo :  "<?php echo str_replace('"','\"',_('forms::les emails ne correspondent pas'))?>"
						}
						
					},
					errorPlacement: function(error, element) {
						error.prependTo( element.parent().next() );
					}
				}
		);
	});
	</script>
</head>

<body>
	<div style="width:950px;margin-left:auto;margin-right:auto;">
		<div style="margin-top:70px;height:35px;">
			<table style="width:100%;">
				<tr style="height:35px;">
					<td><span class="title-name"><?php echo GV_homeTitle?></span><span class="title-desc"><?php echo _('admin::compte-utilisateur changer mon mot de passe')?></span></td>
				</tr>
			</table>
		</div>
		<div style="height:530px;" class="tab-pane">
			<div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">
				<?php
				if($updated)
				{
					?><div style="margin:200px 0 0;"><?php
					echo _('admin::compte-utilisateur un email de confirmation vient de vous etre envoye. Veuillez suivre les instructions contenue pour continuer');
					?>
					</div>
					<div>
						<a href="/login/account.php" target="_self" class="link"><?php echo _('admin::compte-utilisateur retour a mon compte')?></a>
					</div>
					<?php
				}
				else
				{
					if($error)
					{
						?>
						<div class="notice" style="text-align:center;margin:20px 0"><?php echo _('phraseanet::erreur : oups ! une erreur est survenue pendant l\'operation !')?></div>
						<div class="notice" style="text-align:center;margin:20px 0"><?php echo $error?></div>
						<?php
					}
					?>
					<form method="post" action="/login/reset-email.php" id="mainform">
						<table style="margin:70px  auto 0;">
							<tr>
								<td class="form_label"><label for="form_login"><?php echo _('admin::compte-utilisateur identifiant')?></label></td>
								<td class="form_input"><?php echo $session->login?></td>
								<td class="form_alert"></td>
							</tr>
							<tr>
								<td class="form_label"><label for="form_password"><?php echo _('admin::compte-utilisateur mot de passe')?></label></td>
								<td class="form_input"><input autocomplete="off" type="password" name="form_password" id="form_password"/></td>
								<td class="form_alert"><?php echo isset($needed['form_password'])?$needed['form_password']:''?></td>
							</tr>
							<tr style="height:10px;">
								<td colspan="3"></td>
							</tr>
							<tr>
								<td class="form_label"><label for="form_email"><?php echo _('admin::compte-utilisateur nouvelle adresse email')?></label></td>
								<td class="form_input"><input type="text" name="form_email" id="form_email"/></td>
								<td class="form_alert"><?php echo isset($needed['form_email'])?$needed['form_email']:''?></td>
							</tr>
							<tr>
								<td class="form_label"><label for="form_email_confirm"><?php echo _('admin::compte-utilisateur confirmer la nouvelle adresse email')?></label></td>
								<td class="form_input"><input autocomplete="off" type="text" name="form_email_confirm" id="form_email_confirm"/></td>
								<td class="form_alert"><?php echo isset($needed['form_email_confirm'])?$needed['form_email_confirm']:''?></td>
							</tr>
						</table>
						<input type="submit" style="margin:20px auto;">
						<input type="button" value="Cancel" onclick="self.location.replace('account.php');">
					<form>
					<div>
						<div><?php echo _('admin::compte-utilisateur: Pourquoi me demande-t-on mon mot de passe pour changer mon adresse email ?')?></div>
						<div><?php echo _('admin::compte-utilisateur: Votre adresse e-mail sera utilisee lors de la perte de votre mot de passe afin de pouvoir le reinitialiser, il est important que vous soyez la seule personne a pouvoir la changer.')?></div>
					</div>
				<?php
				}
				?>
			</div>
		</div>
	</div>
</body>