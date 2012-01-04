<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_sqldownload extends module_report_sql implements module_report_sqlReportInterface
{

  protected $restrict = false;

  public function __construct(module_report $report)
  {
    parent::__construct($report);
    if($report->isInformative())
    {
      $this->restrict = true;
    }
  }

  public function buildSql()
  {
    $registry = registry::get_instance();
    $report_filters = $this->filter->getReportFilter();
    $record_filters = $this->filter->getRecordFilter() ? : array('sql' => '', 'params' => array());
    $this->params = array_merge($report_filters['params'], $record_filters['params']);

    if ($this->groupby == false)
    {
      $this->sql = "
        SELECT
         log.user,
         log.site,
         log.societe,
         log.pays,
         log.activite,
         log.fonction,
         log.usrid,
         record.coll_id,
         record.xml,
         log_docs.date AS ddate,
         log_docs.id,
         log_docs.log_id,
         log_docs.record_id,
         log_docs.final,
         log_docs.comment
       FROM (log
        INNER JOIN log_docs ON log.id = log_docs.log_id
        INNER JOIN record ON log_docs.record_id = record.record_id
       )
       WHERE ";

      $this->sql .= $report_filters['sql'] ? : '';

      $this->sql .= ' AND ( log_docs.action = \'download\' OR log_docs.action = \'mail\')';
      if($this->restrict)
        $this->sql .= ' AND ( log_docs.final = "document" OR log_docs.final = "preview")';
      $this->sql .= empty($record_filters['sql']) ? '' : ' AND ( ' . $record_filters['sql'] . ' )';

      $this->sql .= $this->filter->getOrderFilter() ? : '';

//      var_dump(str_replace(array_keys($this->params), array_values($this->params), $this->sql), $this->sql, $this->params);
      $stmt = $this->connbas->prepare($this->sql);
      $stmt->execute($this->params);
      $this->total_row = $stmt->rowCount();
      $stmt->closeCursor();

      $this->sql .= $this->filter->getLimitFilter() ? : '';
    }
    else
    {
      $name = $this->groupby;
      $field = $this->getTransQuery($this->groupby);

      if ($name == 'record_id' && $this->on == 'DOC')
      {
        $this->sql = '
             SELECT
              TRIM( ' . $field . ' ) AS ' . $name . ',
              SUM(1) AS telechargement,
              record.coll_id,
              record.xml,
              log_docs.final,
              log_docs.comment,
              subdef.size,
              subdef.file,
              subdef.mime
             FROM ( log
              INNER JOIN log_docs ON log.id = log_docs.log_id
              INNER JOIN record ON log_docs.record_id = record.record_id
              INNER JOIN subdef ON (log_docs.record_id = subdef.record_id
               AND subdef.name = log_docs.final
              )
             )
             WHERE
            ';
      }
      elseif ($this->on == 'DOC')
      {
        $this->sql = '
           SELECT
            TRIM(' . $field . ') AS ' . $name . ',
            SUM(1) AS telechargement
           FROM ( log
            INNER JOIN log_docs ON log.id = log_docs.log_id
            INNER JOIN record ON log_docs.record_id = record.record_id
            INNER JOIN subdef ON ( log_docs.record_id = subdef.record_id
             AND subdef.name = log_docs.final
            )
           )
           WHERE
                ';
      }
      else
      {

        $this->sql = '
         SELECT
          TRIM( ' . $this->getTransQuery($this->groupby) . ') AS ' . $name . ',
          SUM(1) AS nombre
         FROM ( log
          INNER JOIN log_docs ON log.id = log_docs.log_id
          INNER JOIN record ON log_docs.record_id = record.record_id
          INNER JOIN subdef ON (record.record_id = subdef.record_id AND subdef.name = "document")
         )
         WHERE ';
      }

      $this->sql .= $report_filters['sql'];

      $this->sql .= ' AND ( log_docs.action = \'download\' OR log_docs.action = \'mail\')';

      $this->sql .= empty($record_filters['sql']) ? '' : ' AND ( ' . $record_filters['sql'] . ' )';

      $this->sql .= $this->on == 'DOC' ? 'AND subdef.name =  \'document\' ' : '';

      $this->sql .= ' GROUP BY ' . $this->groupby;

      $this->sql .= ( $name == 'record_id' && $this->on == 'DOC') ? ' , final' : '';

      if ($this->filter->getOrderFilter())
        $this->sql .= $this->filter->getOrderFilter();

      $stmt = $this->connbas->prepare($this->sql);
      $stmt->execute($this->params);
      $this->total = $stmt->rowCount();
      $stmt->closeCursor();

      $this->sql .= $this->filter->getLimitFilter() ? : '';
    }

    return $this;
  }

  public function sqlDistinctValByField($field)
  {
    $report_filters = $this->filter->getReportFilter();
    $params = array_merge($report_filters['params']);
    $this->params = $params;

    $sql = '
            SELECT  DISTINCT( ' . $this->getTransQuery($field) . ' ) AS val
            FROM (log
                INNER JOIN log_docs ON log.id = log_docs.log_id
                INNER JOIN record ON log_docs.record_id = record.record_id
                INNER JOIN subdef ON log_docs.record_id = subdef.record_id)
            WHERE ';

    $sql .= $report_filters['sql'];
    $sql .= ' AND (log_docs.action = ' .
            '\'download\'OR log_docs.action = \'mail\')';

    $sql .= $this->on == 'DOC' ? 'AND subdef.name =  \'document\'' : '';
    $sql .= $this->filter->getOrderFilter() ? : '';
    $sql .= $this->filter->getLimitFilter() ? : '';

    return array('sql' => $sql, 'params' => $params);
  }

}
