<?php

require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$session = session::getInstance();

$lng = isset($session->locale) ? $session->locale : GV_default_lng;

if (isset($session->usr_id) && isset($session->ses_id))
{
  $ses_id = $session->ses_id;
  $usr_id = $session->usr_id;
}
else
{
  header("Location: /login/prod/");
  exit();
}

$request = httpRequest::getInstance();
$parm = $request->get_parms('action', 'from_id', 'to_id', 'actions', 'id');

$output = '';

$action = $parm['action'];

switch ($action)
{
  case 'lazaret_add_record_to_base':
    try
    {
      $lazaret_file = new lazaretFile($parm['id']);
      $lazaret_file->add_to_base();
      $output = "1";
    }
    catch (Exception $e)
    {
      $output = $e->getMessage();
    }
    break;
  case 'lazaret_global_operation':
    $errors = array();
    foreach($parm['actions'] as $action=>$datas)
    {
      if($datas === "")
        continue;
      switch ($action)
      {
        case 'add':
          foreach($datas as $data)
          {
            try
            {
              $lazaret_file = new lazaretFile($data['id']);
              $lazaret_file->add_to_base();
            }
            catch(Exception $e)
            {
              $errors[] = $e->getMessage();
            }
          }
          break;
        case 'delete':
          foreach($datas as $data)
          {
            try
            {
              $lazaret_file = new lazaretFile($data['id']);
              $lazaret_file->delete();
            }
            catch(Exception $e)
            {
              $errors[] = $e->getMessage();
            }
          }
          break;
        case 'substitute':
          foreach($datas as $data)
          {
            try
            {
              $lazaret_file = new lazaretFile($data['from']);
              $lazaret_file->substitute($data['from'], $data['to']);
            }
            catch(Exception $e)
            {
              $errors[] = $e->getMessage();
            }
          }
          break;
        default:
          break;
      }
    }
    $output = p4string::jsonencode(
                array(
                    'error'=>(count($errors)>0),
                    'message'=>implode("\n", $errors)
                )
              );
    break;
  case 'lazaret_delete_record':
    try
    {
      $lazaret_file = new lazaretFile($parm['id']);
      $lazaret_file->delete();
      $output = "1";
    }
    catch (Exception $e)
    {
      $output = $e->getMessage();
    }
    break;
  case 'lazaret_substitute_record':
    try
    {
      $lazaret_file = new lazaretFile($parm['from_id']);
      $lazaret_file->substitute($parm['from_id'], $parm['to_id']);
      $output = "1";
    }
    catch (Exception $e)
    {
      $output = $e->getMessage();
    }
    break;
  case 'get_lazaret_html':
    try
    {
      $lazaret = new lazaret();
      $lazaret_elements = $lazaret->elements;

      $twig = new supertwig();
      $twig->addFilter(array('nl2br' => 'nl2br'));
      $twig->addFilter(array('phraseadate' => 'phraseadate::getPrettyString'));
      $twig->addFilter(array('basnames' => 'phrasea::bas_names'));

      $output = $twig->render(
              'upload/lazaret.twig',
              array('lazaret' => $lazaret_elements)
      );
    }
    catch (Exception $e)
    {
      $output = $e->getMessage();
    }
    break;
  case 'get_lazaret_count':
    try
    {
      $lazaret = new lazaret();
      $count = (int)$lazaret->get_count();

      $ret = array('error' => false, 'count' => $count);
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'count' => 0);
    }

    $output = p4string::jsonencode($ret);
    break;
}
echo $output;

