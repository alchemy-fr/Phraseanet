<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_subdef extends task_databoxAbstract
{
    /**
     * Record buffer for writing meta datas after building subdefs
     *
     * @var array
     */
    protected $recs_to_write = array();

    /**
     * Maximum buffer size before flushing records
     *
     * @var <type>
     */
    protected $record_buffer_size;

    /**
     * Return about text
     *
     * @return <type>
     */
    public function help()
    {
        return(
            _("task::subdef:creation des sous definitions des documents d'origine")
            );
    }

    /**
     * Returns task name
     *
     * @return string
     */
    public function getName()
    {
        return(_('task::subdef:creation des sous definitions'));
    }


    /**
     *
     * generates le code js de l'interface 'graphic view'
     *
     */
    public function printInterfaceJS()
    {
        ?>
        <script type="text/javascript">
            function taskFillGraphic_<?php echo(get_class($this));?>(xml)
            {
                if(xml)
                {
                    xml = $.parseXML(xml);
                    xml = $(xml);

                    with(document.forms['graphicForm'])
                    {
                        period.value  = xml.find("period").text();
                        flush.value   = xml.find("flush").text();
                        maxrecs.value = xml.find("maxrecs").text();
                        maxmegs.value = xml.find("maxmegs").text();
                    }
                }
            }

            $(document).ready(function(){
                $("#graphicForm *").change(function(){
                    var limits = {
                                            'period': {min:1,  max:300,  allowempty:false} ,
                                            'flush':  {min:1,  max:100,  allowempty:false} ,
                                            'maxrecs':{min:10, max:1000, allowempty:true} ,
                                            'maxmegs':{min:2,  max:100,  allowempty:true}
                                        } ;
                    var name = $(this).attr("name");
                    if(name && limits[name])
                    {
                        var v = $(this).val();
                        if(v != "" || !limits[name].allowempty)
                        {
                            v = 0|v;
                            if(v < limits[name].min)
                                $(this).val(limits[name].min);
                            else if(v > limits[name].max)
                                $(this).val(limits[name].max);
                        }
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * return interface 'graphic view'
     *
     */

    public function getInterfaceHTML()
    {
        ob_start();
        ?>
        <form id="graphicForm" name="graphicForm" onsubmit="return(false);" method="post">
            <br/>
            <?php echo _('task::_common_:periodicite de la tache') ?>&nbsp;:&nbsp;
            <input type="text" name="period" style="width:40px;" value="">
            <?php echo _('task::_common_:secondes (unite temporelle)') ?><br/>
            <br/>
            <?php echo sprintf(_("task::_common_:passer tous les %s records a l'etape suivante"), '<input type="text" name="flush" style="width:40px;" value="">'); ?>
            <br/>
            <br/>
            <?php echo _('task::_common_:relancer la tache tous les') ?>&nbsp;
            <input type="text" name="maxrecs" style="width:40px;" value="">
            <?php echo _('task::_common_:records, ou si la memoire depasse') ?>&nbsp;
            <input type="text" name="maxmegs" style="width:40px;" value=""> Mo
            <br/>
        </form>
        <?php

        return ob_get_clean();
    }

    public function retrieveSbasContent(databox $databox)
    {
        $connbas = $databox->get_connection();

        $sql = 'SELECT coll_id, record_id
              FROM record
              WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0
              ORDER BY record_id DESC LIMIT 0, 20';

        $stmt = $connbas->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $rs;
    }

    public function processOneContent(databox $databox, Array $row)
    {
        $record_id = $row['record_id'];
        $this->log(sprintf(
                "Generate subdefs for :  sbas_id %s / record %s "
                , $this->sbas_id, $record_id));

        try {
            $record = new record_adapter($this->dependencyContainer, $this->sbas_id, $record_id);

            $record->generate_subdefs($databox, $this->dependencyContainer);
        } catch (\Exception $e) {
            $this->log(
                sprintf(
                    'Generate failed for record %d on databox %s (%s)', $record_id, $record->get_databox()->get_viewname(), $e->getMessage()
                )
            );
        }

        $this->recs_to_write[] = $record->get_record_id();

        if (count($this->recs_to_write) >= $this->record_buffer_size) {
            $this->flushRecordsSbas();
        }
        unset($record);

        return $this;
    }

    protected function postProcessOneContent(databox $databox, Array $row)
    {
        $connbas = $databox->get_connection();
        $sql = 'UPDATE record
              SET jeton=(jeton & ~' . JETON_MAKE_SUBDEF . '), moddate=NOW()
              WHERE record_id=:record_id';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $row['record_id']));
        $stmt->closeCursor();

        return $this;
    }

    protected function flushRecordsSbas()
    {
        $sql = implode(', ', $this->recs_to_write);

        if ($sql != '') {
            $this->log(sprintf(
                    'setting %d record(s) to subdef meta writing'
                    , count($this->recs_to_write)
                ));

            try {
                $connbas = connection::getPDOConnection($this->dependencyContainer, $this->sbas_id);
                $sql = 'UPDATE record
                SET status=(status & ~0x03),
                    jeton=(jeton | ' . JETON_WRITE_META_SUBDEF . ')
                WHERE record_id IN (' . $sql . ')';
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            } catch (Exception $e) {
                $this->log($e->getMessage());
            }
        }
        $this->recs_to_write = array();

        return $this;
    }
}

