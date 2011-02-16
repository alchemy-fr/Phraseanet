<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$conn = connection::getInstance();
$session = session::getInstance();
$lng = $session->locale;
	
if(!$conn)
{	
	header('Location: /login/prod/');
	exit();
}

$request = httpRequest::getInstance();
$parm = $request->get_post_datas('code');

$datas = random::helloToken($parm['code']);

if($datas)
{
	random::removeToken($parm['code']);
	$usr_id = $datas['usr_id'];
	$mail = $datas['datas'];
	$sql = 'SELECT usr_id, mail_locked, usr_mail FROM usr WHERE usr_id="'.$conn->escape_string($usr_id).'" AND usr_mail="'.$conn->escape_string($mail).'"';
	if($rs = $conn->query($sql))
	{
		if($conn->num_rows($rs)>0)
		{
			if($row = $conn->fetch_assoc($rs))
			{
				if($row['mail_locked'] == 1)
				{
					$sql = 'UPDATE usr SET mail_locked="0" WHERE usr_id = "'.$conn->escape_string($usr_id).'"';
					if($conn->query($sql))
					{
						if(p4string::checkMail($row['usr_mail']))
						{
							$isUser = false;
							
							$sql = 'SELECT base_id FROM basusr WHERE usr_id="'.$conn->escape_string($row['usr_id']).'" AND actif="1" ';
							if($rsBas = $conn->query($sql))
							{
								if($conn->num_rows($rsBas)>0)
									$isUser = true;										
							}
							
							if($isUser)
							{
								mail::mail_confirm_registered($row['usr_mail']);
							}
							else
							{
								$others = '';
								$sql = 'SELECT base_id FROM demand WHERE usr_id="'.$conn->escape_string($row['usr_id']).'" AND en_cours="1" ';
								if($rsDem = $conn->query($sql))
								{
									while($rowDem = $conn->fetch_assoc($rsDem))
									{
										$others .= '<li>'.phrasea::bas_names($rowDem['base_id'])."</li>\n";
									}	
								}
								mail::mail_confirm_unregistered($row['usr_mail'], $others);
							}
						}
						
						header('Location: /login?app=client&confirm=ok');
						exit();
					}
				}
				else
				{
					header('Location: /login?app=client&confirm=already');
					exit();
				}
			}
		}
	}
}
header('Location: /login/client/');
exit();
?>