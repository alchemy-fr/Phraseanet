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
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\StoryWZRepository;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Alchemy\Phrasea\Out\Module\PDFCgu;
use Assert\Assertion;
use Doctrine\DBAL\Connection;

// use Symfony\Component\Filesystem\Filesystem;


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
            /** @var StoryWZRepository $repository */
            $repository = $app['repo.story-wz'];

            $storyWZ = $repository->findByUserAndId($this->app, $app->getAuthenticatedUser(), $storyWZid);

            $lst = $storyWZ->getRecord($this->app)->get_serialize_key();
        }

        if ($sstid != "") {
            /** @var BasketRepository $repository */
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
                            $record->get_title(['removeExtension' => true, 'encode'=> record_adapter::ENCODE_NONE]) . '_' . $n,
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

    const NO_STAMP = 'NO_STAMP';
    const STAMP_SYNC = 'STAMP_SYNC';
    const STAMP_ASYNC = 'STAMP_ASYNC';
    /**
     * @param User $user
     * @param Filesystem $filesystem
     * @param array $wantedSubdefs
     * @param bool $rename_title
     * @param bool $includeBusinessFields
     * @param $no_stamp
     * @return array
     * @throws Exception
     */
    public function prepare_export(User $user, Filesystem $filesystem, Array $wantedSubdefs, $rename_title, $includeBusinessFields, $stampMethod)
    {
        if (!is_array($wantedSubdefs)) {
            throw new Exception('No subdefs given');
        }

        if(!$stampMethod) {
            $stampMethod = self::STAMP_SYNC;
        }

        $includeBusinessFields = (bool) $includeBusinessFields;
        $files = [];
        $n_files = 0;
        $file_names = [];
        $size = 0;
        $unicode = $this->app['unicode'];

        /** @var record_exportElement $download_element */
        foreach ($this->elements as $download_element) {

            $id = count($files);

            $files[$id] = [
                'base_id'       => $download_element->getBaseId(),
                'databox_id'    => $download_element->getDataboxId(),
                'record_id'     => $download_element->getRecordId(),
                'original_name' => '',
                'export_name'   => '',
                'subdefs'       => [],
            ];

            $BF = false;

            if ($includeBusinessFields && $this->app->getAclForUser($user)->has_right_on_base($download_element->getBaseId(), \ACL::CANMODIFRECORD)) {
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
                $export_name = strip_tags($download_element->get_title(['removeExtension' => true, 'encode'=> record_adapter::ENCODE_FOR_URI]));
                // if the "title" ends up with a "filename-like" field, remove extension
                if (strtolower(substr($export_name, -strlen($extension)-1)) === '.'.strtolower($extension)) {
                    $export_name = substr($export_name, 0, strlen($export_name)-1-strlen($extension));
                }
            } else {
                $export_name = $original_name;
            }

            // cleanup the exportname so it can be used as a filename (even if it came from the originale_name)
            $export_name = str_replace([' ', "\t", "\r", "\n"], '_', $export_name);
            $export_name = $unicode->remove_nonazAZ09($export_name, true, true, true);  // keep '_', '-', '.'
            // really no luck if nothing left
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

                set_time_limit(0);
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
                            'file' => $sd[$subdefName]->get_file(),
                            'to_stamp' => false,
                            'to_watermark' => false
                        ];

                        break;
                    case 'document':
                        $subdef_ok = true;
                        $tmp_pathfile = [
                            'path' => $sd[$subdefName]->get_path(),
                            'file' => $sd[$subdefName]->get_file(),
                            'to_stamp' => false,
                            'to_watermark' => false
                        ];

                        if($this->app['conf']->get(['registry', 'actions', 'export-stamp-choice']) !== true || $stampMethod !== self::NO_STAMP ){
                            // stamp is mandatory, or user did not check "no stamp" : we must apply stamp
                            if($stampMethod === self::STAMP_SYNC) {
                                // we prepare a direct download, we must stamp now
                                $path = \recordutils_image::stamp($this->app, $sd[$subdefName]);
                                if ($path && file_exists($path)) {
                                    $tmp_pathfile = [
                                        'path'         => dirname($path),
                                        'file'         => basename($path),
                                        'to_stamp'     => false,
                                        'to_watermark' => false
                                    ];
                                }
                            }
                            else {
                                // we prepare an email or ftp download : the worker will apply stamp
                                $tmp_pathfile ['to_stamp'] = true;
                            }
                        }

                        break;
                    case 'preview':
                        $tmp_pathfile = [
                            'path' => $sd[$subdefName]->get_path(),
                            'file' => $sd[$subdefName]->get_file(),
                            'to_stamp' => false,
                            'to_watermark' => false
                        ];

                        if (!$this->app->getAclForUser($user)->has_right_on_base($download_element->getBaseId(), \ACL::NOWATERMARK)
                            && !$this->app->getAclForUser($user)->has_preview_grant($download_element)
                            && $sd[$subdefName]->get_type() == media_subdef::TYPE_IMAGE )
                        {
                            $path = recordutils_image::watermark($this->app, $sd[$subdefName]);
                            if (file_exists($path)) {
                                $tmp_pathfile = [
                                    'path' => dirname($path),
                                    'file' => basename($path),
                                    'to_stamp' => false,
                                    'to_watermark' => false
                                ];
                                $subdef_ok = true;
                            }
                        }
                        else {
                            $subdef_ok = true;
                        }

                        break;
                }

                if ($subdef_ok) {
                    switch ($properties['class']) {
                        case 'caption':
                            $ajout = '_caption';
                            if ($subdefName == 'caption-yaml') {
                                $ext = 'txt';
                                $mime = 'text/plain';
                            } else {
                                $ext = 'xml';
                                $mime = 'text/xml';
                            }

                            $files[$id]["subdefs"][$subdefName] = [
                                "ajout"     => $ajout,
                                "exportExt" => $ext,
                                "label"     => $properties['label'],
                                "path"      => null,
                                "file"      => null,
                                "to_stamp"  => false,
                                "size"      => 0,
                                "mime"      => $mime,
                                "folder"    => $download_element->get_directory()
                            ];

                            break;
                        case 'document':
                        case 'preview':
                        case 'thumbnail':
                            $ajout = $subdefName == 'document' ? '' : ("_" . $subdefName);
                            $infos = pathinfo(p4string::addEndSlash($tmp_pathfile["path"]) . $tmp_pathfile["file"]);
                            $ext = isset($infos['extension']) ? $infos['extension'] : '';

                            $files[$id]["subdefs"][$subdefName] = [
                                "ajout"     => $ajout,
                                "exportExt" => $ext,
                                "label"     => $properties['label'],
                                "path"      => $tmp_pathfile["path"],
                                "file"      => $tmp_pathfile["file"],
                                "to_stamp"  => $tmp_pathfile["to_stamp"],
                                "size"      => $sd[$subdefName]->get_size(),
                                "mime"      => $sd[$subdefName]->get_mime(),
                                "folder"    => $download_element->get_directory()
                            ];

                            $size += $sd[$subdefName]->get_size();

                            break;
                        default:    // should not happen
                            $ajout = $ext = '';

                            break;
                    }

                    $sizeMaxAjout = max($sizeMaxAjout, mb_strlen($ajout));
                    $sizeMaxExt   = max($sizeMaxExt, mb_strlen($ext));
                }
            }

            // end loop on downloadable subdefs

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
            foreach(['caption'=>CaptionSerializer::SERIALIZE_XML, 'caption-yaml'=>CaptionSerializer::SERIALIZE_YAML]
                    as $subdefName => $serializeMethod)
            {
                if (in_array($subdefName, $wantedSubdefs)) {
                    if (!$caption_dir) {
                        // do this only once
                        $caption_dir = $this->app['tmp.caption.path'] . '/' . time() . $this->app->getAuthenticatedUser()->getId() . '/';
                        $filesystem->mkdir($caption_dir, 0750);
                    }

                    $file = $files[$id]["export_name"]
                        . $files[$id]["subdefs"][$subdefName]["ajout"] . '.'
                        . $files[$id]["subdefs"][$subdefName]["exportExt"];

                    $desc = $this->app['serializer.caption']->serialize($download_element->get_caption(), $serializeMethod, $BF);
                    file_put_contents($caption_dir . $file, $desc);

                    $files[$id]["subdefs"][$subdefName]["path"] = $caption_dir;
                    $files[$id]["subdefs"][$subdefName]["file"] = $file;
                    $files[$id]["subdefs"][$subdefName]["size"] = filesize($caption_dir . $file);
                    $files[$id]["subdefs"][$subdefName]['businessfields'] = $BF ? '1' : '0';
                }
            }
        }

        $this->list = [
            'files' => $files,
            'names' => $file_names,
            'size'  => $size,
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
        $databoxIds = [];
        $recordIdsPerDatabox = [];

        // group recordId per databoxId
        foreach ($files as $file) {
            $recordIdsPerDatabox[$file['databox_id']][] = $file['record_id'];
        }

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

                        if (!in_array($record['databox_id'], $databoxIds)) {
                            // add also the databox cgu in the zip
                            $databoxIds[] = $record['databox_id'];

                            // if cgu content empty, do not add pdf in zip
                            if (!PDFCgu::isDataboxCguEmpty($app, $record['databox_id'])) {
                                try {
                                    $pdfCgu = new PDFCgu($app, $record['databox_id'], $recordIdsPerDatabox[$record['databox_id']]);
                                    $pdfCgu->save();

                                    $databoxCguPath = PDFCgu::getDataboxCguPath($app, $record['databox_id']);
                                } catch (\Exception $e) {
                                    $app['logger']->error("Exception occurred when generating cgu pdf : " . $e->getMessage());

                                    continue;
                                }

                                $archiveFiles[$app['unicode']->remove_diacritics($obj["folder"].PDFCgu::getDataboxCguPdfName($app, $record['databox_id']))] = $databoxCguPath;
                                $toRemove[] = $databoxCguPath;
                            }
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

    public function has_stamp_option()
    {
        if ($this->total_download == 0) {
            return false;
        }

        $domprefs = new DOMDocument();
        foreach ($this->elements as $download_element) {
            if ( ($domprefs->loadXML($download_element->getCollection()->get_prefs())) === false) {
                continue;
            }
            $xpprefs = new DOMXPath($domprefs);
            $stampNodes = $xpprefs->query('/baseprefs/stamp');
            if ($stampNodes->length != 0) {

                return true;
            }

        }

        return false;
    }
}
