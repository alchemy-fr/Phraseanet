<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class task_period_subdef extends task_databoxAbstract
{
    const MINMEGS = 20;
    const MAXMEGS = 64;

    const MINFLUSH = 10;
    const MAXFLUSH = 100;

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
     * must return the xml (text) version of the form
     *
     * @param  string $oldxml
     * @return string
     */
    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms('period', 'flush', 'maxrecs', 'maxmegs');
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (@$dom->loadXML($oldxml)) {
            $xmlchanged = false;

            foreach (array('str:period', 'str:flush', 'str:maxrecs', 'str:maxmegs') as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if (($ns = $dom->getElementsByTagName($pname)->item(0)) != NULL) {
                    while (($n = $ns->firstChild)) {
                        $ns->removeChild($n);
                    }
                } else {
                    $ns = $dom->documentElement->appendChild($dom->createElement($pname));
                }
                switch ($ptype) {
                    case "str":
                    case "pop":
                        $ns->appendChild($dom->createTextNode($pvalue));
                        break;
                    case "boo":
                        $ns->appendChild($dom->createTextNode($pvalue ? '1' : '0'));
                        break;
                }
                $xmlchanged = true;
            }
        }

        return($dom->saveXML());
    }

    /**
     * must fill the graphic form (using js) from xml
     *
     * @param  string $xml
     * @param  string $form
     * @return string
     */
    public function xml2graphic($xml, $form)
    {
        if (false !== $sxml = simplexml_load_string($xml)) {
            if ((int) ($sxml->period) < self::MINPERIOD) {
                $sxml->period = self::MINPERIOD;
            } elseif ((int) ($sxml->period) > self::MAXPERIOD) {
                $sxml->period = self::MAXPERIOD;
            }

            if ((int) ($sxml->flush) < self::MINFLUSH) {
                $sxml->flush = self::MINFLUSH;
            } elseif ((int) ($sxml->flush) > self::MAXFLUSH) {
                $sxml->flush = self::MAXFLUSH;
            }

            if ((int) ($sxml->maxrecs) < self::MINRECS) {
                $sxml->maxrecs = self::MINRECS;
            } elseif (self::MAXRECS != -1 && (int) ($sxml->maxrecs) > self::MAXRECS) {
                $sxml->maxrecs = self::MAXRECS;
            }

            if ((int) ($sxml->maxmegs) < self::MINMEGS) {
                $sxml->maxmegs = self::MINMEGS;
            } elseif (self::MAXMEGS != -1 && (int) ($sxml->maxmegs) > self::MAXMEGS) {
                $sxml->maxmegs = self::MAXMEGS;
            }
            ?>
            <script type="text/javascript">
            <?php echo $form ?>.period.value  = "<?php echo p4string::MakeString($sxml->period, "js", '"') ?>";
            <?php echo $form ?>.flush.value   = "<?php echo p4string::MakeString($sxml->flush, "js", '"') ?>";
            <?php echo $form ?>.maxrecs.value = "<?php echo p4string::MakeString($sxml->maxrecs, "js", '"') ?>";
            <?php echo $form ?>.maxmegs.value = "<?php echo p4string::MakeString($sxml->maxmegs, "js", '"') ?>";
            </script>

            <?php

            return("");
        } else {
            return("BAD XML");
        }
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
                if (xml) {
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
                var limits = {
                    'period' :{'min':<?php echo self::MINPERIOD; ?>, 'max':<?php echo self::MAXPERIOD; ?>},
                    'flush'  :{'min':<?php echo self::MINFLUSH; ?>,  'max':<?php echo self::MAXFLUSH; ?>},
                    'maxrecs':{'min':<?php echo self::MINRECS; ?>,   'max':<?php echo self::MAXRECS; ?>},
                    'maxmegs':{'min':<?php echo self::MINMEGS; ?>,   'max':<?php echo self::MAXMEGS; ?>}
                } ;
                $(".formElem").change(function(){
                    fieldname = $(this).attr("name");
                    switch ((this.nodeName+$(this).attr("type")).toLowerCase()) {
                        case "inputtext":
                            if (typeof(limits[fieldname])!='undefined') {
                                var v = 0|this.value;
                                if(v < limits[fieldname].min)
                                    v = limits[fieldname].min;
                                else if(v > limits[fieldname].max)
                                    v = limits[fieldname].max;
                                this.value = v;
                            }
                            break;
                    }
                    setDirty();
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
            <input class="formElem" type="text" name="period" style="width:40px;" value="">
            <?php echo _('task::_common_:secondes (unite temporelle)') ?>
            <br/>
            <br/>
            <?php echo sprintf(_("task::_common_:passer tous les %s records a l'etape suivante"), '<input class="formElem" type="text" name="flush" style="width:40px;" value="">'); ?>
            <br/>
            <br/>
            <?php echo _('task::_common_:relancer la tache tous les') ?>&nbsp;
            <input class="formElem" type="text" name="maxrecs" style="width:40px;" value="">
            <?php echo _('task::_common_:records, ou si la memoire depasse') ?>&nbsp;
            <input class="formElem" type="text" name="maxmegs" style="width:40px;" value="">
            Mo
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
              ORDER BY record_id DESC LIMIT 0, '.$this->maxrecs;
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
                "Generate subdefs for : sbasid=%s / databox=%s / recordid=%s "
                , $databox->get_sbas_id(), $databox->get_dbname() , $record_id)
                , self::LOG_INFO
            );

        try {
            $record = new record_adapter($this->dependencyContainer, $this->sbas_id, $record_id);

            $record->generate_subdefs($databox, $this->dependencyContainer);
        } catch (\Exception $e) {
            $this->log(
                sprintf(
                "Generate subdefs failed for : sbasid=%s / databox=%s / recordid=%s : %s"
                , $databox->get_sbas_id(), $databox->get_dbname() , $record_id, $e->getMessage())
                , self::LOG_WARNING
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
                ), self::LOG_INFO);

            try {
                $connbas = connection::getPDOConnection($this->dependencyContainer, $this->sbas_id);
                $sql = 'UPDATE record
                SET status=(status & ~0x03),
                    jeton=(jeton | ' . JETON_WRITE_META_SUBDEF . ')
                WHERE record_id IN (' . $sql . ')';
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            } catch (\Exception $e) {
                $this->log($e->getMessage(), self::LOG_CRITICAL);
            }
        }
        $this->recs_to_write = array();

        return $this;
    }
}
