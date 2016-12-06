<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\User;
use Assert\Assertion;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;

class set_export extends set_abstract
{
    private static $maxFilenameLength = 256;

    /**
     * @param int $newLength
     */
    public static function setMaxFilenameLength($newLength)
    {
        Assertion::integer($newLength);
        Assertion::greaterThan($newLength, 0);

        self::$maxFilenameLength = $newLength;
    }

    /**
     * @var Application
     */
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
     * @param  Application $app
     * @param  string $lst
     * @param  int $sstid
     * @param  int $storyWZid
     */
    public function __construct(Application $app, $lst, $sstid, $storyWZid = null)
    {
        $this->app = $app;

        $remain_hd = [];

        if ($storyWZid) {
            $repository = $app['repo.story-wz'];

            $storyWZ = $repository->findByUserAndId($this->app, $app->getAuthenticatedUser(), $storyWZid);

            $lst = $storyWZ->getRecord($this->app)->get_serialize_key();
        }

        if ($sstid != "") {
            $repository = $app['repo.baskets'];

            $Basket = $repository->findUserBasket($sstid, $app->getAuthenticatedUser(), false);
            $this->exportName = str_replace([' ', '\\', '/'], '_', $Basket->getName()) . "_" . date("Y-n-d");

            foreach ($Basket->getElements() as $basket_element) {
                $base_id = $basket_element->getRecord($this->app)->getBaseId();
                $record_id = $basket_element->getRecord($this->app)->getRecordId();

                if (!isset($remain_hd[$base_id])) {
                    if ($app->getAclForUser($app->getAuthenticatedUser())->is_restricted_download($base_id)) {
                        $remain_hd[$base_id] = $app->getAclForUser($app->getAuthenticatedUser())->remaining_download($base_id);
                    } else {
                        $remain_hd[$base_id] = false;
                    }
                }

                $current_element = new record_exportElement(
                    $app,
                    $basket_element->getRecord($this->app)->getDataboxId(),
                    $record_id,
                    $Basket->getName(),
                    $remain_hd[$base_id]
                );
                $this->add_element($current_element);

                $remain_hd[$base_id] = $current_element->get_remain_hd();
            }
        } else {
            $this->exportName = "Export_" . date("Y-n-d") . '_' . mt_rand(100, 999);

            $n = 1;

            $records = new \Alchemy\Phrasea\Record\RecordReferenceCollection();

            foreach (explode(';', $lst) as $basrec) {
                try {
                    $records[] = \Alchemy\Phrasea\Record\RecordReference::createFromRecordReference($basrec);
                } catch (Exception $exception) {
                    // Ignore invalid record references
                    continue;
                }
            }

            foreach ($records->toRecords($app->getApplicationBox()) as $record) {
                if ($record->isStory()) {
                    foreach ($record->getChildren() as $child_basrec) {
                        $base_id = $child_basrec->getBaseId();
                        $record_id = $child_basrec->getRecordId();

                        if (!isset($remain_hd[$base_id])) {
                            if ($app->getAclForUser($app->getAuthenticatedUser())->is_restricted_download($base_id)) {
                                $remain_hd[$base_id] = $app->getAclForUser($app->getAuthenticatedUser())->remaining_download($base_id);
                            } else {
                                $remain_hd[$base_id] = false;
                            }
                        }

                        $current_element = new record_exportElement(
                            $app,
                            $child_basrec->getDataboxId(),
                            $record_id,
                            $record->get_title(['removeExtension' => true]) . '_' . $n,
                            $remain_hd[$base_id]
                        );
                        $this->add_element($current_element);

                        $remain_hd[$base_id] = $current_element->get_remain_hd();
                    }
                } else {
                    $base_id = $record->getBaseId();
                    $record_id = $record->getRecordId();

                    if (!isset($remain_hd[$base_id])) {
                        if ($app->getAclForUser($app->getAuthenticatedUser())->is_restricted_download($base_id)) {
                            $remain_hd[$base_id] = $app->getAclForUser($app->getAuthenticatedUser())->remaining_download($base_id);
                        } else {
                            $remain_hd[$base_id] = false;
                        }
                    }

                    $current_element = new record_exportElement(
                        $app,
                        $record->getDataboxId(),
                        $record_id,
                        '',
                        $remain_hd[$base_id]
                    );
                    $this->add_element($current_element);

                    $remain_hd[$base_id] = $current_element->get_remain_hd();
                }
                $n++;
            }
        }

        $display_download = [];
        $display_orderable = [];

        $this->total_download = 0;
        $this->total_order = 0;
        $this->total_ftp = 0;

        $this->businessFieldsAccess = false;

        /** @var record_exportElement $download_element */
        foreach ($this->get_elements() as $download_element) {
            if ($app->getAclForUser($app->getAuthenticatedUser())->has_right_on_base($download_element->getBaseId(), \ACL::CANMODIFRECORD)) {
                $this->businessFieldsAccess = true;
            }

            foreach ($download_element->get_downloadable() as $name => $properties) {
                if (!isset($display_download[$name])) {
                    $display_download[$name] = [
                        'size' => 0,
                        'total' => 0,
                        'available' => 0,
                        'refused' => [],
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
                        'total' => 0,
                        'available' => 0,
                        'refused' => [],
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
            $display_download[$name]['size'] = (int)$values['size'];
        }

        $display_ftp = [];

        $hasadminright = $app->getAclForUser($app->getAuthenticatedUser())->has_right(\ACL::CANADDRECORD)
            || $app->getAclForUser($app->getAuthenticatedUser())->has_right(\ACL::CANDELETERECORD)
            || $app->getAclForUser($app->getAuthenticatedUser())->has_right(\ACL::CANMODIFRECORD)
            || $app->getAclForUser($app->getAuthenticatedUser())->has_right(\ACL::COLL_MANAGE)
            || $app->getAclForUser($app->getAuthenticatedUser())->has_right(\ACL::COLL_MODIFY_STRUCT);

        $this->ftp_datas = [];

        if ($this->app['conf']->get([
                'registry',
                'ftp',
                'ftp-enabled',
            ]) && ($hasadminright || $this->app['conf']->get(['registry', 'ftp', 'ftp-user-access']))
        ) {
            $display_ftp = $display_download;
            $this->total_ftp = $this->total_download;

            $lst_base_id = array_keys($app->getAclForUser($app->getAuthenticatedUser())->get_granted_base());

            $userFilterSQL = '';
            $params = [];
            $types = [];

            if (!$hasadminright) {
                $userFilterSQL = ' AND Users.id = :usr_id';
                $params['usr_id'] = $app->getAuthenticatedUser()->getId();
                $types['usr_id'] = PDO::PARAM_INT;
            }

            $sql = "SELECT Users.id AS usr_id ,Users.login AS usr_login ,Users.email AS usr_mail, FtpCredential.*\n"
                . "FROM (\n"
                . " FtpCredential INNER JOIN Users ON (FtpCredential.active = 1 AND FtpCredential.user_id = Users.id)\n"
                . "INNER JOIN\n"
                . " basusr\n"
                . "ON (Users.id=basusr.usr_id"
                . $userFilterSQL
                . " AND (basusr.base_id IN (:baseIds)))\n"
                . ")\n"
                . "GROUP BY Users.id\n";

            $params['baseIds'] = $lst_base_id;
            $types['baseIds'] = Connection::PARAM_INT_ARRAY;

            $datas[] = [
                'name' => $app->trans('export::ftp: reglages manuels'),
                'usr_id' => '0',
                'address' => '',
                'login' => '',
                'password' => '',
                'ssl' => false,
                'dest_folder' => '',
                'prefix_folder' => 'Export_' . date("Y-m-d_H.i.s"),
                'passive' => false,
                'max_retry' => 5,
                'sendermail' => $app->getAuthenticatedUser()->getEmail(),
            ];

            foreach ($app->getApplicationBox()->get_connection()->fetchAll($sql, $params, $types) as $row) {
                $datas[] = [
                    'name' => $row["usr_login"],
                    'usr_id' => $row['usr_id'],
                    'address' => $row['address'],
                    'login' => $row['login'],
                    'password' => $row['password'],
                    'ssl' => !!$row['tls'],
                    'dest_folder' => $row['reception_folder'],
                    'prefix_folder' =>
                        (strlen(trim($row['repository_prefix_name'])) > 0 ?
                            trim($row['repository_prefix_name']) :
                            'Export_' . date("Y-m-d_H.i.s")),
                    'passive' => !!$row['passive'],
                    'max_retry' => $row['max_retry'],
                    'usr_mail' => $row['usr_mail'],
                    'sender_mail' => $app->getAuthenticatedUser()->getEmail(),
                ];
            }

            $this->ftp_datas = $datas;
        }

        $this->display_orderable = $display_orderable;
        $this->display_download = $display_download;
        $this->display_ftp = $display_ftp;
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
     * @return array
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
     * @return array
     */
    public function get_display_orderable()
    {
        return $this->display_orderable;
    }

    /**
     * @return array
     */
    public function get_display_download()
    {
        return $this->display_download;
    }

    /**
     * @return array
     */
    public function get_display_ftp()
    {
        return $this->display_ftp;
    }

    /**
     * @return int
     */
    public function get_total_download()
    {
        return $this->total_download;
    }

    /**
     * @return int
     */
    public function get_total_order()
    {
        return $this->total_order;
    }

    /**
     * @return int
     */
    public function get_total_ftp()
    {
        return $this->total_ftp;
    }

    /**
     * @param User $user
     * @param Filesystem $filesystem
     * @param array $subdefs
     * @param bool $rename_title
     * @param bool $includeBusinessFields
     * @return array
     * @throws Exception
     */
    public function prepare_export(User $user, Filesystem $filesystem, array $subdefs, $rename_title, $includeBusinessFields)
    {
        if (!is_array($subdefs)) {
            throw new Exception('No subdefs given');
        }

        $includeBusinessFields = (bool) $includeBusinessFields;
        $files = [];
        $n_files = 0;
        $file_names = [];
        $size = 0;
        $unicode = new \unicode();

        /** @var record_exportElement $download_element */
        foreach ($this->get_elements() as $download_element) {
            $id = count($files);

            $files[$id] = [
                'base_id' => $download_element->getBaseId(),
                'record_id' => $download_element->getRecordId(),
                'original_name' => '',
                'export_name' => '',
                'subdefs' => [],
            ];

            $BF = false;

            if ($includeBusinessFields && $this->app->getAclForUser($user)->has_right_on_base($download_element->getBaseId(), \ACL::CANMODIFRECORD)) {
                $BF = true;
            }

            $desc = $this->app['serializer.caption']->serialize($download_element->get_caption(), CaptionSerializer::SERIALIZE_XML, $BF);

            $files[$id]['original_name'] =
            $files[$id]['export_name'] =
                $download_element->get_original_name(false);

            $files[$id]['original_name'] =
                trim($files[$id]['original_name']) != '' ?
                    $files[$id]['original_name'] : $id;

            $infos = pathinfo($files[$id]['original_name']);

            $extension = isset($infos['extension']) ? $infos['extension'] :
                substr($files[$id]['original_name'], 0 - strrpos($files[$id]['original_name'], '.'));

            if ($rename_title) {
                $title = strip_tags($download_element->get_title(['removeExtension' => true]));
                $files[$id]['export_name'] = $unicode->remove_nonazAZ09($title, true, true, true);
            } else {
                $files[$id]["export_name"] = $infos['filename'];
            }

            if (substr(strrev($files[$id]['export_name']), 0, strlen($extension)) != strrev($extension)) {
                $files[$id]['export_name'] .= '.' . $extension;
            }

            $sizeMaxAjout = 0;
            $sizeMaxExt = 0;

            foreach ($download_element->get_downloadable() as $name => $properties) {
                if ($properties === false || !in_array($name, $subdefs)) {
                    continue;
                }

                $subdef = null;
                if (!in_array($name, ['caption', 'caption-yaml'])) {
                    try {
                        // get_subdef() can throw a 404
                        $subdef = $download_element->get_subdef($name);
                    }
                    catch(\Exception $e) {
                        continue;
                    }
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
                            'path' => $subdef->get_path(),
                            'file' => $subdef->get_file(),
                        ];
                        $subdef_export = true;
                        $subdef_alive = true;
                        break;
                    case 'document':
                        $subdef_export = true;
                        $path = \recordutils_image::stamp($this->app, $subdef);
                        $tmp_pathfile = [
                            'path' => $subdef->get_path(),
                            'file' => $subdef->get_file(),
                        ];
                        if (file_exists($path)) {
                            $tmp_pathfile = [
                                'path' => dirname($path),
                                'file' => basename($path),
                            ];
                            $subdef_alive = true;
                        }
                        break;

                    case 'preview':
                        $subdef_export = true;

                        $tmp_pathfile = [
                            'path' => $subdef->get_path(),
                            'file' => $subdef->get_file(),
                        ];
                        if (!$this->app->getAclForUser($user)->has_right_on_base($download_element->getBaseId(), \ACL::NOWATERMARK)
                            && !$this->app->getAclForUser($user)->has_preview_grant($download_element)
                            && $subdef->get_type() == media_subdef::TYPE_IMAGE
                        ) {
                            $path = recordutils_image::watermark($this->app, $subdef);
                            if (file_exists($path)) {
                                $tmp_pathfile = [
                                    'path' => dirname($path),
                                    'file' => basename($path),
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
                            $files[$id]["subdefs"][$name]["size"] = $subdef->get_size();
                            $files[$id]["subdefs"][$name]["mime"] = $subdef->get_mime();
                            $files[$id]["subdefs"][$name]["folder"] = $download_element->get_directory();
                            $files[$id]["subdefs"][$name]["exportExt"] = isset($infos['extension']) ? $infos['extension'] : '';

                            $size += $subdef->get_size();

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

            $max_length = self::$maxFilenameLength - 1 - $sizeMaxExt - $sizeMaxAjout;

            $name = $files[$id]["export_name"];

            $start_length = mb_strlen($name);
            if ($start_length > $max_length)
                $name = mb_substr($name, 0, $max_length);

            $n = 1;

            while (in_array(mb_strtolower($name), $file_names)) {
                $n++;
                $suffix = "-" . $n; // pour diese si besoin
                $max_length = self::$maxFilenameLength - 1 - $sizeMaxExt - $sizeMaxAjout - mb_strlen($suffix);
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
            $good_keys = [
                'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
                'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
                'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3',
                '4', '5', '6', '7', '8', '9', '-', '_', '.', '#',
            ];

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
                $caption_dir = $this->app['tmp.caption.path'] . '/' . time() . $this->app->getAuthenticatedUser()->getId() . '/';

                $filesystem->mkdir($caption_dir, 0750);

                $desc = $this->app['serializer.caption']->serialize($download_element->get_caption(), CaptionSerializer::SERIALIZE_XML, $BF);

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
                $caption_dir = $this->app['tmp.caption.path'] . '/' . time() . $this->app->getAuthenticatedUser()->getId() . '/';

                $filesystem->mkdir($caption_dir, 0750);

                $desc = $this->app['serializer.caption']->serialize($download_element->get_caption(), CaptionSerializer::SERIALIZE_YAML, $BF);

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
            'size' => $size,
            'count' => $n_files,
        ];

        return $this->list;
    }

    /**
     * @param Application $app
     * @param Token $token
     * @param array $list
     * @param string $zipFile
     *
     * @return string
     */
    public static function build_zip(Application $app, Token $token, array $list, $zipFile)
    {
        if (isset($list['complete']) && $list['complete'] === true) {
            return $zipFile;
        }

        $files = $list['files'];

        $list['complete'] = false;

        $token->setData(serialize($list));
        $app['manipulator.token']->update($token);

        $toRemove = [];
        $archiveFiles = [];

        foreach ($files as $record) {
            if (isset($record["subdefs"])) {
                foreach ($record["subdefs"] as $o => $obj) {
                    $path = p4string::addEndSlash($obj["path"]) . $obj["file"];
                    if (is_file($path)) {
                        $name = $obj["folder"]
                            . $record["export_name"]
                            . $obj["ajout"]
                            . (isset($obj["exportExt"]) && trim($obj["exportExt"]) != '' ? '.' . $obj["exportExt"] : '');

                        $archiveFiles[$app['unicode']->remove_diacritics($name)] = $path;
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

        $app['zippy']->create($zipFile, $archiveFiles);

        $list['complete'] = true;

        $token->setData(serialize($list));
        $app['manipulator.token']->update($token);

        $app['filesystem']->remove($toRemove);
        $app['filesystem']->chmod($zipFile, 0760);

        return $zipFile;
    }

    /**
     * @todo a revoir le cas anonymous
     *
     * @param Application $app
     * @param array $list
     * @param String $type
     * @param boolean $anonymous
     * @param string $comment
     *
     * @return void
     */
    public static function log_download(Application $app, array $list, $type, $anonymous = false, $comment = '')
    {
        $tmplog = [];
        $files = $list['files'];

        $event_name = in_array($type, [
            Session_Logger::EVENT_EXPORTMAIL,
            Session_Logger::EVENT_EXPORTDOWNLOAD,
        ]) ? $type : Session_Logger::EVENT_EXPORTDOWNLOAD;

        foreach ($files as $record) {
            foreach ($record["subdefs"] as $o => $obj) {
                $sbas_id = phrasea::sbasFromBas($app, $record['base_id']);

                $record_object = new record_adapter($app, $sbas_id, $record['record_id']);

                $app['phraseanet.logger']($record_object->getDatabox())->log($record_object, $event_name, $o, $comment);

                if ($o != "caption") {
                    $log["rid"] = $record_object->getRecordId();
                    $log["subdef"] = $o;
                    $log["poids"] = $obj["size"];
                    $log["shortXml"] = $app['serializer.caption']->serialize($record_object->get_caption(), CaptionSerializer::SERIALIZE_XML);
                    $tmplog[$record_object->getBaseId()][] = $log;
                    if (!$anonymous && $o == 'document' && null !== $app->getAuthenticatedUser()) {
                        $app->getAclForUser($app->getAuthenticatedUser())->remove_remaining($record_object->getBaseId());
                    }
                }

                unset($record_object);
            }
        }

        $list_base = array_unique(array_keys($tmplog));

        if (!$anonymous && null !== $app->getAuthenticatedUser()) {
            $sql = "UPDATE basusr SET remain_dwnld = :remain_dl WHERE base_id = :base_id AND usr_id = :usr_id";

            $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);

            foreach ($list_base as $base_id) {
                if ($app->getAclForUser($app->getAuthenticatedUser())->is_restricted_download($base_id)) {
                    $params = [
                        ':remain_dl' => $app->getAclForUser($app->getAuthenticatedUser())->remaining_download($base_id),
                        ':base_id' => $base_id,
                        ':usr_id' => $app->getAuthenticatedUser()->getId(),
                    ];

                    $stmt->execute($params);
                }
            }

            $stmt->closeCursor();
        }
    }
}
