<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Filesystem\Filesystem;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class set_export extends set_abstract
{
    private static $maxFilenameLength = 256;

    /**
     * @param int $newLength
     */
    public static function setMaxFilenameLength($newLength)
    {
        if (!is_int($newLength) || $newLength <= 0) {
            throw new \InvalidArgumentException('Expects $newLength argument to be a positive integer');
        }

        self::$maxFilenameLength = $newLength;
    }

    /**
     * @var Application
     */
    protected $app;
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
    protected $exportName;

    /**
     *
     * @param  Application $app
     * @param  string      $lst
     * @param  integer     $sstid
     * @param  integer     $storyWZid
     * @return set_export
     */
    public function __construct(Application $app, $lst, $sstid, $storyWZid = null)
    {
        $this->app = $app;

        $download_list = array();

        $remain_hd = array();

        if ($storyWZid) {
            $repository = $app['EM']->getRepository('\\Entities\\StoryWZ');

            $storyWZ = $repository->findByUserAndId($this->app, $app['authentication']->getUser(), $storyWZid);

            $lst = $storyWZ->getRecord($this->app)->get_serialize_key();
        }

        if ($sstid != "") {
            $repository = $app['EM']->getRepository('\Entities\Basket');

            /* @var $repository \Repositories\BasketRepository */
            $Basket = $repository->findUserBasket($this->app, $sstid, $app['authentication']->getUser(), false);
            $this->exportName = str_replace(array(' ', '\\', '/'), '_', $Basket->getName()) . "_" . date("Y-n-d");

            foreach ($Basket->getElements() as $basket_element) {
                /* @var $basket_element \Entities\BasketElement */
                $base_id = $basket_element->getRecord($this->app)->get_base_id();
                $record_id = $basket_element->getRecord($this->app)->get_record_id();

                if (!isset($remain_hd[$base_id])) {
                    if ($app['authentication']->getUser()->ACL()->is_restricted_download($base_id)) {
                        $remain_hd[$base_id] = $app['authentication']->getUser()->ACL()->remaining_download($base_id);
                    } else {
                        $remain_hd[$base_id] = false;
                    }
                }

                $current_element = $download_list[] =
                    new record_exportElement(
                        $app,
                        $basket_element->getRecord($this->app)->get_sbas_id(),
                        $record_id,
                        $Basket->getName(),
                        $remain_hd[$base_id]
                );

                $remain_hd[$base_id] = $current_element->get_remain_hd();
            }
        } else {
            $this->exportName = "Export_" . date("Y-n-d") . '_' . mt_rand(100, 999);

            $tmp_lst = explode(';', $lst);
            $n = 1;
            foreach ($tmp_lst as $basrec) {
                $basrec = explode('_', $basrec);
                if (count($basrec) != 2)
                    continue;

                try {
                    $record = new record_adapter($this->app, $basrec[0], $basrec[1]);
                } catch (\Exception_Record_AdapterNotFound $e) {
                    continue;
                }

                if ($record->is_grouping()) {
                    foreach ($record->get_children() as $child_basrec) {
                        $base_id = $child_basrec->get_base_id();
                        $record_id = $child_basrec->get_record_id();

                        if (!isset($remain_hd[$base_id])) {
                            if ($app['authentication']->getUser()->ACL()->is_restricted_download($base_id)) {
                                $remain_hd[$base_id] = $app['authentication']->getUser()->ACL()->remaining_download($base_id);
                            } else {
                                $remain_hd[$base_id] = false;
                            }
                        }

                        $current_element = $download_list[] =
                            new record_exportElement(
                                $app,
                                $child_basrec->get_sbas_id(),
                                $record_id,
                                $record->get_title(null, null, true) . '_' . $n,
                                $remain_hd[$base_id]
                        );

                        $remain_hd[$base_id] = $current_element->get_remain_hd();
                    }
                } else {
                    $base_id = $record->get_base_id();
                    $record_id = $record->get_record_id();

                    if (!isset($remain_hd[$base_id])) {
                        if ($app['authentication']->getUser()->ACL()->is_restricted_download($base_id)) {
                            $remain_hd[$base_id] = $app['authentication']->getUser()->ACL()->remaining_download($base_id);
                        } else {
                            $remain_hd[$base_id] = false;
                        }
                    }

                    $current_element =
                        $download_list[$basrec[0] . '_' . $basrec[1]] =
                        new record_exportElement(
                            $app,
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

        foreach ($this->elements as $download_element) {
            if ($app['authentication']->getUser()->ACL()->has_right_on_base($download_element->get_base_id(), 'canmodifrecord')) {
                $this->businessFieldsAccess = true;
            }

            foreach ($download_element->get_downloadable() as $name => $properties) {
                if (!isset($display_download[$name])) {
                    $display_download[$name] = array(
                        'size'      => 0,
                        'total'     => 0,
                        'available' => 0,
                        'refused'   => array()
                    );
                }

                $display_download[$name]['total']++;

                if ($properties !== false) {
                    $display_download[$name]['available']++;
                    $display_download[$name]['label'] = $properties['label'];
                    $display_download[$name]['class'] = $properties['class'];
                    $this->total_download++;
                    $display_download[$name]['size'] += $download_element->get_size($name);
                } else {
                    $display_download[$name]['refused'][] = $download_element->get_thumbnail();
                }
            }
            foreach ($download_element->get_orderable() as $name => $properties) {
                if (!isset($display_orderable[$name])) {
                    $display_orderable[$name] = array(
                        'total'     => 0,
                        'available' => 0,
                        'refused'   => array()
                    );
                }

                $display_orderable[$name]['total']++;

                if ($properties !== false) {
                    $display_orderable[$name]['available']++;
                    $this->total_order++;
                } else {
                    $display_orderable[$name]['refused'][] = $download_element->get_thumbnail();
                }
            }
        }

        foreach ($display_download as $name => $values) {
            $display_download[$name]['size'] = (int) $values['size'];
        }

        $display_ftp = array();

        $hasadminright = $app['authentication']->getUser()->ACL()->has_right('addrecord')
            || $app['authentication']->getUser()->ACL()->has_right('deleterecord')
            || $app['authentication']->getUser()->ACL()->has_right('modifyrecord')
            || $app['authentication']->getUser()->ACL()->has_right('coll_manage')
            || $app['authentication']->getUser()->ACL()->has_right('coll_modify_struct');

        $this->ftp_datas = array();

        if ($this->app['phraseanet.registry']->get('GV_activeFTP') && ($hasadminright || $this->app['phraseanet.registry']->get('GV_ftp_for_user'))) {
            $display_ftp = $display_download;
            $this->total_ftp = $this->total_download;

            $lst_base_id = array_keys($app['authentication']->getUser()->ACL()->get_granted_base());

            if ($hasadminright) {
                $sql = "SELECT usr.usr_id,usr_login,usr.addrFTP,usr.loginFTP,usr.sslFTP,
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
            } elseif ($this->app['phraseanet.registry']->get('GV_ftp_for_user')) {
                $sql = "SELECT usr.usr_id,usr_login,usr.addrFTP,usr.loginFTP,usr.sslFTP,
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
                $params = array(':usr_id' => $app['authentication']->getUser()->get_id());
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
                'sendermail'      => $app['authentication']->getUser()->get_email()
            );

            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute($params);
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
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
                    'sendermail'      => $app['authentication']->getUser()->get_email()
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
     * Return export name
     *
     * @return string
     */
    public function getExportName()
    {
        return $this->exportName;
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
     * @param User_Adapter $user
     * @param Filesystem $filesystem
     * @param array $wantedSubdefs
     * @param $rename_title
     * @param $includeBusinessFields
     * @return array
     * @throws Exception
     */
    public function prepare_export(User_Adapter $user, Filesystem $filesystem, Array $wantedSubdefs, $rename_title, $includeBusinessFields)
    {
        if (!is_array($wantedSubdefs)) {
            throw new Exception('No subdefs given');
        }

        $includeBusinessFields = (bool) $includeBusinessFields;
        $files = array();
        $n_files = 0;
        $file_names = array();
        $size = 0;
        $unicode = new \unicode();

        /** @var record_exportElement $download_element */
        foreach ($this->elements as $download_element) {

            $id = count($files);

            $files[$id] = array(
                'base_id'       => $download_element->get_base_id(),
                'record_id'     => $download_element->get_record_id(),
                'original_name' => '',
                'export_name'   => '',
                'subdefs'       => array()
            );

            $BF = false;

            if ($includeBusinessFields && $user->ACL()->has_right_on_base($download_element->get_base_id(), 'canmodifrecord')) {
                $BF = true;
            }

            // $original_name : the original_name WITHOUT extension (ex. "DSC_1234")
            // $extension     : the extension WITHOUT DOT (ex. "jpg")
            // $export_name   : the export name WITHOUT extension, (ex. "Hollydays_2016_DSC_1234")

            //
            // build the original_name
            //
            $original_name = trim($download_element->get_original_name(false));   // false: don't remove extension
            if($original_name !== '') {
                $infos = pathinfo($original_name);  // pathinfo DOES USE the last '.' to find extension
                $original_name = $infos['filename'];
                $extension = isset($infos['extension']) ? $infos['extension'] : '';
            }
            else {
                $extension = '';
            }
            if($original_name == '') {
                $original_name = (string)$id;
            }
            $files[$id]['original_name'] = $original_name;

            //
            // build the export_name
            //
            if ($rename_title) {
                // use the title (may be a concat of fields)
                $export_name = strip_tags($download_element->get_title(null, null, true));
                // if the "title" ends up with a "filename-like" field, remove extension
                if (strtolower(substr($export_name, -strlen($extension)-1)) === '.'.strtolower($extension)) {
                    $export_name = substr($export_name, 0, strlen($export_name)-1-strlen($extension));
                }
            } else {
                $export_name = $original_name;
            }

            // cleanup the exportname so it can be used as a filename (even if it came from the originale_name)
            $export_name = str_replace([' ', "\t", "\r", "\n"], '_', $export_name);
            $export_name = $unicode->remove_nonazAZ09($export_name, true, true, true);
            // really no luck if nothing left ?
            if($export_name == '') {
                $export_name = (string)$id;
            }

            //
            // loop on subdefs to be downloaded (this may change the export_name)
            //
            $sizeMaxAjout = $sizeMaxExt = 0;
            $sd = $download_element->get_subdefs();
            foreach ($download_element->get_downloadable() as $subdefName => $properties) {
                if ($properties === false || !in_array($subdefName, $wantedSubdefs)) {
                    continue;
                }
                if (!in_array($subdefName, ['caption', 'caption-yaml']) && !isset($sd[$subdefName])) {
                    continue;
                }

                set_time_limit(100);
                $subdef_ok = false;

                $n_files++;

                $tmp_pathfile = [
                    'path' => null,
                    'file' => null
                ];

                switch ($properties['class']) {
                    case 'caption':
                    case 'caption-yaml':
                        $subdef_ok = true;
                        break;
                    case 'thumbnail':
                        $subdef_ok = true;
                        $tmp_pathfile = [
                            'path' => $sd[$subdefName]->get_path(),
                            'file' => $sd[$subdefName]->get_file()
                        ];
                        break;
                    case 'document':
                        $subdef_ok = true;
                        $tmp_pathfile = [
                            'path' => $sd[$subdefName]->get_path(),
                            'file' => $sd[$subdefName]->get_file()
                        ];
                        $path = \recordutils_image::stamp($this->app , $sd[$subdefName]);
                        if (file_exists($path)) {
                            $tmp_pathfile = [
                                'path' => dirname($path),
                                'file' => basename($path)
                            ];
                        }
                        break;
                    case 'preview':
                        $tmp_pathfile = [
                            'path' => $sd[$subdefName]->get_path(),
                            'file' => $sd[$subdefName]->get_file()
                        ];
                        if (!$user->ACL()->has_right_on_base($download_element->get_base_id(), "nowatermark")
                            && !$user->ACL()->has_preview_grant($download_element)
                            && $sd[$subdefName]->get_type() == media_subdef::TYPE_IMAGE) {
                            $path = recordutils_image::watermark($this->app, $sd[$subdefName]);
                            if (file_exists($path)) {
                                $tmp_pathfile = [
                                    'path' => dirname($path),
                                    'file' => basename($path)
                                ];
                                $subdef_ok = true;
                            }
                        } else {
                            $subdef_ok = true;
                        }
                        break;
                }

                if ($subdef_ok) {
                    switch ($properties['class']) {
                        case 'caption':
                            $ajout = '_caption';
                            if ($subdefName == 'caption-yaml') {
                                $ext = 'yml';
                                $mime = 'text/x-yaml';
                            } else {
                                $ext = 'xml';
                                $mime = 'text/xml';
                            }

                            $files[$id]["subdefs"][$subdefName]["ajout"] = $ajout;
                            $files[$id]["subdefs"][$subdefName]["exportExt"] = $ext;
                            $files[$id]["subdefs"][$subdefName]["label"] = $properties['label'];
                            $files[$id]["subdefs"][$subdefName]["path"] = null;
                            $files[$id]["subdefs"][$subdefName]["file"] = null;
                            $files[$id]["subdefs"][$subdefName]["size"] = 0;
                            $files[$id]["subdefs"][$subdefName]["mime"] = $mime;
                            $files[$id]["subdefs"][$subdefName]["folder"] = $download_element->get_directory();

                            break;

                        case 'document':
                        case 'preview':
                        case 'thumbnail':
                            $ajout = $subdefName == 'document' ? '' : ("_" . $subdefName);
                            $infos = pathinfo(p4string::addEndSlash($tmp_pathfile["path"]) . $tmp_pathfile["file"]);
                            $ext = isset($infos['extension']) ? $infos['extension'] : '';

                            $files[$id]["subdefs"][$subdefName]["ajout"] = $ajout;
                            $files[$id]["subdefs"][$subdefName]["exportExt"] = $ext;
                            $files[$id]["subdefs"][$subdefName]["label"] = $properties['label'];
                            $files[$id]["subdefs"][$subdefName]["path"] = $tmp_pathfile["path"];
                            $files[$id]["subdefs"][$subdefName]["file"] = $tmp_pathfile["file"];
                            $files[$id]["subdefs"][$subdefName]["size"] = $sd[$subdefName]->get_size();
                            $files[$id]["subdefs"][$subdefName]["mime"] = $sd[$subdefName]->get_mime();
                            $files[$id]["subdefs"][$subdefName]["folder"] = $download_element->get_directory();

                            $size += $sd[$subdefName]->get_size();

                            break;

                        default:    // should no happen
                            $ajout = $ext = '';
                            break;
                    }

                    $sizeMaxAjout = max($sizeMaxAjout, mb_strlen($ajout));
                    $sizeMaxExt   = max($sizeMaxExt, mb_strlen($ext));
                }
            } // end loop on downloadable subdefs

            // check that no exportNames are double, else add a number
            // also truncate exportName so the whole filename will not be too long
            //     "aTooLongName_caption.xml" --> "aTooLo_caption-2.xml"
            $n = 1;
            do {
                $nSuffix = ($n==1) ? '' : ('#'.$n);
                $maxExportNameLength = self::$maxFilenameLength - $sizeMaxAjout - strlen($nSuffix) - 1 - $sizeMaxExt;
                $newExportName = mb_substr($export_name, 0, $maxExportNameLength).$nSuffix;
                $kName = mb_strtolower($newExportName);
                $n++;
            }
            while(in_array($kName, $file_names));

            // here we have a unique exportName
            $file_names[] = $kName;

            $files[$id]["export_name"] = $newExportName;

            //
            // add files for caption and/or caption-yaml ?
            //
            $caption_dir = null;
            foreach(['caption'=>\caption_record::SERIALIZE_XML, 'caption-yaml'=>\caption_record::SERIALIZE_YAML]
                    as $subdefName => $serializeMethod) {

                if (in_array($subdefName, $wantedSubdefs)) {
                    if (!$caption_dir) {
                        // do this only once
                        $caption_dir = $this->app['root.path'] . '/tmp/desc_tmp/'
                            . time() . $this->app['authentication']->getUser()->get_id() . '/';

                        $filesystem->mkdir($caption_dir, 0750);
                    }

                    $file = $files[$id]["export_name"]
                        . $files[$id]["subdefs"][$subdefName]["ajout"] . '.'
                        . $files[$id]["subdefs"][$subdefName]["exportExt"];

                    $desc = $download_element->get_caption()->serialize($serializeMethod, $BF);
                    file_put_contents($caption_dir . $file, $desc);

                    $files[$id]["subdefs"][$subdefName]["path"] = $caption_dir;
                    $files[$id]["subdefs"][$subdefName]["file"] = $file;
                    $files[$id]["subdefs"][$subdefName]["size"] = filesize($caption_dir . $file);
                    $files[$id]["subdefs"][$subdefName]['businessfields'] = $BF ? '1' : '0';
                }
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
     * @param Application $app
     * @param String      $token
     * @param Array       $list
     * @param string      $zipFile
     *
     * @return string
     */
    public static function build_zip(Application $app, $token, Array $list, $zipFile)
    {
        $zip = new ZipArchiveImproved();

        if ($zip->open($zipFile, ZIPARCHIVE::CREATE) !== true) {
            return false;
        }
        if (isset($list['complete']) && $list['complete'] === true) {
            return;
        }

        $files = $list['files'];

        $list['complete'] = false;

        $app['tokens']->updateToken($token, serialize($list));

        $toRemove = array();

        foreach ($files as $record) {
            if (isset($record["subdefs"])) {
                foreach ($record["subdefs"] as $o => $obj) {
                    $path = p4string::addEndSlash($obj["path"]) . $obj["file"];
                    if (is_file($path)) {
                        $name = $obj["folder"]
                            . $record["export_name"]
                            . $obj["ajout"]
                            . (isset($obj["exportExt"]) && trim($obj["exportExt"]) != '' ? '.' . $obj["exportExt"] : '');

                        $name = $app['unicode']->remove_diacritics($name);

                        $zip->addFile($path, $name);

                        if ($o == 'caption') {
                            if (!in_array(dirname($path), $toRemove)) {
                                $toRemove[] = dirname($path);
                            }
                            $toRemove[] = $path;
                        }
                    }
                }
            }
        }

        $zip->close();

        $list['complete'] = true;

        $app['tokens']->updateToken($token, serialize($list));

        $app['filesystem']->remove($toRemove);
        $app['filesystem']->chmod($zipFile, 0760);

        return $zipFile;
    }

    /**
     * @todo a revoir le cas anonymous
     *
     * @param Application $app
     * @param Array       $list
     * @param String      $type
     * @param boolean     $anonymous
     * @param string      $comment
     *
     * @return Void
     */
    public static function log_download(Application $app, Array $list, $type, $anonymous = false, $comment = '')
    {
        $tmplog = array();
        $files = $list['files'];

        $event_name = in_array($type, array(Session_Logger::EVENT_EXPORTMAIL,Session_Logger::EVENT_EXPORTDOWNLOAD)) ? $type : Session_Logger::EVENT_EXPORTDOWNLOAD;

        foreach ($files as $record) {
            foreach ($record["subdefs"] as $o => $obj) {
                $sbas_id = phrasea::sbasFromBas($app, $record['base_id']);

                $record_object = new record_adapter($app, $sbas_id, $record['record_id']);

                $app['phraseanet.logger']($record_object->get_databox())->log($record_object, $event_name, $o, $comment);

                if ($o != "caption") {
                    $log["rid"] = $record_object->get_record_id();
                    $log["subdef"] = $o;
                    $log["poids"] = $obj["size"];
                    $log["shortXml"] = $record_object->get_caption()->serialize(caption_record::SERIALIZE_XML);
                    $tmplog[$record_object->get_base_id()][] = $log;
                    if (!$anonymous && $o == 'document' && null !== $app['authentication']->getUser()) {
                        $app['authentication']->getUser()->ACL()->remove_remaining($record_object->get_base_id());
                    }
                }

                unset($record_object);
            }
        }

        $list_base = array_unique(array_keys($tmplog));

        if (!$anonymous && null !== $app['authentication']->getUser()) {
            $sql = "UPDATE basusr
            SET remain_dwnld = :remain_dl
            WHERE base_id = :base_id AND usr_id = :usr_id";

            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);

            foreach ($list_base as $base_id) {
                if ($app['authentication']->getUser()->ACL()->is_restricted_download($base_id)) {
                    $params = array(
                        ':remain_dl' => $app['authentication']->getUser()->ACL()->remaining_download($base_id)
                        , ':base_id' => $base_id
                        , ':usr_id'  => $app['authentication']->getUser()->get_id()
                    );

                    $stmt->execute($params);
                }
            }

            $stmt->closeCursor();
        }

        return;
    }
}
