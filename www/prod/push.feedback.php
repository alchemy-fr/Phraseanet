<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
if(!isset($session->usr_id) || !isset($session->ses_id))
{
	exit();
}
header('Content-Type: text/html; charset=UTF-8');

$request = httpRequest::getInstance();

if(!$request->has_post_datas())
     return false;

$parm = $request->get_parms('ACTION');

include(GV_RootPath.'lib/push.api.php');
		 
$lng = !isset($session->locale)?GV_default_lng:$session->locale;
$usr_id = $session->usr_id;
$ses_id = $session->ses_id;

$output = "";

  $act = $parm['ACTION'];

  switch($act){

	  case "GETLANGUAGE":{
	  		$output = getPushLanguage($usr_id,$ses_id,$lng);
	  };break;
	  
	  case "CHECKMAIL":{
	  		$parm = $request->get_parms('mail', 'usr_id');
	  		$output = newUserCheckMail($usr_id,$ses_id,$lng,$parm['mail'],$parm['usr_id']);
	  };break;
	  
	  case "ADD_USR":{
	  		$parm = $request->get_parms('IDENT', 'MAIL', 'NOM', 'PREN', 'SOCIE', 'FUNC', 'ACTI', 'COUNTRY', 'CIV', 'ID', 'DATE_END',
	  		 'baseInsc', 'baseWm', 'basePreview');
	  		$arrayUsr = array(
	  			'IDENT'=>$parm['IDENT']
	  			,'MAIL'=>$parm['MAIL']
	  			,'NOM'=>$parm['NOM']
	  			,'PREN'=>$parm['PREN']
	  			,'SOCIE'=>$parm['SOCIE']
	  			,'FUNC'=>$parm['FUNC']
	  			,'ACTI'=>$parm['ACTI']
	  			,'COUNTRY'=>$parm['COUNTRY']
	  			,'CIV'=>$parm['CIV']
	  			,'ID'=>$parm['ID']
	  			,'DATE_END'=>$parm['DATE_END']
	  		);
	  		$output = createUserOnFly($usr_id,$ses_id,$arrayUsr,$parm['baseInsc']?json_decode($parm['baseInsc']):array(),$parm['basePreview']?json_decode($parm['basePreview']):array(),$parm['baseWm']?json_decode($parm['baseWm']):array());
	  };break;
	  
	  case "HD_USER":{
	  		$parm = $request->get_parms('token', 'usrs', 'value');
	  		$output = hd_user($usr_id,$ses_id,$parm['token'],json_decode($parm['usrs']),$parm['value']);
	  };break;
	  
	  case "SEARCHUSERS":{
	  		$parm = $request->get_parms('token', 'view', 'filters', 'page', 'sort', 'perPage');
	  		$output = whoCanIPush($usr_id,$ses_id,$lng,$parm['token'],$parm['view'],urlencode($parm['filters']),
	  										$parm['page'],$parm['sort'],$parm['perPage']);
	  };break;
	  
	  case "ADDUSER":{
	  		$parm = $request->get_parms('token', 'usr_id');
	  		$output = addUser($usr_id,$ses_id,$parm['token'],$parm['usr_id']);
	  };break;
	  
	  case "LOADUSERS":{
	  		$parm = $request->get_parms('token', 'filters');
	  		$output = loadUsers($usr_id,$ses_id,$parm['token'],urlencode($parm['filters']));
	  };break;
	  
	  case "UNLOADUSERS":{
	  		$parm = $request->get_parms('token', 'filters');
	  		$output = unloadUsers($usr_id,$ses_id,$parm['token'],urlencode($parm['filters']));
	  };break;
	  
	  case "SAVELIST":{
	  		$parm = $request->get_parms('name', 'filters', 'token');
	  		$output = saveList($usr_id,$ses_id,$lng,$parm['name'],$parm['token']);
	  };break;
	  
	  case "SAVEILIST":{
	  		$parm = $request->get_parms('token', 'filters', 'name');
	  		$output = saveiList($usr_id,$ses_id,$lng,$parm['name'],$parm['token'],$parm['filters']);
	  };break;
	  
	  case "GETLISTS":{
	  		$output = loadLists($usr_id,$ses_id,$lng);
	  };break;
	  
	  case "DELETEILIST":{
	  		$parm = $request->get_parms('name');
	  		$output = deleteiList($usr_id,$ses_id,$parm['name'],$lng);
	  };break;
	  
	  case "DELETELIST":{
	  		$parm = $request->get_parms('lists');
	  		$output = deleteList($usr_id,$ses_id,$parm['lists'],$lng);
	  };break;
	  
	  case "LOADILIST":{
	  		$parm = $request->get_parms('name');
	  		$output = loadIList($parm['name']);
	  };break;
  }
  
  echo $output;

  
