<?php
class exportftp extends export
{
	
	
	public function export_ftp($usr_to, $host, $login, $password, $ssl, $retry, $passif, $destfolder, $makedirectory, $logfile)
	{
		$session = session::getInstance();
		$user_f = user::getInstance($session->usr_id);
		$conn = connection::getInstance();
		
		$email_dest = '';
		if($usr_to)
		{
			$user_t = user::getInstance($usr_to);
			$email_dest = $user_t->email;
		}
		
		
		$text_mail_receiver = "Bonjour,\n";
		$text_mail_receiver.= "L'utilisateur " . $user_f->display_name." (login : ".$user_f->login.") 
			a fait un transfert FTP sur le serveur ayant comme adresse \"".$host."\" avec le login \"".$login."\"  ";
		$text_mail_receiver.= "et pour repertoire de destination \"".$destfolder."\"\n";
			
		$text_mail_sender = "Bonjour,\n";
		$text_mail_sender.= "Vous avez fait un export FTP  avec les caracteristiques de connexion suivantes\n" ;
		$text_mail_sender.= "- adresse du serveur : \"".$host."\"\n";
		$text_mail_sender.= "- login utilisï¿½ \"".$login."\"\n";
		$text_mail_sender.= "- repertoire de destination \"".$destfolder."\"\n";
		$text_mail_sender.= "\n";
			
		$fn = "id";	 					$fv = "null";
		$fn.= ",crash";	 				$fv.= ",0";
		$fn.= ",nbretry";				$fv.= ",".(((int)$retry*1)>0?(int)$retry:5)."";
		$fn.= ",mail";	 				$fv.= ",'".$conn->escape_string($email_dest)."'"; // celui du destinataire et celui de l'expedireur 
		$fn.= ",addr";	 				$fv.= ",'".$conn->escape_string($host)."'";
		$fn.= ",login";	 				$fv.= ",'".$conn->escape_string($login)."'";
		$fn.= ",`ssl`";	 				$fv.= ",'".$conn->escape_string(($ssl == '1' ? '1' : '0'))."'";
		$fn.= ",pwd";	 				$fv.= ",'".$conn->escape_string($password)."'";
		$fn.= ",passif";	 			$fv.= ",".($passif=="1"?"1":"0");
		$fn.= ",destfolder";			$fv.= ",'".$conn->escape_string($destfolder)."'";
		$fn.= ",sendermail";			$fv.= ",'".$conn->escape_string($user_f->email)."'";
		$fn.= ",text_mail_receiver";	$fv.= ",'".$conn->escape_string($text_mail_receiver)."'";
		$fn.= ",text_mail_sender";		$fv.= ",'".$conn->escape_string($text_mail_sender)."'";
		$fn.= ",usr_id";				$fv.= ",'".$session->usr_id."'";
		$fn.= ",date";					$fv.= ", NOW()";
		$fn.= ",foldertocreate";		$fv.= ",'".$conn->escape_string($makedirectory)."'";
		$fn.= ",logfile";		$fv.= ",'".$conn->escape_string(!!$logfile ? '1' : '0')."'";
		
		$sql = "INSERT INTO ftp_export ($fn) VALUES ($fv)"; 
		
		if($conn->query($sql))
		{
			$ftp_export_id = $conn->insert_id();
		}
		else
		{
			throw new Exception ('Unable to save the export');
		}
			
		foreach($this->list['files'] as $file)
		{
			foreach($file['subdefs'] as $subdef=>$properties)
			{
				$sql = 'INSERT INTO ftp_export_elements (id, ftp_export_id, base_id, record_id, subdef, filename, folder) VALUES 
						(null, "'.$conn->escape_string($ftp_export_id).'", "'.$conn->escape_string($file['base_id']).'", 
						"'.$conn->escape_string($file['record_id']).'", "'.$conn->escape_string($subdef).'", 
						"'.$conn->escape_string($file['export_name'].$properties["ajout"].'.'.$properties['exportExt']).'", 
						"'.$conn->escape_string($properties['folder']).'")';
				$conn->query($sql);
			}
		}
		
		return true;
	}
	
}