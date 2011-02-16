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
		printf("sql(%s): %s\n", __LINE__, $sql);
	if($rs = $conn->query($sql)) 
	{
		if ($conn->fetch_assoc($rs))
		{
			$conn->free_result($rs);
			if($debug_ldap)
				printf("l'utilisateur '%s' est un 'pur' phrasea.\n", $parm["login"]);
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
	$zgrp = null;			// le grp du user s'il existe		
	set_time_limit(300);
	
	$ldap_conn = @ldap_connect(GV_ldap_addr,  GV_ldap_port);
	if($debug_ldap)
		print("ldap_connect('GV_ldap_addr', 'GV_ldap_port') returned : ".print_r($ldap_conn, true)."\n");
		
	if ($ldap_conn) 
	{
//		@ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 2);

//		if($debug_ldap)
//			print("connected to LDAP Server at GV_ldap_addr : GV_ldap_port \n");
		
		if (GV_ldap_login_consult == "" && GV_ldap_pwd_consult == "")
			$grp_bind = @ldap_bind($ldap_conn);
		else
			$grp_bind = @ldap_bind($ldap_conn, GV_ldap_login_consult, GV_ldap_pwd_consult);
		
		if($debug_ldap)
			print("ldap_bind({conn}, 'GV_ldap_login_consult', 'GV_ldap_pwd_consult') returned : ".print_r($grp_bind, true)."\n");
			
		$t_grp = array();	// la liste des groupes de la phothotheque
		
		if ($grp_bind)
		{
//			if($debug_ldap)
//				print("binded to LDAP Server at GV_ldap_addr : GV_ldap_port \n");
				
			// recherche des groupes de la phototheque
			
//			if($debug_ldap)
//				print("LDAP groups: base='GV_ldap_grp_base_dn', filter='GV_ldap_grp_sfilter' \n");
				
			$t_ldap_fields = array(GV_ldap_grp_value, "dn"); 
//			$res_search = ldap_search($ldap_conn, GV_ldap_grp_base_dn, GV_ldap_grp_sfilter, $t_ldap_fields);
			$grp_search = @ldap_list($ldap_conn, GV_ldap_grp_base_dn, GV_ldap_grp_sfilter, $t_ldap_fields);
			if($debug_ldap)
				print("ldap_list({conn}, 'GV_ldap_grp_base_dn', 'GV_ldap_grp_sfilter', {fields}) returned : ".print_r($grp_search, true)."\n");
				
			if($grp_search)
			{
				$grp_entries = @ldap_get_entries($ldap_conn, $grp_search);
				
				if($debug_ldap)
					print("ldap_get_entries({conn}, {grp_search}) returned : ".print_r($grp_entries, true)."\n");
				if($grp_entries)
				{
					// on boucle sur les groupes
					for($igrp=0; $igrp<$grp_entries['count']; $igrp++)
					{
						if(isset($grp_entries[$igrp]['dn']) && isset($grp_entries[$igrp][GV_ldap_grp_value]) && isset($grp_entries[$igrp][GV_ldap_grp_value][0]))
						{
							$t_grp[] = array(
												  "dn"=>$grp_entries[$igrp]['dn']
												, "name"=> $grp_entries[$igrp][GV_ldap_grp_value][0]
											);
						}
					}
				}				
				ldap_free_result($grp_search);
			}
			
//			ldap_unbind($grp_bind);
//			ldap_unbind($ldap_conn);
		}
							
		// ici on a une liste des groupes, on essaye de logguer le user dessus
		foreach($t_grp as $grp)
		{
			$dn = str_replace('%LOGIN%', $parm['login'], GV_ldap_sfilter) . ',' . $grp['dn'];
				
//			if($debug_ldap)
//				print("binding : dn='".$dn."', pwd='".$oldpwd."'\n");
				
			$usr_bind = @ldap_bind($ldap_conn, $dn, $oldpwd);
			if($debug_ldap)
				print("ldap_bind({conn}, '$dn', '$oldpwd') returned : ".print_r($usr_bind, true)."\n");
				
//			if($debug_ldap)
//				print("usr_bind=".var_export($usr_bind, true)."\n");
				
			if($usr_bind)
			{
				// yes, on est loggue sur le ldap
				$zgrp = $grp['name'];
//				ldap_unbind($usr_bind);
//				ldap_unbind($ldap_conn);
				break;						// le premier groupe ok l'emporte
			}
		}
		
//		@ldap_unbind($ldap_conn);
		@ldap_close($ldap_conn);
	}
	
	if(!$zgrp)
		return(false);
		
	// ici on sait que le user est ok dans le groupe ldap 'zgrp'
	
	// on cherche si ce groupe existe dans phrasea (user modele)
	$rowmodel = null;
	$sql = "SELECT * FROM usr WHERE usr_login='".$conn->escape_string('ldap_'.$zgrp)."'";
	if($debug_ldap)
		printf("sql(%s): %s\n", __LINE__, $sql);
	if ($rs = $conn->query($sql)) 
	{
		if ( $rowmodel = $conn->fetch_assoc($rs))
		{
			// le groupe existe
			$conn->free_result($rs);
		}
		else
		{
			// le groupe n'existe pas, on le cree d'apres le user GV_ldap_user_template
			$conn->free_result($rs);
			$modelgrpid = null;
			$sql2 = "SELECT * FROM usr WHERE usr_login='" . $conn->escape_string(GV_ldap_user_template) . "'";
			if($debug_ldap)
				printf("sql2(%s): %s\n", __LINE__, $sql2);

			$row2 = null;
			if ($rs2 = $conn->query($sql2)) 
			{
				$row2 = $conn->fetch_assoc($rs2);
				$conn->free_result($rs2);
			}
			
			// on duplique le groupe modele
			if($row2)	
			{
				// le groupe modele existe, on le duplique
				$newuid = $conn->getId('usr');
				$sqlf = 'usr_id, usr_login, usr_creationdate, usr_modificationdate, ldap_created, model_of';
				$sqlv = $newuid . ', \'' . $conn->escape_string('ldap_'.$zgrp) . '\', NOW(), NOW(), 1, 1';
				foreach(array('desktop', 'usr_sexe', 'usr_nom', 'usr_prenom', 'usr_password', 'query0'
								, 'usr_mail', 'adresse', 'ville'
								, 'cpostal', 'tel', 'fax', 'fonction', 'societe', 'activite', 'issuperu', 'pays'
								, 'seepwd') as $fn)
				{
					if(array_key_exists($fn, $row2))
					{
						$sqlf .= ', ' . $fn;
						$sqlv .= ', \'' . $conn->escape_string($row2[$fn]) . '\'';
					}
				}
				$sql = 'INSERT INTO usr ('.$sqlf.') VALUES ('.$sqlv.')';
				if($debug_ldap)
					printf("sql(%s): %s\n", __LINE__, $sql);
					
				if($conn->query($sql))
				{
					// on a reussi a creer le user
					// on duplique egalement les droits du groupe modele
					$sql = "SELECT * FROM basusr WHERE usr_id=" . $row2['usr_id'];
					if($debug_ldap)
						printf("sql(%s): %s\n", __LINE__, $sql);
					if ($rs = $conn->query($sql)) 
					{
						while($row = $conn->fetch_assoc($rs))
						{
							foreach($row as $fn=>$fv)
								$row[$fn] = '\'' . $conn->escape_string($fv) . '\'';	// sql ready
							foreach(array('id', 'remain_dwnld', 'lastconn') as $fn)
								unset($row[$fn]);
							$row['usr_id'] = $newuid;
							$row['creationdate'] = 'NOW()';
							
							$sqlf = $sqlv = '';
							foreach($row as $fn=>$fv)
							{
								$sqlf .= ($sqlf?', ':'') . $fn;
								$sqlv .= ($sqlv?', ':'') . $row[$fn];
							}
							$sql = 'INSERT INTO basusr ('.$sqlf.') VALUES ('.$sqlv.')';
							if($debug_ldap)
								printf("sql(%s): %s\n", __LINE__, $sql);
	
							$conn->query($sql);
						}
						$conn->free_result($rs);
					}
					$sql = "SELECT * FROM sbasusr WHERE usr_id=" . $row2['usr_id'];
					if($debug_ldap)
						printf("sql(%s): %s\n", __LINE__, $sql);

					if ($rs = $conn->query($sql)) 
					{
						while($row = $conn->fetch_assoc($rs))
						{
							foreach($row as $fn=>$fv)
								$row[$fn] = '\'' . $conn->escape_string($fv) . '\'';	// sql ready
							foreach(array('sbasusr_id') as $fn)
								unset($row[$fn]);
							$row['usr_id'] = $newuid;
							
							$sqlf = $sqlv = '';
							foreach($row as $fn=>$fv)
							{
								$sqlf .= ($sqlf?', ':'') . $fn;
								$sqlv .= ($sqlv?', ':'') . $row[$fn];
							}
							$sql = 'INSERT INTO sbasusr ('.$sqlf.') VALUES ('.$sqlv.')';
							if($debug_ldap)
								printf("sql(%s): %s\n", __LINE__, $sql);
	
							$conn->query($sql);
						}
						$conn->free_result($rs);
					}
				}
			}
			else
			{
				// le groupe modele n'existe pas : anormal
				if($debug_ldap)
					printf("<span style='color:#ff0000'>le user '%s' n'existe pas</span>\n", GV_ldap_user_template);
			}
		}
	}
	
	// normalement ici le groupe existe dans phrasea (user modele)
	if(!$rowmodel)
	{
		$sql = "SELECT * FROM usr WHERE usr_login='".$conn->escape_string('ldap_'.$zgrp)."'";
		if($debug_ldap)
			printf("sql(%s): %s\n", __LINE__, $sql);
							
		if ($rs = $conn->query($sql)) 
		{
			$rowmodel = $conn->fetch_assoc($rs);
			$conn->free_result($rs);
		}
	}
	
	if(is_array($rowmodel))
	{
		// on verifie si le user existe dans phrasea
		$sql = "SELECT usr_id FROM usr WHERE usr_login='" . $conn->escape_string($parm['login']) . "'";
		if($debug_ldap)
			printf("sql(%s): %s\n", __LINE__, $sql);
			
		$usrid = null;
		if ($rs = $conn->query($sql)) 
		{
			if ($row = $conn->fetch_assoc($rs))
			{
				// le user existait, on met qqs infos a jour (le pwd...)
				$conn->free_result($rs);
				$usrid = $row['usr_id'];
				$sql = "UPDATE usr SET ldap_created=1, usr_password='" . $conn->escape_string($rowmodel['usr_password']) . "', lastModel='" . $conn->escape_string('ldap_'.$zgrp) . "' WHERE usr_id=$usrid";
				if($debug_ldap)
					printf("sql(%s): %s\n", __LINE__, $sql);
					
				$conn->query($sql);
			}
			else 
			{
				// le user n'existe pas, on le cree d'apres le modele
				$conn->free_result($rs);
				
				$usrid = $conn->getId('usr');
				$sqlf = 'usr_id, ldap_created, usr_login, usr_creationdate, model_of, lastModel';
				$sqlv = $usrid . ',1 , \'' . $conn->escape_string($parm['login']) . '\', NOW(), 0, \'' . $conn->escape_string('ldap_'.$zgrp) . '\'';
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
					printf("sql(%s): %s\n", __LINE__, $sql);
					
				if(!$conn->query($sql))
					$usrid = null;		// si on n'a pas reussi a creer le user, on na va pas lui donner de droits
			}
		}
		
		// on donne des droits au user
		if($usrid !== null)
		{
			// on remplace systematiquement les droits du user par ceux du modele
			// (au cas ou le user aurait change de groupe dans ldap, donc de modele dans phrasea) 
			$sql = "DELETE basusr, sbasusr FROM basusr, sbasusr WHERE basusr.usr_id=sbasusr.usr_id AND sbasusr.usr_id=" . $usrid;
			if($debug_ldap)
				printf("sql(%s): %s\n", __LINE__, $sql);
					
			$conn->query($sql);
			$sql = "SELECT * FROM basusr WHERE usr_id=" . $rowmodel['usr_id'];
			if($debug_ldap)
				printf("sql(%s): %s\n", __LINE__, $sql);
					
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
						printf("sql(%s): %s\n", __LINE__, $sql);

					$conn->query($sql);
				}
				$conn->free_result($rs);
			}
			$sql = "SELECT * FROM sbasusr WHERE usr_id=" . $rowmodel['usr_id'];
			if($debug_ldap)
				printf("sql(%s): %s\n", __LINE__, $sql);
				
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
						printf("sql(%s): %s\n", __LINE__, $sql);

					$conn->query($sql);
				}
				$conn->free_result($rs);
			}
			
			$parm['pwd'] = $rowmodel['usr_password'];	// on change le pwd saisi par le user (pour ldap) par celui pour phrasea
			
		}
	}
	else
	{
		// le groupe n'existe (toujours) pas
		if($debug_ldap)
				printf("<span style='color:#ff0000'>le user '%s' n'existe pas</span>\n", 'ldap_'.$zgrp);
	}
}
?>
