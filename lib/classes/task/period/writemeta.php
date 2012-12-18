<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPExiftool\Driver\Metadata;
use PHPExiftool\Driver\Value;
use PHPExiftool\Driver\Tag;
use PHPExiftool\Writer;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_writemeta extends task_databoxAbstract
{
    protected $clear_doc;
    protected $metasubdefs = array();

    public function help()
    {
        return(_("task::writemeta:(re)ecriture des metadatas dans les documents (et subdefs concernees)"));
    }

    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->clear_doc = p4field::isyes($sx_task_settings->cleardoc);
        parent::loadSettings($sx_task_settings);
    }

    public function getName()
    {
        return(_('task::writemeta:ecriture des metadatas'));
    }

    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            'period'
            , 'cleardoc'
            , 'maxrecs'
            , 'maxmegs'
        );
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if ($dom->loadXML($oldxml)) {
            $xmlchanged = false;
            foreach (array(
            'str:period'
            , 'str:maxrecs'
            , 'str:maxmegs'
            , 'boo:cleardoc'
            ) as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if (($ns = $dom->getElementsByTagName($pname)->item(0)) != NULL) {
                    // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                    while (($n = $ns->firstChild)) {
                        $ns->removeChild($n);
                    }
                } else {
                    // le champ n'existait pas dans le xml, on le cree
                    $ns = $dom->documentElement->appendChild($dom->createElement($pname));
                }
                // on fixe sa valeur
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

    public function xml2graphic($xml, $form)
    {
        if (false !== $sxml = simplexml_load_string($xml)) {

            if ((int) ($sxml->period) < self::MINPERIOD) {
                $sxml->period = self::MINPERIOD;
            } elseif ((int) ($sxml->period) > self::MAXPERIOD) {
                $sxml->period = self::MAXPERIOD;
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
            <?php echo $form ?>.period.value        = "<?php echo p4string::MakeString($sxml->period, "js", '"') ?>";
            <?php echo $form ?>.cleardoc.checked    = <?php echo p4field::isyes($sxml->cleardoc) ? "true" : 'false' ?>;
            <?php echo $form ?>.maxrecs.value       = "<?php echo p4string::MakeString($sxml->maxrecs, "js", '"') ?>";
            <?php echo $form ?>.maxmegs.value       = "<?php echo p4string::MakeString($sxml->maxmegs, "js", '"') ?>";
            </script>
            <?php
            return("");
        } else { // ... so we NEVER come here
            // bad xml
            return("BAD XML");
        }
    }

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
                        period.value     = xml.find("period").text();
                        cleardoc.checked = Number(xml.find("cleardoc").text()) > 0;
                        maxrecs.value    = xml.find("maxrecs").text();
                        maxmegs.value    = xml.find("maxmegs").text();
                    }
                }
            }

            $(document).ready(function(){
                var limits = {
                    'period':{'min':<?php echo self::MINPERIOD; ?>, 'max':<?php echo self::MAXPERIOD; ?>},
                    'maxrecs':{'min':<?php echo self::MINRECS; ?>, 'max':<?php echo self::MAXRECS; ?>},
                    'maxmegs':{'min':<?php echo self::MINMEGS; ?>, 'max':<?php echo self::MAXMEGS; ?>}
                } ;
                $(".formElem").change(function(){
                    fieldname = $(this).attr("name");
                    switch((this.nodeName+$(this).attr("type")).toLowerCase())
                    {
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

    public function getInterfaceHTML()
    {
        $sbas_ids = $this->dependencyContainer['phraseanet.user']->ACL()->get_granted_sbas(array('bas_manage'));

        ob_start();
        if (count($sbas_ids) > 0) {
            ?>
            <form name="graphicForm" onsubmit="return(false);" method="post">
                <br/>
                <?php echo _('task::_common_:periodicite de la tache') ?>&nbsp;:&nbsp;
                <input class="formElem" type="text" name="period" style="width:40px;" value="">
                <?php echo _('task::_common_:secondes (unite temporelle)') ?>
                <br/>
                <br/>
                <input class="formElem" type="checkbox" name="cleardoc">
                <?php echo _('task::writemeta:effacer les metadatas non presentes dans la structure') ?>
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
        }

        return ob_get_clean();
    }

    protected function retrieveSbasContent(databox $databox)
    {
        $connbas = $databox->get_connection();
        $subdefgroups = $databox->get_subdef_structure();
        $metasubdefs = array();

        foreach ($subdefgroups as $type => $subdefs) {
            foreach ($subdefs as $sub) {
                $name = $sub->get_name();
                if ($sub->meta_writeable()) {
                    $metasubdefs[$name . '_' . $type] = true;
                }
            }
        }

        $this->metasubdefs = $metasubdefs;

        $sql = 'SELECT record_id, coll_id, jeton
             FROM record WHERE (jeton & ' . JETON_WRITE_META . ' > 0)';

        $stmt = $connbas->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $rs;
    }

    protected function processOneContent(databox $databox, Array $row)
    {
        $record_id = $row['record_id'];
        $jeton = $row['jeton'];

        $record = new record_adapter($this->dependencyContainer, $this->sbas_id, $record_id);

        $type = $record->get_type();
        $subdefs = $record->get_subdefs();

        $tsub = array();

        foreach ($subdefs as $name => $subdef) {
            $write_document = (($jeton & JETON_WRITE_META_DOC) && $name == 'document');
            $write_subdef = (($jeton & JETON_WRITE_META_SUBDEF) && isset($this->metasubdefs[$name . '_' . $type]));

            if (($write_document || $write_subdef) && $subdef->is_physically_present()) {
                $tsub[$name] = $subdef->get_pathfile();
            }
        }

        $metadatas = new Metadata\MetadataBag();

        if ($record->get_uuid()) {
            $metadatas->add(
                new Metadata\Metadata(
                    new Tag\XMPExif\ImageUniqueID(),
                    new Value\Mono($record->get_uuid())
                )
            );
            $metadatas->add(
                new Metadata\Metadata(
                    new Tag\ExifIFD\ImageUniqueID(),
                    new Value\Mono($record->get_uuid())
                )
            );
            $metadatas->add(
                new Metadata\Metadata(
                    new Tag\IPTC\UniqueDocumentID(),
                    new Value\Mono($record->get_uuid())
                )
            );
        }

        foreach ($record->get_caption()->get_fields() as $field) {

            $meta = $field->get_databox_field();
            /* @var $meta \databox_field */

            $datas = $field->get_values();

            if ($meta->is_multi()) {
                $values = array();
                foreach ($datas as $data) {
                    $values[] = $data->getValue();
                }

                $value = new Value\Multi($values);
            } else {
                $data = array_pop($datas);
                $value = $data->getValue();

                $value = new Value\Mono($value);
            }

            $metadatas->add(
                new Metadata\Metadata($meta->get_tag(), $value)
            );
        }

        $writer = new Writer();
        $writer->setModule(Writer::MODULE_MWG, true);

        foreach ($tsub as $name => $file) {

            $writer->erase($name != 'document' || $this->clear_doc);

            try {
                $writer->write($file, $metadatas);

                $this->log(sprintf('meta written for sbasid=%1$d - recordid=%2$d (%3$s)', $this->sbas_id, $record_id, $name), self::LOG_INFO);
            } catch (\PHPExiftool\Exception\Exception $e) {
                $this->log(sprintf('meta NOT written for sbasid=%1$d - recordid=%2$d (%3$s) because "%s"', $this->sbas_id, $record_id, $name, $e->getMessage()), self::LOG_ERROR);
            }
        }

        $writer = $metadatas = null;

        return $this;
    }

    protected function flushRecordsSbas()
    {
        return $this;
    }

    protected function postProcessOneContent(databox $databox, Array $row)
    {
        $connbas = $databox->get_connection();

        $sql = 'UPDATE record SET jeton=jeton & ~' . JETON_WRITE_META . '
            WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $row['record_id']));
        $stmt->closeCursor();

        return $this;
    }
}

