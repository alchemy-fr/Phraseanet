<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Filesystem\Filesystem;

class set_export extends set_abstract
{
    protected $app;
    protected $storage = [];
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

        $download_list = [];

        $remain_hd = [];

        if ($storyWZid) {
            $repository = $app['EM']->getRepository('\\Entities\\StoryWZ');

            $storyWZ = $repository->findByUserAndId($this->app, $app['authentication']->getUser(), $storyWZid);

            $lst = $storyWZ->getRecord($this->app)->get_serialize_key();
        }

        if ($sstid != "") {
            $Basket = $app['converter.basket']->convert($sstid);
            $app['acl.basket']->hasAccess($Basket, $app['authentication']->getUser());

            $this->exportName = str_replace([' ', '\\', '/'], '_', $Basket->getName()) . "_" . date("Y-n-d");

            foreach ($Basket->getElements() as $basket_element) {
                $base_id = $basket_element->getRecord($this->app)->get_base_id();
                $record_id = $basket_element->getRecord($this->app)->get_record_id();

                if (!isset($remain_hd[$base_id])) {
                    if ($app['acl']->get($app['authentication']->getUser())->is_restricted_download($base_id)) {
                        $remain_hd[$base_id] = $app['acl']->get($app['authentication']->getUser())->remaining_download($base_id);
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
                } catch (Exception_Record_AdapterNotFound $e) {
                    continue;
                }

                if ($record->is_grouping()) {
                    foreach ($record->get_children() as $child_basrec) {
                        $base_id = $child_basrec->get_base_id();
                        $record_id = $child_basrec->get_record_id();

                        if (!isset($remain_hd[$base_id])) {
                            if ($app['acl']->get($app['authentication']->getUser())->is_restricted_download($base_id)) {
                                $remain_hd[$base_id] = $app['acl']->get($app['authentication']->getUser())->remaining_download($base_id);
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
                        if ($app['acl']->get($app['authentication']->getUser())->is_restricted_download($base_id)) {
                            $remain_hd[$base_id] = $app['acl']->get($app['authentication']->getUser())->remaining_download($base_id);
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

        $display_download = [];
        $display_orderable = [];

        $this->total_download = 0;
        $this->total_order = 0;
        $this->total_ftp = 0;

        $this->businessFieldsAccess = false;

        foreach ($this->elements as $download_element) {
            if ($app['acl']->get($app['authentication']->getUser())->has_right_on_base($download_element->get_base_id(), 'canmodifrecord')) {
                $this->businessFieldsAccess = true;
            }

            foreach ($download_element->get_downloadable() as $name => $properties) {
                if (!isset($display_download[$name])) {
                    $display_download[$name] = [
                        'size'      => 0,
                        'total'     => 0,
                        'available' => 0,
                        'refused'   => []
                    ];
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
                    $display_orderable[$name] = [
                        'total'     => 0,
                        'available' => 0,
                        'refused'   => []
                    ];
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

        $display_ftp = [];

        $hasadminright = $app['acl']->get($app['authentication']->getUser())->has_right('addrecord')
            || $app['acl']->get($app['authentication']->getUser())->has_right('deleterecord')
            || $app['acl']->get($app['authentication']->getUser())->has_right('modifyrecord')
            || $app['acl']->get($app['authentication']->getUser())->has_right('coll_manage')
            || $app['acl']->get($app['authentication']->getUser())->has_right('coll_modify_struct');

        $this->ftp_datas = [];

        if ($this->app['phraseanet.registry']->get('GV_activeFTP') && ($hasadminright || $this->app['phraseanet.registry']->get('GV_ftp_for_user'))) {
            $display_ftp = $display_download;
            $this->total_ftp = $this->total_download;

            $lst_base_id = array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base());

            if ($hasadminright) {
                $sql = "SELECT usr.usr_id,usr_login,usr.usr_mail, FtpCredential.*
                  FROM (
                    FtpCredential INNER JOIN usr ON (
                        FtpCredential.active = 1 AND FtpCredential.usrId = usr.usr_id
                    ) INNER JOIN basusr ON (
                        usr.usr_id=basusr.usr_id
                        AND (basusr.base_id=
                        '" . implode("' OR basusr.base_id='", $lst_base_id) . "'
                            )
                         )
                      )
                  GROUP BY usr_id  ";
                $params = [];
            } elseif ($this->app['phraseanet.registry']->get('GV_ftp_for_user')) {
                $sql = "SELECT usr.usr_id,usr_login,usr.usr_mail, FtpCredential.*
                  FROM (
                    FtpCredential INNER JOIN usr ON (
                        FtpCredential.active = 1 AND FtpCredential.usrId = usr.usr_id
                    ) INNER JOIN basusr ON (
                        usr.usr_id=basusr.usr_id
                        AND usr.usr_id = :usr_id
                        AND (basusr.base_id=
                        '" . implode("' OR basusr.base_id='", $lst_base_id) . "'
                          )
                        )
                      )
                  GROUP BY usr_id  ";
                $params = [':usr_id' => $app['authentication']->getUser()->get_id()];
            }

            $datas[] = [
                'name'              => $app->trans('export::ftp: reglages manuels'),
                'usr_id'            => '0',
                'address'           => '',
                'login'             => '',
                'password'          => '',
                'ssl'               => false,
                'dest_folder'       => '',
                'prefix_folder'     => 'Export_' . date("Y-m-d_H.i.s"),
                'passive'           => false,
                'max_retry'         => 5,
                'sendermail'        => $app['authentication']->getUser()->get_email()
            ];

            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute($params);
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                $datas[] = [
                    'name'              => $row["usr_login"],
                    'usr_id'            => $row['usr_id'],
                    'address'           => $row['address'],
                    'login'             => $row['login'],
                    'password'          => $row['password'],
                    'ssl'               => !! $row['tls'],
                    'dest_folder'       => $row['reception_folder'],
                    'prefix_folder'     =>
                    (strlen(trim($row['repository_prefix_name'])) > 0 ?
                        trim($row['repository_prefix_name']) :
                        'Export_' . date("Y-m-d_H.i.s")),
                    'passive'           => !! $row['passive'],
                    'max_retry'         => $row['max_retry'],
                    'usr_mail'          => $row['usr_mail'],
                    'sender_mail'       => $app['authentication']->getUser()->get_email()
                ];
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
     *
     * @param User_Adapter $user
     * @param Filesystem   $filesystem
     * @param Array        $subdefs
     * @param boolean      $rename_title
     * @param boolean      $includeBusinessFields
     *
     * @return Array
     */
    public function prepare_export(User_Adapter $user, Filesystem $filesystem, Array $subdefs, $rename_title, $includeBusinessFields)
    {
        if (!is_array($subdefs)) {
            throw new Exception('No subdefs given');
        }

        $includeBusinessFields = !!$includeBusinessFields;

        $files = [];

        $n_files = 0;

        $file_names = [];

        $size = 0;

        $unicode = new \unicode();

        foreach ($this->elements as $download_element) {
            $id = count($files);

            $files[$id] = [
                'base_id'       => $download_element->get_base_id(),
                'record_id'     => $download_element->get_record_id(),
                'original_name' => '',
                'export_name'   => '',
                'subdefs'       => []
            ];

            $BF = false;

            if ($includeBusinessFields && $this->app['acl']->get($user)->has_right_on_base($download_element->get_base_id(), 'canmodifrecord')) {
                $BF = true;
            }

            $desc = $download_element->get_caption()->serialize(caption_record::SERIALIZE_XML, $BF);

            $files[$id]['original_name'] =
                $files[$id]['export_name'] =
                $download_element->get_original_name(true);

            $files[$id]['original_name'] =
                trim($files[$id]['original_name']) != '' ?
                $files[$id]['original_name'] : $id;

            $infos = pathinfo($files[$id]['original_name']);

            $extension = isset($infos['extension']) ? $infos['extension'] : '';

            if ($rename_title) {
                $title = strip_tags($download_element->get_title(null, null, true));

                $files[$id]['export_name'] = $unicode->remove_nonazAZ09($title, true, true, true);
            } else {
                $files[$id]["export_name"] = $infos['filename'];
            }

            $sizeMaxAjout = 0;
            $sizeMaxExt = 0;

            $sd = $download_element->get_subdefs();

            foreach ($download_element->get_downloadable() as $name => $properties) {
                if ($properties === false || !in_array($name, $subdefs)) {
                    continue;
                }
                if (!in_array($name, ['caption', 'caption-yaml']) && !isset($sd[$name])) {
                    continue;
                }

                set_time_limit(100);
                $subdef_export = $subdef_alive = false;

                $n_files++;

                $tmp_pathfile = ['path' => null, 'file' => null];

                switch ($properties['class']) {
                    case 'caption':
                    case 'caption-yaml':
                        $subdef_export = true;
                        $subdef_alive = true;
                        break;
                    case 'thumbnail':
                        $tmp_pathfile = [
                            'path'         => $sd[$name]->get_path()
                            , 'file'         => $sd[$name]->get_file()
                        ];
                        $subdef_export = true;
                        $subdef_alive = true;
                        break;
                    case 'document':
                        $subdef_export = true;
                        $path = \recordutils_image::stamp($this->app , $sd[$name]);
                        $tmp_pathfile = [
                            'path' => $sd[$name]->get_path()
                            , 'file' => $sd[$name]->get_file()
                        ];
                        if (file_exists($path)) {
                            $tmp_pathfile = [
                                'path'        => dirname($path)
                                , 'file'        => basename($path)
                            ];
                            $subdef_alive = true;
                        }
                        break;

                    case 'preview':
                        $subdef_export = true;

                        $tmp_pathfile = [
                            'path' => $sd[$name]->get_path()
                            , 'file' => $sd[$name]->get_file()
                        ];
                        if (!$this->app['acl']->get($user)->has_right_on_base($download_element->get_base_id(), "nowatermark")
                            && !$this->app['acl']->get($user)->has_preview_grant($download_element)
                            && $sd[$name]->get_type() == media_subdef::TYPE_IMAGE) {
                            $path = recordutils_image::watermark($this->app, $sd[$name]);
                            if (file_exists($path)) {
                                $tmp_pathfile = [
                                    'path'        => dirname($path)
                                    , 'file'        => basename($path)
                                ];
                                $subdef_alive = true;
                            }
                        } else {
                            $subdef_alive = true;
                        }
                        break;
                }

                if ($subdef_export === true && $subdef_alive === true) {
                    switch ($properties['class']) {
                        case 'caption':
                            if ($name == 'caption-yaml') {
                                $suffix = '_captionyaml';
                                $extension = 'yml';
                                $mime = 'text/x-yaml';
                            } else {
                                $suffix = '_caption';
                                $extension = 'xml';
                                $mime = 'text/xml';
                            }

                            $files[$id]["subdefs"][$name]["ajout"] = $suffix;
                            $files[$id]["subdefs"][$name]["exportExt"] = $extension;
                            $files[$id]["subdefs"][$name]["label"] = $properties['label'];
                            $files[$id]["subdefs"][$name]["path"] = null;
                            $files[$id]["subdefs"][$name]["file"] = null;
                            $files[$id]["subdefs"][$name]["size"] = 0;
                            $files[$id]["subdefs"][$name]["folder"] = $download_element->get_directory();
                            $files[$id]["subdefs"][$name]["mime"] = $mime;

                            break;
                        case 'document':
                        case 'preview':
                        case 'thumbnail':
                            $infos = pathinfo(p4string::addEndSlash($tmp_pathfile["path"]) .
                                $tmp_pathfile["file"]);

                            $files[$id]["subdefs"][$name]["ajout"] = $name == 'document' ? '' : "_" . $name;
                            $files[$id]["subdefs"][$name]["path"] = $tmp_pathfile["path"];
                            $files[$id]["subdefs"][$name]["file"] = $tmp_pathfile["file"];
                            $files[$id]["subdefs"][$name]["label"] = $properties['label'];
                            $files[$id]["subdefs"][$name]["size"] = $sd[$name]->get_size();
                            $files[$id]["subdefs"][$name]["mime"] = $sd[$name]->get_mime();
                            $files[$id]["subdefs"][$name]["folder"] =
                                $download_element->get_directory();
                            $files[$id]["subdefs"][$name]["exportExt"] =
                                isset($infos['extension']) ? $infos['extension'] : '';

                            $size += $sd[$name]->get_size();

                            break;
                    }

                    $longueurAjoutCourant =
                        mb_strlen($files[$id]["subdefs"][$name]["ajout"]);
                    $sizeMaxAjout = max($longueurAjoutCourant, $sizeMaxAjout);

                    $longueurExtCourant =
                        mb_strlen($files[$id]["subdefs"][$name]["exportExt"]);
                    $sizeMaxExt = max($longueurExtCourant, $sizeMaxExt);
                }
            }

            $max_length = 31 - $sizeMaxExt - $sizeMaxAjout;

            $name = $files[$id]["export_name"];

            $start_length = mb_strlen($name);
            if ($start_length > $max_length)
                $name = mb_substr($name, 0, $max_length);

            $n = 1;

            while (in_array(mb_strtolower($name), $file_names)) {
                $n++;
                $suffix = "-" . $n; // pour diese si besoin
                $max_length = 31 - $sizeMaxExt - $sizeMaxAjout - mb_strlen($suffix);
                $name = mb_strtolower($files[$id]["export_name"]);
                if ($start_length > $max_length)
                    $name = mb_substr($name, 0, $max_length) . $suffix;
                else
                    $name = $name . $suffix;
            }
            $file_names[] = mb_strtolower($name);
            $files[$id]["export_name"] = $name;

            $files[$id]["export_name"] = $unicode->remove_nonazAZ09($files[$id]["export_name"], true, true, true);
            $files[$id]["original_name"] = $unicode->remove_nonazAZ09($files[$id]["original_name"], true, true, true);

            $i = 0;
            $name = utf8_decode($files[$id]["export_name"]);
            $tmp_name = "";
            $good_keys = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
                'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
                'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3',
                '4', '5', '6', '7', '8', '9', '-', '_', '.', '#'];

            while (isset($name[$i])) {
                if (!in_array(mb_strtolower($name[$i]), $good_keys))
                    $tmp_name .= '_';
                else
                    $tmp_name .= $name[$i];

                $tmp_name = str_replace('__', '_', $tmp_name);

                $i++;
            }
            $files[$id]["export_name"] = $tmp_name;

            if (in_array('caption', $subdefs)) {
                $caption_dir = $this->app['root.path'] . '/tmp/desc_tmp/'
                    . time() . $this->app['authentication']->getUser()->get_id() . '/';

                $filesystem->mkdir($caption_dir, 0750);

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

            if (in_array('caption-yaml', $subdefs)) {
                $caption_dir = $this->app['root.path'] . '/tmp/desc_tmp/'
                    . time() . $this->app['authentication']->getUser()->get_id() . '/';

                $filesystem->mkdir($caption_dir, 0750);

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

        $this->list = [
            'files' => $files,
            'names' => $file_names,
            'size'  => $size,
            'count' => $n_files
        ];

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

        $toRemove = [];

        foreach ($files as $record) {
            if (isset($record["subdefs"])) {
                foreach ($record["subdefs"] as $o => $obj) {
                    $path = p4string::addEndSlash($obj["path"]) . $obj["file"];
                    if (is_file($path)) {
                        $name = $obj["folder"]
                            . $record["export_name"]
                            . $obj["ajout"]
                            . '.' . $obj["exportExt"];

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
        $tmplog = [];
        $files = $list['files'];

        $event_names = [
            'mail-export' => Session_Logger::EVENT_EXPORTMAIL,
            'download'    => Session_Logger::EVENT_EXPORTDOWNLOAD
        ];

        $event_name = isset($event_names[$type]) ? $event_names[$type] : Session_Logger::EVENT_EXPORTDOWNLOAD;

        foreach ($files as $record) {
            foreach ($record["subdefs"] as $o => $obj) {
                $sbas_id = phrasea::sbasFromBas($app, $record['base_id']);

                $record_object = new record_adapter($app, $sbas_id, $record['record_id']);

                $app['phraseanet.logger']($record_object->get_databox())
                    ->log($record_object, $event_name, $o, $comment);

                if ($o != "caption") {
                    $log["rid"] = $record_object->get_record_id();
                    $log["subdef"] = $o;
                    $log["poids"] = $obj["size"];
                    $log["shortXml"] = $record_object->get_caption()->serialize(caption_record::SERIALIZE_XML);
                    $tmplog[$record_object->get_base_id()][] = $log;
                    if (!$anonymous && $o == 'document') {
                        $app['acl']->get($app['authentication']->getUser())->remove_remaining($record_object->get_base_id());
                    }
                }

                unset($record_object);
            }
        }

        $list_base = array_unique(array_keys($tmplog));

        if (!$anonymous) {
            $sql = "UPDATE basusr
            SET remain_dwnld = :remain_dl
            WHERE base_id = :base_id AND usr_id = :usr_id";

            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);

            foreach ($list_base as $base_id) {
                if ($app['acl']->get($app['authentication']->getUser())->is_restricted_download($base_id)) {
                    $params = [
                        ':remain_dl' => $app['acl']->get($app['authentication']->getUser())->remaining_download($base_id)
                        , ':base_id'   => $base_id
                        , ':usr_id'    => $app['acl']->get($app['authentication']->getUser())->get_id()
                    ];

                    $stmt->execute($params);
                }
            }

            $stmt->closeCursor();
        }

        return;
    }
}
