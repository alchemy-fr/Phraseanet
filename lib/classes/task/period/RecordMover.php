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
class task_period_RecordMover extends task_appboxAbstract
{

    /**
     *
     * @return string
     */
    public function getName()
    {
        return _("Record Mover");
    }

    /**
     *
     * @return string
     */
    public function help()
    {
        return '';
    }

    /**
     * return the xml (text) version of the form filled by the gui
     *
     * @param  string $oldxml
     * @return string
     */
    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            'period'
            , 'logsql'
        );
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if ($dom->loadXML($oldxml)) {
            $xmlchanged = false;
            foreach (array(
            'str:period'
            , 'boo:logsql'
            ) as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if (($ns = $dom->getElementsByTagName($pname)->item(0))) {
                    // field did exists, remove thevalue
                    while (($n = $ns->firstChild))
                        $ns->removeChild($n);
                } else {
                    // field did not exists, create it
                    $ns = $dom->documentElement->appendChild($dom->createElement($pname));
                }
                // set the value
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

        return $dom->saveXML();
    }

    /**
     * PRINT head for the gui
     */
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
            DIV.terminal
            {
                margin:5px;
                border:1px #000000 solid;
                font-family:monospace;
                font-size:13px;
                text-align:left;
                color:#00FF00;
                background-color:#182018
            }
            DIV.terminal DIV.title
            {
                color:#303830;
                background-color:#00C000;
                padding:2px;
            }
            DIV.terminal DIV.sql
            {
                padding:5px;
            }
            DIV.terminal DIV.sqltest
            {
                padding-left:45px;
                padding-right:25px;
            }
            SPAN.active
            {
                font-weight: bold;
                background-color: #000000;
                color:#00FF00;
            }
            SPAN.notactive
            {
                font-weight: bold;
                background-color: #000000;
                color:#FF0000;
            }

        </style>
        <?php
    }

    /**
     *  PRINT js of the gui
     */
    public function printInterfaceJS()
    {
        ?>
        <script type="text/javascript">
            function taskFillGraphic_<?php echo(get_class($this));?>(xml)
            {
                $("#sqlu").text("");
                $("#sqls").text("");
                if(xml)
                {
                    xml2 = $.parseXML(xml);
                    xml2 = $(xml2);

                    with(document.forms['graphicForm'])
                    {
                        period.value  = xml2.find("period").text();
                        logsql.checked   = Number(xml2.find("logsql").text()) > 0;
//                        maxrecs.value = xml.find("maxrecs").text();
//                        maxmegs.value = xml.find("maxmegs").text();
                    }

                    var data = {};
                    data["ACT"] = "CALCTEST";
                    data["taskid"]=<?php echo $this->getID(); ?>;
                    data["cls"]="RecordMover";
                    data["xml"] = xml;
                    $.ajax({ url: "/admin/task-manager/task/<?php echo $this->getID(); ?>/facility/"
                        , data: data
                        , dataType:'json'
                        , type:"POST"
                        , async:true
                        , success:function(data) {
                            t = "";
                            for (i in data.tasks) {
                                t += "<div class=\"title\">&nbsp;";
                                if(data.tasks[i].active)
                                    t += "<span class=\"active\">&nbsp;X&nbsp;</span>&nbsp;";
                                else
                                    t += "<span class=\"notactive\">&nbsp;X&nbsp;</span>&nbsp;";
                                if(data.tasks[i].name_htmlencoded)
                                    t += "<b>" + data.tasks[i].name_htmlencoded + "</b>";
                                else
                                    t += "<b><i>sans nom</i></b>";

                                if(data.tasks[i].basename_htmlencoded)
                                    t += " (action=" + data.tasks[i].action + ' on ' +  data.tasks[i].basename_htmlencoded + ')';
                                else
                                    t += " (action=" + data.tasks[i].action + ' on <i>Unknown</i>)';
                                t += "</div>";

                                if(data.tasks[i].err_htmlencoded) ;
                                t += "<div class=\"err\">" + data.tasks[i].err_htmlencoded + "</div>";

                                t += "<div class=\"sql\">";

                                if(data.tasks[i].sql && data.tasks[i].sql.test.sql_htmlencoded)
                                    t += "<div class=\"sqltest\">" + data.tasks[i].sql.test.sql_htmlencoded + "</div>";
                                t += "--&gt; <span id=\"SQLRET"+i+"\"><i>wait...</i></span><br/>";

                                t += "</div>";
                            }
                            $("#sqla").html(t);

                            var data = {};
                            data["ACT"] = "PLAYTEST";
                            data["taskid"]=<?php echo $this->getID(); ?>;
                            data["cls"]="RecordMover";
                            data["xml"] = xml;
                            $.ajax({ url: "/admin/task-manager/task/<?php echo $this->getID(); ?>/facility/"
                                , data: data
                                , dataType:'json'
                                , type:"POST"
                                , async:true
                                , success:function(data) {
                                    for (i in data.tasks) {
                                        if (data.tasks[i].sql) {
                                            if (data.tasks[i].sql.test.err) {
                                                $("#SQLRET"+i).html("err: " + data.tasks[i].sql.test.err);
                                            } else {
                                                t = '';
                                                for(j in data.tasks[i].sql.test.result.rids)
                                                    t += (t?', ':'') + data.tasks[i].sql.test.result.rids[j];
                                                if(data.tasks[i].sql.test.result.rids.length < data.tasks[i].sql.test.result.n)
                                                    t += ', ...';
                                                $("#SQLRET"+i).html("n=" + data.tasks[i].sql.test.result.n + ", rids:(" + t + ")");
                                            }
                                        } else {
                                            $("#SQLRET"+i).html("");
                                        }
                                    }
                                }
                            });
                        }
                    });
                }
            }

            $(document).ready(
                function(){
                    (function( $ ){
                        $.fn.serializeJSON=function() {
                            var json = {};
                            jQuery.map($(this).serializeArray(), function(n, i){
                                json[n['name']] = n['value'];
                            });

                            return json;
                        };
                    })( jQuery );

                    var limits = {
                        'period':{'min':<?php echo self::MINPERIOD; ?>, 'max':<?php echo self::MAXPERIOD; ?>},
                        'delay':{min:0}
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
                }
            );

        </script>
        <?php
    }

    /**
     * RETURN the html gui
     *
     * @return string
     */
    public function getInterfaceHTML()
    {
        ob_start();
        ?>
        <form name="graphicForm" onsubmit="return(false);" method="post">
            <br/>
            <?php echo _('task::_common_:periodicite de la tache') ?>
            <input class="formElem" type="text" name="period" style="width:40px;" value="" />
            <?php echo _('task::_common_:secondes (unite temporelle)') ?>
            <br/>
            <input class="formElem" type="checkbox" name="logsql" />&nbsp;log changes
        </form>
        <center>
            <div class="terminal" id="sqla"></div>
        </center>
        <?php
        return ob_get_clean();
    }
    /**
     *
     * retrieveContent & processOneContent : work done by the task
     */
    private $sxTaskSettings = null; // settings in simplexml

    /**
     * return array of records to work on, from sql generated by 'from' clause
     *
     * @param  appbox  $appbox
     * @return array()
     */

    protected function retrieveContent(appbox $appbox)
    {
        $this->maxrecs = 1000;
        $this->sxTaskSettings = simplexml_load_string($this->getSettings());
        if (false === $this->sxTaskSettings || !$this->sxTaskSettings->tasks) {
            return array();
        }

        $ret = array();

        $logsql = (int) ($this->sxTaskSettings->logsql) > 0;

        foreach ($this->sxTaskSettings->tasks->task as $sxtask) {

            if (!$this->running) {
                break;
            }

            $task = $this->calcSQL($sxtask);

            if (!$task['active']) {
                continue;
            }

            if ($logsql) {
                $this->log(sprintf("playing task '%s' on base '%s'"
                        , $task['name']
                        , $task['basename'] ? $task['basename'] : '<unknown>'), self::LOG_INFO);
            }

            try {
                $connbas = connection::getPDOConnection($this->dependencyContainer, $task['sbas_id']);
            } catch (Exception $e) {
                $this->log(sprintf("can't connect sbas %s", $task['sbas_id']), self::LOG_ERROR);
                continue;
            }

            $stmt = $connbas->prepare($task['sql']['real']['sql']);
            if ($stmt->execute(array())) {
                while ($this->running && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE) {

                    $tmp = array('sbas_id'   => $task['sbas_id'], 'record_id' => $row['record_id'], 'action'    => $task['action']);

                    $rec = new record_adapter($this->dependencyContainer, $task['sbas_id'], $row['record_id']);
                    switch ($task['action']) {

                        case 'UPDATE':

                            // change collection ?
                            if (($x = (int) ($sxtask->to->coll['id'])) > 0) {
                                $tmp['coll'] = $x;
                            }

                            // change sb ?
                            if (($x = $sxtask->to->status['mask'])) {
                                $tmp['sb'] = $x;
                            }
                            $ret[] = $tmp;
                            break;

                        case 'DELETE':
                            $tmp['deletechildren'] = false;
                            if ($sxtask['deletechildren'] && $rec->is_grouping()) {
                                $tmp['deletechildren'] = true;
                            }
                            $ret[] = $tmp;
                            break;
                    }
                }
                $stmt->closeCursor();
            }
        }

        return $ret;
    }

    /**
     * work on ONE record
     *
     * @param  appbox                  $appbox
     * @param  array                   $row
     * @return \task_period_RecordMover
     */
    protected function processOneContent(appbox $appbox, Array $row)
    {
        $logsql = (int) ($this->sxTaskSettings->logsql) > 0;
        $databox = $this->dependencyContainer['phraseanet.appbox']->get_databox($row['sbas_id']);
        $rec = new record_adapter($this->dependencyContainer, $row['sbas_id'], $row['record_id']);
        switch ($row['action']) {

            case 'UPDATE':

                // change collection ?
                if (array_key_exists('coll', $row)) {
                    $coll = collection::get_from_coll_id($this->dependencyContainer, $databox, $row['coll']);
                    $rec->move_to_collection($coll, $this->dependencyContainer['phraseanet.appbox']);
                    $this['phraseanet.SE']->updateRecord($rec);
                    if ($logsql) {
                        $this->log(sprintf("on sbas %s move rid %s to coll %s \n", $row['sbas_id'], $row['record_id'], $coll->get_coll_id()));
                    }
                }

                // change sb ?
                if (array_key_exists('sb', $row)) {
                    $status = str_split($rec->get_status());
                    foreach (str_split(strrev($row['sb'])) as $bit => $val) {
                        if ($val == '0' || $val == '1') {
                            $status[31 - $bit] = $val;
                        }
                    }
                    $status = implode('', $status);
                    $rec->set_binary_status($status);
                    $this->dependencyContainer['phraseanet.SE']->updateRecord($rec);
                    if ($logsql) {
                        $this->log(sprintf("on sbas %s set rid %s status to %s \n", $row['sbas_id'], $row['record_id'], $status));
                    }
                }
                break;

            case 'DELETE':
                if ($row['deletechildren'] && $rec->is_grouping()) {
                    foreach ($rec->get_children() as $child) {
                        $child->delete();
                        $this->dependencyContainer['phraseanet.SE']->removeRecord($child);
                        if ($logsql) {
                            $this->log(sprintf("on sbas %s delete (grp child) rid %s \n", $row['sbas_id'], $child->get_record_id()));
                        }
                    }
                }
                $rec->delete();
                $this->dependencyContainer['phraseanet.SE']->removeRecord($rec);
                if ($logsql) {
                    $this->log(sprintf("on sbas %s delete rid %s \n", $row['sbas_id'], $rec->get_record_id()));
                }
                break;
        }

        return $this;
    }

    /**
     * all work done on processOneContent, so nothing to do here
     *
     * @param  appbox                  $appbox
     * @param  array                   $row
     * @return \task_period_RecordMover
     */
    protected function postProcessOneContent(appbox $appbox, Array $row)
    {
        return $this;
    }

    /**
     * compute sql for a task (<task> entry in settings)
     *
     * @param  simplexml $sxtask
     * @param  boolean   $playTest
     * @return array
     */
    private function calcSQL($sxtask, $playTest = false)
    {
        $sbas_id = (int) ($sxtask['sbas_id']);

        $ret = array(
            'name'                 => $sxtask['name'] ? (string) $sxtask['name'] : 'sans nom',
            'name_htmlencoded'     => \p4string::MakeString(($sxtask['name'] ? $sxtask['name'] : 'sans nom'), 'html'),
            'active'               => trim($sxtask['active']) === '1',
            'sbas_id'              => $sbas_id,
            'basename'             => '',
            'basename_htmlencoded' => '',
            'action'               => strtoupper($sxtask['action']),
            'sql'                  => NULL,
            'err'                  => '',
            'err_htmlencoded'      => '',
        );

        try {
            $dbox = $this->dependencyContainer['phraseanet.appbox']->get_databox($sbas_id);

            $ret['basename'] = $dbox->get_viewname();
            $ret['basename_htmlencoded'] = htmlentities($ret['basename']);
            switch ($ret['action']) {
                case 'UPDATE':
                    $ret['sql'] = $this->calcUPDATE($sbas_id, $sxtask, $playTest);
                    break;
                case 'DELETE':
                    $ret['sql'] = $this->calcDELETE($sbas_id, $sxtask, $playTest);
                    $ret['deletechildren'] = (int) ($sxtask['deletechildren']);
                    break;
                default:
                    $ret['err'] = "bad action '" . $ret['action'] . "'";
                    $ret['err_htmlencoded'] = htmlentities($ret['err']);
                    break;
            }
        } catch (Exception $e) {
            $ret['err'] = "bad sbas '" . $sbas_id . "'";
            $ret['err_htmlencoded'] = htmlentities($ret['err']);
        }

        return $ret;
    }

    /**
     * compute entry for a UPDATE query
     *
     * @param  integer   $sbas_id
     * @param  simplexml $sxtask
     * @param  boolean   $playTest
     * @return array
     */
    private function calcUPDATE($sbas_id, &$sxtask, $playTest)
    {
        $tws = array(); // NEGATION of updates, used to build the 'test' sql
        //
        // set coll_id ?
        if (($x = (int) ($sxtask->to->coll['id'])) > 0) {
            $tws[] = 'coll_id!=' . $x;
        }

        // set status ?
        $x = $sxtask->to->status['mask'];
        $mx = str_replace(' ', '0', ltrim(str_replace(array('0', 'x'), array(' ', ' '), $x)));
        $ma = str_replace(' ', '0', ltrim(str_replace(array('x', '0'), array(' ', '1'), $x)));
        if ($mx && $ma)
            $tws[] = '((status ^ 0b' . $mx . ') & 0b' . $ma . ')!=0';
        elseif ($mx)
            $tws[] = '(status ^ 0b' . $mx . ')!=0';
        elseif ($ma)
            $tws[] = '(status & 0b' . $ma . ')!=0';

        // compute the 'where' clause
        list($tw, $join) = $this->calcWhere($sbas_id, $sxtask);

        // ... complete the where to buid the TEST
        if (count($tws) == 1)
            $tw[] = $tws[0];
        elseif (count($tws) > 1)
            $tw[] = '(' . implode(') OR (', $tws) . ')';
        if (count($tw) == 1)
            $where = $tw[0];
        if (count($tw) > 1)
            $where = '(' . implode(') AND (', $tw) . ')';

        // build the TEST sql (select)
        $sql_test = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql_test .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');

        // build the real sql (select)
        $sql = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');

        $ret = array(
            'real' => array(
                'sql'             => $sql,
                'sql_htmlencoded' => htmlentities($sql),
            ),
            'test'            => array(
                'sql'             => $sql_test,
                'sql_htmlencoded' => htmlentities($sql_test),
                'result'          => NULL,
                'err'             => NULL
            )
        );

        if ($playTest) {
            $ret['test']['result'] = $this->playTest($sbas_id, $sql_test);
        }

        return $ret;
    }

    /**
     * compute entry for a DELETE task
     *
     * @param  integer   $sbas_id
     * @param  simplexml $sxtask
     * @param  boolean   $playTest
     * @return array
     */
    private function calcDELETE($sbas_id, &$sxtask, $playTest)
    {
        // compute the 'where' clause
        list($tw, $join) = $this->calcWhere($sbas_id, $sxtask);

        // build the TEST sql (select)
        $sql_test = 'SELECT SQL_CALC_FOUND_ROWS record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql_test .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');
        $sql_test .= ' LIMIT 10';

        // build the real sql (select)
        $sql = 'SELECT record_id FROM record' . $join;
        if (count($tw) > 0)
            $sql .= ' WHERE ' . ((count($tw) == 1) ? $tw[0] : '(' . implode(') AND (', $tw) . ')');

        $ret = array(
            'real' => array(
                'sql'             => $sql,
                'sql_htmlencoded' => htmlentities($sql),
            ),
            'test'            => array(
                'sql'             => $sql_test,
                'sql_htmlencoded' => htmlentities($sql_test),
                'result'          => NULL,
                'err'             => NULL
            )
        );

        if ($playTest) {
            $ret['test']['result'] = $this->playTest($sbas_id, $sql_test);
        }

        return $ret;
    }

    /**
     * compute the 'where' clause
     * returns an array of clauses to be joined by 'and'
     * and a 'join' to needed tables
     *
     * @param  integer   $sbas_id
     * @param  simplecms $sxtask
     * @return array
     */
    private function calcWhere($sbas_id, &$sxtask)
    {
        $connbas = connection::getPDOConnection($this->dependencyContainer, $sbas_id);

        $tw = array();
        $join = '';

        $ijoin = 0;

        // criteria <type type="XXX" />
        if (($x = $sxtask->from->type['type']) !== NULL) {
            switch (strtoupper($x)) {
                case 'RECORD':
                    $tw[] = 'parent_record_id!=record_id';
                    break;
                case 'STORY':
                    $tw[] = 'parent_record_id=record_id';
                    break;
            }
        }

        // criteria <text field="XXX" compare="OP" value="ZZZ" />
        foreach ($sxtask->from->text as $x) {
            $ijoin++;
            $comp = strtoupper($x['compare']);
            if (in_array($comp, array('<', '>', '<=', '>=', '=', '!='))) {
                $s = 'p' . $ijoin . '.name=\'' . $x['field'] . '\' AND p' . $ijoin . '.value' . $comp;
                $s .= '' . $connbas->quote($x['value']) . '';

                $tw[] = $s;
                $join .= ' INNER JOIN prop AS p' . $ijoin . ' USING(record_id)';
            } else {
                // bad comparison operator
            }
        }

        // criteria <date direction ="XXX" field="YYY" delta="Z" />
        foreach ($sxtask->from->date as $x) {
            $ijoin++;
            $s = 'p' . $ijoin . '.name=\'' . $x['field'] . '\' AND NOW()';
            $s .= strtoupper($x['direction']) == 'BEFORE' ? '<' : '>=';
            $delta = (int) ($x['delta']);
            if ($delta > 0)
                $s .= '(p' . $ijoin . '.value+INTERVAL ' . $delta . ' DAY)';
            elseif ($delta < 0)
                $s .= '(p' . $ijoin . '.value-INTERVAL ' . -$delta . ' DAY)';
            else
                $s .= 'p' . $ijoin . '.value';

            $tw[] = $s;
            $join .= ' INNER JOIN prop AS p' . $ijoin . ' USING(record_id)';
        }

        // criteria <coll compare="OP" id="X,Y,Z" />
        if (($x = $sxtask->from->coll) !== NULL) {
            $tcoll = explode(',', $x['id']);
            foreach ($tcoll as $i => $c)
                $tcoll[$i] = (int) $c;
            if ($x['compare'] == '=') {
                if (count($tcoll) == 1) {
                    $tw[] = 'coll_id = ' . $tcoll[0];
                } else {
                    $tw[] = 'coll_id IN(' . implode(',', $tcoll) . ')';
                }
            } elseif ($x['compare'] == '!=') {
                if (count($tcoll) == 1) {
                    $tw[] = 'coll_id != ' . $tcoll[0];
                } else {
                    $tw[] = 'coll_id NOT IN(' . implode(',', $tcoll) . ')';
                }
            } else {
                // bad operator
            }
        }

        // criteria <status mask="XXXXX" />
        $x = $sxtask->from->status['mask'];
        $mx = str_replace(' ', '0', ltrim(str_replace(array('0', 'x'), array(' ', ' '), $x)));
        $ma = str_replace(' ', '0', ltrim(str_replace(array('x', '0'), array(' ', '1'), $x)));
        if ($mx && $ma) {
            $tw[] = '((status^0b' . $mx . ')&0b' . $ma . ')=0';
        } elseif ($mx) {
            $tw[] = '(status^0b' . $mx . ')=0';
        } elseif ($ma) {
            $tw[] = '(status&0b' . $ma . ")=0";
        }

        if (count($tw) == 1) {
            $where = $tw[0];
        }
        if (count($tw) > 1) {
            $where = '(' . implode(') AND (', $tw) . ')';
        }

        return array($tw, $join);
    }

    /**
     * play a 'test' sql on sbas, return the number of records and the 10 first rids
     *
     * @param  integer $sbas_id
     * @param  string  $sql
     * @return array
     */
    private function playTest($sbas_id, $sql)
    {
        $connbas = connection::getPDOConnection($this->dependencyContainer, $sbas_id);
        $result = array('rids' => array(), 'err' => '', 'n'   => null);

        $result['n'] = $connbas->query('SELECT COUNT(*) AS n FROM (' . $sql . ') AS x')->fetchColumn();

        $stmt = $connbas->prepare('SELECT record_id FROM (' . $sql . ') AS x LIMIT 10');
        if ($stmt->execute(array())) {
            while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
                $result['rids'][] = $row['record_id'];
            }
            $stmt->closeCursor();
        } else {
            $result['err'] = $connbas->last_error();
        }

        return $result;
    }

    /**
     * facility called by xhttp/jquery from interface for ex. when switching interface from gui<->xml
     */
    public function facility()
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            'ACT', 'xml'
        );

        $ret = array('tasks' => array());
        switch ($parm2['ACT']) {
            case 'CALCTEST':
                $sxml = simplexml_load_string($parm2['xml']);
                foreach ($sxml->tasks->task as $sxtask) {
                    $ret['tasks'][] = $this->calcSQL($sxtask, false);
                }
                break;
            case 'PLAYTEST':
                $sxml = simplexml_load_string($parm2['xml']);
                foreach ($sxml->tasks->task as $sxtask) {
                    $ret['tasks'][] = $this->calcSQL($sxtask, true);
                }
                break;
            case 'CALCSQL':
                $xml = $this->graphic2xml('<?xml version="1.0" encoding="UTF-8"?><tasksettings/>');
                $sxml = simplexml_load_string($xml);
                foreach ($sxml->tasks->task as $sxtask) {
                    $ret['tasks'][] = $this->calcSQL($sxtask, false);
                }
                break;
        }

        return json_encode($ret);
    }
}
