<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$error = true;
if(isset($session->usr_id) && isset($session->ses_id))
{
	if(isset($session->admin) && $session->admin !== false)
		if(($ses_id = phrasea_open_session($session->ses_id, $session->usr_id)) > 0)
			$error = false;
}
if($error)
{
	phrasea::headers(403);
}
phrasea::headers();

$request = httpRequest::getInstance();
$parm = $request->get_parms('fil', 'act');

$logdir = p4string::addEndSlash(GV_RootPath.'logs');
$logfile = $logdir.$parm['fil'] ;

if(file_exists($logfile))
{
	if($parm['act']=='CLR')
	{
		file_put_contents($logfile, '');
		header("Location: showlog.php?fil=".urlencode($parm['fil']));
	}
	else
	{
		printf("<html lang=\"".$session->usr_i18n."\"><body><h4>%s&nbsp;  <a href=\"showlog.php?fil=%s&act=CLR\">effacer</a></h4>\n", $logfile, urlencode($parm['fil']));
		print("<pre>\n");
		print((file_get_contents($logfile)));
		print("</pre>\n</body></html>");
	}
}
else
{
	printf("file <b>%s</b> does not exists\n", $logfile);
}
?>

