<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
$lng = $session->locale;

$request = httpRequest::getInstance();
$parm = $request->get_parms('error','sent', 'token','form_password', 'form_password_confirm','mail');
$conn = connection::getInstance();

$needed = array();
			
if(isset($parm["mail"]) && trim($parm["mail"])!="" )
{
	if(!p4string::checkMail($parm["mail"]))
	{
			header('Location: /login/forgotpwd.php?error=mail');
			die();
	}
	
	if($conn)
	{
		$sql = "SELECT usr_id, usr_login FROM usr WHERE usr_mail='".$conn->escape_string($parm['mail'])."' and usr_login not like '(#deleted_%' AND invite='0' AND usr_login != 'autoregister'";
		
		$url = $ulogin = false;
		
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$date = phraseadate::format_mysql(new DateTime('1 day'));
				$ulogin = $row['usr_login'];
				$url = random::getUrlToken('password',$row['usr_id'],$date);
			}
		}
		
		if($url !== false)				
		{
			$url = GV_ServerName.'/login/forgotpwd.php?token='.$url;
			if(	mail::forgot_passord($parm['mail'],$ulogin,$url) === true )
			{
					header('Location: /login/forgotpwd.php?sent=ok');
					die();
			}
			else
			{
				header('Location: /login/forgotpwd.php?error=mailserver');
					die();
			}
		}
		else
		{
				header('Location: /login/forgotpwd.php?error=mailserver');
				die();
		}
	}
	else
	{
		header("Location: /login/?error=base");
		exit();
	}
	die();
}
if(isset($parm['token']) && isset($parm['form_password']) && isset($parm['form_password_confirm']))
{
			if($parm['form_password'] !== $parm['form_password_confirm'])
				$needed['form_password'] = $needed['form_password_confirm'] = _('forms::les mots de passe ne correspondent pas');
			elseif(strlen(trim($parm['form_password']))<5)
				$needed['form_password'] = _('forms::la valeur donnee est trop courte');
			elseif(trim($parm['form_password']) != str_replace(array("\r\n","\n","\r","\t"," "),"_",$parm['form_password']))
				$needed['form_password'] = _('forms::la valeur donnee contient des caracteres invalides');
				
			if(count($needed) == 0)
			{
				$sql = 'UPDATE usr SET usr_password = "'.$conn->escape_string(hash('sha256',$parm['form_password_confirm'])).'" WHERE usr_id=(SELECT usr_id FROM tokens WHERE value="'.$conn->escape_string($parm['token']).'")';
				if($conn->query($sql))
				{
					$conn->query('DELETE FROM tokens WHERE value="'.$conn->escape_string($parm['token']).'"');
					header('Location: index.php?confirm=password-update-ok');
					exit();
				}
			}
}

phrasea::headers();

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head> 
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
		<link type="text/css" rel="stylesheet" href="/login/home.css" />
		<title><?php echo _('admin::compte-utilisateur changer mon mot de passe');?></title>
	</head>
	<body >
		<div style="width:950px;margin:0 auto;">
			<div style="margin-top:70px;height:35px;">
				<table style="width:100%;">
					<tr style="height:35px;">
						<td style="width:auto;"><div style="font-size:28px;color:#b1b1b1;"><?php echo GV_homeTitle?></div></td>
						<td style="color:#b1b1b1;text-align:right;">
						</td>
					</tr>
				</table>
			</div>
			<div style="height:530px;background-color:#525252;">
				<div id="id-main" class="tab-content" style="display:block;">
					<div style="width:560px;float:left;height:490px;">
						<img src="/skins/icons/home.jpg" style="margin: 85px 10px; width: 540px;"/>
					</div>
					<div style="width:360px;float:right;height:490px;">
						<div style="margin:60px 25px">	
						<?php
						$tokenize = false;
						if($parm['token'] !== null)
						{
							$sql = 'SELECT id FROM tokens WHERE value="'.$conn->escape_string($parm['token']).'"';
							if($rs = $conn->query($sql))
							{
								if($conn->num_rows($rs) > 0)
								{
									$tokenize = true;
									
									?>
									<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
									<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.js"></script>
									<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.password.js"></script>
									
									
									<script type="text/javascript" >
										<?php 
											$rules = 'form_password_confirm:{required:true}';
											$msg = '
											form_password_confirm : {equalTo:"'._('forms::les mots de passe ne correspondent pas').'"}';
												
														?>
											$(document).ready(function() {
		
												$.validator.passwordRating.messages = {
														"similar-to-username": "<?php echo _('forms::le mot de passe est trop similaire a l\'identifiant');?>",
														"too-short": "<?php echo _('forms::la valeur donnee est trop courte')?>",
														"very-weak": "<?php echo _('forms::le mot de passe est trop simple')?>",
														"weak": "<?php echo _('forms::le mot de passe est trop simple')?>",
														"good": "<?php echo _('forms::le mot de passe est bon')?>",
														"strong": "<?php echo _('forms::le mot de passe est tres bon')?>"
													}
												
												$("#password-reset").validate(
														{
															rules: {
																<?php echo $rules?>
															},
															messages: {
																<?php echo $msg?>
															},
															errorPlacement: function(error, element) {
																error.prependTo( element.parent().next() );
															}
														}
											);
		
											$('#form_password').rules("add",{password: "#form_login"});
											$('#form_password_confirm').rules("add",{equalTo: "#form_password"});
											$("#form_password").valid();
											
										});
									</script>
									
									<form name="send" action="/login/forgotpwd.php" method="post" id="password-reset">
										
										<div>
											<label for="form_password"><?php echo _('admin::compte-utilisateur nouveau mot de passe') ?> :</label>
										</div>
										<div class="form_input">
											<input autocomplete="off" type="password" value="" id="form_password" name="form_password"/>
										</div>
										<div class="form_alert">
											<?php echo isset($needed['form_password']) ? $needed['form_password'] : '' ; ?>
											<div class="password-meter">
												<div class="password-meter-message">&nbsp;</div>
												<div class="password-meter-bg">
													<div class="password-meter-bar"></div>
												</div>
											</div>
										</div>
										<div style="margin-top:40px;">
											<label for="form_password" ><?php echo _('admin::compte-utilisateur confirmer le mot de passe') ?> :</label>
										</div>
										<div class="form_input">
											<input autocomplete="off" type="password" value="" id="form_password_confirm" name="form_password_confirm"/>
										</div>
										<div class="form_alert">
											<?php echo isset($needed['form_password_confirm']) ? $needed['form_password_confirm'] : '' ; ?>
										</div>
										<div style="margin-top:10px;">
											<input type="hidden" value="<?php echo $parm['token']; ?>" name="token"/>
											<input type="submit" value="valider"/>
										</div>
									</form>
									
									<?php 
									
								}
							}
							if(!$tokenize)
							{
								$parm['error'] = 'token';	
							}
						}
						if(!$tokenize)
						{
						
							if($parm['error'] !== null)
							{
								switch($parm['error'])
								{
									case 'mailserver':
										echo '<div style="background:#00a8FF;">'._('phraseanet::erreur: Echec du serveur mail').'</div>';
									break;
									case 'noaccount':
										echo '<div style="background:#00a8FF;">'._('phraseanet::erreur: Le compte n\'a pas ete trouve').'</div>';
									break;
									case 'mail':
										echo '<div style="background:#00a8FF;">'._('phraseanet::erreur: Echec du serveur mail').'</div>';
									break;
									case 'token':
										echo '<div style="background:#00a8FF;">'._('phraseanet::erreur: l\'url n\'est plus valide').'</div>';
									break;
								}
								
							}
							if($parm['sent'] !== null)
							{
								switch($parm['sent'])
								{
									case 'ok':
										echo '<div style="background:#00a8FF;">'._('phraseanet:: Un email vient de vous etre envoye').'</div>';
									break;
								}
								
							}
						?>
							<form name="send" action="/login/forgotpwd.php" method="post" >
								
								<div style="margin-top:20px;font-size:16px;font-weight:bold;">
										<?php echo _('login:: Forgot your password')?>
								</div>
								<div style="margin-top:20px;">
										<?php echo _('login:: Entrez votre adresse email')?>
								</div>
								<div style="margin-top:20px;">
									<input name="mail" type="text" style="width:100%">
								</div>
								<div style="margin-top:10px;">
									<input type="submit" value="<?php echo _('boutton::valider');?>"/>
								</div>
							</form>
						<?php 
						}
						?>
							<div style="margin-top:40px;">
								<a class="link" href="index.php" target="_self"><?php echo _('login:: Retour a l\'accueil');?></a>
							</div>
						</div>
						
					</div>
				</div>
				<div style="text-align:right;position:relative;margin:18px 10px 0 0;font-size:10px;font-weight:normal;"><span>&copy; Copyright Alchemy 2005-<?php echo date('Y')?></span></div>
			</div>
		</div>
	</body>
</html>

