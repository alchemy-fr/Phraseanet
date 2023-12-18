<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;

class ReportActions extends Report
{
    private $appKey;

    /** @var  \ACL */
    private $acl;

    private $collIds = null;
    private $permalink = null;
    private $sqlColSelect = null;
    private $sqlFieldSelect = null;

    /* those vars will be set once by computeVars() */
    private $name = null;
    private $sql = null;
    private $columnTitles = [];
    private $keyName = null;
    private $actions = [];
    private $isDownloadReport = false;


    public function getColumnTitles()
    {
        $this->computeVars();
        // only for group downloads all and download by record
        if ((empty($this->parms['group']) || $this->parms['group'] == 'record') && !empty($this->permalink)) {
            $this->columnTitles[] = 'permalink_' . $this->permalink;
        }

        return $this->columnTitles;
    }

    public function getKeyName()
    {
        $this->computeVars();
        return $this->keyName;
    }

    public function getName()
    {
        $this->computeVars();
        return $this->name;
    }

    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;

        return $this;
    }

    public function setACL($acl)
    {
        $this->acl = $acl;

        return $this;
    }

    public function setCollIds($collIds)
    {
        $this->collIds = $collIds;

        return $this;
    }

    public function setPermalink($permalink)
    {
        $this->permalink = $permalink;

        return $this;
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;

        return $this;
    }

    public function setAsDownloadReport(bool $isDownloadReport)
    {
        $this->isDownloadReport = !!$isDownloadReport;

        return $this;
    }

    public function getAllRows($callback)
    {
        $app = $this->getDatabox()->getPhraseApplication();
        $userRepository = $app['repo.users'];

        $this->computeVars();
        $stmt = $this->databox->get_connection()->executeQuery($this->sql, []);
        while (($row = $stmt->fetch())) {

            // only for group downloads all and download by user
            if (empty($this->parms['group']) || $this->parms['group'] == 'user') {
                try {
                    /** @var User $user */
                    $user = $userRepository->find($row['usrid']);
                    $row['user'] = $user->getDisplayName();
                    $row['email'] = $user->getEmail();
                } catch (\Exception $e) {

                }
            }

            // only for group downloads all and download by record
            if ((empty($this->parms['group']) || $this->parms['group'] == 'record') && !empty($this->permalink)) {
                try {
                    $permalinkUrl = '';
                    $record = $this->databox->get_record($row['record_id']);
                    // if from GUI, check if user has access to subdef in collection
                    if (!isset($this->acl) || $this->acl->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDPREVIEW)) {
                        $permalinkUrl = $record->get_subdef($this->permalink)->get_permalink()->get_url()->__toString();
                    }
                } catch (\Exception $e) {
                    // the record or subdef is not found
                } catch (\Throwable $e) {
                    // there is no permalink created ???
                }

                $row['permalink_' . $this->permalink] = $permalinkUrl;
            }

            $callback($row);
        }
        $stmt->closeCursor();
    }

    private function computeVars()
    {
        if(!is_null($this->name)) {
            // vars already computed
            return;
        }

        switch ($this->parms['group']) {
            case null:
                if ($this->isDownloadReport) {
                    $this->name = "Downloads";
                    $this->columnTitles = ['id', 'usrid', 'user', 'email', 'fonction', 'societe', 'activite', 'pays', 'date', 'record_id', 'record_type', 'coll_id' ,'coll_name' ,'subdef', 'action', 'destinataire'];
                } else {
                    $this->columnTitles = ['id', 'usrid', 'user', 'email', 'fonction', 'societe', 'activite', 'pays', 'date', 'record_id', 'record_type', 'coll_id','coll_name' ,'final', 'action', 'comment'];
                }

                $this->sqlColSelect = [];
                $this->sqlFiedlSelect = [];
                foreach($this->getDatabox()->get_meta_structure() as $field) {
                    // skip the fields that can't be reported
                    if(!$field->is_report()) {
                        continue;
                    }

                    // column names is not important in the result, simply match the 'title' position
                    $this->columnTitles[] = $field->get_name();
                    $this->sqlColSelect[] = sprintf("GROUP_CONCAT(IF(`m`.`meta_struct_id`=%s, `m`.`value`, NULL)) AS `f%s`", $field->get_id(), $field->get_id());
                    $this->sqlFieldSelect[] = sprintf("`F`.`f%s`", $field->get_id());
                }

                $this->sqlColSelect = join(",\n", $this->sqlColSelect);
                $this->sqlFieldSelect = join(",\n", $this->sqlFieldSelect);

                if($this->parms['anonymize']) {
                    $sql = "SELECT `ld`.`id`, `l`.`usrid`, '-' AS `user`, '-' AS `email`, '-' AS `fonction`, '-' AS `societe`, '-' AS `activite`, '-' AS `pays`,\n"
                        . "        `ld`.`date`, `ld`.`record_id`, IF(`r`.`parent_record_id` = 0 , 'record' , 'story') AS `record_type`, `ld`.`coll_id`, `c`.`asciiname` AS `coll_name`, `ld`.`final`, `ld`.`action`, `ld`.`comment` AS `destinataire`,\n"
                        . $this->sqlFieldSelect . " \n"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " LEFT JOIN `coll` AS `c` ON `ld`.`coll_id` = `c`.`coll_id` \n"
                        . " LEFT JOIN `record` AS `r` ON `ld`.`record_id` = `r`.`record_id`"
                        . " LEFT JOIN (SELECT `m`.`record_id`, " . $this->sqlColSelect . " FROM `metadatas` AS `m` GROUP BY `m`.`record_id` ) AS `F` ON `ld`.`record_id` = `F`.`record_id` \n"
                        . " WHERE {{GlobalFilter}}";
                }
                else {
                    $sql = "SELECT `ld`.`id`, `l`.`usrid`, `l`.`user`, '-' AS `email`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                        . "        `ld`.`date`, `ld`.`record_id`, IF(`r`.`parent_record_id` = 0 , 'record' , 'story') AS `record_type`, `ld`.`coll_id`, `c`.`asciiname` AS `coll_name`, `ld`.`final`, `ld`.`action`, `ld`.`comment` AS `destinataire`,\n"
                        . $this->sqlFieldSelect . " \n"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " LEFT JOIN `coll` AS `c` ON `ld`.`coll_id` = `c`.`coll_id` \n"
                        . " LEFT JOIN `record` AS `r` ON `ld`.`record_id` = `r`.`record_id`"
                        . " LEFT JOIN (SELECT `m`.`record_id`, " . $this->sqlColSelect . " FROM `metadatas` AS `m` GROUP BY `m`.`record_id` ) AS `F` ON `ld`.`record_id` = `F`.`record_id` \n"
                        . " WHERE {{GlobalFilter}}";
                }

                $this->keyName = 'id';

                break;
            case 'user':
                $this->name = "Downloads by user";
                $this->columnTitles = ['usrid', 'user', 'email', 'fonction', 'societe', 'activite', 'pays', 'min_date', 'max_date', 'nb'];
                if($this->parms['anonymize']) {
                    $sql = "SELECT `l`.`usrid`, '-' AS `user`, '-' AS `email`, '-' AS `fonction`, '-' AS `societe`, '-' AS `activite`, '-' AS `pays`,\n"
                        . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " WHERE {{GlobalFilter}}\n"
                        . " GROUP BY `l`.`usrid`\n"
                        . " ORDER BY `nb` DESC";
                }
                else {
                    $sql = "SELECT `l`.`usrid`, `l`.`user`, '-' AS `email`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                        . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " WHERE {{GlobalFilter}}\n"
                        . " GROUP BY `l`.`usrid`\n"
                        . " ORDER BY `nb` DESC";
                }
                $this->keyName = 'usrid';
                break;
            case 'record':
                $this->name = "Downloads by record";
                $this->columnTitles = ['record_id', 'min_date', 'max_date', 'nb'];
                $sql = "SELECT `ld`.`record_id`,\n"
                    . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY `l`.`usrid`\n"
                    . " ORDER BY `nb` DESC"
                ;
                $this->keyName = 'record_id';
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        if (isset($this->acl)) {
            // get acl-filtered coll_id(s) as already sql-quoted
            $collIds = $this->getCollIds($this->acl, $this->parms['bases']);
        } else {
            $collIds = $this->collIds;
        }

        if ($this->isDownloadReport) {
            $this->actions = ['download', 'mail'];
        } else {
            $this->name = "export databox action";
        }

        if(!empty($collIds)) {
            $filter = "";
            if (!empty($this->actions)) {
                $actionFilter = join("' ,'", $this->actions);
                $filter = "`action` IN('" . $actionFilter . "') AND ";
            }

            $filter .= " `ld`.`coll_id` IN(" . join(',', $collIds) . ")\n"
                . "  AND `l`.`usrid`>0";

            if ($this->isDownloadReport) {
                // filter subdefs by class
                $subdefsToReport = ['document' => $this->databox->get_connection()->quote('document')];
                foreach ($this->getDatabox()->get_subdef_structure() as $subGroup) {
                    foreach ($subGroup->getIterator() as $sub) {
                        if(in_array($sub->get_class(), ['document', 'preview'])) {
                            // keep only unique names
                            $subdefsToReport[$sub->get_name()] = $this->databox->get_connection()->quote($sub->get_name());
                        }
                    }
                }

                $subdefsToReport = join(',', $subdefsToReport);
                $filter .="  AND `ld`.`final` IN(" . $subdefsToReport . ")";
            }

                // next line : comment to disable "site", to test on an imported dataset from another instance
            $filter .= "\n  AND `l`.`site` =  " . $this->databox->get_connection()->quote($this->appKey);

            if($this->parms['dmin']) {
                $filter .= "\n  AND `ld`.`date` >= " . $this->databox->get_connection()->quote($this->parms['dmin']);
            }
            if($this->parms['dmax']) {
                $filter .= "\n  AND `ld`.`date` <= " . $this->databox->get_connection()->quote($this->parms['dmax'] . " 23:59:59");
            }
        }
        else {
            // no collections report ?
            // keep the sql intact (to match placeholders/parameters), but enforce empty result
            $filter = "FALSE";
        }

        $this->sql = str_replace('{{GlobalFilter}}', $filter, $sql);

        // file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, $this->sql), FILE_APPEND);
    }

}
