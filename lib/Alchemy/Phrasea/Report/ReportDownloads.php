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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;


class ReportDownloads extends Report
{
    private $appKey;

    /** @var  \ACL */
    private $acl;

    /* those vars will be set once by computeVars() */
    private $name = null;
    private $sql = null;
    private $columnTitles = [];
    private $keyName = null;


    public function getColumnTitles()
    {
        $this->computeVars();
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

    public function getAllRows($callback)
    {
        $this->computeVars();
        $stmt = $this->databox->get_connection()->executeQuery($this->sql, []);
        while (($row = $stmt->fetch())) {
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
                $this->name = "Downloads";
                $this->columnTitles = ['id', 'usrid', 'user', 'fonction', 'societe', 'activite', 'pays', 'date', 'record_id', 'coll_id', 'subdef'];
                if($this->parms['anonymize']) {
                    $sql = "SELECT `ld`.`id`, `l`.`usrid`, '-' AS `user`, '-' AS `fonction`, '-' AS `societe`, '-' AS `activite`, '-' AS `pays`,\n"
                        . "        `ld`.`date`, `ld`.`record_id`, `ld`.`coll_id`, `ld`.`final`"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " WHERE {{GlobalFilter}}";
                }
                else {
                    $sql = "SELECT `ld`.`id`, `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                        . "        `ld`.`date`, `ld`.`record_id`, `ld`.`coll_id`, `ld`.`final`"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " WHERE {{GlobalFilter}}";
                }
                $this->keyName = 'id';
                break;
            case 'user':
                $this->name = "Downloads by user";
                $this->columnTitles = ['usrid', 'user', 'fonction', 'societe', 'activite', 'pays', 'min_date', 'max_date', 'nb'];
                if($this->parms['anonymize']) {
                    $sql = "SELECT `l`.`usrid`, '-' AS `user`, '-' AS `fonction`, '-' AS `societe`, '-' AS `activite`, '-' AS `pays`,\n"
                        . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " WHERE {{GlobalFilter}}\n"
                        . " GROUP BY `l`.`usrid`\n"
                        . " ORDER BY `nb` DESC";
                }
                else {
                    $sql = "SELECT `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
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

        // get acl-filtered coll_id(s) as already sql-quoted
        $collIds = $this->getCollIds($this->acl, $this->parms['bases']);

        if(!empty($collIds)) {

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

            $filter = "`action`='download' AND `ld`.`coll_id` IN(" . join(',', $collIds) . ")\n"
                    . "  AND `l`.`usrid`>0\n"
                    . "  AND `ld`.`final` IN(" . $subdefsToReport . ")";

                // next line : comment to disable "site", to test on an imported dataset from another instance
            $filter .= "\n  AND `l`.`site` =  " . $this->databox->get_connection()->quote($this->appKey);

            if($this->parms['dmin']) {
                $filter .= "\n  AND `ld`.`date` >= " . $this->databox->get_connection()->quote($this->parms['dmin']);
            }
            if($this->parms['dmax']) {
                $filter .= "\n  AND `ld`.`date` <= " . $this->databox->get_connection()->quote($this->parms['dmax']);
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
