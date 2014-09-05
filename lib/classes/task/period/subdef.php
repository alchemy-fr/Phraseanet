<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Core\Configuration\Configuration;

use MediaAlchemyst\Transmuter\Image2Image;

class task_period_subdef extends task_databoxAbstract
{
    const MINFLUSH = 10;

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

    protected $thumbnailExtraction;

    protected $recordtype = array();

    /**
     * Return about text
     *
     * @return <type>
     */
    public static function help()
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
    public static function getName()
    {
        return(_('task::subdef:creation des sous definitions'));
    }

    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->thumbnailExtraction = (Boolean) trim($sx_task_settings->embedded);
        $this->mono_sbas_id = (int) trim($sx_task_settings->sbas);

        foreach(array('image',
                    'video',
                    'audio',
                    'document',
                    'flash',
                    'unknown') as $f)
        {
            if(!isset($sx_task_settings->{"type_".$f})) {
                $sx_task_settings->{"type_".$f} = "1";
            }
            $this->recordtype[$f] = (Boolean) trim($sx_task_settings->{"type_".$f});
        }

        parent::loadSettings($sx_task_settings);
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

        $parm2 = $request->get_parms(
            'period',
            'sbas',
            'type_image',
            'type_video',
            'type_audio',
            'type_document',
            'type_flash',
            'type_unknown',
            'flush',
            'maxrecs',
            'maxmegs',
            'embedded'
        );
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (@$dom->loadXML($oldxml)) {
            $xmlchanged = false;

            foreach (array('str:period', 'str:sbas'
                     , 'boo:type_image'
                     , 'boo:type_video'
                     , 'boo:type_audio'
                     , 'boo:type_document'
                     , 'boo:type_flash'
                     , 'boo:type_unknown'
                     , 'str:flush', 'str:maxrecs', 'str:maxmegs', 'boo:embedded') as $pname) {
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
            <?php echo $form ?>.embedded.value = <?php echo (Boolean) trim($sxml->embedded); ?>;
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

                    with($("#graphicForm"))
                    {
                        var f = ["period", "flush", "maxrecs", "maxmegs"];
                        for(var i in f) {
                            $("INPUT[name='"+f[i]+"']").val(xml.find(f[i]).text());
                        }
                        var f = ["image", "video", "audio", "document", "flash", "unknown"];
                        for(var i  in f) {
                            // if not defined in settings, default to checked
                            var v = xml.find("type_"+f[i]).text();
                            if(v == "") {
                                v = "1";
                            }
                            $("INPUT[name='type_"+f[i]+"']").attr("checked", !!parseInt(v));
                        }
                        $("INPUT[name='embedded']").attr("checked", !!parseInt(xml.find("embedded").text()));
                        $("SELECT[name='sbas']").children("option[value='"+xml.find("sbas").text()+"']").attr("selected", "selected");
                        $("SELECT[name='recordtype']").children("option[value='"+xml.find("recordtype").text()+"']").attr("selected", "selected");
                    }
                }
            }

            $(document).ready(function () {
                var limits = {
                    'period' :{'min':<?php echo self::MINPERIOD; ?>, 'max':<?php echo self::MAXPERIOD; ?>},
                    'flush'  :{'min':<?php echo self::MINFLUSH; ?>,  'max':<?php echo self::MAXFLUSH; ?>},
                    'maxrecs':{'min':<?php echo self::MINRECS; ?>,   'max':<?php echo self::MAXRECS; ?>},
                    'maxmegs':{'min':<?php echo self::MINMEGS; ?>,   'max':<?php echo self::MAXMEGS; ?>}
                } ;
                $(".formElem").change(function () {
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
        <form id="graphicForm" class="form-horizontal" name="graphicForm" onsubmit="return(false);" method="post">
            <div class="control-group">
                <label class="control-label"><?php echo _('task::_common_:periodicite de la tache') ?></label>
                <div class="controls">
                    <input class="formElem input-small" type="text" name="period" />
                    <span class="help-inline"><?php echo _('task::_common_:secondes (unite temporelle)') ?></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"> <?php echo sprintf(_("Databox")) ?></label>
                <div class="controls">
                    <select name="sbas">
                        <option value=""><?php echo _("All"); ?></option>
                        <?php
foreach ($this->dependencyContainer['phraseanet.appbox']->get_databoxes() as $databox) {
    printf("<option value=\"%s\">%s (%s)</option>\n"
        , $databox->get_sbas_id()
        , p4string::MakeString($databox->get_viewname(), 'html')
        , $databox->get_sbas_id()
    );
}
                        ?>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"> <?php echo _("recordtype"); ?></label>
                <div class="controls">
                    <label class="checkbox inline">
                        <input type="checkbox" name="type_image" value="image"> <?php echo _("image"); ?>
                    </label>
                    <label class="checkbox inline">
                        <input type="checkbox" name="type_video" value="video"> <?php echo _("video"); ?>
                    </label>
                    <label class="checkbox inline">
                        <input type="checkbox" name="type_audio" value="audio"> <?php echo _("audio"); ?>
                    </label>
                    <label class="checkbox inline">
                        <input type="checkbox" name="type_document" value="document"> <?php echo _("document"); ?>
                    </label>
                    <label class="checkbox inline">
                        <input type="checkbox" name="type_flash" value="flash"> <?php echo _("flash"); ?>
                    </label>
                    <label class="checkbox inline">
                        <input type="checkbox" name="type_unknown" value="unknown"> <?php echo _("unknown"); ?>
                    </label>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"> <?php echo sprintf(_("Number of records to process per batch")) ?></label>
                <div class="controls">
                   <input class="formElem input-mini" type="text" name="flush" value="">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Restart the task every X records') ?></label>
                <div class="controls">
                    <input class="formElem input-mini" type="text" name="maxrecs" value="">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Restart the task if memory reaches') ?></label>
                <div class="controls">
                    <input class="formElem input-mini" type="text" name="maxmegs" value="">
                    <span class="help-inline">Mo</span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('Try to extract embedded thumbnails') ?></label>
                <div class="controls">
                    <input class="formElem input-mini" type="checkbox" name="embedded" value="1">
                </div>
            </div>
        </form>
        <?php

        return ob_get_clean();
    }

    public function retrieveSbasContent(databox $databox)
    {
        $sqltypes = "";
        $sqlqmark = array();
        $sqlparms = array();
        foreach($this->recordtype as $k=>$v) {
            if($v > 0) {
                $sqltypes .= ($sqltypes?',':'') . "'" . $k . "'";
                $sqlqmark[] = '?';
                $sqlparms[] = $k;
            }
        }
        $rs = array();
        if(count($sqlqmark) > 0)
        {
            Image2Image::$lookForEmbeddedPreview = $this->thumbnailExtraction;

            $connbas = $databox->get_connection();

            $sql = 'SELECT SQL_CALC_FOUND_ROWS coll_id, record_id'
                  . ' FROM record'
                  . ' WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0'
                  . ' AND type IN(' . implode(',', $sqlqmark) . ')'
                  . ' ORDER BY record_id DESC LIMIT 0, '.$this->maxrecs;
            $stmt = $connbas->prepare($sql);
            $stmt->execute($sqlparms);
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $sql = 'SELECT FOUND_ROWS()';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $this->_todo = (int) $stmt->fetchColumn(0);
            $stmt->closeCursor();

            $this->setProgress(0, $this->_todo);
        }
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
        if($this->_todo > 0) {
            $this->_todo--;
            $this->setProgress(0, $this->_todo);
        }

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

    /**
     * @param array $params
     */
    public static function getDefaultSettings(Configuration $config, array $params = array())
    {
        $period = isset($params['period']) ? $params['period'] : self::MINPERIOD;
        $flush = isset($params['flush']) ? $params['flush'] : self::MINFLUSH;
        $maxrecs = isset($params['maxrecs']) ? $params['maxrecs'] : self::MINRECS;
        $maxmegs = isset($params['maxmegs']) ? $params['maxmegs'] : self::DEFMEGS;

        return sprintf('<?xml version="1.0" encoding="UTF-8"?>
            <tasksettings>
                <period>%s</period>
                <flush>%s</flush>
                <maxrecs>%s</maxrecs>
                <maxmegs>%s</maxmegs>
                <embedded>0</embedded>
                <sbas></sbas>
            </tasksettings>',
            min(max($period, self::MINPERIOD), self::MAXPERIOD),
            min(max($flush, self::MINFLUSH), self::MAXFLUSH),
            min(max($maxrecs, self::MINRECS), self::MAXRECS),
            min(max($maxmegs, self::MINMEGS), self::MAXMEGS)
        );
    }
}
