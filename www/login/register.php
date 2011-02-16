<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

	
require(GV_RootPath.'lib/countries.php');
require_once(GV_RootPath.'lib/inscript.api.php');
require(GV_RootPath.'lib/geonames.php');

$session = session::getInstance();
$register_enabled = login::register_enabled();

if(!$register_enabled)
{
	header('Location: /login/index.php?no-register-avalaible');
	exit;	
}


$request = httpRequest::getInstance();


$lng = $session->locale;

$conn = connection::getInstance();
if(!$conn)
{
	die();
}


$needed = array();

$arrayVerif = array("form_login"=>true,
	 "form_password"=>true,
	 "form_password_confirm"=>true,
	 "form_gender"=>true,
	 "form_lastname"=>true,
	 "form_firstname"=>true,
	 "form_email"=>true,
	 "form_job"=>true,
	 "form_company" =>true,  
	 "form_activity"=>true,
	 "form_phone"=>true,
	 "form_fax"=>true,
	 "form_address"=>true,
	 "form_zip"=>true,
	 "form_geonameid"=>true,
	 "demand"=>true);

if(is_file(GV_RootPath.'config/register-fields.php'))
	include(GV_RootPath.'config/register-fields.php');

$arrayVerif['form_login'] = true;
$arrayVerif['form_password'] = true;
$arrayVerif['form_password_confirm'] = true;
$arrayVerif['demand'] = true;
if(GV_needMail) 
	$arrayVerif['form_email'] = true;
	
$lstreceiver = array();
	
$parm = $request->get_parms("form_login", "form_password", "form_city", "form_password_confirm", 
"form_gender","form_lastname","form_firstname","form_email","form_job", "form_company" , 'demand',
"form_activity","form_phone","form_fax","form_address","form_zip","form_geonameid","demand");

if($request->has_post_datas())
{
	
	$needed = array_diff_key($arrayVerif,$request->get_post_datas());
	
	if(sizeof($needed)===0 || (sizeof($needed)===1 && isset($needed['form_login']) && $needed['form_login']===true && GV_needMail))
	{
	
		foreach($parm as $field=>$value)
		{
			if(is_string($value) && isset($arrayVerif[$field]))
			{
				if(trim($value) == '' )
					$needed[$field] = _('forms::ce champ est requis');
			}
			
		}
		
		// 1 - on verifie les password
		if($parm['form_password'] !== $parm['form_password_confirm'])
			$needed['form_password'] = $needed['form_password_confirm'] = _('forms::les mots de passe ne correspondent pas');
		elseif(strlen(trim($parm['form_password']))<5)
			$needed['form_password'] = _('forms::la valeur donnee est trop courte');
		elseif(trim($parm['form_password']) != str_replace(array("\r\n","\n","\r","\t"," "),"_",$parm['form_password']))
			$needed['form_password'] = _('forms::la valeur donnee contient des caracteres invalides');
		
		//2 - on verifie que lemail a lair correcte si elle est requise ou si GV_needMail
		if(trim($parm['form_email'])!='' && !p4string::checkMail($parm['form_email']))
			$needed['form_email'] = _('forms::l\'email semble invalide');
			
		//on verifie le login
		if(!GV_needMail && strlen($parm['form_login'])<5)
			$needed['form_login'] = _('forms::la valeur donnee est trop courte');
			
			
			
		if(sizeof($needed)===1 && isset($needed['form_login']) && $needed['form_login']===true && GV_needMail)
		{
				unset($needed['form_login']);
				$parm['form_login'] = $parm['form_email'];
		}				
		
		$sql = 'SELECT usr_id FROM usr WHERE (usr_mail = "'.$conn->escape_string(mb_strtolower(trim($parm['form_email']))).'") AND usr_login NOT LIKE "(#deleted%"';
		if(mb_strtolower(trim($parm['form_email']))!='' && ($rs = $conn->query($sql)))
		{
			if(($n = ($conn->num_rows($rs))) !== false)
			{
				if($n>0)
					$needed['form_email'] = _('forms::un utilisateur utilisant cette adresse email existe deja');
			}
		}
			
		$sql = 'SELECT usr_id FROM usr WHERE (usr_login="'.$conn->escape_string(trim($parm['form_login'])).'") AND usr_login NOT LIKE "(#deleted%"';
		if($rs = $conn->query($sql))
		{
			if(($n = ($conn->num_rows($rs))) !== false)
			{
				if($n>0)
					$needed['form_login'] = _('forms::un utilisateur utilisant ce login existe deja');
			}
			else
				exit('error db3');
		}
		else
			exit('error db4');
		
		if(!isset($parm['demand']) ||  sizeof($parm['demand']) === 0)
			$needed['demandes'] = true;	
		
	
		//4 on verifieles demandes
		
		//5 on insere l'utilisateur
		
		$inscriptions = giveMeBases();
		$inscOK = array();
			
		if(sizeof($needed) === 0)
		{
			$newUsrEmail = (trim($parm['form_email'])!='')?$parm['form_email']:false;
			$lb = phrasea::bases();	
				
			foreach($lb["bases"] as $oneBase)
			{	
				$mailsBas = array() ;
				
				if(isset($oneBase["xmlstruct"]))
				{
								
					$mailColl = array();
					foreach($oneBase["collections"] as $oneColl )
					{
						if(!in_array($oneColl['base_id'],$parm['demand']))
							continue;
						
						if(isset($inscriptions[$oneBase['sbas_id']]) && $inscriptions[$oneBase['sbas_id']]['inscript'] === true && (isset($inscriptions[$oneBase['sbas_id']]['Colls'][$oneColl['coll_id']]) || isset($inscriptions[$oneBase['sbas_id']]['CollsCGU'][$oneColl['coll_id']])))
							$inscOK[$oneColl['base_id']] = true;
						else
							$inscOK[$oneColl['base_id']] = false;
						
					}
				}
			}
			

			$newid = $conn->getId("USR");
			
			if($newid)
			{
				$fieldsname = "usr_id"; 							$fieldsvalue = "$newid";
				$fieldsname.= ",usr_sexe"; 					$fieldsvalue.= ",".$parm['form_gender'];
				$fieldsname.= ",usr_nom"; 						$fieldsvalue.= ",'".$conn->escape_string($parm["form_firstname"])."'";
				$fieldsname.= ",usr_prenom"; 				$fieldsvalue.= ",'".$conn->escape_string($parm["form_lastname"])."'";
				$fieldsname.= ",usr_login"; 					$fieldsvalue.= ",'".$conn->escape_string($parm["form_login"])."'";
				$fieldsname.= ",usr_password"; 				$fieldsvalue.= ",'".$conn->escape_string(hash('sha256',$parm["form_password"]))."'";
				
				$fieldsname.= ",usr_mail"; 						$fieldsvalue.= ",".(trim($parm["form_email"]) != '' ? "'".$conn->escape_string($parm["form_email"])."'" : 'null')."";
				
				if(GV_needMail)
				{	$fieldsname.= ",mail_locked"; 				$fieldsvalue.= ",'1'";}
				
				$fieldsname.= ",usr_creationdate"; 			$fieldsvalue.= ",NOW()";
				$fieldsname.= ",usr_modificationdate";	$fieldsvalue.= ",NOW()";
				$fieldsname.= ",adresse"; 						$fieldsvalue.= ",'".$conn->escape_string($parm["form_address"])."'";
				$fieldsname.= ",cpostal";	 					$fieldsvalue.= ",'".$conn->escape_string($parm["form_zip"])."'";
				$fieldsname.= ",tel"; 								$fieldsvalue.= ",'".$conn->escape_string($parm["form_phone"])."'";
				$fieldsname.= ",fax"; 								$fieldsvalue.= ",'".$conn->escape_string($parm["form_fax"])."'";
				$fieldsname.= ",fonction"; 						$fieldsvalue.= ",'".$conn->escape_string($parm["form_job"])."'";
				$fieldsname.= ",societe"; 						$fieldsvalue.= ",'".$conn->escape_string($parm["form_company"])."'";
				$fieldsname.= ",activite"; 						$fieldsvalue.= ",'".$conn->escape_string($parm["form_activity"])."'";
				$fieldsname.= ",issuperu"; 						$fieldsvalue.= ",0";
				$fieldsname.= ",code8"; 							$fieldsvalue.= ",0";
				$fieldsname.= ",geonameid"; 							$fieldsvalue.= ",'".$conn->escape_string($parm["form_geonameid"])."'";
				$fieldsname.= ",model_of"; 					$fieldsvalue.= ",0";
		
				$sql = "INSERT INTO usr (".$fieldsname.") VALUES (".$fieldsvalue.")";
		
				if($conn->query($sql))
				{
					//user cree, je branche autoregister si ya	
					$autoSB = $autoB = array();
				
					$sqlSB = 'SELECT sb.* from sbasusr sb, usr u WHERE sb.usr_id = u.usr_id AND u.usr_login="autoregister"';
					if(GV_autoregister && ($rsSB = $conn->query($sqlSB)))
					{
						while($rowSB = $conn->fetch_assoc($rsSB))
						{
							$autoSB[$rowSB['sbas_id']] = $rowSB;
						}
					}
					$sqlB = 'SELECT b.* from basusr b, usr u WHERE b.usr_id = u.usr_id AND u.usr_login="autoregister"';
					if(GV_autoregister && ($rsB = $conn->query($sqlB)))
					{
						while($rowB = $conn->fetch_assoc($rsB))
						{
							$autoB[$rowB['base_id']] = $rowB;
						}
					}
					
					$autoReg = $demandOK = array();
					$setRegisModel = false;
					
					foreach($parm['demand'] as $base_id)
					{
						if($inscOK[$base_id] && !isset($autoReg[$base_id]) && !isset($demandOK[$base_id]))
						{
							$sbasid = phrasea::sbasFromBas($base_id);
							if(isset($autoSB[$sbasid]))
							{
								
								if(!$setRegisModel)
								{
									$sql = 'UPDATE usr  SET lastModel = "autoregister" WHERE usr_id = "'.$conn->escape_string($newid).'"';
									$conn->query($sql);
									$setRegisModel = true;
								}
								
								
								$sql = 'INSERT INTO sbasusr
								 (sbas_id,usr_id,bas_manage,bas_modify_struct,bas_modif_th,bas_chupub) VALUES 
								 ("'.$conn->escape_string($sbasid).'","'.$conn->escape_string($newid).'","'.$conn->escape_string($autoSB[$sbasid]['bas_manage']).'","'.$conn->escape_string($autoSB[$sbasid]['bas_modify_struct']).'","'.$conn->escape_string($autoSB[$sbasid]['bas_modif_th']).'","'.$conn->escape_string($autoSB[$sbasid]['bas_chupub']).'")';

								$conn->query($sql);
								
								unset($autoSB[phrasea::sbasFromBas($base_id)]);
							}
							if(isset($autoB[$base_id]))
							{
								
								$sql = "INSERT INTO basusr (SELECT null as id, base_id, '".$conn->escape_string($newid)."' as usr_id, canpreview, canhd, canputinalbum, candwnldhd, candwnldsubdef, candwnldpreview, cancmd, canadmin, actif, canreport, canpush, now() as creationdate, basusr_infousr, mask_and, mask_xor, restrict_dwnld, month_dwnld_max, remain_dwnld, time_limited, limited_from, limited_to, canaddrecord, canmodifrecord, candeleterecord, chgstatus, '0000-00-00 00:00:00' as lastconn, imgtools, manage, modify_struct, bas_manage, bas_modify_struct, needwatermark FROM basusr WHERE usr_id=(SELECT usr_id FROM usr WHERE usr_login='autoregister') AND base_id='".$conn->escape_string($base_id)."')";
								$conn->query($sql);
								unset($parm['demand'][$base_id]);
								unset($autoB[$base_id]);
								$autoReg[$base_id] = true;
							}
							else
							{
								
								$sql = "INSERT INTO demand (date_modif, usr_id, base_id, en_cours, refuser) VALUES (now(), '".$conn->escape_string($newid)."' , '".$conn->escape_string($base_id)."', 1, 0)";
								$conn->query($sql);
								
								unset($parm['demand'][$base_id]);
								
								$demandOK[$base_id] = true;
							}
						}
					}
					
					$event_mngr = eventsmanager::getInstance();
					
					$params = array(
						'demand' => $demandOK
						,'autoregister'	=> $autoReg
						,'usr_id'		=> $newid
					);
					
					$event_mngr->trigger('__REGISTER_AUTOREGISTER__', $params);
					$event_mngr->trigger('__REGISTER_APPROVAL__', $params);
								
					
					if(GV_needMail && $newUsrEmail)
					{
						header('Location: /login/sendmail-confirm.php?usr_id='.$newid);
						exit;
					}
					elseif($newUsrEmail)
					{
						$others = $auto = '';
						
						foreach($autoReg as $base_id=>$isOk)
							if($isOk)
								$auto .= '<li>'.phrasea::sbas_names(phrasea::sbasFromBas($base_id)).' - '.phrasea::bas_names($base_id)."</li>\n";
								
						foreach($demandOK as $base_id=>$isOk)
							if($isOk)
								$others .= '<li>'.phrasea::sbas_names(phrasea::sbasFromBas($base_id)).' - '.phrasea::bas_names($base_id)."</li>\n";
						
						mail::register_user($newUsrEmail, $auto, $others);							
					}	
				}

				if(count($autoReg)>0 || count($demandOK)>0)
				{
					if(count($autoReg)>0)
					{
						header('Location: /login/index.php?confirm=register-ok');
						exit;
					}
					else
					{
						header('Location: /login/index.php?confirm=register-ok-wait');
						exit;
					}
				}
			}
		}
	}
}


phrasea::headers();
?>
<html lang="<?php echo $session->usr_i18n;?>">
			<head>
				<link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
				<link REL="stylesheet" TYPE="text/css" HREF="/login/geonames.css" />
				<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
				<title><?php echo GV_homeTitle?> - <?php echo _('login:: register')?></title>
				<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js"></script>
				<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.js"></script>
				<script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.password.js"></script>
				<script type="text/javascript">

					<?php
					$first = true;
					$sep = $msg = $rules = '';
					foreach($arrayVerif as $ar=>$ver)
						if($ar != 'form_password')
						{
							if(!$first)
								$sep = ',';
							$first = false;
							$rules .= $sep.$ar.':{required:true}';
							$msg .= $sep.$ar.': {';
							$msg .= 'required : "'._('forms::ce champ est requis').'"';
							
							if($ar == 'form_login' || $ar == 'form_password')
								$msg .= ' ,minlength: "'._('forms::la valeur donnee est trop courte').'"';
								
							if($ar == 'form_password')
								$msg .= ' ,minlength: "'._('forms::la valeur donnee est trop courte').'"';
							if($ar == 'form_password_confirm')
								$msg .= ' ,equalTo: "'._('forms::les mots de passe ne correspondent pas').'"';
							if($ar == 'form_email')
								$msg .= ',email:"'.(str_replace('"','\"',_('forms::l\'email semble invalide'))).'"';
							
							$msg .= '}';
						}
					
					?>
											
					$(document).ready(function() {

						$.validator.passwordRating.messages = {
								"similar-to-username": "<?php echo _('forms::le mot de passe est trop similaire a l\'identifiant')?>",
								"too-short": "<?php echo _('forms::la valeur donnee est trop courte')?>",
								"very-weak": "<?php echo _('forms::le mot de passe est trop simple')?>",
								"weak": "<?php echo _('forms::le mot de passe est trop simple')?>",
								"good": "<?php echo _('forms::le mot de passe est bon')?>",
								"strong": "<?php echo _('forms::le mot de passe est tres bon')?>"
							}
						
						$("#register").validate(
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

						$('#form_email').rules("add",{email:true});

						$('#form_login').rules("add",{
							minlength: 5
						});
						
						$('#form_password').rules("add",{password: "#form_login"});
						$('#form_password_confirm').rules("add",{equalTo: "#form_password"});

						
						$("#form_password").valid();

						initialize_geoname_field($('#form_geonameid'));
					});

				</script>
				<script type="text/javascript" src="/login/geonames.js"></script>
				</head>
				<body>
				
					<div style="width:950px;margin-left:auto;margin-right:auto;">
								<div style="margin-top:70px;height:35px;">
									<table style="width:100%;">
										<tr style="height:35px;">
											<td style="width:600px;"><span style="white-space:nowrap;font-size:28px;color:#b1b1b1;"><?php echo GV_homeTitle?> - <?php echo _('login:: register')?></span></td>
											<td></td>
											<td style="color:#b1b1b1;text-align:right;">
											<?php
											if($register_enabled)
											{
												?>
													<a href="/login/register.php" class="tab active" id="register-tab"><?php echo _('login:: register')?></a>
												<?php
											}
											?>
													<a href="/login/" class="tab" id="main-tab"><?php echo _('login:: accueil')?></a>
											</td>
										</tr>
									</table>
								</div>
								<div style="height:530px;" class="tab-pane">
									<div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">




										<form id="register" name="creation" action="/login/register.php" method="post">
					
											<table id="form_register_table" cellspacing="0" cellpadding="0" style="font-size:11px;margin:0 auto;">
												<tr style="height:10px;"> 
													<td colspan="3">
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_login">
															<?php echo (isset($arrayVerif['form_login']) && $arrayVerif['form_login']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur identifiant')?> <br/><span style="font-size:9px;"><?php echo sprintf(_('forms:: %d caracteres minimum'),5)?></span> :
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_login" autocomplete="off" type="text" value="<?php echo $parm ["form_login"]?>" class="input_element" name="form_login">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_login'])?$needed['form_login']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_password">
															<?php echo (isset($arrayVerif['form_password']) && $arrayVerif['form_password']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur mot de passe')?> <br/><span style="font-size:9px;"><?php echo  sprintf(_('forms:: %d caracteres minimum'),5)?></span> :
														</label>
													</td>
													<td class="form_input"> 
														<input autocomplete="off" type="password" value="<?php echo $parm ["form_password"]?>" class="input_element password" name="form_password" id="form_password" />

													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_password'])?$needed['form_password']:''?>
														<div class="password-meter">
															<div class="password-meter-message">&nbsp;</div>
															<div class="password-meter-bg">
																<div class="password-meter-bar"></div>
															</div>
														</div>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_password_confirm">
															<?php echo (isset($arrayVerif['form_password_confirm']) && $arrayVerif['form_password_confirm']===true)?'<span class="requiredField">*</span>':''?>	<span style="font-size:9px;">Confirmation</span> :
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_password_confirm" autocomplete="off" type="password" value="<?php echo $parm ["form_password_confirm"]?>" class="input_element" name="form_password_confirm">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_password_confirm'])?$needed['form_password_confirm']:''?>
													</td>
												</tr>
												<tr> 
													<td colspan="3">
														<hr/>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_email">
															<?php echo (isset($arrayVerif['form_email']) && $arrayVerif['form_email']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur email')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_email" autocomplete="off" type="text" value="<?php echo $parm["form_email"]?>" class="input_element" name="form_email">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_email'])?$needed['form_email']:''?>
													</td>
												</tr>
												<tr><td colspan="3">&nbsp;</td></tr>
												<tr>
													<td class="form_label">
														<label for="form_city">
															<?php echo (isset($arrayVerif['form_geonameid']) && $arrayVerif['form_geonameid']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur ville')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_geonameid" type="text" geonameid="<?php echo $parm["form_geonameid"]?>" value="<?php echo geonames::name_from_id($parm["form_geonameid"])?>" class="input_element geoname_field" name="form_geonameid">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_geonameid'])?$needed['form_geonameid']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
													<?php echo _('admin::compte-utilisateur sexe')?> : 
													</td>
													<td class="form_input"> 
														<input type="radio" class="checkbox" name="form_gender" style="width:10px;" <?php echo (($parm ["form_gender"]==0)?"checked":"")?> value="0"><?php echo _('admin::compte-utilisateur:sexe: mademoiselle')?>
														<input type="radio" class="checkbox" name="form_gender" style="width:10px;" <?php echo (($parm ["form_gender"]==1)?"checked":"")?> value="1"><?php echo _('admin::compte-utilisateur:sexe: madame')?>
														<input type="radio" class="checkbox" name="form_gender" style="width:10px;" <?php echo (($parm ["form_gender"]==2)?"checked":"")?> value="2"><?php echo _('admin::compte-utilisateur:sexe: monsieur')?>		
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_gender'])?$needed['form_gender']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_lastname">
															<?php echo (isset($arrayVerif['form_lastname']) && $arrayVerif['form_lastname']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur nom')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_lastname" autocomplete="off" type="text" value="<?php echo $parm["form_lastname"]?>" class="input_element" name="form_lastname">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_lastname'])?$needed['form_lastname']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_firstname">
															<?php echo (isset($arrayVerif['form_firstname']) && $arrayVerif['form_firstname']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur prenom')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_firstname" autocomplete="off" type="text" value="<?php echo $parm["form_firstname"]?>" class="input_element" name="form_firstname">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_firstname'])?$needed['form_firstname']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_job">
															<?php echo (isset($arrayVerif['form_job']) && $arrayVerif['form_job']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur poste')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_job" autocomplete="off" type="text" value="<?php echo $parm["form_job"]?>" class="input_element" name="form_job">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_job'])?$needed['form_job']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_activity">
															<?php echo (isset($arrayVerif['form_activity']) && $arrayVerif['form_activity']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur activite')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_activity" autocomplete="off" type="text" value="<?php echo $parm["form_activity"]?>" class="input_element" name="form_activity">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_activity'])?$needed['form_activity']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_phone">
															<?php echo (isset($arrayVerif['form_phone']) && $arrayVerif['form_phone']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur telephone')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_phone" autocomplete="off" type="text" value="<?php echo $parm["form_phone"]?>" class="input_element" name="form_phone">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_phone'])?$needed['form_phone']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_fax">
															<?php echo (isset($arrayVerif['form_fax']) && $arrayVerif['form_fax']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur fax')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_fax" autocomplete="off" type="text" value="<?php echo $parm["form_fax"]?>" class="input_element" name="form_fax">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_fax'])?$needed['form_fax']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_company">
															<?php echo (isset($arrayVerif['form_company']) && $arrayVerif['form_company']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur societe')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_company" autocomplete="off" type="text" value="<?php echo $parm["form_company"]?>" class="input_element" name="form_company">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_company'])?$needed['form_company']:''?>
													</td>
												</tr>
												<tr> 
													<td class="form_label">
														<label for="form_address">
															<?php echo (isset($arrayVerif['form_address']) && $arrayVerif['form_address']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur adresse')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_address" autocomplete="off" type="text" value="<?php echo $parm["form_address"]?>" class="input_element" name="form_address">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_address'])?$needed['form_address']:''?>
													</td>
												</tr>
												
												<tr> 
													<td class="form_label">
														<label for="form_zip">
															<?php echo (isset($arrayVerif['form_zip']) && $arrayVerif['form_zip']===true)?'<span class="requiredField">*</span>':''?> <?php echo _('admin::compte-utilisateur code postal')?> : 
														</label>
													</td>
													<td class="form_input"> 
														<input id="form_zip" autocomplete="off" type="text" value="<?php echo $parm["form_zip"]?>" class="input_element" name="form_zip">
													</td>
													<td class="form_alert">
														<?php echo isset($needed['form_zip'])?$needed['form_zip']:''?>
													</td>
												</tr>
												

												<tr> 
													<td colspan="3">
														<hr/>
													</td>				
												</tr>
											</table>
		
			
			<?php 
			if(GV_autoselectDB)
			{
				?>
				<div style="display:none;">
				<?php
			}
			?>
											<div style="width:600px;height:20px;text-align:center;margin:0 auto;"><?php echo _('admin::compte-utilisateur actuellement, acces aux bases suivantes : ')?></div>
											<div class="requiredField" style="width:600px;height:20px;text-align:center;margin:0 auto;"><?php echo isset($needed['demand'])?'Vous n\'avez selectionne aucune base':''?></div>
														
											<div style="width:600px;center;margin:0 5px;">
							
									
												<?php
												$demandes = null;
												if(is_array($parm["demand"]))
													foreach($parm["demand"] as $id)
														$demandes[$id]=true;
																
												echo giveInscript($lng,$demandes);
												?>			
											</div>
							
			<?php 
			if(GV_autoselectDB)
			{
				?>
				</div>
				<?php
			}
			?>
											<input type="hidden" value="<?php echo $lng?>" name="lng">
											<div style="margin:10px 0;text-align:center;"><input type="submit" value="<?php echo _('boutton::valider')?>"/></div>
										</form>

									</div>
									<div style="text-align:right;position:relative;margin:18px 10px 0 0;font-size:10px;font-weight:normal;"><span>&copy; Copyright Alchemy 2005-<?php echo date('Y')?></span></div>
								</div>
							</div>
			
					<script type="text/javascript">
					
						$('.tab').hover(function(){
							$(this).addClass('active');
						}, function(){
							$(this).removeClass('active');
						});
					</script>
					
				</body>
			</html>
