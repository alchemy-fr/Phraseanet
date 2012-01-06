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
require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms("srt", "ord", "act", "p0", // base_id
                            "str" // si act=CHGSTRUCTURE, structure en xml
);


$parm['p0'] = (int) $parm['p0'];

if ($parm['p0'] <= 0)
  phrasea::headers(400);

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if (!$user->ACL()->has_right_on_sbas($parm['p0'], 'bas_modify_struct'))
{
  phrasea::headers(403);
}

$databox = databox::get_instance((int) $parm['p0']);
$fields = $databox->get_meta_structure();
$available_fields = databox::get_available_metadatas();
$available_dc_fields = $databox->get_available_dcfields();

phrasea::headers();

if (!empty($_POST))
{

  $databox->get_connection()->beginTransaction();
  $error = false;
  try
  {
    $httpRequest = new http_request();
    $ids = $httpRequest->get_parms('field_ids');

    $reg_attrs = $httpRequest->get_parms('regname', 'regdesc', 'regdate');
    if (is_array($ids['field_ids']))
    {
      foreach ($ids['field_ids'] as $id)
      {
        try
        {
          $local_parms = $httpRequest->get_parms(
                  'name_' . $id
                  , 'thumbtitle_' . $id
                  , 'src_' . $id
                  , 'multi_' . $id
                  , 'indexable_' . $id
                  , 'readonly_' . $id
                  , 'required_' . $id
                  , 'separator_' . $id
                  , 'type_' . $id
                  , 'tbranch_' . $id
                  , 'report_' . $id
                  , 'dces_' . $id
          );

          $field = databox_field::get_instance($databox, $id);
          $field->set_name($local_parms['name_' . $id]);
          $field->set_thumbtitle($local_parms['thumbtitle_' . $id]);
          $field->set_source($local_parms['src_' . $id]);
          $field->set_multi($local_parms['multi_' . $id]);
          $field->set_indexable($local_parms['indexable_' . $id]);
          $field->set_required($local_parms['required_' . $id]);
          $field->set_separator($local_parms['separator_' . $id]);
          $field->set_readonly($local_parms['readonly_' . $id]);
          $field->set_type($local_parms['type_' . $id]);
          $field->set_tbranch($local_parms['tbranch_' . $id]);
          $field->set_report($local_parms['report_' . $id]);

          $dces_element = null;
          if ($local_parms['dces_' . $id] !== '')
          {
            $class = 'databox_Field_DCES_' . $local_parms['dces_' . $id];
            if (class_exists($class))
              $dces_element = new $class();
          }

          $field->set_dces_element($dces_element);
          $field->save();

          if ($reg_attrs['regname'] == $field->get_id())
          {
            $field->set_regname();
          }
          if ($reg_attrs['regdate'] == $field->get_id())
          {
            $field->set_regdate();
          }
          if ($reg_attrs['regdesc'] == $field->get_id())
          {
            $field->set_regdesc();
          }
        }
        catch (Exception $e)
        {
          continue;
        }
      }
    }

    $parms = $httpRequest->get_parms('newfield');

    if ($parms['newfield'])
    {
      databox_field::create($databox, $parms['newfield']);
    }

    $ids = $httpRequest->get_parms('todelete_ids');
    if (is_array($ids['todelete_ids']))
    {
      foreach ($ids['todelete_ids'] as $id)
      {
        try
        {
          $field = databox_field::get_instance($databox, $id);
          $field->delete();
        }
        catch (Exception $e)
        {
          
        }
      }
    }
  }
  catch (Exception $e)
  {
    $error = true;
  }
  if ($error)
    $databox->get_connection()->rollBack();
  else
    $databox->get_connection()->commit();
  phrasea::redirect('/admin/description.php?p0=' . $parm['p0']);
}

$params = array(
    'databox' => $databox,
    'fields' => $fields,
    'available_fields' => $available_fields,
    'available_dc_fields' => $available_dc_fields
);


$core = \bootstrap::getCore();
$twig = $core->getTwig();

echo $twig->render('admin/databox/doc_structure.twig', $params);
