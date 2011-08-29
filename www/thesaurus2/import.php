<?php
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
header("Content-Type: text/html; charset=UTF-8");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
        "bid"
        , 'piv'
        , "id"
        , 't'
        , 'dlg'
);

set_time_limit(300);

$imported = false;
$err = '';

if ($parm["bid"] !== null)
{
  $loaded = false;
  $connbas = connection::getInstance($parm['bid']);
  if ($connbas)
  {
    $sql = "SELECT value AS xml FROM pref WHERE prop='thesaurus'";
    if ($rsbas = $connbas->query($sql))
    {
      if ($rowbas = $connbas->fetch_assoc($rsbas))
      {
        $xml = trim($rowbas["xml"]);

        $dom = new DOMDocument();
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        if (($dom->loadXML($xml)))
        {
          $err = '';
          if ($parm['id'] == '')
          {
            // on importe un thï¿½saurus entier
            $node = $dom->documentElement;
            while ($node->firstChild)
              $node->removeChild($node->firstChild);

            $err = importFile($dom, $node);
          }
          else
          {
            // on importe dans une branche
            $err = 'not implemented';
          }

          if (!$err)
          {
            $imported = true;
            $dom->documentElement->setAttribute('modification_date', date("YmdHis"));

            $sql = 'UPDATE pref SET value=\'' . $connbas->escape_string($dom->saveXML()) . '\' WHERE prop="thesaurus"';
            $connbas->query($sql);

            $cache_abox = cache_appbox::getInstance();
            $cache_abox->delete('thesaurus_' . $parm['bid']);
          }
        }
      }
      $connbas->free_result($rsbas);
    }

    if (!$err)
    {
      $sql = 'SELECT value AS struct FROM pref WHERE prop="structure"';
      if (($rsbas = $connbas->query($sql)))
      {
        if (($rowbas = $connbas->fetch_assoc($rsbas)))
        {
          if (($dom = @DOMDocument::loadXML($rowbas["struct"])))
          {
            $xp = new DOMXPath($dom);
            $fields = $xp->query("/record/description/*");
            for ($i = 0; $i < $fields->length; $i++)
            {
              $fields->item($i)->removeAttribute('tbranch');
              $fields->item($i)->removeAttribute('newterm');
            }
            $dom->documentElement->setAttribute('modification_date', date("YmdHis"));

            $sql = 'UPDATE pref SET value=\'' . $connbas->escape_string($dom->saveXML()) . '\' WHERE prop="structure"';
            $connbas->query($sql);

            $cache_appbox = cache_appbox::getInstance();
            $cache_appbox->delete('list_bases');
            cache_databox::update($parm['bid'], 'structure');
          }
        }
        $connbas->free_result($rsbas);
      }

      $sql = 'SELECT value AS cterms FROM pref WHERE prop="cterms"';
      if (($rsbas = $connbas->query($sql)))
      {
        if (($rowbas = $connbas->fetch_assoc($rsbas)))
        {
          $dom = new DOMDocument();
          $dom->formatOutput = true;
          $dom->preserveWhiteSpace = false;
          if (($dom->loadXML($rowbas["cterms"])))
          {
            $node = $dom->documentElement;
            while ($node->firstChild)
              $node->removeChild($node->firstChild);
            $dom->documentElement->setAttribute('modification_date', date("YmdHis"));

            $sql = 'UPDATE pref SET value=\'' . $connbas->escape_string($dom->saveXML()) . '\' WHERE prop="cterms"';
            $connbas->query($sql);
          }
        }
        $connbas->free_result($rsbas);
      }

      // flag records as 'to reindex'
      $sql = 'UPDATE RECORD SET status=status & ~3';
      $connbas->query($sql);
    }
  }
}

if ($parm["dlg"])
{
  $opener = "window.dialogArguments.win";
}
else
{
  $opener = "opener";
}
?>

<html lang="<?php echo $session->usr_i18n; ?>">
  <body onload='parent.importDone("<?php print(p4string::MakeString($err, 'js')); ?>");'>
  </body>
</html>

<?php

function checkEncoding($string, $string_encoding)
{
  $fs = $string_encoding == 'UTF-8' ? 'UTF-32' : $string_encoding;

  $ts = $string_encoding == 'UTF-32' ? 'UTF-8' : $string_encoding;

  return $string === mb_convert_encoding(mb_convert_encoding($string, $fs, $ts), $ts, $fs);
}

function importFile($dom, $node)
{
  global $parm;
  $err = '';

  $cbad = array();
  $cok = array();
  for ($i = 0; $i < 32; $i++)
  {
    $cbad[] = chr($i);
    $cok[] = '_';
  }

  if (($fp = fopen($_FILES['fil']['tmp_name'], 'rb')))
  {
    $iline = 0;
    $curdepth = -1;
    $tid = array(-1 => -1, 0 => -1);
    while (!$err && !feof($fp) && ($line = fgets($fp)) !== FALSE)
    {
      $iline++;
      if (trim($line) == '')
        continue;
      for ($depth = 0; $line != '' && $line[0] == "\t"; $depth++)
        $line = substr($line, 1);
      if ($depth > $curdepth + 1)
      {
        $err = sprintf(_("over-indent at line %s"), $iline);
        continue;
      }

      $line = trim($line);

      if (!checkEncoding($line, 'UTF-8'))
      {
        $err = sprintf(_("bad encoding at line %s"), $iline);
        continue;
      }

      $line = str_replace($cbad, $cok, ($oldline = $line));
      if ($line != $oldline)
      {
        $err = sprintf(_("bad character at line %s"), $iline);
        continue;
      }

      while ($curdepth >= $depth)
      {
        $curdepth--;
        $node = $node->parentNode;
      }
      $curdepth = $depth;

      $nid = (int) ($node->getAttribute('nextid'));
      $id = $node->getAttribute('id') . '.' . $nid;
      $pid = $node->getAttribute('id');

      $te_id = ($pid ? ($pid . '.') : 'T') . $nid;

      $node->setAttribute('nextid', (string) ($nid + 1));

      $te = $node->appendChild($dom->createElement('te'));
      $te->setAttribute('id', $te_id);

      $node = $te;

      $tsy = explode(';', $line);
      $nsy = 0;
      foreach ($tsy as $syn)
      {
        $lng = $parm['piv'];
        $hit = '';
        $kon = '';

        if (($ob = strpos($syn, '[')) !== false)
        {
          if (($cb = strpos($syn, ']', $ob)) !== false)
          {
            $lng = trim(substr($syn, $ob + 1, $cb - $ob - 1));
            $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
          }
          else
          {
            $lng = trim(substr($syn, $ob + 1));
            $syn = substr($syn, 0, $ob);
          }

          if (($ob = strpos($syn, '[')) !== false)
          {
            if (($cb = strpos($syn, ']', $ob)) !== false)
            {
              $hit = trim(substr($syn, $ob + 1, $cb - $ob - 1));
              $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
            }
            else
            {
              $hit = trim(substr($syn, $ob + 1));
              $syn = substr($syn, 0, $ob);
            }
          }
        }
        if (($ob = strpos($syn, '(')) !== false)
        {
          if (($cb = strpos($syn, ')', $ob)) !== false)
          {
            $kon = trim(substr($syn, $ob + 1, $cb - $ob - 1));
            $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
          }
          else
          {
            $kon = trim(substr($syn, $ob + 1));
            $syn = substr($syn, 0, $ob);
          }
        }

        $syn = trim($syn);

        $sy = $node->appendChild($dom->createElement('sy'));
        $sy->setAttribute('id', $te_id . '.' . $nsy);
        $v = $syn;
        if ($kon)
          $v .= ' (' . $kon . ')';
        $sy->setAttribute('v', $v);
        $sy->setAttribute('w', noaccent_utf8($syn, PARSED));
        if ($kon)
          $sy->setAttribute('k', noaccent_utf8($kon, PARSED));

        $sy->setAttribute('lng', $lng);

        $nsy++;
      }

      $te->setAttribute('nextid', (string) $nsy);
    }

    fclose($fp);
  }
  return($err);
}

function no_dof($dom, $node)
{
  global $parm;

  $t = $parm['t'];


  $t = preg_replace('/\\r|\\n/', '£', $t);
  $t = preg_replace('/££*/', '£', $t);
  $t = preg_replace('/£\\s*;/', ' ;', $t);
  $tlig = explode('£', $t);

  $mindepth = 999999;
  foreach ($tlig as $lig)
  {
//		echo('.');
//		flush();

    if (trim($lig) == '')
      continue;
    for ($depth = 0; $lig != '' && $lig[$depth] == "\t"; $depth++)
      ;
    if ($depth < $mindepth)
      $mindepth = $depth;
  }

  $curdepth = -1;
  $tid = array(-1 => -1, 0 => -1);
  foreach ($tlig as $lig)
  {
//		echo('-');
//		flush();

    $lig = substr($lig, $mindepth);
    if (trim($lig) == '')
      continue;
    for ($depth = 0; $lig != '' && $lig[0] == "\t"; $depth++)
      $lig = substr($lig, 1);

//		printf("curdepth=%s, depth=%s : %s\n", $curdepth, $depth, $lig);

    if ($depth > $curdepth + 1)
    {
      // error
//			print('<span style="color:#ff0000">error over-indent at</span> \'' . $lig . "'\n");
      continue;
    }

    while ($curdepth >= $depth)
    {
      $curdepth--;
      $node = $node->parentNode;
    }
    $curdepth = $depth;

    $nid = (int) ($node->getAttribute('nextid'));
    $id = $node->getAttribute('id') . '.' . $nid;
    $pid = $node->getAttribute('id');

// print("pid=".$pid);

    $te_id = ($pid ? ($pid . '.') : 'T') . $nid;

    $node->setAttribute('nextid', (string) ($nid + 1));

    $te = $node->appendChild($dom->createElement('te'));
    $te->setAttribute('id', $te_id);

    $node = $te;

    $tsy = explode(';', $lig);
    $nsy = 0;
    foreach ($tsy as $syn)
    {
      $lng = $parm['piv'];
      $hit = '';
      $kon = '';

      if (($ob = strpos($syn, '[')) !== false)
      {
        if (($cb = strpos($syn, ']', $ob)) !== false)
        {
          $lng = trim(substr($syn, $ob + 1, $cb - $ob - 1));
          $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
        }
        else
        {
          $lng = trim(substr($syn, $ob + 1));
          $syn = substr($syn, 0, $ob);
        }

        if (($ob = strpos($syn, '[')) !== false)
        {
          if (($cb = strpos($syn, ']', $ob)) !== false)
          {
            $hit = trim(substr($syn, $ob + 1, $cb - $ob - 1));
            $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
          }
          else
          {
            $hit = trim(substr($syn, $ob + 1));
            $syn = substr($syn, 0, $ob);
          }
        }
      }
      if (($ob = strpos($syn, '(')) !== false)
      {
        if (($cb = strpos($syn, ')', $ob)) !== false)
        {
          $kon = trim(substr($syn, $ob + 1, $cb - $ob - 1));
          $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
        }
        else
        {
          $kon = trim(substr($syn, $ob + 1));
          $syn = substr($syn, 0, $ob);
        }
      }
      /* 			
       */
      $syn = trim($syn);

      //		for($id='T',$i=0; $i<=$curdepth; $i++)
      //			$id .= '.' . $tid[$i];
//	$id = '?';
//			printf("depth=%s (%s) ; sy='%s', kon='%s', lng='%s', hit='%s' \n", $depth, $id, $syn, $kon, $lng, $hit);

      /* 			
        $nid = (int)($node->getAttribute('nextid'));
        $pid = $node->getAttribute('id');

        $id = ($pid ? ($pid.'.'):'T') . $nid ;
        $node->setAttribute('nextid', (string)($nid+1));

       */
      $sy = $node->appendChild($dom->createElement('sy'));
      $sy->setAttribute('id', $te_id . '.' . $nsy);
      $v = $syn;
      if ($kon)
        $v .= ' (' . $kon . ')';
      $sy->setAttribute('v', $v);
      $sy->setAttribute('w', noaccent_utf8($syn, PARSED));
      if ($kon)
        $sy->setAttribute('k', noaccent_utf8($kon, PARSED));

      $sy->setAttribute('lng', $lng);

      $nsy++;
    }

    $te->setAttribute('nextid', (string) $nsy);
  }
}
?>