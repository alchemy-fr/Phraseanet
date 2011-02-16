<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
$lng = $session->locale;

$request = httpRequest::getInstance();
$parm = $request->get_post_datas('usr_id');

$conn = connection::getInstance();
if(!$conn)
{
	die();
}
$confirm = '';
if(is_numeric((int)$parm['usr_id']))
{
	
	$usr_id = $email = false;
	$sql = 'select usr_mail,usr_id from usr WHERE usr_id = "'.$conn->escape_string($parm['usr_id']).'"';
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
		{
			$usr_id = $row['usr_id'];
			$email = $row['usr_mail'];
		}
	}
	
	if($email !== false && $usr_id!==false)
	{
		if(mail::mail_confirmation($email,$usr_id)===true)
			$confirm = 'mail-sent';
	}							
}
header('Location: /login/index.php?confirm='.$confirm);
exit();
?>