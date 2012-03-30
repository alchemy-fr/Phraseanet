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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class set_export extends set_abstract
{

  protected $storage = array();
  protected $total_download;
  protected $total_order;
  protected $total_ftp;
  protected $display_orderable;
  protected $display_download;
  protected $display_ftp;
  protected $ftp_datas;
  protected $list;
  protected $businessFieldsAccess;

  /**
   *
   * @param string $lst
   * @param int $sstid
   * @return set_export
   */
  public function __construct($lst, $sstid, $storyWZid = null)
  {
    $Core = bootstrap::getCore();

    $appbox   = appbox::get_instance($Core);
    $session  = $appbox->get_session();
    $registry = $appbox->get_registry();

    $user = $Core->getAuthenticatedUser();

    $download_list = array();

    $remain_hd = array();

    if ($storyWZid)
    {
      $repository = $Core->getEntityManager()->getRepository('\\Entities\\StoryWZ');

      $storyWZ = $repository->findByUserAndId($user, $storyWZid);

      $lst = $storyWZ->getRecord()->get_serialize_key();
    }

    if ($sstid != "")
    {
      $em         = $Core->getEntityManager();
      $repository = $em->getRepository('\Entities\Basket');

      /* @var $repository \Repositories\BasketRepository */
      $Basket = $repository->findUserBasket($sstid, $user, false);

      foreach ($Basket->getElements() as $basket_element)
      {
        /* @var $basket_element \Entities\BasketElement */
        $base_id   = $basket_element->getRecord()->get_base_id();
        $record_id = $basket_element->getRecord()->get_record_id();

        if (!isset($remain_hd[$base_id]))
        {
          if ($user->ACL()->is_restricted_download($base_id))
          {
            $remain_hd[$base_id] = $user->ACL()->remaining_download($base_id);
          }
          else
          {
            $remain_hd[$base_id] = false;
          }
        }

        $current_element = $download_list[] =
          new record_exportElement(
            $basket_element->getRecord()->get_sbas_id(),
            $record_id,
            $Basket->getName() . '/',
            $remain_hd[$base_id]
        );

        $remain_hd[$base_id] = $current_element->get_remain_hd();
      }
    }
    else
    {
      $tmp_lst = explode(';', $lst);
      $n       = 1;
      foreach ($tmp_lst as $basrec)
      {
        $basrec = explode('_', $basrec);
        if (count($basrec) != 2)
          continue;

        try
        {
          $record = new record_adapter($basrec[0], $basrec[1]);
        }
        catch (Exception_Record_AdapterNotFound $e)
        {
          continue;
        }

        if ($record->is_grouping())
        {
          foreach ($record->get_children() as $child_basrec)
          {
            $base_id   = $child_basrec->get_base_id();
            $record_id = $child_basrec->get_record_id();

            if (!isset($remain_hd[$base_id]))
            {
              if ($user->ACL()->is_restricted_download($base_id))
              {
                $remain_hd[$base_id] =
                  $user->ACL()->remaining_download($base_id);
              }
              else
              {
                $remain_hd[$base_id] = false;
              }
            }

            $current_element = $download_list[] =
              new record_exportElement(
                $child_basrec->get_sbas_id(),
                $record_id,
                $record->get_title() . '_' . $n . '/',
                $remain_hd[$base_id]
            );

            $remain_hd[$base_id] = $current_element->get_remain_hd();
          }
        }
        else
        {
          $base_id   = $record->get_base_id();
          $record_id = $record->get_record_id();

          if (!isset($remain_hd[$base_id]))
          {
            if ($user->ACL()->is_restricted_download($base_id))
            {
              $remain_hd[$base_id] =
                $user->ACL()->remaining_download($base_id);
            }
            else
            {
              $remain_hd[$base_id] = false;
            }
          }

          $current_element                              =
            $download_list[$basrec[0] . '_' . $basrec[1]] =
            new record_exportElement(
              $record->get_sbas_id(),
              $record_id,
              '',
              $remain_hd[$base_id]
          );

          $remain_hd[$base_id] = $current_element->get_remain_hd();
        }
        $n++;
      }
    }

    $this->elements = $download_list;

    $display_download = array();
    $display_orderable = array();

    $this->total_download = 0;
    $this->total_order = 0;
    $this->total_ftp = 0;

    $this->businessFieldsAccess = false;

    foreach ($this->elements as $download_element)
    {
      if($user->ACL()->has_right_on_base($download_element->get_base_id(), 'canmodifrecord'))
      {
        $this->businessFieldsAccess = true;
      }

      foreach ($download_element->get_downloadable() as $name => $properties)
      {
        if (!isset($display_download[$name]))
        {
          $display_download[$name] = array(
            'size'           => 0,
            'total'          => 0,
            'available'      => 0,
            'refused'        => array()
          );
        }

        $display_download[$name]['total']++;

        if ($properties !== false)
        {
          $display_download[$name]['available']++;
          $display_download[$name]['label'] = $properties['label'];
          $display_download[$name]['class'] = $properties['class'];
          $this->total_download++;
          $display_download[$name]['size'] += $download_element->get_size($name);
        }
        else
        {
          $display_download[$name]['refused'][] = $download_element->get_thumbnail();
        }
      }
      foreach ($download_element->get_orderable() as $name => $properties)
      {
        if (!isset($display_orderable[$name]))
        {
          $display_orderable[$name] = array(
            'total'     => 0,
            'available' => 0,
            'refused'   => array()
          );
        }

        $display_orderable[$name]['total']++;

        if ($properties !== false)
        {
          $display_orderable[$name]['available']++;
          $this->total_order++;
        }
        else
        {
          $display_orderable[$name]['refused'][] = $download_element->get_thumbnail();
        }
      }
    }

    foreach ($display_download as $name => $values)
    {
      $display_download[$name]['size'] = (int) $values['size'];
    }

    $display_ftp = array();

    $hasadminright = $user->ACL()->has_right('addrecord')
      || $user->ACL()->has_right('deleterecord')
      || $user->ACL()->has_right('modifyrecord')
      || $user->ACL()->has_right('coll_manage')
      || $user->ACL()->has_right('coll_modify_struct');

    $this->ftp_datas = array();

    if ($registry->get('GV_activeFTP') && ($hasadminright || $registry->get('GV_ftp_for_user')))
    {
      $display_ftp = $display_download;
      $this->total_ftp = $this->total_download;

      $lst_base_id = array_keys($user->ACL()->get_granted_base());

      if ($hasadminright)
      {
        $sql    = "SELECT usr.usr_id,usr_login,usr.addrFTP,usr.loginFTP,usr.sslFTP,
                  usr.pwdFTP,usr.destFTP,prefixFTPfolder,usr.passifFTP,
                  usr.retryFTP,usr.usr_mail
                  FROM (usr INNER JOIN basusr
                      ON ( activeFTP=1
                        AND usr.usr_id=basusr.usr_id
                        AND (basusr.base_id=
                        '" . implode("' OR basusr.base_id='", $lst_base_id) . "'
                            )
                         )
                      )
                  GROUP BY usr_id  ";
        $params = array();
      }
      elseif ($registry->get('GV_ftp_for_user'))
      {
        $sql    = "SELECT usr.usr_id,usr_login,usr.addrFTP,usr.loginFTP,usr.sslFTP,
                usr.pwdFTP,usr.destFTP,prefixFTPfolder,
                usr.passifFTP,usr.retryFTP,usr.usr_mail
                FROM (usr INNER JOIN basusr
                    ON ( activeFTP=1 AND usr.usr_id=basusr.usr_id
                      AND usr.usr_id = :usr_id
                        AND (basusr.base_id=
                        '" . implode("' OR basusr.base_id='", $lst_base_id) . "'
                          )
                        )
                      )
                  GROUP BY usr_id  ";
        $params = array(':usr_id' => $session->get_usr_id());
      }

      $datas[] = array(
        'name'            => _('export::ftp: reglages manuels'),
        'usr_id'          => '0',
        'addrFTP'         => '',
        'loginFTP'        => '',
        'pwdFTP'          => '',
        'ssl'             => '0',
        'destFTP'         => '',
        'prefixFTPfolder' => 'Export_' . date("Y-m-d_H.i.s"),
        'passifFTP'       => false,
        'retryFTP'        => 5,
        'mailFTP'         => '',
        'sendermail'      => $user->get_email()
      );

      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute($params);
      $rs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        $datas[] = array(
          'name'            => $row["usr_login"],
          'usr_id'          => $row['usr_id'],
          'addrFTP'         => $row['addrFTP'],
          'loginFTP'        => $row['loginFTP'],
          'pwdFTP'          => $row['pwdFTP'],
          'ssl'             => $row['sslFTP'],
          'destFTP'         => $row['destFTP'],
          'prefixFTPfolder' =>
          (strlen(trim($row['prefixFTPfolder'])) > 0 ?
            trim($row['prefixFTPfolder']) :
            'Export_' . date("Y-m-d_H.i.s")),
          'passifFTP'       => ($row['passifFTP'] > 0),
          'retryFTP'        => $row['retryFTP'],
          'mailFTP'         => $row['usr_mail'],
          'sendermail'      => $user->get_email()
        );
      }

      $this->ftp_datas = $datas;
    }

    $this->display_orderable = $display_orderable;
    $this->display_download = $display_download;
    $this->display_ftp = $display_ftp;


    return $this;
  }

  /**
   *
   * @return Array
   */
  public function get_ftp_datas()
  {
    return $this->ftp_datas;
  }
  public function has_business_fields_access()
  {
    return $this->businessFieldsAccess;
  }

  /**
   *
   * @return Array
   */
  public function get_display_orderable()
  {
    return $this->display_orderable;
  }

  /**
   *
   * @return Array
   */
  public function get_display_download()
  {
    return $this->display_download;
  }

  /**
   *
   * @return Array
   */
  public function get_display_ftp()
  {
    return $this->display_ftp;
  }

  /**
   *
   * @return Int
   */
  public function get_total_download()
  {
    return $this->total_download;
  }

  /**
   *
   * @return Int
   */
  public function get_total_order()
  {
    return $this->total_order;
  }

  /**
   *
   * @return Int
   */
  public function get_total_ftp()
  {
    return $this->total_ftp;
  }

  /**
   *
   * @param Array $subdefs
   * @param boolean $rename_title
   * @return Array
   */
  public function prepare_export(Array $subdefs, $rename_title, $includeBusinessFields )
  {
    if (!is_array($subdefs))
    {
      throw new Exception('No subdefs given');
    }

    $includeBusinessFields = !!$includeBusinessFields;

    $appbox   = appbox::get_instance(\bootstrap::getCore());
    $session  = $appbox->get_session();
    $registry = $appbox->get_registry();

    $unicode = new unicode();

    $files = array();

    $n_files = 0;

    $file_names = array();

    $size = 0;
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    foreach ($this->elements as $download_element)
    {
      $id = count($files);

      $files[$id] = array(
        'base_id'       => $download_element->get_base_id(),
        'record_id'     => $download_element->get_record_id(),
        'original_name' => '',
        'export_name'   => '',
        'subdefs'       => array()
      );

      $rename_done = false;

      $BF = false;

      if($includeBusinessFields && $user->ACL()->has_right_on_base($download_element->get_base_id(), 'canmodifrecord'))
      {
        $BF = true;
      }

      $desc = $download_element->get_caption()->serialize(caption_record::SERIALIZE_XML, $BF);

      $files[$id]['original_name'] =
        $files[$id]['export_name']   =
        $download_element->get_original_name();

      $files[$id]['original_name'] =
        trim($files[$id]['original_name']) != '' ?
        $files[$id]['original_name'] : $id;

      $infos = pathinfo($files[$id]['original_name']);

      $extension = isset($infos['extension']) ? $infos['extension'] : '';


      if ($rename_title)
      {
        $title = strip_tags($download_element->get_title());

        $files[$id]['export_name'] = $unicode->remove_nonazAZ09($title, true);
        $rename_done               = true;
      }
      else
      {
        $files[$id]["export_name"] = $infos['filename'];
      }

      $sizeMaxAjout = 0;
      $sizeMaxExt   = 0;

      $sd = $download_element->get_subdefs();

      foreach ($download_element->get_downloadable() as $name => $properties)
      {
        if ($properties === false || !in_array($name, $subdefs))
        {
          continue;
        }
        if (!in_array($name, array('caption', 'caption-yaml')) && !isset($sd[$name]))
        {
          continue;
        }

        set_time_limit(100);
        $subdef_export = $subdef_alive  = false;

        $n_files++;

        $tmp_pathfile = array('path' => null, 'file' => null);

        switch ($properties['class'])
        {
          case 'caption':
          case 'caption-yaml':
            $subdef_export = true;
            $subdef_alive  = true;
            break;
          case 'thumbnail':
            $tmp_pathfile  = array(
              'path'         => $sd[$name]->get_path()
              , 'file'         => $sd[$name]->get_file()
            );
            $subdef_export = true;
            $subdef_alive  = true;
            break;
          case 'document':
            $subdef_export = true;
            $path          = recordutils_image::stamp(
                $download_element->get_base_id()
                , $download_element->get_record_id()
                , true
            );
            $tmp_pathfile  = array(
              'path' => $sd[$name]->get_path()
              , 'file' => $sd[$name]->get_file()
            );
            if (file_exists($path))
            {
              $tmp_pathfile = array(
                'path'        => dirname($path)
                , 'file'        => basename($path)
              );
              $subdef_alive = true;
            }
            break;

          case 'preview':
            $subdef_export = true;

            $tmp_pathfile = array(
              'path' => $sd[$name]->get_path()
              , 'file' => $sd[$name]->get_file()
            );
            if (!$user->ACL()->has_right_on_base($download_element->get_base_id(), "nowatermark")
              && !$user->ACL()->has_preview_grant($download_element)
              && $sd[$name]->get_type() == media_subdef::TYPE_IMAGE)
            {
              $path = recordutils_image::watermark(
                  $download_element->get_base_id()
                  , $download_element->get_record_id()
              );
              if (file_exists($path))
              {
                $tmp_pathfile = array(
                  'path'        => dirname($path)
                  , 'file'        => basename($path)
                );
                $subdef_alive = true;
              }
            }
            else
            {
              $subdef_alive = true;
            }
            break;
        }

        if ($subdef_export === true && $subdef_alive === true)
        {
          switch ($properties['class'])
          {
            case 'caption':
              if ($name == 'caption-yaml')
              {
                $suffix    = '_captionyaml';
                $extension = 'yml';
                $mime      = 'text/x-yaml';
              }
              else
              {
                $suffix    = '_caption';
                $extension = 'xml';
                $mime      = 'text/xml';
              }

              $files[$id]["subdefs"][$name]["ajout"]     = $suffix;
              $files[$id]["subdefs"][$name]["exportExt"] = $extension;
              $files[$id]["subdefs"][$name]["label"]     = $properties['label'];
              $files[$id]["subdefs"][$name]["path"]      = null;
              $files[$id]["subdefs"][$name]["file"]      = null;
              $files[$id]["subdefs"][$name]["size"]      = 0;
              $files[$id]["subdefs"][$name]["folder"]    = $download_element->get_directory();
              $files[$id]["subdefs"][$name]["mime"]      = $mime;

              break;
            case 'document':
            case 'preview':
            case 'thumbnail':
              $infos = pathinfo(p4string::addEndSlash($tmp_pathfile["path"]) .
                $tmp_pathfile["file"]);

              $files[$id]["subdefs"][$name]["ajout"]     =
                $properties['class'] == 'document' ? '' : "_" . $name;
              $files[$id]["subdefs"][$name]["path"]      = $tmp_pathfile["path"];
              $files[$id]["subdefs"][$name]["file"]      = $tmp_pathfile["file"];
              $files[$id]["subdefs"][$name]["label"]     = $properties['label'];
              $files[$id]["subdefs"][$name]["size"]      = $sd[$name]->get_size();
              $files[$id]["subdefs"][$name]["mime"]      = $sd[$name]->get_mime();
              $files[$id]["subdefs"][$name]["folder"]    =
                $download_element->get_directory();
              $files[$id]["subdefs"][$name]["exportExt"] =
                isset($infos['extension']) ? $infos['extension'] : '';

              $size += $sd[$name]->get_size();

              break;
          }

          $longueurAjoutCourant =
            mb_strlen($files[$id]["subdefs"][$name]["ajout"]);
          $sizeMaxAjout         = max($longueurAjoutCourant, $sizeMaxAjout);

          $longueurExtCourant =
            mb_strlen($files[$id]["subdefs"][$name]["exportExt"]);
          $sizeMaxExt         = max($longueurExtCourant, $sizeMaxExt);
        }
      }

      $max_length = 31 - $sizeMaxExt - $sizeMaxAjout;

      $name = $files[$id]["export_name"];

      $start_length = mb_strlen($name);
      if ($start_length > $max_length)
        $name         = mb_substr($name, 0, $max_length);

      $n = 1;

      while (in_array(mb_strtolower($name), $file_names))
      {
        $n++;
        $suffix                    = "-" . $n; // pour diese si besoin
        $max_length                = 31 - $sizeMaxExt - $sizeMaxAjout - mb_strlen($suffix);
        $name                      = mb_strtolower($files[$id]["export_name"]);
        if ($start_length > $max_length)
          $name                      = mb_substr($name, 0, $max_length) . $suffix;
        else
          $name                      = $name . $suffix;
      }
      $file_names[]              = mb_strtolower($name);
      $files[$id]["export_name"] = $name;

      $files[$id]["export_name"]   = $unicode->remove_nonazAZ09($files[$id]["export_name"]);
      $files[$id]["original_name"] = $unicode->remove_nonazAZ09($files[$id]["original_name"]);

      $i         = 0;
      $name      = utf8_decode($files[$id]["export_name"]);
      $tmp_name  = "";
      $good_keys = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
        'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
        'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3',
        '4', '5', '6', '7', '8', '9', '-', '_', '.', '#');

      while (isset($name[$i]))
      {
        if (!in_array(mb_strtolower($name[$i]), $good_keys))
          $tmp_name .= '_';
        else
          $tmp_name .= $name[$i];

        $tmp_name = str_replace('__', '_', $tmp_name);

        $i++;
      }
      $files[$id]["export_name"] = $tmp_name;

      if (in_array('caption', $subdefs))
      {
        $caption_dir = $registry->get('GV_RootPath') . 'tmp/desc_tmp/'
          . time() . $session->get_usr_id()
          . $session->get_ses_id() . '/';

        system_file::mkdir($caption_dir);

        $desc = $download_element->get_caption()->serialize(\caption_record::SERIALIZE_XML, $BF);

        $file = $files[$id]["export_name"]
          . $files[$id]["subdefs"]['caption']["ajout"] . '.'
          . $files[$id]["subdefs"]['caption']["exportExt"];

        $path = $caption_dir;

        file_put_contents($path . $file, $desc);

        $files[$id]["subdefs"]['caption']["path"] = $path;
        $files[$id]["subdefs"]['caption']["file"] = $file;
        $files[$id]["subdefs"]['caption']["size"] = filesize($path . $file);
        $files[$id]["subdefs"]['caption']['businessfields'] = $BF ? '1' : '0';
      }
      if (in_array('caption-yaml', $subdefs))
      {
        $caption_dir = $registry->get('GV_RootPath') . 'tmp/desc_tmp/'
          . time() . $session->get_usr_id()
          . $session->get_ses_id() . '/';
        system_file::mkdir($caption_dir);

        $desc = $download_element->get_caption()->serialize(\caption_record::SERIALIZE_YAML, $BF);

        $file = $files[$id]["export_name"]
          . $files[$id]["subdefs"]['caption-yaml']["ajout"] . '.'
          . $files[$id]["subdefs"]['caption-yaml']["exportExt"];

        $path = $caption_dir;

        file_put_contents($path . $file, $desc);

        $files[$id]["subdefs"]['caption-yaml']["path"] = $path;
        $files[$id]["subdefs"]['caption-yaml']["file"] = $file;
        $files[$id]["subdefs"]['caption-yaml']["size"] = filesize($path . $file);
        $files[$id]["subdefs"]['caption-yaml']['businessfields'] = $BF ? '1' : '0';
      }
    }

    $this->list = array(
      'files' => $files,
      'names' => $file_names,
      'size'  => $size,
      'count' => $n_files
    );


    return $this->list;
  }

  /**
   *
   * @param String $token
   * @param Array $list
   * @param string $zipFile
   * @return string
   */
  public static function build_zip($token, Array $list, $zipFile)
  {
    $zip = new ZipArchiveImproved();

    if ($zip->open($zipFile, ZIPARCHIVE::CREATE) !== true)
    {
      return false;
    }
    if (isset($list['complete']) && $list['complete'] === true)
    {
      return;
    }

    $files = $list['files'];


    $list['complete'] = false;

    random::updateToken($token, serialize($list));

    $str_in = array("à", "á", "â", "ã", "ä", "å", "ç", "è", "é", "ê",
      "ë", "ì", "í", "î", "ï", "ð", "ñ", "ò", "ó", "ô",
      "õ", "ö", "ù", "ú", "û", "ü", "ý", "ÿ");
    $str_out = array("a", "a", "a", "a", "a", "a", "c", "e", "e", "e",
      "e", "i", "i", "i", "i", "o", "n", "o", "o", "o",
      "o", "o", "u", "u", "u", "u", "y", "y");

    $caption_dirs = $unlinks      = array();

    foreach ($files as $record)
    {
      if (isset($record["subdefs"]))
      {
        foreach ($record["subdefs"] as $o => $obj)
        {
          $path = p4string::addEndSlash($obj["path"]) . $obj["file"];
          if (is_file($path))
          {
            $name = $obj["folder"]
              . $record["export_name"]
              . $obj["ajout"]
              . '.' . $obj["exportExt"];

            $name = str_replace($str_in, $str_out, $name);

            $zip->addFile($path, $name);

            if ($o == 'caption')
            {
              if (!in_array(dirname($path), $caption_dirs))
                $caption_dirs[] = dirname($path);
              $unlinks[]      = $path;
            }
          }
        }
      }
    }

    $zip->close();

    $list['complete'] = true;

    random::updateToken($token, serialize($list));

    foreach ($unlinks as $u)
    {
      @unlink($u);
    }
    foreach ($caption_dirs as $c)
    {
      @rmdir($c);
    }

    $system_file = new system_file($zipFile);
    $system_file->chmod();

    return $zipFile;
  }

  /**
   *
   * @param string $file
   * @param string $exportname
   * @param string $mime
   * @param string $disposition
   * @return Void
   */
  public static function stream_file(
  $file, $exportname, $mime, $disposition = 'attachment')
  {
    $registry = registry::get_instance();

    $disposition = in_array($disposition, array('inline', 'attachment')) ?
      $disposition : 'attachment';

    $response = new Symfony\Component\HttpFoundation\Response();

    if (is_file($file))
    {
      $testPath = function($file, $registry)
        {
          return strpos($file, $registry->get('GV_RootPath') . 'tmp/download/') !== false
            || strpos($file, $registry->get('GV_RootPath') . 'tmp/lazaret/') !== false
            || strpos($file, $registry->get('GV_X_Accel_Redirect')) !== false;
        };

      if ($registry->get('GV_modxsendfile') && $testPath($file, $registry))
      {
        $file_xaccel = str_replace(
          array(
          $registry->get('GV_X_Accel_Redirect'),
          $registry->get('GV_RootPath') . 'tmp/download/',
          $registry->get('GV_RootPath') . 'tmp/lazaret/'
          )
          , array(
          '/' . $registry->get('GV_X_Accel_Redirect_mount_point') . '/',
          '/download/',
          '/lazaret/'
          )
          , $file
        );
        $response->headers->set('X-Sendfile', $file);
        $response->headers->set('X-Accel-Redirect', $file_xaccel);
        $response->headers->set('Pragma', 'public', true);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Name', $exportname);
        $response->headers->set('Content-Disposition', $disposition . "; filename=" . $exportname . ";");
        $response->headers->set('Content-Length', filesize($file));

        return $response;
      }
      else
      {
        /**
         *
         * Header "Pragma: public" SHOULD be present.
         * In case it is not present, download on IE 8 and previous over HTTPS
         * will fail.
         *
         * @todo : merge this shitty fix with Response object.
         *
         */
        if (!headers_sent())
        {
          header("Pragma: public");
        }

        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Name', $exportname);
        $response->headers->set('Content-Disposition', $disposition . "; filename=" . $exportname . ";");
        $response->headers->set('Content-Length', filesize($file));
        $response->setContent(file_get_contents($file));

        return $response;
      }
    }

    return $response;
  }

  /**
   *
   * @param String $data
   * @param String $exportname
   * @param String $mime
   * @param String $disposition
   * @return Void
   */
  public static function stream_data($data, $exportname, $mime, $disposition = 'attachment')
  {

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Content-Type: " . $mime);
    header("Content-Length: " . strlen($data));
    header("Cache-Control: max-age=3600, must-revalidate ");
    header("Content-Disposition: " . $disposition
      . "; filename=" . $exportname . ";");

    echo $data;

    return true;
  }

  /**
   * @todo a revoir le cas anonymous
   *
   * @param Array $list
   * @param String> $type
   * @param boolean $anonymous
   * @return Void
   */
  public static function log_download(Array $list, $type, $anonymous = false, $comment = '')
  {
    //download
    $appbox  = appbox::get_instance(\bootstrap::getCore());
    $session = $appbox->get_session();
    $user    = false;
    if ($anonymous)
    {

    }
    else
    {
      $user = User_Adapter::getInstance($session->get_usr_id(), appbox::get_instance(\bootstrap::getCore()));
    }

    $tmplog = array();
    $files = $list['files'];

    $event_names = array(
      'mail-export' => Session_Logger::EVENT_EXPORTMAIL,
      'download'    => Session_Logger::EVENT_EXPORTDOWNLOAD
    );

    $event_name = isset($event_names[$type]) ? $event_names[$type] : Session_Logger::EVENT_EXPORTDOWNLOAD;

    foreach ($files as $record)
    {
      foreach ($record["subdefs"] as $o => $obj)
      {
        $sbas_id       = phrasea::sbasFromBas($record['base_id']);
        $record_object = new record_adapter($sbas_id, $record['record_id']);

        $session->get_logger($record_object->get_databox())
          ->log($record_object, $event_name, $o, $comment);

        if ($o != "caption")
        {
          $log["rid"]                              = $record_object->get_record_id();
          $log["subdef"]                           = $o;
          $log["poids"]                            = $obj["size"];
          $log["shortXml"]                         = $record_object->get_caption()->serialize(caption_record::SERIALIZE_XML);
          $tmplog[$record_object->get_base_id()][] = $log;
          if (!$anonymous && $o == 'document')
            $user->ACL()->remove_remaining($record_object->get_base_id());
        }

        unset($record_object);
      }
    }

    $export_types = array(
      'download'    => 0,
      'mail-export' => 2,
      'ftp'         => 4
    );

    $list_base = array_unique(array_keys($tmplog));

    if (!$anonymous)
    {
      $sql = "UPDATE basusr
            SET remain_dwnld = :remain_dl
            WHERE base_id = :base_id AND usr_id = :usr_id";

      $stmt = $appbox->get_connection()->prepare($sql);

      foreach ($list_base as $base_id)
      {
        if ($user->ACL()->is_restricted_download($base_id))
        {
          $params = array(
            ':remain_dl' => $user->ACL()->remaining_download($base_id)
            , ':base_id'   => $base_id
            , ':usr_id'    => $user->get_id()
          );

          $stmt->execute($params);
        }
      }

      $stmt->closeCursor();
    }

    return;
  }

}
