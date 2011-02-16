<?php
if (file_exists( dirname( __FILE__ ) . "/../config/_GV.php") && file_exists( dirname( __FILE__ ) . "/../config/connexion.inc"))
{
	include( dirname( __FILE__ ) . "/../lib/bootstrap.php" );
	
	$browser = browser::getInstance();
	if($browser->isNewGeneration())
		header("Location: /login/prod/");
	else
		header("Location: /login/client/");
	exit();
}


header("Location: /setup/");
exit();
