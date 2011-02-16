<?php
ignore_user_abort(true);
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$request = httpRequest::getInstance();
$parm = $request->get_parms('sbid'
							, 'act'		// 'SAVELST' | 'SAVESSEL' | 'SAVEGRP'
							, 'regbasprid'	// bid_rid_parent of grp (if SAVEGRP)
							, 'ssel'		// ssel_id (if SAVESSEL)
							, 'mds'
							, 'lst'
							, 'newrepresent'
							);  
							
$lng = isset($session->locale)?$session->locale:GV_default_lng;

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

switch($parm['act'])
{
	case 'SAVEGRP':
		
		if( $parm['newrepresent']!='' && $parm['newrepresent']!=NULL )
		{
			// if not empty, we changed the 'representative' image
			include('./saveedit_chgrep.php');
		}
		break;
}

if($parm['mds'])
{
	include('./saveedit_mdesc.php');
}

