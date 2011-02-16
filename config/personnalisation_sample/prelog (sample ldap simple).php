<?php
if(GV_ldap_enabled)
{
	ckLDAP($conn, $debug_ldap);
	printf("<!-- apres ckLDAP : login='" . $parm['login'] . "', pwd='" . $parm['pwd'] . "' -->\n");
}

// die();

function ckLDAP($conn, $debug_ldap=false)
{
	global $parm;
	
	if(!$parm['login'])
		return(false);
	
	// on commence par verifier si c'est un user 'pur phrasea'
//	$sql = "SELECT usr_id FROM usr WHERE usr_login='".$conn->escape_string($parm["login"])."' AND usr_password='".$conn->escape_string($parm["pwd"])."' AND ldap_created=0"; //  GV_ldap_userid_template";
	$sql = "SELECT usr_id FROM usr WHERE usr_login='".$conn->escape_string($parm["login"])."' AND ldap_created=0"; //  GV_ldap_userid_template";
	if($debug_ldap)
		printf("<b>%s</b> : sql='%s'\n", __LINE__, $sql);
	if($rs = $conn->query($sql)) 
	{
		if ($conn->fetch_assoc($rs))
		{
			$conn->free_result($rs);
			if($debug_ldap)
				printf("<b>%s</b> : l'utilisateur '%s' est un 'pur' phrasea.\n", __LINE__, $parm["login"]);
			return(true);				// user pur phrasea, fini !
		}
		else
		{
			$conn->free_result($rs);
		}
	}
	
	// ici le user n'existe pas, ou s'il existe c'est bien un ldap_created
	// on commence par invalider le pwd saisi, car il sera remplace par celui du groupe si tout est ok
	$oldpwd = $parm['pwd'];
	$parm['pwd'] = '*invalid_pwd*';
	
	// on verifie son l'existance du user dans le ldap
	set_time_limit(300);
	
	$ldap_conn = @ldap_connect(GV_ldap_addr,  GV_ldap_port);
	if($debug_ldap)
		printf("<b>%s</b> : ldap_connect('%s', '%s') returned : '%s'\n", __LINE__, GV_ldap_addr, GV_ldap_port, print_r($ldap_conn, true));
		
	if ($ldap_conn) 
	{
		ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);

		$ldap_login_consult = str_replace('%LOGIN%', $parm['login'], GV_ldap_base_dn);
		$ldap_pwd_consult   = $oldpwd;

		$grp_bind = @ldap_bind($ldap_conn, $ldap_login_consult, $ldap_pwd_consult);
		if($debug_ldap)
		{
			printf("<b>%s</b> : ldap_bind({conn}, '%s', '%s') returned : '%s'\n", __LINE__, $ldap_login_consult, $ldap_pwd_consult, print_r($grp_bind, true));
		}
		@ldap_close($ldap_conn);
		
		if(!$grp_bind)	// pas dans le ldap : fin
			return(false);
	}
	
	
	// lit le modèle
	$rowmodel = null;
	$sql = "SELECT * FROM usr WHERE usr_login='".$conn->escape_string(GV_ldap_user_template)."'";
	if($debug_ldap)
		printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
						
	if ($rs = $conn->query($sql)) 
	{
		$rowmodel = $conn->fetch_assoc($rs);
		$conn->free_result($rs);
	}
	
	if(is_array($rowmodel))
	{
		if($debug_ldap)
			printf("<b>%s</b> : modele '%s' touve en id=%s \n", __LINE__, GV_ldap_user_template, $rowmodel['usr_id']);
			
		// on verifie si le user existe dans phrasea
		$sql = "SELECT usr_id FROM usr WHERE usr_login='" . $conn->escape_string($parm['login']) . "'";
		if($debug_ldap)
			printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
			
		$usrid = null;
		if ($rs = $conn->query($sql)) 
		{
			if ($row = $conn->fetch_assoc($rs))
			{
				if($debug_ldap)
					printf("<b>%s</b> : user '%s' touve en id=%s \n", __LINE__, $conn->escape_string($parm['login']), $row['usr_id']);

				// le user existait, on met qqs infos a jour (le pwd...)
				$conn->free_result($rs);
				$usrid = $row['usr_id'];
				$sql = "UPDATE usr SET ldap_created=1, usr_password='" . $conn->escape_string($rowmodel['usr_password']) . "', lastModel='" . $conn->escape_string(GV_ldap_user_template) . "' WHERE usr_id=$usrid";
				if($debug_ldap)
					printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
					
				$conn->query($sql);

				$parm['pwd'] = $rowmodel['usr_password'];	// on change le pwd saisi par le user (pour ldap) par celui pour phrasea
			}
			else 
			{
				// le user n'existe pas, on le cree d'apres le modele
				$conn->free_result($rs);
				
				$usrid = $conn->getId('usr');
				$sqlf = 'usr_id, ldap_created, usr_login, usr_creationdate, model_of, lastModel';
				$sqlv = $usrid . ',1 , \'' . $conn->escape_string($parm['login']) . '\', NOW(), 0, \'' . $conn->escape_string(GV_ldap_user_template) . '\'';
				foreach(array('desktop', 'usr_sexe', 'usr_nom', 'usr_prenom', 'usr_password', 'query0'
								, 'usr_mail', 'adresse', 'ville'
								, 'cpostal', 'tel', 'fax', 'fonction', 'societe', 'activite', 'issuperu', 'pays'
								, 'seepwd') as $fn)
				{
					if(array_key_exists($fn, $rowmodel))
					{
						$sqlf .= ', ' . $fn;
						$sqlv .= ', \'' . $conn->escape_string($rowmodel[$fn]) . '\'';
					}
				}
				$sql = 'INSERT INTO usr ('.$sqlf.') VALUES ('.$sqlv.')';
				if($debug_ldap)
					printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
					
				if($conn->query($sql))
				{
					if($debug_ldap)
						printf("<b>%s</b> : user '%s' cree en id=%s \n", __LINE__, $conn->escape_string($parm['login']), $usrid);

					// on ajoute les droits (basusr)
					$sql = "SELECT * FROM basusr WHERE usr_id=" . $rowmodel['usr_id'];
					if($debug_ldap)
						printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
							
					if ($rs = $conn->query($sql)) 
					{
						while($row = $conn->fetch_assoc($rs))
						{
							foreach($row as $fn=>$fv)
								$row[$fn] = '\'' . $conn->escape_string($fv) . '\'';	// sql ready
							foreach(array('id', 'remain_dwnld', 'lastconn') as $fn)
								unset($row[$fn]);
							$row['usr_id'] = $usrid;
							$row['creationdate'] = 'NOW()';
							
							$sqlf = $sqlv = '';
							foreach($row as $fn=>$fv)
							{
								$sqlf .= ($sqlf?', ':'') . $fn;
								$sqlv .= ($sqlv?', ':'') . $row[$fn];
							}
							$sql = 'INSERT INTO basusr ('.$sqlf.') VALUES ('.$sqlv.')';
							if($debug_ldap)
								printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
		
							$conn->query($sql);
						}
						$conn->free_result($rs);
					}
					
					// on ajoute les droits (sbasusr)
					$sql = "SELECT * FROM sbasusr WHERE usr_id=" . $rowmodel['usr_id'];
					if($debug_ldap)
						printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
						
					if ($rs = $conn->query($sql)) 
					{
						while($row = $conn->fetch_assoc($rs))
						{
							foreach($row as $fn=>$fv)
								$row[$fn] = '\'' . $conn->escape_string($fv) . '\'';	// sql ready
							foreach(array('sbasusr_id') as $fn)
								unset($row[$fn]);
							$row['usr_id'] = $usrid;
							
							$sqlf = $sqlv = '';
							foreach($row as $fn=>$fv)
							{
								$sqlf .= ($sqlf?', ':'') . $fn;
								$sqlv .= ($sqlv?', ':'') . $row[$fn];
							}
							$sql = 'INSERT INTO sbasusr ('.$sqlf.') VALUES ('.$sqlv.')';
							if($debug_ldap)
								printf("<b>%s</b> : sql='%s' \n", __LINE__, $sql);
		
							$conn->query($sql);
						}
						$conn->free_result($rs);
					}
			
					$parm['pwd'] = $rowmodel['usr_password'];	// on change le pwd saisi par le user (pour ldap) par celui pour phrasea
				}
				else
				{
					// erreur insert usr
				}
			}
		}
		else
		{
			// erreur select usr
		}
	}
	else
	{
		// le modèle n'existe pas
	}
}
?>
