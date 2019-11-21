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


class ReportRecords extends Report
{
    /** @var  \ACL */
    private $acl;

    /* those vars will be set once by computeVars() */
    private $name = null;
    private $sqlWhere = null;
    private $sqlColSelect = null;
    private $columnTitles = null;
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

    public function setACL($acl)
    {
        $this->acl = $acl;

        return $this;
    }

    public function getAllRows($callback)
    {
        $this->computeVars();

        $lastRid = 0;
        while(true) {
            $sql = "SELECT MIN(record_id) AS `from`, MAX(record_id) AS `to` FROM (\n"
                . "SELECT record_id FROM record AS `r`\n"
                . "WHERE " . $this->sqlWhere . " AND record_id>" . $lastRid . " LIMIT 5000) AS _t";
            $stmt = $this->databox->get_connection()->executeQuery($sql, []);
            $row = $stmt->fetch();
            $stmt->closeCursor();

            if($row && !is_null($row['from']) && !is_null($row['to'])) {
                $sql = "SELECT r.record_id, c.asciiname, r.moddate, r.mime, r.type, r.originalname,\n"
                    . $this->sqlColSelect . "\n"
                    . "FROM (`record` AS `r` LEFT JOIN `coll` AS `c` USING(`coll_id`)) LEFT JOIN `metadatas` AS `m` USING(`record_id`)\n"
                    . "WHERE " . $this->sqlWhere . "\n"
                    . "  AND r.record_id >= " . $row['from'] . " AND r.record_id <= " . $row['to'] . "\n"
                    . "GROUP BY `record_id`\n";

                $stmt = $this->databox->get_connection()->executeQuery($sql, []);
                $rows = $stmt->fetchAll();
                $stmt->closeCursor();
                foreach($rows as $row) {
                    $callback($row);
                    $lastRid = $row['record_id'];
                }
            }
            else {
                break;
            }
        }
    }

    private function computeVars()
    {
        if(!is_null($this->name)) {
            // vars already computed
            return;
        }

        // pivot-like query on metadata fields
        $this->sqlColSelect = [];
        $this->columnTitles = ['record_id', 'collection', 'moddate', 'mime', 'type', 'originalname'];
        foreach($this->getDatabox()->get_meta_structure() as $field) {
            // skip the fields that can't be reported
            if(!$field->is_report() || ($field->isBusiness() && !$this->acl->can_see_business_fields($this->getDatabox()))) {
                continue;
            }
            // if a list of meta was provided, just keep those
            if(is_array($this->parms['meta']) && !in_array($field->get_name(), $this->parms['meta'])) {
                continue;
            }
            // column names is not important in the result, simply match the 'title' position
            $this->columnTitles[] = $field->get_name();
            $this->sqlColSelect[] = sprintf("GROUP_CONCAT(IF(`m`.`meta_struct_id`=%s, `m`.`value`, NULL)) AS `f%s`", $field->get_id(), $field->get_id());
        }

        $this->sqlColSelect = join(",\n", $this->sqlColSelect);

        // get acl-filtered coll_id(s) as already sql-quoted
        $collIds = $this->getCollIds($this->acl, $this->parms['base']);
        if(!empty($collIds)) {
            $this->sqlWhere = "`r`.`parent_record_id`=0 AND `r`.`coll_id` IN(" . join(',', $collIds) . ")";
            if(!is_null($this->parms['dmin'])) {
                $this->sqlWhere .= " AND r.moddate >= " . $this->databox->get_connection()->quote($this->parms['dmin']);
            }
            if(!is_null($this->parms['dmax'])) {
                $this->sqlWhere .= " AND r.moddate <= " . $this->databox->get_connection()->quote($this->parms['dmax']);
            }
        }
        else {
            $this->sqlWhere = "FALSE";
        }

        $this->name = "Databox";
    }

}
