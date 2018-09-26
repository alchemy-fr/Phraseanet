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


class ReportRecords extends Report implements ReportInterface
{
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
        return [];
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

        // pivot-like query on metadata fields
        $colSelect = [];
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
            $colSelect[] = sprintf("GROUP_CONCAT(IF(`m`.`meta_struct_id`=%s, `m`.`value`, NULL)) AS `f%s`", $field->get_id(), $field->get_id());
        }

        // get acl-filtered coll_id(s) as already sql-quoted
        $collIds = $this->getCollIds($this->acl, $this->parms['base']);
        if(!empty($collIds)) {
            $where = "`r`.`parent_record_id`=0 AND `r`.`coll_id` IN(" . join(',', $collIds) . ")";
            if(!is_null($this->parms['dmin'])) {
                $where .= " AND r.moddate >= " . $this->databox->get_connection()->quote($this->parms['dmin']);
            }
            if(!is_null($this->parms['dmax'])) {
                $where .= " AND r.moddate <= " . $this->databox->get_connection()->quote($this->parms['dmax']);
            }
        }
        else {
            $where = "FALSE";
        }

        $this->sql = "SELECT r.record_id, c.asciiname, r.moddate, r.mime, r.type, r.originalname,\n"
            . join(",\n", $colSelect) . "\n"
            . "FROM (`record` AS `r` INNER JOIN `coll` AS `c` USING(`coll_id`)) INNER JOIN `metadatas` AS `m` USING(`record_id`)\n"
            . "WHERE " . $where . "\n"
            // . "  AND r.record_id>=17496 AND r.record_id<=17497\n"
            . "GROUP BY `record_id`\n"
. "LIMIT 75000";

        $this->name = "Databox";

    }

}
