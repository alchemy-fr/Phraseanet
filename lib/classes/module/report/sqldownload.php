<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class module_report_sqldownload extends module_report_sql implements module_report_sqlReportInterface
{
    protected $restrict = false;

    public function __construct(Application $app, module_report $report)
    {
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
        parent::__construct($app, $report);
        if ($report->isInformative()) {
            $this->restrict = true;
        }
    }

    public function buildSql()
    {
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
        $customFieldMap = array();

        $filter = $this->filter->getReportFilter() ? : array('params' => array(), 'sql' => false);
        $this->params = array_merge(array(), $filter['params']);

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);
        if ($this->groupby == false) {
/*
            $this->sql = "
                SELECT DISTINCT(log.id), log.user, log.societe, log.pays, log.activite, log_colls.coll_id,
                log.fonction, log.usrid, log_docs.date AS ddate, log_docs.record_id, log_docs.final, log_docs.comment
                FROM log_docs
                INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_docs.log_id)
                INNER JOIN log_colls FORCE INDEX (couple)  ON (log.id = log_colls.log_id)
                WHERE (" .$filter['sql'] . ") AND (log_docs.action = 'download' OR log_docs.action = 'mail')";
*/
            $this->sql = "
                SELECT log.id, log.user, log.societe, log.pays, log.activite, record.coll_id,
                log.fonction, log.usrid, log_docs.date AS ddate, log_docs.record_id, log_docs.final, log_docs.comment
                FROM
                    (
                        log_docs
                        INNER JOIN
                        log FORCE INDEX (date_site) ON (log.id = log_docs.log_id)
                    )
                    LEFT JOIN
                    record ON (record.record_id=log_docs.record_id)
                WHERE (" .$filter['sql'] . ") AND (log_docs.action = 'download' OR log_docs.action = 'mail')";

            if ($this->restrict) {
                $this->sql .= ' AND (log_docs.final = "document" OR log_docs.final = "preview")';
            }
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $this->sql), FILE_APPEND);
        } else {
            $name = $this->groupby;
            $field = $this->getTransQuery($this->groupby);

            if ($name == 'record_id' && $this->on == 'DOC') {
                $this->sql = '
                    SELECT ' . $name . ', SUM(1) AS telechargement, tt.comment, tt.size, tt.file, tt.mime, tt.final
                    FROM (
                       SELECT log.id, TRIM( ' . $field . ' ) AS ' . $name . ', log_docs.comment, subdef.size, subdef.file, subdef.mime, log_docs.final
                       FROM log FORCE INDEX (date_site)
                       INNER JOIN log_docs ON (log.id = log_docs.log_id)

                       LEFT JOIN record ON (log_docs.record_id = record.record_id)
                       LEFT JOIN subdef ON (log_docs.record_id = subdef.record_id)';

                $customFieldMap = array(
                    $field => $name,
                    'log_docs.comment'  => 'tt.comment',
                    'subdef.size'       => 'tt.size',
                    'subdef.file'       => 'tt.file',
                    'subdef.mime'       => 'tt.mime',
                    'log_docs.final'    => 'tt.final',
                );
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $this->sql), FILE_APPEND);

            } elseif ($this->on == 'DOC') {
                $this->sql = '
                    SELECT ' . $name . ', SUM(1) AS telechargement
                    FROM (
                        SELECT DISTINCT(log.id), TRIM(' . $field . ') AS ' . $name . '
                        FROM log FORCE INDEX (date_site)
                        INNER JOIN log_docs ON (log.id = log_docs.log_id)
                        INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                        INNER JOIN record ON (log_docs.record_id = record.record_id)
                        INNER JOIN subdef ON ( log_docs.record_id = subdef.record_id)';
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $this->sql), FILE_APPEND);
            } else {
                $this->sql = '
                    SELECT ' . $name . ', SUM(1) AS nombre
                    FROM (
                        SELECT DISTINCT(log.id), TRIM( ' . $this->getTransQuery($this->groupby) . ') AS ' . $name . '
                        FROM log FORCE INDEX (date_site)
                        INNER JOIN log_docs ON (log.id = log_docs.log_id)
                        INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                        INNER JOIN record ON (log_docs.record_id = record.record_id)
                        INNER JOIN subdef ON (record.record_id = subdef.record_id)';
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $this->sql), FILE_APPEND);
            }

            $this->sql .= ' WHERE (subdef.name = log_docs.final) AND ' . $filter['sql'] . ' ';
            $this->sql .= 'AND ( log_docs.action = \'download\' OR log_docs.action = \'mail\') ';
            $this->sql .= $this->on == 'DOC' ? 'AND subdef.name =  \'document\' ' : '';
            $this->sql .= ') as tt';
            $this->sql .= ' GROUP BY ' . $name . ' ' . ($name == 'record_id' && $this->on == 'DOC' ? ', final' : '');
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $this->sql), FILE_APPEND);
        }

        $stmt = $this->connbas->prepare($this->sql);
        $stmt->execute($this->params);
        $this->total_row = $stmt->rowCount();
        $stmt->closeCursor();
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        if (count($customFieldMap) > 0) {
            $this->sql .= $this->filter->getOrderFilter($customFieldMap) ? : '';
        } else {
            $this->sql .= $this->filter->getOrderFilter() ? : '';
        }

        if ($this->enable_limit) {
            $this->sql .= $this->filter->getLimitFilter() ? : '';
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $this->sql), FILE_APPEND);

        return $this;
    }

    public function sqlDistinctValByField($field)
    {
        $filter = $this->filter->getReportFilter() ? : array('params' => array(), 'sql' => false);
        $this->params = array_merge(array(), $filter['params']);

        $this->sql = '
            SELECT DISTINCT(tt.val)
            FROM (
                SELECT DISTINCT(log.id), ' . $this->getTransQuery($field) . ' AS val
                FROM log FORCE INDEX (date_site)
                    INNER JOIN log_docs ON (log.id = log_docs.log_id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                    INNER JOIN record ON (log_docs.record_id = record.record_id)
                    INNER JOIN subdef ON (log_docs.record_id = subdef.record_id)
                WHERE (' . $filter['sql'] . ')
                AND (log_docs.action = "download" OR log_docs.action = "mail")' .
                ($this->on == 'DOC' ? ' AND subdef.name =  "document"' : '') .
            ') AS tt';

        $this->sql .= $this->filter->getOrderFilter() ? : '';
        $this->sql .= $this->filter->getLimitFilter() ? : '';

        return array('sql' => $this->sql, 'params' => $this->params);
    }
}
