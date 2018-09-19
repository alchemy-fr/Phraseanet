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


class ReportDownloads extends Report implements ReportInterface
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

    public function getSql()
    {
        $this->computeVars();
        return $this->sql;
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

    public function getSqlParms()
    {
        return [
            ':site' => $this->appKey,
            ':dmin' => $this->parms['dmin'],
            ':dmax' => $this->parms['dmax']
        ];
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

    private function computeVars()
    {
        if(!is_null($this->sql)) {
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
                        . " WHERE {{GlobalFilter}}"
                        . " GROUP BY `l`.`usrid`\n"
                        . " ORDER BY `nb` DESC";
                }
                else {
                    $sql = "SELECT `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                        . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                        . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                        . " WHERE {{GlobalFilter}}"
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
                    . " WHERE {{GlobalFilter}}"
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

            $this->sql = str_replace(
                '{{GlobalFilter}}',
                "`action`='download' AND `ld`.`coll_id` IN(" . join(',', $collIds) . ")\n"
                . " AND `l`.`site` =  :site AND !ISNULL(`l`.`usrid`) AND `ld`.`date` >= :dmin AND `ld`.`date` <= :dmax\n"
                // here : diabled "site", to test on an imported dataset from another instance
                // . " AND (TRUE OR `l`.`site` =  :site) AND !ISNULL(`l`.`usrid`) AND `ld`.`date` >= :dmin AND `ld`.`date` <= :dmax\n"
                . " AND `ld`.`final` IN(" . $subdefsToReport . ")",
                $sql
            );
        }

        if(is_null($this->sql)) {
            // no collections report ?
            // keep the sql intact (to match placeholders/parameters), but enforce empty result
            $this->sql = str_replace(
                '{{GlobalFilter}}',
                "FALSE",
                $sql
            );
        }
    }

}
