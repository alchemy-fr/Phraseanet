<?php

require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$start = microtime(true);

$conn = connection::getInstance();

//liste des colonnes a ajout�es
$col = array('fonction','societe','activite','pays');

//on recupere toutes les databox liees a notre application box dans $tab_sbas
$sql = " SELECT * FROM sbas";
if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs))
		$tab_sbas[$row['sbas_id']] = array('dbname' => $row['dbname']);
}
//on recupere ous les champs de la requete
$f_req = "";
foreach($col as $key => $column)
	$f_req .= (($f_req) ? ',': '') . $column;
	
//On recupere dans $tab_usr tous les user de l'application box
$sql2 = "SELECT usr_id, ".$f_req." FROM usr";
if($rs2 = $conn->query($sql2))
{
	while($row2 = $conn->fetch_assoc($rs2))
		$tab_usr[$row2['usr_id']] = array('fonction' => $row2['fonction'], 'societe' => $row2['societe'], 'activite' => $row2['activite'], 'pays' => $row2['pays']);
}

//Ajoute une colonne si elle n'existe pas
function add_column($conn, $column)
{
	if($column == "activite")
		$nb = 200;
	else
		$nb = 64;
		
	$sql= "SHOW COLUMNS FROM `log` LIKE '$column'";
	if($rs = $conn->query($sql))
	{
		if(mysql_num_rows($rs) > 0) 
			return;
		else
		{
			$sql_update = "ALTER TABLE `log` ADD (".$column." VARCHAR(".$nb.") CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL)";
			$conn->query($sql_update);
		}
	}
}


//On parcours toutes les databox li�es a l'application box
foreach($tab_sbas as $sbasid => $name)
{
	echo "working on base : ".$name['dbname']."\n";
	//si on peut �tablir la connexion
	if(connection::getInstance($sbasid))
	{
		$connbas = connection::getInstance($sbasid);
		//on ajoute une nouvelle colonne pour chaque champs
		foreach($col as $key => $column)
			add_column($connbas, $column);

		//Puis on remplis les colonnes
		foreach($tab_usr as $id => $columns)
		{
			$f_req = "";
			foreach($columns as $column => $value)
				$f_req .= (($f_req) ? ',': '') . $column." = '".$value."'" ; //on recupere le champs et sa valeur
			$sql = "UPDATE log SET ".$f_req." WHERE usrid = ".$id;
			$connbas->query($sql);
		}
	}
	echo "process complete for ".$name['dbname']."\n";
}
$end = microtime(true);
echo 'Script termine; Temps d\'execution : '.round($end - $start, 4).' secondes';
