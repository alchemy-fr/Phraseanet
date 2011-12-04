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
  function help()
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
   * @param string $oldxml
   * @return string
   */
  public function graphic2xml($oldxml)
  {
    $request = http_request::getInstance();

    $parm2 = $request->get_parms(
                    'period'
                    , 'flush'
                    , 'maxrecs'
                    , 'maxmegs'
    );
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if ($dom->loadXML($oldxml))
    {
      $xmlchanged = false;

      foreach (array("str:period", "str:flush", "str:maxrecs", "str:maxmegs") as $pname)
      {
        $ptype = substr($pname, 0, 3);
        $pname = substr($pname, 4);
        $pvalue = $parm2[$pname];
        if ($ns = $dom->getElementsByTagName($pname)->item(0))
        {
          while (($n = $ns->firstChild))
            $ns->removeChild($n);
        }
        else
        {
          $ns = $dom->documentElement->appendChild($dom->createElement($pname));
        }
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

  /**
   * must fill the graphic form (using js) from xml
   *
   * @param string $xml
   * @param string $form
   * @return string
   */
  public function xml2graphic($xml, $form)
  {
    if (($sxml = simplexml_load_string($xml)))
    {
      if ((int) ($sxml->period) < 10)
        $sxml->period = 10;
      elseif ((int) ($sxml->period) > 300)
        $sxml->period = 300;

      if ((string) ($sxml->flush) == '')
        $sxml->flush = 10;
      elseif ((int) ($sxml->flush) < 1)
        $sxml->flush = 1;
      elseif ((int) ($sxml->flush) > 100)
        $sxml->flush = 100;

      if ((string) ($sxml->maxrecs) == '')
        $sxml->maxrecs = 100;
      if ((int) ($sxml->maxrecs) < 10)
        $sxml->maxrecs = 10;
      elseif ((int) ($sxml->maxrecs) > 500)
        $sxml->maxrecs = 500;

      if ((string) ($sxml->maxmegs) == '')
        $sxml->maxmegs = 6;
      if ((int) ($sxml->maxmegs) < 3)
        $sxml->maxmegs = 3;
      elseif ((int) ($sxml->maxmegs) > 32)
        $sxml->maxmegs = 32;
?>
      <script type="text/javascript">
<?php echo $form ?>.period.value  = "<?php echo p4string::MakeString($sxml->period, "js", '"') ?>";
<?php echo $form ?>.flush.value   = "<?php echo p4string::MakeString($sxml->flush, "js", '"') ?>";
<?php echo $form ?>.maxrecs.value = "<?php echo p4string::MakeString($sxml->maxrecs, "js", '"') ?>";
<?php echo $form ?>.maxmegs.value = "<?php echo p4string::MakeString($sxml->maxmegs, "js", '"') ?>";
      </script>
<?php

      return("");
    }
    else
    {
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
      function chgxmltxt(textinput, fieldname)
      {
        var limits = { 'period':{min:1, 'max':300} , 'flush':{min:1, 'max':100} , 'maxrecs':{min:10, 'max':1000} , 'maxmegs':{min:2, 'max':100} } ;
        if(typeof(limits[fieldname])!='undefined')
        {
          var v = 0|textinput.value;
          if(v < limits[fieldname].min)
            v = limits[fieldname].min;
          else if(v > limits[fieldname].max)
            v = limits[fieldname].max;
          textinput.value = v;
        }
        setDirty();
      }
      function chgxmlck_die(ck)
      {
        if(ck.checked)
        {
          if(document.forms['graphicForm'].maxrecs.value == "")
            document.forms['graphicForm'].maxrecs.value = 500;
          if(document.forms['graphicForm'].maxmegs.value == "")
            document.forms['graphicForm'].maxmegs.value = 4;
          document.forms['graphicForm'].maxrecs.disabled = document.forms['graphicForm'].maxmegs.disabled = false;
        }
        else
        {
          document.forms['graphicForm'].maxrecs.disabled = document.forms['graphicForm'].maxmegs.disabled = true;
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
    </script>
<?php
  }

  public function  getGraphicForm()
  {
    return true;
  }

  /**
   * generates interface 'graphic view'
   *
   */
  public function printInterfaceHTML()
  {
    ob_start();
?>
    <form name="graphicForm" onsubmit="return(false);" method="post">
      <br/>
<?php echo _('task::_common_:periodicite de la tache') ?>&nbsp;:&nbsp;
      <input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">
  <?php echo _('task::_common_:secondes (unite temporelle)') ?><br/>
    <br/>
  <?php echo sprintf(_("task::_common_:passer tous les %s records a l'etape suivante"), '<input type="text" name="flush" style="width:40px;" onchange="chgxmltxt(this, \'flush\');" value="">'); ?>
    <br/>
    <br/>
<?php echo _('task::_common_:relancer la tache tous les') ?>&nbsp;
    <input type="text" name="maxrecs" style="width:40px;" onchange="chgxmltxt(this, 'maxrecs');" value="">
  <?php echo _('task::_common_:records, ou si la memoire depasse') ?>&nbsp;
    <input type="text" name="maxmegs" style="width:40px;" onchange="chgxmltxt(this, 'maxmegs');" value="">
    Mo
    <br/>
  </form>
<?php
    $out = ob_get_clean();

    return $out;
  }

  protected function flush_records_sbas()
  {
    $sql = implode(', ', $this->recs_to_write);

    if ($sql != '')
    {
      $this->log(sprintf(
                      'setting %d record(s) to subdef meta writing'
                      , count($this->recs_to_write)
      ));

      try
      {
        $connbas = connection::getPDOConnection($this->sbas_id);
        $sql = 'UPDATE record
                SET status=(status & ~0x03),
                    jeton=(jeton | ' . JETON_WRITE_META_SUBDEF . ')
                WHERE record_id IN (' . $sql . ')';
        $stmt = $connbas->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
      }
      catch (Exception $e)
      {
        $this->log($e->getMessage());
      }
    }
    $this->recs_to_write = array();

    return $this;
  }

  public function retrieve_sbas_content(databox $databox)
  {
    $connbas = $databox->get_connection();

    $sql = 'SELECT coll_id, record_id
              FROM record
              WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0
                AND parent_record_id = 0
              ORDER BY record_id DESC LIMIT 0, 20';

    $stmt = $connbas->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    return $rs;
  }

  public function process_one_content(databox $databox, Array $row)
  {
    $record_id = $row['record_id'];
    $this->log(sprintf(
                    "Generate subdefs for :  sbas_id %s / record %s "
                    , $this->sbas_id, $record_id));
    $record = new record_adapter($this->sbas_id, $record_id);

    $record->generate_subdefs($databox, null, $this->debug);

    $this->recs_to_write[] = $record->get_record_id();

    if (count($this->recs_to_write) >= $this->record_buffer_size)
    {
      $this->flush_records_sbas();
    }
    unset($record);

    return $this;
  }

  protected function post_process_one_content(databox $databox, Array $row)
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

}

