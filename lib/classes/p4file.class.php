<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class p4file
{

  public static function apache_tokenize($file)
  {
    $ret = false;
    $registry = registry::get_instance();

    if ($registry->get('GV_h264_streaming') && is_file($file))
    {
      if (($pos = mb_strpos($file, $registry->get('GV_mod_auth_token_directory_path'))) === false)
      {
        return false;
      }

      $server = new system_server();

      if ($server->is_nginx())
      {
        $fileToProtect = mb_substr($file, mb_strlen($registry->get('GV_mod_auth_token_directory_path')));

        $secret = $registry->get('GV_mod_auth_token_passphrase');
        $protectedPath = p4string::addFirstSlash(p4string::delEndSlash($registry->get('GV_mod_auth_token_directory')));

        $hexTime = strtoupper(dechex(time() + 3600));

        $token = md5($protectedPath . $fileToProtect . '/' . $secret . '/' . $hexTime);

        $url = $protectedPath . $fileToProtect . '/' . $token . '/' . $hexTime;

        $ret = $url;
      }
      elseif ($server->is_apache())
      {
        $fileToProtect = mb_substr($file, mb_strlen($registry->get('GV_mod_auth_token_directory_path')));


        $secret = $registry->get('GV_mod_auth_token_passphrase');        // Same as AuthTokenSecret
        $protectedPath = p4string::addEndSlash(p4string::delFirstSlash($registry->get('GV_mod_auth_token_directory')));         // Same as AuthTokenPrefix
        $hexTime = dechex(time());             // Time in Hexadecimal

        $token = md5($secret . $fileToProtect . $hexTime);

        // We build the url
        $url = '/' . $protectedPath . $token . "/" . $hexTime . $fileToProtect;


        $ret = $url;
      }
    }

    return $ret;
  }

  public static function archiveFile(system_file &$system_file, $base_id, $delete=true, $name=false)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $registry = $appbox->get_registry();

    $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_ORIGINALNAME, $name ? $name : $system_file->getFilename());
    $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_PARENTDIRECTORY, null);
    $system_file->set_phrasea_tech_field(system_file::TECH_FIELD_SUBPATH, null);

    $go = User_Adapter::getInstance($session->get_usr_id(), $appbox)
            ->ACL()
            ->has_right_on_base($base_id, 'canaddrecord');

    if (!$go)
    {
      echo "\ndroits insuffisants";

      return false;
    }

    $sbas_id = phrasea::sbasFromBas($base_id);
    $databox = databox::get_instance($sbas_id);
    $collection = collection::get_from_base_id($base_id);

    $collprefs = simplexml_load_string($collection->get_prefs());

    $server_coll_id = phrasea::collFromBas($base_id);

    if ($collprefs === false)
    {
      if (GV_debug)
        echo 'Error loading collprefs';

      return false;
    }

    $metadatas = $system_file->extract_metadatas($databox->get_meta_structure());

    $status = "0";

    if ($collprefs->status)
      $status = (string) ($collprefs->status);

    $record_id = $record = false;

    try
    {
      $record = record_adapter::create($collection, $system_file, $name);
      $record_id = $record->get_record_id();
      $record->set_metadatas($metadatas['metadatas']);
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
      if ($record instanceof record_adapter)
        $record->delete();

      return false;
    }

    $record->set_binary_status(databox_status::dec2bin($status));
    $record->rebuild_subdefs();
    $record->reindex();

    if ($delete)
    {
      @unlink($system_file->getPathname());
      unset($system_file);
    }

    return $record_id;
  }

  public static function check_file_error($filename, $sbas_id, $originalname)
  {
    $registry = registry::get_instance();

    $system_file = new system_file($filename);

    $doctype = $system_file->get_phrasea_type();

    $databox = databox::get_instance($sbas_id);
    if ($baseprefs = $databox->get_sxml_structure())
    {
      $file_checks = $baseprefs->filechecks;
    }
    else
      throw new Exception(_('prod::erreur : impossible de lire les preferences de base'));


    $errors = array();

    $datas = exiftool::get_fields($filename, array('Color Space Data', 'Color Space', 'Color Mode', 'Image Width', 'Image Height'));

    if ($checks = $file_checks->$doctype)
    {
      foreach ($checks[0] as $name => $value)
      {
        switch ($name)
        {
          case 'name':
            $records = record_adapter::get_records_by_originalname($databox, $original_name, 0, 1);
            if (count($records) > 0)
              $errors[] = sprintf(_('Le fichier \'%s\' existe deja'), $originalname);
            break;
          case 'size':
            $min = min($datas['Image Height'], $datas['Image Width']);
            if ($min < (int) $value)
            {
              $errors[] = sprintf(_('Taille trop petite : %dpx'), $min);
            }
            break;
          case 'color_space':
            $required = in_array($value, array('sRGB', 'RGB')) ? 'RGB' : $value;
            $go = false;

            $results = array($datas['Color Space'], $datas['Color Space Data'], $datas['Color Mode']);

            $results_str = implode(' ', $results);

            if (trim($results_str) === '')
            {
              $go = true;
            }
            else
            {
              if ($required == 'RGB' && count(array_intersect($results, array('sRGB', 'RGB'))) > 0)
              {
                $go = true;
              }
              elseif (in_array($required, $results))
              {
                $go = true;
              }
            }


            if (!$go)
            {
              $errors[] = sprintf(_('Mauvais mode colorimetrique : %s'), $results_str);
            }

            break;
        }
      }
    }

    return $errors;
  }

  public static function substitute(record_Interface $record, $new_pathfile, $filename, $update_filename = false)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $registry = $appbox->get_registry();

    $sbas_id = $record->get_sbas_id();
    $record_id = $record->get_record_id();
    $base_id = $record->get_base_id();
    $connbas = connection::getPDOConnection($sbas_id);


    $databox = databox::get_instance($sbas_id);
    if ($baseprefs = $databox->get_sxml_structure())
    {
      $pathhd = p4string::addEndSlash((string) ($baseprefs->path));
      $baseurl = (string) ($baseprefs->baseurl);
    }
    else
      throw new Exception(_('prod::erreur : impossible de lire les preferences de base'));

    if (trim($pathhd) == "" || !is_dir($pathhd))
      throw new Exception(_('prod::substitution::erreur : impossible d\'acceder au dossier de stockage "' . $pathhd . '" '));

    $sd = $record->get_subdefs('document');

    if (isset($sd) && isset($sd["document"]))
    {
      $sd["document"]["path"] = p4string::addEndSlash($sd["document"]["path"]);

      $pathhd = p4string::addEndSlash($sd["document"]["path"]);
      $filehd = $sd["document"]["file"];

      $pathfile = $pathhd . $filehd;

      if (file_exists($pathfile) && !is_dir($pathfile))
      {
        if (!@unlink($pathfile))
          throw new Exception(_('prod::substitution::erreur : impossible de supprimer l\'ancien document'));
      }
    }
    else
    {
      $ext = "";
      $pitmp = pathinfo($filename);
      if (isset($pitmp['extension']))
        $ext = $pitmp['extension'];
      $filehd = $record_id . "_document." . $ext;
      $pathhd = databox::dispatch($pathhd);

      $pathfile = $pathhd . $filehd;
    }

    $sql = "DELETE FROM subdef WHERE record_id = :record_id AND name='document'";

    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $record_id));
    $stmt->closeCursor();

    $width = $height = 0;

    if (!rename($new_pathfile, $pathfile))
      throw new Exception(_('prod::substitution::document remplace avec succes'));

    if ($tempHD = getimagesize($pathfile))
    {
      $width = $tempHD[0];
      $height = $tempHD[1];
    }

    $system_file = new system_file($pathfile);


    $sql = "INSERT INTO subdef (record_id, name, path, file, baseurl, width, height, mime, size) VALUES
                (:record_id, 'document', :pathhd, :file_hd , :baseurl, :width  ,  :height , :mime, :filesize)";

    $params = array(
        ':record_id' => $record_id
        , ':pathhd' => $pathhd
        , ':file_hd' => $filehd
        , ':baseurl' => $baseurl
        , ':width' => $width
        , ':height' => $height
        , ':mime' => $system_file->get_mime()
        , ':filesize' => filesize($pathfile)
    );

    $stmt = $connbas->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $session->get_logger($record->get_databox())
            ->log($record, Session_Logger::EVENT_SUBSTITUTE, 'HD', '');

    $desc = $record->get_xml();

    $doc = new DOMDocument();
    $doc->loadXML($desc);
    $params = $doc->getElementsByTagName('doc');
    $newname = null;

    foreach ($params as $param)
    {
      $oldname = $param->getAttribute('originalname');
      if ($oldname)
      {
        $oldpi = pathinfo($oldname);
        $val = "";
        $pi = pathinfo($filename);
        if (isset($pi["extension"]))
          $val = $pi["extension"];

        if ($update_filename)
          $newname = $pi['basename'];
        else
          $newname = $oldpi['filename'] . "." . $val;
      }
      if ($newname)
        $param->setAttribute('originalname', $newname);
      $param->removeAttribute('channels');
      $param->removeAttribute('bits');

      $param->setAttribute('height', $height);
      $param->setAttribute('width', $width);
      $param->setAttribute('size', filesize($pathfile));
      $param->setAttribute('mime', $system_file->get_mime());
      if (isset($tempHD["bits"]))
        $param->setAttribute('bits', $tempHD["bits"]);
      if (isset($tempHD["channels"]))
        $param->setAttribute('channels', $tempHD["channels"]);
    }

    $xp_rec = new DOMXPath($doc);
    $databox = databox::get_instance($sbas_id);
    $sx_struct = $databox->get_sxml_structure();
    foreach ($sx_struct->description->children() as $fn => $fv)
    {
      if (isset($fv["src"]) && (substr($fv["src"], 0, 2) == "tf"))
      {
        switch ($fv["src"])
        {
          case "tf-chgdocdate":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = date('Y/m/d H:i:s', time());
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = date('Y/m/d H:i:s', time());
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;

          case "tf-filename":
            if ($newname && ($recdesc = $xp_rec->query("/record/description/$fn")))
            {
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = $newname;
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = $newname;
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;

          case "tf-extension":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              $val = "";
              $pi = pathinfo($filename);
              if (isset($pi["extension"]))
                $val = $pi["extension"];
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = $val;
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = $val;
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;

          case "tf-mimetype":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              $val = $system_file->get_mime();
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = $val;
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = $val;
                $newnode = $recdesc->appendChild($node);
              }
            }

            break;

          case "tf-size":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = filesize($pathfile);
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = filesize($pathfile);
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;

          case "tf-width":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = $width;
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = $width;
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;

          case "tf-height":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = $height;
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = $height;
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;

          case "tf-bits":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              $val = "";
              if (isset($tempHD["bits"]))
                $val = $tempHD["bits"];
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = $val;
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = $val;
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;

          case "tf-channels":
            if ($recdesc = $xp_rec->query("/record/description/$fn"))
            {
              $val = "";
              if (isset($tempHD["channels"]))
                $val = $tempHD["channels"];
              if ($recdesc->item(0))
                $recdesc->item(0)->nodeValue = $val;
              else
              {
                $recdesc = $xp_rec->query("/record/description")->item(0);
                $node = $doc->createElement($fn);
                $node->nodeValue = $val;
                $newnode = $recdesc->appendChild($node);
              }
            }
            break;
        }
      }
    }
    $newXml = $doc->savexml();

    $record->set_xml($newXml);

    $sql = "UPDATE record SET status=status & ~3, moddate=NOW()
            WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $record_id));
    $stmt->closeCursor();

    $record->write_metas();
    $filesToSet = null;
    $filesToSet[] = $pathfile;

    return true;
  }

}
