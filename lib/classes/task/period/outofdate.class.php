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
 * @package     task_manager
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
    return(_('task::outofdate:deplacement de docs perimes'));
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
    if ($dom->loadXML($oldxml))
    {
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
      ) as $pname)
      {
        $ptype = substr($pname, 0, 3);
        $pname = substr($pname, 4);
        $pvalue = $parm2[$pname];
        if ($ns = $dom->getElementsByTagName($pname)->item(0))
        {
          // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
          while (($n = $ns->firstChild))
            $ns->removeChild($n);
        }
        else
        {
          // le champ n'existait pas dans le xml, on le cr�e
          $dom->documentElement->appendChild($dom->createTextNode("\t"));
          $ns = $dom->documentElement->appendChild($dom->createElement($pname));
          $dom->documentElement->appendChild($dom->createTextNode("\n"));
        }
        // on fixe sa valeur
        switch ($ptype)
        {
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
    if (($sxml = simplexml_load_string($xml))) // in fact XML IS always valid here...
    {
      // ... but we could check for safe values
      if ((int) ($sxml->period) < 10)
        $sxml->period = 10;
      elseif ((int) ($sxml->period) > 1440) // 1 jour
        $sxml->period = 1440;

      if ((string) ($sxml->delay) == '')
        $sxml->delay = 0;
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
        for(j in pops)
        {
          for(opts=<?php echo $form ?>[pops[j].name].options, i=0; i<opts.length; i++)
          {
            if(opts[i].value == pops[j].val)
            {
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
      </script>
<?php

      return("");
    }
    else // ... so we NEVER come here
    {
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
    </style>
<?php
  }

  // ====================================================================
  // printInterfaceJS() : g�n�rer le code js de l'interface 'graphic view'
  // ====================================================================
  public function printInterfaceJS()
  {
    global $parm;
?>
    <script type="text/javascript">
      function chgxmltxt(textinput, fieldname)
      {
        var limits = { 'period':{min:1, 'max':1440} , 'delay':{min:0} } ;
        if(typeof(limits[fieldname])!='undefined')
        {
          var v = 0|textinput.value;
          if(limits[fieldname].min && v < limits[fieldname].min)
            v = limits[fieldname].min;
          else if(limits[fieldname].max && v > limits[fieldname].max)
            v = limits[fieldname].max;
          textinput.value = v;
        }
        setDirty();
      }
      function chgxmlck(checkinput, fieldname)
      {
        setDirty();
      }
      function chgxmlpopup(popupinput, fieldname)
      {
        setDirty();
      }
      function chgsbas(sbaspopup)
      {
        var xmlhttp = new XMLHttpRequest_with_xpath();
        xmlhttp.open("POST", "/admin/taskfacility.php", false);
        xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        xmlhttp.send("cls=outofdate&taskid=<?php echo $this->get_task_id() ?>&bid="+sbaspopup.value);

        for(fld=1; fld<=2; fld++)
        {
          var p = document.getElementById("field"+fld);
          while( (f=p.firstChild) )
            p.removeChild(f);
          var t = xmlhttp.xpathSearch('/result/date_fields/field');
          if(t.length > 0)
          {
            var o = p.appendChild(document.createElement('option'));
            o.setAttribute('value', '');
            o.appendChild(document.createTextNode("..."));
            for(i in t)
            {
              var o = p.appendChild(document.createElement('option'));
              o.setAttribute('value', t[i].firstChild.nodeValue);
              o.appendChild(document.createTextNode(t[i].firstChild.nodeValue));
              o.setAttribute('class', "jsFilled");
            }
          }
        }

        for(fld=0; fld<=2; fld++)
        {
          var p = document.getElementById("status"+fld);
          while( (f=p.firstChild) )
            p.removeChild(f);
          var t = xmlhttp.xpathSearch('/result/status_bits/bit');
          if(t.length > 0)
          {
            var o = p.appendChild(document.createElement('option'));
            o.setAttribute('value', '');
            o.appendChild(document.createTextNode("..."));
            for(i in t)
            {
              var o = p.appendChild(document.createElement('option'));
              o.setAttribute('value', t[i].getAttribute("n")+"_"+t[i].getAttribute("value"));
              o.appendChild(document.createTextNode(t[i].firstChild.nodeValue));
              o.setAttribute('class', "jsFilled");
            }
          }
        }

        for(fld=0; fld<=2; fld++)
        {
          var p = document.getElementById("coll"+fld);
          while( (f=p.firstChild) )
            p.removeChild(f);
          var t = xmlhttp.xpathSearch('/result/collections/collection');
          if(t.length > 0)
          {
            var o = p.appendChild(document.createElement('option'));
            o.setAttribute('value', '');
            o.appendChild(document.createTextNode("..."));
            for(i in t)
            {
              var o = p.appendChild(document.createElement('option'));
              o.setAttribute('value', t[i].getAttribute("id"));
              o.appendChild(document.createTextNode(t[i].firstChild.nodeValue));
              o.setAttribute('class', "jsFilled");
            }
          }
        }
        delete xmlhttp;
        // setDirty();
      }

      function XMLHttpRequest_with_xpath()
      {
        var x = new XMLHttpRequest();
        x.xpathSearch = function(xpath) {
          var t = new Array();
          if(x.responseXML.evaluate)
          {
            var tmp = x.responseXML.evaluate(xpath, x.responseXML, null, 4, null);
            var i;
            while(i = tmp.iterateNext())
              t.push(i);
          }
          else if(typeof(x.responseXML.selectNodes))
          {
            var tmp = x.responseXML.selectNodes(xpath);
            for(var i=0; i<tmp.length; i++)
            t.push(tmp.item(i));
          }

          return(t);
        };

        return(x);
      }

    </script>
<?php
  }

  // ====================================================================
  // callback : must return the name graphic form to submit
  // if not implemented, assume 'graphicForm'
  // ====================================================================
  function getGraphicForm()
  {
    return true;
  }

  // ====================================================================
  // printInterfaceHTML(..) : g�n�rer l'interface 'graphic view' !! EN UTF-8 !!
  // ====================================================================
  public function printInterfaceHTML()
  {
    global $usr_id;

    $appbox = appbox::get_instance();
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
    foreach ($sbas_list as $databox)
    {
      $selected = '';
      print("\t\t\t\t<option value=\"" . $databox->get_sbas_id() . "\" $selected>" . p4string::MakeString($databox->get_sbas_id(), "form") . "</option>\n");
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
<?php
        $out = ob_get_clean();

        return $out;
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
      function help()
      {
        return(_("task::outofdate:deplacement de docs suivant valeurs de champs 'date'"));
      }

      // ======================================================================================================
      // ===== run() : le code d'�x�cution de la t�che proprement dite
      // ======================================================================================================

      private $sxTaskSettings = null; // les settings de la tache en simplexml
      private $connbas = null;  // cnx � la base
      private $msg = "";

      protected function run2()
      {
        $ret = '';


        $sbas_id = (int) ($this->sxTaskSettings->sbas_id);

        $conn = connection::getPDOConnection();

        $this->connbas = connection::getPDOConnection($sbas_id);

        $this->running = true;
        $this->tmask = array();
        $this->tmaskgrp = array();
        $this->period = 60;


        // ici la t�che tourne tant qu'elle est active
        $last_exec = 0;
        $loop = 0;
        while ($this->running)
        {
          $this->set_last_exec_time();

          $databox = databox::get_instance($sbas_id);

          $sql = "SELECT * FROM task2 WHERE task_id = :task_id";
          $stmt = $conn->prepare($sql);
          $stmt->execute(array(':task_id' => $this->get_task_id()));
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          $stmt->closeCursor();

          if ($row)
          {
            if ($row['status'] == 'tostop')
            {
              $ret = 'stopped';
              $this->running = false;
            }
            else
            {
              if ($this->sxTaskSettings = simplexml_load_string($row['settings']))
              {
                $period = (int) ($this->sxTaskSettings->period);
                if ($period <= 0 || $period >= 24 * 60)
                  $period = 60;
              }
              else
              {
                $period = 60;
              }
              $this->connbas = connection::getPDOConnection($sbas_id);

              $now = time();
              if ($now - $last_exec >= $period * 60) // period est en minutes
              {

                $r = $this->doRecords();
                // printf("line %s r=%s\n", __LINE__, $r);

                if ($r == 'NORECSTODO')
                {
                  $last_exec = $now;
                }
                else
                {
                  // on a trait� des records, on restart (si on a �t� lanc� par le scheduler)
                  if ($row['status'] == 'started')
                  {
                    // ask for wakeup by scheduler
                    $ret = 'torestart';
                    $this->running = false;
                  }
                }

                if ($loop > 5 || memory_get_usage() >> 20 >= 15)
                {
                  $ret = 'torestart';
                  $this->running = false;
                }
              }
              else
              {
                $conn->close();
                $this->connbas->close();
                unset($conn);
                sleep(5);
                $conn = connection::getPDOConnection();
                $this->connbas = connection::getPDOConnection($sbas_id);
              }
            }
          }
          else
          {
            $ret = 'stopped';
            $this->running = false;
          }
          $loop++;
        }

        return($ret);
      }

      function doRecords()
      {
        $ndone = 0;
        $ret = 'NORECSTODO';

        $date1 = $date2 = time();
        $field1 = $field2 = '';

        // test : DATE 1
        if (($field1 = trim($this->sxTaskSettings->field1)) != '')
        {
          if (($delta = (int) ($this->sxTaskSettings->fieldDv1)) > 0)
          {
            if ($this->sxTaskSettings->fieldDs1 == '-')
              $date1 += 86400 * $delta;
            else
              $date1 -= 86400 * $delta;
          }
        }
        // test : DATE 2
        if (($field2 = trim($this->sxTaskSettings->field2)) != '')
        {
          if (($delta = (int) ($this->sxTaskSettings->fieldDv2)) > 0)
          {
            if ($this->sxTaskSettings->fieldDs2 == '-')
              $date2 += 86400 * $delta;
            else
              $date2 -= 86400 * $delta;
          }
        }

        $date1 = date("YmdHis", $date1);
        $date2 = date("YmdHis", $date2);

        $sqlset = $params = $tmp_params = array();
        for ($i = 0; $i <= 2; $i++)
        {
          $sqlwhere[$i] = '';
          $sqlset[$i] = '';
          $x = 'status' . $i;
          @list($tostat, $statval) = explode('_', (string) ($this->sxTaskSettings->{$x}));
          if ($tostat >= 4 && $tostat <= 63)
          {
            if ($statval == '0')
            {
              $sqlset[$i] = 'status=status & ~(1<<' . $tostat . ')';
              $sqlwhere[$i] .= '(status & (1<<' . $tostat . ') = 0)';
            }
            elseif ($statval == '1')
            {
              $sqlset[$i] = 'status=status|(1<<' . $tostat . ')';
              $sqlwhere[$i] .= '(status & (1<<' . $tostat . ') != 0)';
            }
          }
          $x = 'coll' . $i;
          if (($tocoll = (string) ($this->sxTaskSettings->{$x})) != '')
          {
            $sqlset[$i] .= ( $sqlset[$i] ? ', ' : '') . ('coll_id = :coll_id_set'. $i);
            $sqlwhere[$i] .= ( $sqlwhere[$i] ? ' AND ' : '') . '(coll_id = :coll_id_where'. $i.')';
            $tmp_params[':coll_id_set' . $i] = $tocoll;
            $tmp_params[':coll_id_where' . $i] = $tocoll;
          }
        }
        for ($i = 0; $i <= 2; $i++)
        {
          if (!$sqlwhere[$i])
            $sqlwhere[$i] = '1';
        }

        $nchanged = 0;
        // $sqlupd = 'UPDATE record INNER JOIN prop ON prop.record_id=record.record_id';

        if ($date1)
        {
          $sql = "UPDATE prop AS p1 INNER JOIN record USING(record_id)
                    SET " . $sqlset[0] .
                  " WHERE p1.name = :name1 AND :date1 <= p1.value
                    AND (" . $sqlwhere[1] . " OR " . $sqlwhere[2] . ")";

          $params = array();
          $params[':name1'] = $field1;
          $params[':date1'] = $date1;
          $params[':coll_id_set0'] = $tmp_params[':coll_id_set0'];
          $params[':coll_id_where1'] = $tmp_params[':coll_id_where1'];
          $params[':coll_id_where2'] = $tmp_params[':coll_id_where2'];

          $stmt = $this->connbas->prepare($sql);
          $stmt->execute($params);
          $n = $stmt->rowCount();
          $stmt->closeCursor();

          $nchanged += $n;
          if ($n > 0)
            $this->log(sprintf(("SQL=%s\n - %s changes"), $sql, $n));
        }

        if ($date2 && $date1)
        {
          $sql = "UPDATE (prop AS p1 INNER JOIN prop AS p2 USING(record_id))
                    INNER JOIN record USING(record_id)
                    SET " . $sqlset[1] .
                  " WHERE p1.name = :name1 AND p2.name = :name2
                    AND :date1 > p1.value AND :date2 <= p2.value
                    AND (" . $sqlwhere[0] . " OR " . $sqlwhere[2] . ")";

          $params = array();
          $params[':name1'] = $field1;
          $params[':name2'] = $field2;
          $params[':date1'] = $date1;
          $params[':date2'] = $date2;
          $params[':coll_id_set1'] = $tmp_params[':coll_id_set1'];
          $params[':coll_id_where0'] = $tmp_params[':coll_id_where0'];
          $params[':coll_id_where2'] = $tmp_params[':coll_id_where2'];

          $stmt = $this->connbas->prepare($sql);
          $stmt->execute($params);
          $n = $stmt->rowCount();
          $stmt->closeCursor();

          $nchanged += $n;
          if ($n > 0)
            $this->log(sprintf(("SQL=%s\n - %s changes"), $sql, $n));
        }

        if ($date2)
        {
          $sql = "UPDATE prop AS p2 INNER JOIN record USING(record_id)
                    SET " . $sqlset[2] .
                  " WHERE p2.name = :name2 AND :date2 > p2.value
                    AND (" . $sqlwhere[0] . " OR " . $sqlwhere[1] . ")";

          $params = array();
          $params[':name2'] = $field2;
          $params[':date2'] = $date2;
          $params[':coll_id_set2'] = $tmp_params[':coll_id_set2'];
          $params[':coll_id_where0'] = $tmp_params[':coll_id_where0'];
          $params[':coll_id_where1'] = $tmp_params[':coll_id_where1'];

          $stmt = $this->connbas->prepare($sql);
          $stmt->execute($params);
          $n = $stmt->rowCount();
          $stmt->closeCursor();

          $nchanged += $n;
          if ($n > 0)
            $this->log(sprintf(("SQL=%s\n - %s changes"), $sql, $n));
        }

        $ret = ($nchanged > 0 ? $nchanged : 'NORECSTODO');

        return($ret);
      }

      public function facility()
      {
        //global $parm;
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
                        "bid"
        );

        phrasea::headers(200, true, 'text/xml','UTF-8', false);

        $ret = new DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $element = $ret->createElement('result');

        $root = $ret->appendChild($element);
        $root->appendChild($ret->createCDATASection(var_export($parm2, true)));
        $dfields = $root->appendChild($ret->createElement("date_fields"));
        $statbits = $root->appendChild($ret->createElement("status_bits"));
        $coll = $root->appendChild($ret->createElement("collections"));

        $xml = NULL;
        $sbas_id = (int) $parm2['bid'];
        try
        {
          $databox = databox::get_instance($sbas_id);
          $meta_struct = $databox->get_meta_structure();

          foreach ($meta_struct as $meta)
          {
            if (mb_strtolower($meta->get_type()) == 'date')
              $dfields->appendChild($ret->createElement("field"))->appendChild($ret->createTextNode($meta->get_name()));
          }

          $status = $databox->get_statusbits();
          foreach ($status as $n => $stat)
          {
            $node = $statbits->appendChild($ret->createElement("bit"));
            $node->setAttribute('n', $n);
            $node->setAttribute('value', '0');
            $node->setAttribute('label', $stat['labeloff']);
            $node->appendChild($ret->createTextNode($stat['labeloff']));
            $node = $statbits->appendChild($ret->createElement("bit"));
            $node->setAttribute('n', $n);
            $node->setAttribute('value', '1');
            $node->setAttribute('label', $stat['labelon']);
            $node->appendChild($ret->createTextNode($stat['labelon']));
          }

          foreach ($databox->get_collections() as $collection)
          {
            $node = $coll->appendChild($ret->createElement("collection"));
            $node->setAttribute('id', $collection->get_coll_id());

            $node->appendChild($ret->createTextNode($collection->get_name()));
          }
        }
        catch (Exception $e)
        {

        }

        print($ret->saveXML());
      }

    }
?>
