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
class task_period_writemeta extends task_databoxAbstract
{

  protected $clear_doc;
  protected $metasubdefs = array();

  function help()
  {
    return(_("task::writemeta:(re)ecriture des metadatas dans les documents (et subdefs concernees)"));
  }

  protected function load_settings(SimpleXMLElement $sx_task_settings)
  {
    $this->clear_doc = p4field::isyes($sx_task_settings->cleardoc);
    parent::load_settings($sx_task_settings);

    return $this;
  }

  public function getName()
  {
    return(_('task::writemeta:ecriture des metadatas'));
  }

  public function graphic2xml($oldxml)
  {
    $request = http_request::getInstance();

    $parm2 = $request->get_parms(
                    "period"
                    , 'cleardoc'
                    , 'maxrecs'
                    , 'maxmegs'
    );
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if ($dom->loadXML($oldxml))
    {
      $xmlchanged = false;
      foreach (array("str:period", 'str:maxrecs', 'str:maxmegs', 'boo:cleardoc') as $pname)
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
          // le champ n'existait pas dans le xml, on le cree
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

  public function xml2graphic($xml, $form)
  {
    if (($sxml = simplexml_load_string($xml))) // in fact XML IS always valid here...
    {
      // ... but we could check for safe values (ex. 0 < period < 3600)
      if ((int) ($sxml->period) < 10)
        $sxml->period = 10;
      elseif ((int) ($sxml->period) > 300)
        $sxml->period = 300;

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
<?php echo $form ?>.period.value        = "<?php echo p4string::MakeString($sxml->period, "js", '"') ?>";
<?php echo $form ?>.cleardoc.checked    = <?php echo p4field::isyes($sxml->cleardoc) ? "true" : 'false' ?>;
<?php echo $form ?>.maxrecs.value       = "<?php echo p4string::MakeString($sxml->maxrecs, "js", '"') ?>";
<?php echo $form ?>.maxmegs.value       = "<?php echo p4string::MakeString($sxml->maxmegs, "js", '"') ?>";
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

  public function printInterfaceJS()
  {
?>
    <script type="text/javascript">
      function chgxmltxt(textinput, fieldname)
      {
        var limits = { 'period':{min:1, 'max':300} , 'maxrecs':{min:10, 'max':1000} , 'maxmegs':{min:2, 'max':100} } ;
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

  function getGraphicForm()
  {
    return true;
  }

  public function printInterfaceHTML()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $session = $appbox->get_session();
    $sbas_ids = User_Adapter::getInstance($session->get_usr_id(), $appbox)
                    ->ACL()->get_granted_sbas(array('bas_manage'));

    ob_start();
    if (count($sbas_ids) > 0)
    {
?>
      <form name="graphicForm" onsubmit="return(false);" method="post">
        <br/>
<?php echo _('task::_common_:periodicite de la tache') ?>&nbsp;:&nbsp;
        <input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">
  <?php echo _('task::_common_:secondes (unite temporelle)') ?><br/>
      <br/>
      <input type="checkbox" name="cleardoc" onchange="chgxmlck(this)">
<?php echo _('task::writemeta:effacer les metadatas non presentes dans la structure') ?>
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
    }
    $out = ob_get_clean();

    return $out;
  }

  protected function retrieve_sbas_content(databox $databox)
  {
    $connbas = $databox->get_connection();
    $subdefgroups = $databox->get_subdef_structure();
    $metasubdefs = array();

    foreach ($subdefgroups as $type => $subdefs)
    {
      foreach ($subdefs as $sub)
      {
        $name = $sub->get_name();
        if ($sub->meta_writeable())
          $metasubdefs[$name . '_' . $type] = true;
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

  protected function format_value($type, $value)
  {
    if ($type == 'date')
    {
      $value = str_replace(array("-", ":", "/", "."), array(" ", " ", " ", " "), $value);
      $ip_date_yyyy = 0;
      $ip_date_mm = 0;
      $ip_date_dd = 0;
      $ip_date_hh = 0;
      $ip_date_nn = 0;
      $ip_date_ss = 0;
      switch (sscanf($value, "%d %d %d %d %d %d", $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss))
      {
        case 1:
          $value = sprintf("%04d:00:00", $ip_date_yyyy);
          break;
        case 2:
          $value = sprintf("%04d:%02d:00", $ip_date_yyyy, $ip_date_mm);
          break;
        case 3:
        case 4:
        case 5:
        case 6:
          $value = sprintf("%04d:%02d:%02d", $ip_date_yyyy, $ip_date_mm, $ip_date_dd);
          break;
        default:
          $value = '0000:00:00';
      }
    }

    return $value;
  }

  protected function process_one_content(databox $databox, Array $row)
  {
    $coll_id = $row['coll_id'];
    $record_id = $row['record_id'];
    $jeton = $row['jeton'];

    $record = new record_adapter($this->sbas_id, $record_id);

    $type = $record->get_type();
    $subdefs = $record->get_subdefs();

    $tsub = array();

    foreach ($subdefs as $name => $subdef)
    {
      $write_document = (($jeton & JETON_WRITE_META_DOC) && $name == 'document');
      $write_subdef = (($jeton & JETON_WRITE_META_SUBDEF) && isset($this->metasubdefs[$name . '_' . $type]));

      if ($write_document || $write_subdef)
      {
        $tsub[$name] = $subdef->get_pathfile();
      }
    }

    $registry = $databox->get_registry();

    $fields = $record->get_caption()->get_fields();

    $subCMD = '';

    if ($record->get_uuid())
    {
      $subCMD .= '-XMP-exif:ImageUniqueID=';
      $subCMD .= escapeshellarg($record->get_uuid());
      $subCMD .= '-IPTC:UniqueDocumentID=';
      $subCMD .= escapeshellarg($record->get_uuid());
    }

    foreach ($fields as $field)
    {
      $meta = $field->get_databox_field();

      if (trim($meta->get_metadata_source()) === '')
        continue;

      $multi = $meta->is_multi();
      $type = $meta->get_type();
      $datas = $field->get_values();

      if ($multi)
      {
        foreach ($datas as $value)
        {
          $value = $this->format_value($type, $value->getValue());

          $subCMD .= '-'.$meta->get_metadata_namespace().':'.$meta->get_metadata_tagname().'=';
          $subCMD .= escapeshellarg($value).' ';
        }
      }
      else
      {
        $value = array_pop($datas);
        $datas = $this->format_value($type, $value->getValue());

        $subCMD .= '-'.$meta->get_metadata_namespace().':'.$meta->get_metadata_tagname().'=';
        $subCMD .= escapeshellarg($datas).' ';
      }
    }

    foreach ($tsub as $name => $file)
    {
      $cmd = '';
      if ($this->system == 'WINDOWS')
        $cmd = 'start /B /LOW ';
      $cmd .= ( $registry->get('GV_exiftool') . ' -m -overwrite_original ');
      if ($name != 'document' || $this->clear_doc)
        $cmd .= '-all:all= ';

      $cmd .= ' -codedcharacterset=utf8 ';

      $cmd .= $subCMD.' '.escapeshellarg($file);

      $this->log(sprintf(('writing meta for sbas_id=%1$d - record_id=%2$d (%3$s)'), $this->sbas_id, $record_id, $name));

      $s = trim(shell_exec($cmd));

      $this->log("\t" . $s);
    }

    return $this;
  }

  protected function flush_records_sbas()
  {
    return $this;
  }

  protected function post_process_one_content(databox $databox, Array $row)
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
