<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();
if (!$session->is_authenticated())
{
  exit();
}
header('Content-Type: text/html; charset=UTF-8');

$request = http_request::getInstance();

if (!$request->has_post_datas())

  return false;

$parm = $request->get_parms('ACTION');

include($registry->get('GV_RootPath') . 'lib/classes/deprecated/push.api.php');

$lng = Session_Handler::get_locale();
$usr_id = $session->get_usr_id();

$output = "";

$act = $parm['ACTION'];

switch ($act)
{

  case "GETLANGUAGE":
    {
      $output = getPushLanguage($usr_id, $lng);
    };
    break;

  case "CHECKMAIL":
    {
      $parm = $request->get_parms('mail', 'usr_id');
      $output = newUserCheckMail($usr_id, $lng, $parm['mail'], $parm['usr_id']);
    };
    break;

  case "ADD_USR":
    {
      $parm = $request->get_parms('IDENT', 'MAIL', 'NOM', 'PREN', 'SOCIE', 'FUNC', 'ACTI', 'COUNTRY', 'CIV', 'ID', 'DATE_END',
                      'baseInsc', 'baseWm', 'basePreview');
      $arrayUsr = array(
          'IDENT' => $parm['IDENT']
          , 'MAIL' => $parm['MAIL']
          , 'NOM' => $parm['NOM']
          , 'PREN' => $parm['PREN']
          , 'SOCIE' => $parm['SOCIE']
          , 'FUNC' => $parm['FUNC']
          , 'ACTI' => $parm['ACTI']
          , 'COUNTRY' => $parm['COUNTRY']
          , 'CIV' => $parm['CIV']
          , 'ID' => $parm['ID']
          , 'DATE_END' => $parm['DATE_END']
      );
      $output = createUserOnFly($usr_id, $arrayUsr, $parm['baseInsc'] ? json_decode($parm['baseInsc']) : array(), $parm['basePreview'] ? json_decode($parm['basePreview']) : array(), $parm['baseWm'] ? json_decode($parm['baseWm']) : array());
    };
    break;

  case "HD_USER":
    {
      $parm = $request->get_parms('token', 'usrs', 'value');
      $output = hd_user($usr_id, $parm['token'], json_decode($parm['usrs']), $parm['value']);
    };
    break;

  case "SEARCHUSERS":
    {
      $parm = $request->get_parms('token', 'view', 'filters', 'page', 'sort', 'perPage');
      $output = whoCanIPush($usr_id, $lng, $parm['token'], $parm['view'], urlencode($parm['filters']),
                      $parm['page'], $parm['sort'], $parm['perPage']);
    };
    break;

  case "ADDUSER":
    {
      $parm = $request->get_parms('token', 'usr_id');
      $output = addUser($usr_id, $parm['token'], $parm['usr_id']);
    };
    break;

  case "LOADUSERS":
    {
      $parm = $request->get_parms('token', 'filters');
      $output = loadUsers($usr_id, $parm['token'], urlencode($parm['filters']));
    };
    break;

  case "UNLOADUSERS":
    {
      $parm = $request->get_parms('token', 'filters');
      $output = unloadUsers($usr_id, $parm['token'], urlencode($parm['filters']));
    };
    break;

  case "SAVELIST":
    {
      $parm = $request->get_parms('name', 'filters', 'token');
      $output = saveList($usr_id, $lng, $parm['name'], $parm['token']);
    };
    break;

  case "SAVEILIST":
    {
      $parm = $request->get_parms('token', 'filters', 'name');
      $output = saveiList($usr_id, $lng, $parm['name'], $parm['token'], $parm['filters']);
    };
    break;

  case "GETLISTS":
    {
      $output = loadLists($usr_id, $lng);
    };
    break;

  case "DELETEILIST":
    {
      $parm = $request->get_parms('name');
      $output = deleteiList($usr_id, $parm['name'], $lng);
    };
    break;

  case "DELETELIST":
    {
      $parm = $request->get_parms('lists');
      $output = deleteList($usr_id, $parm['lists'], $lng);
    };
    break;

  case "LOADILIST":
    {
      $parm = $request->get_parms('name');
      $output = loadIList($parm['name']);
    };
    break;
}

echo $output;



