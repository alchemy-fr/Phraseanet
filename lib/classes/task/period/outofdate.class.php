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
class task_period_outofdate extends task_abstract
{

    // ====================================================================
    // getName : must return the name for this kind of task
    // MANDATORY
    // ====================================================================
    public function getName()
    {
        return(_('Documents perimes'));
    }

    // ====================================================================
    // graphic2xml : must return the xml (text) version of the form
    // ====================================================================
    public function graphic2xml($oldxml)
    {
//    global $parm;
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            "sbas_id"
            , "period"
            , 'field1'
            , 'fieldDs1'
            , 'fieldDv1'
            , 'field2'
            , 'fieldDs2'
            , 'fieldDv2'
            , 'status0'
            , 'coll0'
            , 'status1'
            , 'coll1'
            , 'status2'
            , 'coll2'
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
            'str:field1',
            'str:fieldDs1',
            'str:fieldDv1',
            'str:field2',
            'str:fieldDs2',
            'str:fieldDv2',
            'str:status0',
            'str:coll0',
            'str:status1',
            'str:coll1',
            'str:status2',
            'str:coll2'
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

    // ====================================================================
    // xml2graphic : must fill the graphic form (using js) from xml
    // ====================================================================
    public function xml2graphic($xml, $form)
    {
        if (false !== $sxml = simplexml_load_string($xml)) {
            if ((int) ($sxml->period) < 10) {
                $sxml->period = 10;
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

                    {'name':"field1",  'val':"<?php echo p4string::MakeString($sxml->field1, "js") ?>"},
                    {'name':"field2",  'val':"<?php echo p4string::MakeString($sxml->field2, "js") ?>"},
                    {'name':"fieldDs1",  'val':"<?php echo p4string::MakeString($sxml->fieldDs1, "js") ?>"},
                    {'name':"fieldDs2",  'val':"<?php echo p4string::MakeString($sxml->fieldDs2, "js") ?>"},

                    {'name':"status0",  'val':"<?php echo p4string::MakeString($sxml->status0, "js") ?>"},
                    {'name':"coll0",    'val':"<?php echo p4string::MakeString($sxml->coll0, "js") ?>"},

                    {'name':"status1",  'val':"<?php echo p4string::MakeString($sxml->status1, "js") ?>"},
                    {'name':"coll1",    'val':"<?php echo p4string::MakeString($sxml->coll1, "js") ?>"},

                    {'name':"status2",  'val':"<?php echo p4string::MakeString($sxml->status2, "js") ?>"},
                    {'name':"coll2",    'val':"<?php echo p4string::MakeString($sxml->coll2, "js") ?>"}
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
            <?php echo $form ?>.fieldDv1.value = "<?php echo p4string::MakeString($sxml->fieldDv1, "js", '"') ?>";
            <?php echo $form ?>.fieldDv2.value = "<?php echo p4string::MakeString($sxml->fieldDv2, "js", '"') ?>";
                parent.calcSQL();
            </script>
            <?php

            return("");
        } else { // ... so we NEVER come here
            // bad xml
            return("BAD XML");
        }
    }

    // ====================================================================
    // printInterfaceHEAD() :
    // ====================================================================
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
            .sqlcmd
            {
                font-family:monospace;
                font-size:12px;
                text-align:left; color:#00e000;
            }
            .sqlparams
            {
                font-family:monospace;
                font-size:12px;
                text-align:left; color:#00e0e0;
            }
        </style>
        <?php
    }

    // ====================================================================
    // printInterfaceJS() : generer le code js de l'interface 'graphic view'
    // ====================================================================
    public function printInterfaceJS()
    {
        ?>
        <script type="text/javascript">

            (function( $ ){
                $.fn.serializeJSON=function() {
                    var json = {};
                    jQuery.map($(this).serializeArray(), function(n, i){
                        json[n['name']] = n['value'];
                    });

                    return json;
                };
            })( jQuery );


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
                calcSQL();
            }

            function chgxmlck(checkinput, fieldname)
            {
                setDirty();
                calcSQL();
            }

            function chgxmlpopup(popupinput, fieldname)
            {
                setDirty();
                calcSQL();
            }

            function calcSQL()
            {
                var data = $("form[name='graphicForm']").serializeJSON();
                data["taskid"] = <?php echo $this->getID(); ?>;
                data["ACT"] = "CALCSQL";
                data["cls"]="outofdate";
                $.ajax({ url: "/admin/taskfacility.php"
                    , data: data
                    , dataType:'json'
                    , type:"POST"
                    , async:false
                    , success:function(data) {
                        var s = "";
                        for (i in data) {
                            s += (s?"<br/>\n":"");
                            s += "<div class=\"sqlcmd\">" + (data[i]["sql"]+'<br/>\n') + "</div>\n";
                            var ptxt = "";
                            for(p in data[i]["params"])
                                ptxt += (ptxt?", ":"") + p + ':' + data[i]["params"][p];
                            s += "<div class=\"sqlparams\">params:{" + ptxt + "}</div>\n";
                        }
                        $("#cmd").html(s);
                    }
                });
            }

            function chgsbas(sbaspopup)
            {
                var data = {taskid:<?php echo $this->getID(); ?>, bid: sbaspopup.value};
                data["ACT"] = "GETBASE";
                data["cls"]="outofdate";
                $.ajax({ url: "/admin/taskfacility.php"
                    , data: data
                    , dataType:'json'
                    , type:"POST"
                    , async:false
                    , success:function(data) {
                        var html = "<option value=\"\">...</option>";
                        for(i in data.date_fields)
                            html += "\n<option class=\"jsFilled\" value=\"" + data.date_fields[i] + "\">" + data.date_fields[i] + "</option>";
                        for(fld=1; fld<=2; fld++)
                            $("#field"+fld).html(html);

                        var html = "<option value=\"\">...</option>";
                        for(i in data.collections)
                            html += "\n<option class=\"jsFilled\" value=\"" + data.collections[i].id + "\">" + data.collections[i].name + "</option>";
                        for(fld=0; fld<=2; fld++)
                            $("#coll"+fld).html(html);

                        var html = "<option value=\"\">...</option>";
                        for(i in data.status_bits)
                            html += "\n<option class=\"jsFilled\" value=\"" + data.status_bits[i].n+"_"+data.status_bits[i].value + "\">" + data.status_bits[i].label + "</option>";
                        for(fld=0; fld<=2; fld++)
                            $("#status"+fld).html(html);
                    }
                });

                return;
            }

        </script>
        <?php
    }

    // ====================================================================
    // getInterfaceHTML(..) : retourner l'interface 'graphic view' !! EN UTF-8 !!
    // ====================================================================
    public function getInterfaceHTML()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
        ob_start();

        $sbas_list = $user->ACL()->get_granted_sbas(array('bas_manage'));
        ?>
        <form name="graphicForm" onsubmit="return(false);" method="post">
            <?php echo _('task::outofdate:Base') ?>&nbsp;:&nbsp;

            <select onchange="chgsbas(this);setDirty();" name="sbas_id">
                <option value="">...</option>
                <?php
                foreach ($sbas_list as $databox) {
                    $selected = '';
                    print("\t\t\t\t<option value=\"" . $databox->get_sbas_id() . "\" $selected>" . p4string::MakeString($databox->get_viewname(), "form") . "</option>\n");
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
                    <td>
                        &nbsp;
                    </td>
                    <td style="width:20%;">
                        <?php echo _('task::outofdate:before') ?>&nbsp;
                    </td>
                    <td colspan="2" style="width:20%; white-space:nowrap;">
                        <select style="width:100px" name="field1" id="field1" onchange="chgxmlpopup(this, 'field1');"></select>
                        <br/>
                        <select name="fieldDs1" id="fieldDs1" onchange="chgxmlpopup(this, 'fieldDs1');">
                            <option value="+">+</option>
                            <option value="-">-</option>
                        </select>
                        <input name="fieldDv1" id="fieldDv1" onchange="chgxmltxt(this, 'fieldDv1');" type="text" style="width:30px" value="0"></input>&nbsp;<?php echo _('admin::taskoutofdate: days ') ?>
                    </td>
                    <td style="width:20%; padding-left:20px; padding-right:20px;">
                        <?php echo _('task::outofdate:between') ?>&nbsp;
                    </td>
                    <td colspan="2" style="width:20%; white-space:nowrap;">
                        <select style="width:100px" name="field2" id="field2" onchange="chgxmlpopup(this, 'field2');"></select>
                        <br/>
                        <select name="fieldDs2" id="fieldDs2" onchange="chgxmlpopup(this, 'fieldDs2');">
                            <option value="+">+</option>
                            <option value="-">-</option>
                        </select>
                        <input name="fieldDv2" id="fieldDv2" onchange="chgxmltxt(this, 'fieldDv2');" type="text" style="width:30px" value="0"></input>&nbsp;<?php echo _('admin::taskoutofdate: days ') ?>
                    </td>
                    <td  style="width:20%;">
                        <?php echo _('task::outofdate:after') ?>&nbsp;
                    </td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;">
                        <?php echo _('task::outofdate:coll.') ?>&nbsp;:
                    </td>
                    <td colspan="2" style="border-right:1px solid #000000">
                        <select name="coll0" id="coll0" onchange="chgxmlpopup(this, 'coll0');"></select>
                    </td>
                    <td colspan="3" style="border-right:1px solid #000000">
                        <select name="coll1" id="coll1" onchange="chgxmlpopup(this, 'coll1');"></select>
                    </td>
                    <td colspan="2">
                        <select name="coll2" id="coll2" onchange="chgxmlpopup(this, 'coll2');"></select>
                    </td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;">
                        <?php echo _('task::outofdate:status') ?>&nbsp;:<br/>
                    </td>
                    <td colspan="2" style="border-right:1px solid #000000">
                        <select name="status0" id="status0" onchange="chgxmlpopup(this, 'status0');"></select>
                    </td>
                    <td colspan="3" style="border-right:1px solid #000000">
                        <select name="status1" id="status1" onchange="chgxmlpopup(this, 'status1');"></select>
                    </td>
                    <td colspan="2">
                        <select name="status2" id="status2" onchange="chgxmlpopup(this, 'status2');"></select>
                    </td>
                </tr>
            </table>
        </form>
        <br/>
        <center>
            <div style="margin:10px; padding:5px; border:1px #000000 solid; background-color:#404040" id="cmd">cmd</div>
        </center>
        <?php

        return ob_get_clean();
    }
    // ====================================================================
    // $argt : command line args specifics to this task (optional)
    // ====================================================================
    public $argt = array(
        //    "--truc" => array("set"=>false, "values"=>array(), "usage"=>" : usage du truc")
    );

    // ======================================================================================================
    // ===== help() : text displayed if --help (optional)
    // ======================================================================================================
    public function help()
    {
        return(_("task::outofdate:deplacement de docs suivant valeurs de champs 'date'"));
    }
    // ======================================================================================================
    // ===== run() : le code d'execution de la tache proprement dite
    // ======================================================================================================

    protected $sxTaskSettings = null; // les settings de la tache en simplexml
    private $connbas = null;  // cnx a la base
    private $msg = "";
    private $sbas_id;

    protected function run2()
    {
        $ret = '';
        $conn = connection::getPDOConnection();

        $this->sxTaskSettings = simplexml_load_string($this->settings);

        $this->sbas_id = (int) ($this->sxTaskSettings->sbas_id);

        $this->connbas = connection::getPDOConnection($this->sbas_id);

        $this->running = true;
        $this->tmask = array();
        $this->tmaskgrp = array();
        $this->period = 60;


        // ici la tache tourne tant qu'elle est active
        $loop = 0;
        while ($this->running) {
            if ( ! $conn->ping()) {
                $this->log(("Warning : abox connection lost, restarting in 10 min."));
                for ($i = 0; $i < 60 * 10; $i ++ ) {
                    sleep(1);
                }
                $this->running = false;

                return(self::STATUS_TORESTART);
            }

            try {
                $connbas = connection::getPDOConnection($this->sbas_id);
                if ( ! $connbas->ping()) {
                    throw new Exception('Mysql has gone away');
                }
            } catch (Exception $e) {
                $this->log(("dbox connection lost, restarting in 10 min."));
                for ($i = 0; $i < 60 * 10; $i ++ ) {
                    sleep(1);
                }
                $this->running = false;

                return(self::STATUS_TORESTART);
            }

            $this->setLastExecTime();

            $sql = "SELECT * FROM task2 WHERE task_id = :task_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':task_id' => $this->getID()));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($row) {
                if ($row['status'] == 'tostop') {
                    $ret = self::STATUS_STOPPED;
                    $this->running = false;
                } else {
                    if (false !== $this->sxTaskSettings = simplexml_load_string($row['settings'])) {
                        $period = (int) ($this->sxTaskSettings->period);
                        if ($period <= 0 || $period >= 24 * 60) {
                            $period = 60;
                        }
                    } else {
                        $period = 60;
                    }
                    $this->connbas = connection::getPDOConnection($this->sbas_id);

                    $duration = time();

                    $r = $this->doRecords();

                    switch ($r) {
                        case 'WAIT':
                            $this->setState(self::STATE_STOPPED);
                            $this->running = false;
                            break;
                        case 'BAD':
                            $this->setState(self::STATE_STOPPED);
                            $this->running = false;
                            break;
                        case 'NORECSTODO':
                            $duration = time() - $duration;
                            if ($duration < $period) {
                                sleep($period - $duration);
                                $conn = connection::getPDOConnection();
                            }
                            break;
                        case 'MAXRECSDONE':
                        case 'MAXMEMORY':
                        case 'MAXLOOP':
                            if ($row['status'] == self::STATE_STARTED && $this->getRunner() !== self::RUNNER_MANUAL) {
                                $this->setState(self::STATE_TORESTART);
                                $this->running = false;
                            }
                            break;
                        default:
                            if ($row['status'] == self::STATE_STARTED) {
                                $this->setState(self::STATE_STOPPED);
                                $this->running = false;
                            }
                            break;
                    }
                }
            } else {
                $this->setState(self::STATE_STOPPED);
                $this->running = false;
            }
            $loop ++;
        }
    }

    public function doRecords()
    {
        $ndone = 0;
        $ret = 'NORECSTODO';

        $tsql = $this->calcSQL($this->sxTaskSettings);

        $nchanged = 0;
        foreach ($tsql as $xsql) {
            try {
                $stmt = $this->connbas->prepare($xsql['sql']);
                if ($stmt->execute($xsql['params'])) {
                    $n = $stmt->rowCount();
                    $stmt->closeCursor();

                    $nchanged += $n;
                    if ($n > 0) {
                        $this->log(sprintf("SQL='%s' ; parms=%s - %s changes", $xsql['sql'], var_export($xsql['params']), $n));
                    }
                } else {
                    $this->log(sprintf("ERROR SQL='%s' ; parms=%s", $xsql['sql'], var_export($xsql['params'], true)));
                }
            } catch (ErrorException $e) {
                $this->log(sprintf("ERROR SQL='%s' ; parms=%s", $xsql['sql'], var_export($xsql['params'], true)));
            }
        }

        $ret = ($nchanged > 0 ? $nchanged : 'NORECSTODO');

        return($ret);
    }

    private function calcSQL($sxTaskSettings)
    {
        $ret = array();

        $this->sxTaskSettings = $sxTaskSettings;

        $date1 = $date2 = NULL;
        $field1 = $field2 = '';

        // test : DATE 1
        if (($field1 = trim($this->sxTaskSettings->field1)) != '') {
            $date1 = time();
            if (($delta = (int) ($this->sxTaskSettings->fieldDv1)) > 0) {
                if ($this->sxTaskSettings->fieldDs1 == '-') {
                    $date1 += 86400 * $delta;
                } else {
                    $date1 -= 86400 * $delta;
                }
            }
            $date1 = date("YmdHis", $date1);
        }
        // test : DATE 2
        if (($field2 = trim($this->sxTaskSettings->field2)) != '') {
            $date2 = time();
            if (($delta = (int) ($this->sxTaskSettings->fieldDv2)) > 0) {
                if ($this->sxTaskSettings->fieldDs2 == '-') {
                    $date2 += 86400 * $delta;
                } else {
                    $date2 -= 86400 * $delta;
                }
            }
            $date2 = date("YmdHis", $date2);
        }

        $sqlset = $params = $tmp_params = array();
        $sqlwhere = array();
        for ($i = 0; $i <= 2; $i ++ ) {
            $sqlwhere[$i] = '';
            $sqlset[$i] = '';
            $x = 'status' . $i;
            @list($tostat, $statval) = explode('_', (string) ($this->sxTaskSettings->{$x}));
            if ($tostat >= 4 && $tostat <= 63) {
                if ($statval == '0') {
                    $sqlset[$i] = 'status=status & ~(1<<' . $tostat . ')';
                    $sqlwhere[$i] .= '(status & (1<<' . $tostat . ') = 0)';
                } elseif ($statval == '1') {
                    $sqlset[$i] = 'status=status|(1<<' . $tostat . ')';
                    $sqlwhere[$i] .= '(status & (1<<' . $tostat . ') != 0)';
                }
            }
            $x = 'coll' . $i;
            if (($tocoll = (string) ($this->sxTaskSettings->{$x})) != '') {
                $sqlset[$i] .= ( $sqlset[$i] ? ', ' : '') . ('coll_id = :coll_id_set' . $i);
                $sqlwhere[$i] .= ( $sqlwhere[$i] ? ' AND ' : '') . '(coll_id = :coll_id_where' . $i . ')';
                $tmp_params[':coll_id_set' . $i] = $tocoll;
                $tmp_params[':coll_id_where' . $i] = $tocoll;
            }
        }

        if ($date1 && $sqlset[0]) {
            $params = array();
            $params[':name1'] = $field1;
            $params[':date1'] = $date1;
            $params[':coll_id_set0'] = $tmp_params[':coll_id_set0'];

            $w = 'p1.name = :name1 AND :date1 <= p1.value';
            if ($sqlwhere[1] && $sqlwhere[2]) {
                $w .= ' AND ((' . $sqlwhere[1] . ') OR (' . $sqlwhere[2] . '))';
                $params[':coll_id_where1'] = $tmp_params[':coll_id_where1'];
                $params[':coll_id_where2'] = $tmp_params[':coll_id_where2'];
            } else {
                if ($sqlwhere[1]) {
                    $w .= ' AND ' . $sqlwhere[1];
                    $params[':coll_id_where1'] = $tmp_params[':coll_id_where1'];
                } elseif ($sqlwhere[2]) {
                    $w .= ' AND ' . $sqlwhere[2];
                    $params[':coll_id_where2'] = $tmp_params[':coll_id_where2'];
                }
            }

            $sql = "UPDATE prop AS p1 INNER JOIN record USING(record_id)"
                . " SET " . $sqlset[0]
                . " WHERE " . $w;

            $ret[] = array('sql'    => $sql, 'params' => $params);
        }

        if ($date1 && $date2) {
            $params = array();
            $params[':name1'] = $field1;
            $params[':name2'] = $field2;
            $params[':date1'] = $date1;
            $params[':date2'] = $date2;
            $params[':coll_id_set1'] = $tmp_params[':coll_id_set1'];

            $w = 'p1.name = :name1 AND p2.name = :name2 AND :date1 > p1.value AND :date2 <= p2.value';
            if ($sqlwhere[0] && $sqlwhere[2]) {
                $w .= ' AND ((' . $sqlwhere[0] . ') OR (' . $sqlwhere[2] . '))';
                $params[':coll_id_where0'] = $tmp_params[':coll_id_where0'];
                $params[':coll_id_where2'] = $tmp_params[':coll_id_where2'];
            } else {
                if ($sqlwhere[0]) {
                    $w .= ' AND ' . $sqlwhere[0];
                    $params[':coll_id_where0'] = $tmp_params[':coll_id_where0'];
                } elseif ($sqlwhere[2]) {
                    $w .= ' AND ' . $sqlwhere[2];
                    $params[':coll_id_where2'] = $tmp_params[':coll_id_where2'];
                }
            }

            $sql = "UPDATE (prop AS p1 INNER JOIN prop AS p2 USING(record_id))"
                . " INNER JOIN record USING(record_id)"
                . " SET " . $sqlset[1]
                . " WHERE " . $w;

            $ret[] = array('sql'    => $sql, 'params' => $params);
        }

        if ($date2 && $sqlset[2]) {
            $params = array();
            $params[':name2'] = $field2;
            $params[':date2'] = $date2;
            $params[':coll_id_set2'] = $tmp_params[':coll_id_set2'];

            $w = 'p2.name = :name2 AND :date2 > p2.value';
            if ($sqlwhere[0] && $sqlwhere[1]) {
                $w .= ' AND ((' . $sqlwhere[0] . ') OR (' . $sqlwhere[1] . '))';
                $params[':coll_id_where0'] = $tmp_params[':coll_id_where0'];
                $params[':coll_id_where1'] = $tmp_params[':coll_id_where1'];
            } else {
                if ($sqlwhere[0]) {
                    $w .= ' AND ' . $sqlwhere[0];
                    $params[':coll_id_where0'] = $tmp_params[':coll_id_where0'];
                } elseif ($sqlwhere[1]) {
                    $w .= ' AND ' . $sqlwhere[1];
                    $params[':coll_id_where1'] = $tmp_params[':coll_id_where1'];
                }
            }

            $sql = "UPDATE prop AS p2 INNER JOIN record USING(record_id)"
                . " SET " . $sqlset[2]
                . " WHERE " . $w;

            $ret[] = array('sql'    => $sql, 'params' => $params);
        }

        return($ret);
    }

    public function facility()
    {
        $ret = NULL;

        $request = http_request::getInstance();
        $parm2 = $request->get_parms(
            'ACT', 'bid'
        );

        phrasea::headers(200, true, 'application/json', 'UTF-8', false);
        $ret = NULL;
        switch ($parm2['ACT']) {
            case 'CALCSQL':
                $xml = $this->graphic2xml('<?xml version="1.0" encoding="UTF-8"?><tasksettings/>');
                $sxml = simplexml_load_string($xml);
                $ret = $this->calcSQL($sxml);
                break;
            case 'GETBASE':
                $ret = array('date_fields' => array(), 'status_bits' => array(), 'collections' => array());

                if ($parm2['bid'] != '') {
                    $sbas_id = (int) $parm2['bid'];
                    try {
                        $databox = databox::get_instance($sbas_id);
                        $meta_struct = $databox->get_meta_structure();

                        foreach ($meta_struct as $meta) {
                            if (mb_strtolower($meta->get_type()) == 'date') {
                                $ret['date_fields'][] = $meta->get_name();
                            }
                        }

                        $status = $databox->get_statusbits();
                        foreach ($status as $n => $stat) {
                            $labelon = $stat['labelon'] ? $stat['labelon'] : ($n . '-ON');
                            $labeloff = $stat['labeloff'] ? $stat['labeloff'] : ($n . '-OFF');
                            $ret['status_bits'][] = array('n'                   => $n, 'value'               => 0, 'label'               => $labeloff);
                            $ret['status_bits'][] = array('n'     => $n, 'value' => 1, 'label' => $labelon);
                        }

                        foreach ($databox->get_collections() as $collection) {
                            $ret['collections'][] = array('id'   => $collection->get_coll_id(), 'name' => $collection->get_name());
                        }
                    } catch (Exception $e) {

                    }
                }
                break;
        }
        print(json_encode($ret));
    }
}
