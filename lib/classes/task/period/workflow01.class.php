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
class task_period_workflow01 extends task_databoxAbstract
{

    public function getName()
    {
        return(_('task::workflow01'));
    }

    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            "sbas_id"
            , "period"
            , 'status0'
            , 'coll0'
            , 'status1'
            , 'coll1'
        );
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if ($dom->loadXML($oldxml)) {
            $xmlchanged = false;
            // foreach($parm2 as $pname=>$pvalue)
            foreach (array(
            "str:sbas_id",
            "str:period",
            'str:status0',
            'str:coll0',
            'str:status1',
            'str:coll1',
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
                    $dom->documentElement->appendChild($dom->createTextNode("\t"));
                    $ns = $dom->documentElement->appendChild($dom->createElement($pname));
                    $dom->documentElement->appendChild($dom->createTextNode("\n"));
                }
                // on fixe sa valeur
                switch ($ptype) {
                    case "str":
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
            if ((int) ($sxml->period) < 1) {
                $sxml->period = 1;
            } elseif ((int) ($sxml->period) > 1440) { // 1 jour
                $sxml->period = 1440;
            }

            if ((string) ($sxml->delay) == '') {
                $sxml->delay = 0;
            }
            ?>
            <script type="text/javascript">
                var i;
                var opts;
                var pops = [
                    {'name':"sbas_id", 'val':"<?php echo p4string::MakeString($sxml->sbas_id, "js") ?>"},

                    {'name':"status0",  'val':"<?php echo p4string::MakeString($sxml->status0, "js") ?>"},
                    {'name':"coll0",    'val':"<?php echo p4string::MakeString($sxml->coll0, "js") ?>"},

                    {'name':"status1",  'val':"<?php echo p4string::MakeString($sxml->status1, "js") ?>"},
                    {'name':"coll1",    'val':"<?php echo p4string::MakeString($sxml->coll1, "js") ?>"}
                ];
                for (j in pops) {
                    for (opts=<?php echo $form ?>[pops[j].name].options, i=0; i<opts.length; i++) {
                        if (opts[i].value == pops[j].val) {
                            opts[i].selected = true;
                            break;
                        }
                    }
                    if(j==0)
                        parent.chgsbas(<?php echo $form ?>[pops[j].name]);
                }
            <?php echo $form ?>.period.value   = "<?php echo p4string::MakeString($sxml->period, "js", '"') ?>";
                parent.calccmd();
            </script>

            <?php

            return("");
        } else { // ... so we NEVER come here
            // bad xml
            return("BAD XML");
        }
    }

    public function printInterfaceHEAD()
    {
        ?>
        <style>
            OPTION.jsFilled
            {
                padding-left:10px;
                padding-right:20px;
            }
            #OUTOFDATETAB TD
            {
                text-align:center;
            }
        </style>
        <?php
    }

    public function printInterfaceJS()
    {
        ?>
        <script type="text/javascript">
            function calccmd()
            {
                var cmd = '';
                with(document.forms['graphicForm'])
                {
                    cmd += "";
                    if ((coll0.value||status0.value) && (coll1.value||status1.value)) {
                        cmd += "UPDATE record SET ";
                        u = "";
                        if(coll1.value)
                            u += (u?", ":"") + "coll_id=" + coll1.value;
                        if (status1.value) {
                            x = status1.value.split("_");
                            if(x[1]=="0")
                                u += (u?", ":"") + "status=status&~(1<<" + x[0] + ")";
                            else
                                u += (u?", ":"") + "status=status|(1<<" + x[0] + ")";
                        }
                        cmd += u;
                        w = "";
                        if(coll0.value)
                            w += (w?" AND ":"") + "coll_id=" + coll0.value;
                        if (status0.value) {
                            x = status0.value.split("_");
                            if(x[1]=="0")
                                w += (w?" AND ":"") + "(status>>" + x[0] + ")&1=0";
                            else
                                w += (w?" AND ":"") + "(status>>" + x[0] + ")&1=1";
                        }
                        cmd += " WHERE " + w;
                    }
                }
                document.getElementById('cmd').innerHTML = cmd;
            }

            function chgxmltxt(textinput, fieldname)
            {
                var limits = { 'period':{min:1, 'max':1440} , 'delay':{min:0} } ;
                if (typeof(limits[fieldname])!='undefined') {
                    var v = 0|textinput.value;
                    if(limits[fieldname].min && v < limits[fieldname].min)
                        v = limits[fieldname].min;
                    else if(limits[fieldname].max && v > limits[fieldname].max)
                        v = limits[fieldname].max;
                    textinput.value = v;
                }
                setDirty();
                calccmd();
            }

            function chgxmlck(checkinput, fieldname)
            {
                setDirty();
                calccmd();
            }

            function chgxmlpopup(popupinput, fieldname)
            {
                setDirty();
                calccmd();
            }

            function chgsbas(sbaspopup)
            {
                for (fld=0; fld<=1; fld++) {
                    var p = document.getElementById("status"+fld);
                    while( (f=p.firstChild) )
                        p.removeChild(f);
                    var o = p.appendChild(document.createElement('option'));
                    o.setAttribute('value', '');
                    o.appendChild(document.createTextNode("..."));

                    var p = document.getElementById("coll"+fld);
                    while( (f=p.firstChild) )
                        p.removeChild(f);
                    var o = p.appendChild(document.createElement('option'));
                    o.setAttribute('value', '');
                    o.appendChild(document.createTextNode("..."));
                }
                if (sbaspopup.value > 0) {
                    $.ajax({
                        url:"/admin/taskfacility.php"
                        , async:false
                        , data:{'cls':'workflow01', 'taskid':<?php echo $this->getID() ?>, 'bid':sbaspopup.value}
                        , success:function(data){
                            for (fld=0; fld<=1; fld++) {
                                var p = document.getElementById("status"+fld);
                                for (i in data.status_bits) {
                                    var o = p.appendChild(document.createElement('option'));
                                    o.setAttribute('value', data.status_bits[i].n + "_" + data.status_bits[i].value);
                                    o.appendChild(document.createTextNode(data.status_bits[i].label));
                                    o.setAttribute('class', "jsFilled");
                                }
                            }

                            for (fld=0; fld<=1; fld++) {
                                var p = document.getElementById("coll"+fld);
                                for (i in data.collections) {
                                    var o = p.appendChild(document.createElement('option'));
                                    o.setAttribute('value', ""+data.collections[i].id);
                                    o.appendChild(document.createTextNode(data.collections[i].name));
                                    o.setAttribute('class', "jsFilled");
                                }
                            }
                        }});
                }
                calccmd();
            }
        </script>
        <?php
    }

    public function getInterfaceHTML()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
        ob_start();
        ?>
        <form name="graphicForm" onsubmit="return(false);" method="post">
            <?php echo _('task::outofdate:Base') ?>&nbsp;:&nbsp;

            <select onchange="chgsbas(this);setDirty();" name="sbas_id">
                <option value="">...</option>
                <?php
                $sbas_ids = $user->ACL()->get_granted_sbas(array('bas_manage'));
                foreach ($sbas_ids as $databox) {
                    print('<option value="' . $databox->get_sbas_id() . '">' . p4string::MakeString($databox->get_viewname(), "form") . '</option>');
                }
                ?>
            </select>

            &nbsp;

            <br/>
            <br/>

            <?php echo _('task::_common_:periodicite de la tache') ?>&nbsp;:&nbsp;
            <input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">
            <?php echo _('task::_common_:minutes (unite temporelle)') ?><br/>
            <br/>

            <table id="OUTOFDATETAB" style="margin-right:10px; ">
                <tr>
                    <td style="white-space:nowrap;">
                        Collection&nbsp;:
                    </td>
                    <td>
                        <select name="coll0" id="coll0" onchange="chgxmlpopup(this, 'coll0');"></select>
                    </td>
                    <td rowspan="2">
                        &nbsp;&nbsp;====&gt;&nbsp;&nbsp;
                    </td>
                    <td>
                        <select name="coll1" id="coll1" onchange="chgxmlpopup(this, 'coll1');"></select>
                    </td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;">
                        Status&nbsp;:
                    </td>
                    <td>
                        <select name="status0" id="status0" onchange="chgxmlpopup(this, 'status0');"></select>
                    </td>
                    <td>
                        <select name="status1" id="status1" onchange="chgxmlpopup(this, 'status1');"></select>
                    </td>
                </tr>
            </table>
        </form>
        <br>
        <center>
            <div style="margin:10px; padding:5px; border:1px #000000 solid; font-family:monospace; font-size:16px; text-align:left; color:#00e000; background-color:#404040" id="cmd">cmd</div>
        </center>
        <?php

        return ob_get_clean();
    }

    public function help()
    {
        return(_("task::outofdate:deplacement de docs suivant valeurs de champs 'date'"));
    }
    protected $status_origine;
    protected $coll_origine;
    protected $status_destination;
    protected $coll_destination;

    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->status_origine = (string) $sx_task_settings->status0;
        $this->status_destination = (string) $sx_task_settings->status1;

        $this->coll_origine = (int) $sx_task_settings->coll0;
        $this->coll_destination = (int) $sx_task_settings->coll1;

        parent::loadSettings($sx_task_settings);

        $this->mono_sbas_id = (int) $sx_task_settings->sbas_id;
        // in minutes
        $this->period = (int) $sx_task_settings->period * 60;

        if ($this->period <= 0 || $this->period >= 24 * 60) {
            $this->period = 60;
        }
    }

    protected function retrieveSbasContent(databox $databox)
    {
        static $firstCall = true;

        $connbas = $databox->get_connection();

        $sql_s = $sql_w = '';
        $sql_parms = array();
        if ($this->coll_origine != '') {
            $sql_w .= ($sql_w ? ' AND ' : '') . '(coll_id=:coll_org)';
            $sql_parms[':coll_org'] = $this->coll_origine;
        }
        if ($this->status_origine != '') {
            $x = explode('_', $this->status_origine);
            if (count($x) !== 2) {
                throw new Exception('Error in settings for status origin');
            }
            $sql_w .= ($sql_w ? ' AND ' : '')
                . '((status >> :stat_org_n & 1) = :stat_org_v)';
            $sql_parms[':stat_org_n'] = $x[0];
            $sql_parms[':stat_org_v'] = $x[1];
        }
        if ($this->coll_destination != '') {
            $sql_s .= ($sql_s ? ', ' : '') . 'coll_id=:coll_dst';
            $sql_parms[':coll_dst'] = $this->coll_destination;
        }
        if ($this->status_destination != '') {
            $x = explode('_', $this->status_destination);
            if (count($x) !== 2) {
                throw new Exception('Error in settings for status destination');
            }
            $sql_s .= ($sql_s ? ', ' : '');
            if ((int) $x[1] === 0) {
                $sql_s .= 'status = status &~(1 << :stat_dst)';
            } else {
                $sql_s .= 'status = status |(1 << :stat_dst)';
            }
            $sql_parms[':stat_dst'] = (int) $x[0];
        }

        if ($sql_w && $sql_s) {
            $sql = 'UPDATE record SET ' . $sql_s . ' WHERE ' . $sql_w;
            $stmt = $connbas->prepare($sql);
            $stmt->execute($sql_parms);

            if ($firstCall || $stmt->rowCount() != 0) {
                $this->log(sprintf(("SQL=%s\n  (parms=%s)\n  - %s changes"), str_replace(array("\r\n", "\n", "\r", "\t"), " ", $sql)
                        , str_replace(array("\r\n", "\n", "\r", "\t"), " ", var_export($sql_parms, true))
                        , $stmt->rowCount()));
                $firstCall = false;
            }
            $stmt->closeCursor();
        }

        return array();
    }

    protected function processOneContent(databox $databox, Array $row)
    {
        return $this;
    }

    protected function flushRecordsSbas()
    {
        return $this;
    }

    protected function postProcessOneContent(databox $databox, Array $row)
    {
        return $this;
    }

    public function facility()
    {
        $request = http_request::getInstance();

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

        $parm = $request->get_parms("bid");

        phrasea::headers(200, true, 'text/json', 'UTF-8', false);

        $retjs = array('result'      => NULL,
            'date_fields' => array(),
            'status_bits' => array(),
            'collections' => array()
        );

        $sbas_id = (int) $parm['bid'];
        try {
            $databox = databox::get_instance($sbas_id);
            foreach ($databox->get_meta_structure() as $meta) {
                if ($meta->get_type() !== 'date') {
                    continue;
                }
                $retjs['date_fields'][] = $meta->get_name();
            }

            $status = $databox->get_statusbits();

            foreach ($status as $n => $s) {
                $retjs['status_bits'][] = array(
                    'n'                     => $n,
                    'value'                 => 0,
                    'label'                 => $s['labeloff'] ? $s['labeloff'] : 'non ' . $s['name']);
                $retjs['status_bits'][] = array(
                    'n'     => $n,
                    'value' => 1,
                    'label' => $s['labelon'] ? $s['labelon'] : $s['name']);
            }

            $base_ids = $user->ACL()->get_granted_base(array(), array($sbas_id));
            foreach ($base_ids as $collection) {
                $retjs['collections'][] = array('id'   => (string) ($collection->get_coll_id()), 'name' => $collection->get_name());
            }
        } catch (Exception $e) {

        }

        return p4string::jsonencode($retjs);
    }
}
