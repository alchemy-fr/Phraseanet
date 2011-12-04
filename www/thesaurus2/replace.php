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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
phrasea::headers(200, true);
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();
require($registry->get('GV_RootPath') . "www/thesaurus2/xmlhttp.php");

$request = http_request::getInstance();
$parm = $request->get_parms(
                "bid"
                , "piv"
                , "pid"  // id du pere (te)
                , "id"  // id du synonyme (sy)
                , "typ"
                , "dlg"
);

if ($parm["dlg"])
{
  $opener = "window.dialogArguments.win";
}
else
{
  $opener = "opener";
}

$url = "thesaurus2/xmlhttp/getsy.x.php";
$url .= "?bid=" . urlencode($parm["bid"]);
$url .= "&piv=" . urlencode($parm["piv"]);
$url .= "&sortsy=0";
$url .= "&id=" . urlencode($parm["id"]);
$url .= "&typ=" . urlencode($parm["typ"]);

$dom = xmlhttp($url);
$fullpath = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
$zterm = $dom->getElementsByTagName("sy")->item(0)->getAttribute("t");
$hits = $dom->getElementsByTagName("hits")->item(0)->firstChild->nodeValue;
?>

<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <title>Corriger...</title>

    <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
    <style type="text/css">
      a
      {
        text-decoration:none;
        font-size: 10px;
      }
      .path_separator
      {
        color:#ffff00;
      }
      .main_term
      {
        font-weight:900;
        xcolor:#ff0000;
      }
    </style>

    <script type="text/javascript" src="./xmlhttp.js"></script>
    <script type="text/javascript">
      function loaded()
      {
        window.name="REPLACE";
        self.focus();
        ckField();
      }
      function ckField()
      {
        fields = document.getElementsByName("field[]");
        chk = false;
        for(i=0; i<fields.length && !chk; i++)
        {
          if( fields[i].checked )
            chk = true;
        }
        // document.getElementById("submit_button").disabled = (!chk) || (document.forms[0].rpl.value==document.forms[0].src.value);
        document.getElementById("submit_button").disabled = (document.forms[0].rpl.value==document.forms[0].src.value);
        document.getElementById("rplrec").disabled = !chk;
        document.getElementById("rplrec").checked = chk;

        return(true);
      }
      function clkBut(button)
      {
        switch(button)
        {
          case "submit":
            // document.forms[0].target="LINKFIELD";
            document.forms[0].submit();
            break;
          case "cancel":
            self.close();
            break;
        }
      }
      function clkRepl()
      {
        var o;
        if(!(o=document.getElementById("rplrec")).checked)
        {
          fields = document.getElementsByName("field[]");
          for(i=0; i<fields.length; i++)
            fields[i].checked = false;
          o.disabled = true;
        }
      }
    </script>
  </head>
  <body onload="loaded();" class="dialog">
    <div style='text-align:right'><b>id:</b>&nbsp;<?php echo $parm["id"] ?></div>
    <H4><?php echo $fullpath ?></H4><br/><br/>
<?php
// printf("present dans %s fiche(s).<br/>\n", $dom->getElementsByTagName("hits")->item(0)->firstChild->nodeValue );

if ($parm["typ"] == "TH")
{
  $loaded = false;
  try
  {
    $databox = databox::get_instance((int) $parm['bid']);
    $domstruct = $databox->get_dom_structure();
    $domth = $databox->get_dom_thesaurus();

    if ($domstruct && $domth)
    {
      $xpathth = new DOMXPath($domth);
      $xpathstruct = new DOMXPath($domstruct);
?>
      <center>
        <form action="replace2.php" method="post" target="REPLACE">
          <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>">
          <input type="hidden" name="piv" value="<?php echo $parm["piv"] ?>">
          <input type="hidden" name="dlg" value="<?php echo $parm["dlg"] ?>">
          <input type="hidden" name="pid" value="<?php echo $parm["pid"] ?>">
          <input type="hidden" name="id"  value="<?php echo $parm["id"] ?>">
<?php echo utf8_encode("Corriger le terme") ?>
          <b><?php echo $zterm ?></b><input type="hidden" name="src" value="<?php echo p4string::MakeString($zterm, "js") ?>">
<?php echo utf8_encode("par : ") ?><input type="text" name="rpl" style="width:150px;" onkeyup="ckField();return(true);" value="<?php echo p4string::MakeString($zterm, "js") ?>">
        <br/>
        <br/>
        <input type="checkbox" id="rplrec" name="rplrec" onclick="clkRepl();return(true);" disabled>
        <label for="rplrec"><?php echo utf8_encode("et corriger egalement dans le champ :") ?></label>
        <br/>
        <br/>
        <div style="width:70%; height:110px; overflow:scroll;" class="x3Dbox">
<?php
        $fields = $xpathstruct->query("/record/description/*");
        for ($i = 0; $i < $fields->length; $i++)
        {
          $fieldname = $fields->item($i)->nodeName;
          $tbranch = $fields->item($i)->getAttribute("tbranch");
          $ck = "";
          if ($tbranch)
          {
            // ce champ a un tbranch, est-ce qu'il permet d'atteindre le terme selectionne ?
            $branches = $xpathth->query($tbranch);
            for ($j = 0; $j < $branches->length; $j++)
            {
              $q = ".//sy[@id='" . $parm["id"] . "']";
              // printf("searching %s against id=%s<br/>\n", $q, $branches->item($j)->getAttribute("id"));
              if ($xpathth->query($q, $branches->item($j))->length > 0)
              {
                // oui
                $ck = true;
              }
            }
          }
          if ($ck)
          {
            printf("\t\t<input type=\"radio\" name=\"field[]\" value=\"%s\" onclick=\"return(ckField());\"><b>%s</b><br/>\n"
                    , $fieldname, $fieldname);
          }
          else
          {
            printf("\t\t<input type=\"radio\" name=\"field[]\" value=\"%s\" onclick=\"return(ckField());\">%s<br/>\n"
                    , $fieldname, $fieldname);
          }
        }
?>
        </div>
        <br/>
        <input type="button" id="cancel_button" value="Annuler" onclick="clkBut('cancel');" style="width:80px;">
        &nbsp;&nbsp;&nbsp;
        <input type="button" id="submit_button" value="Corriger" onclick="clkBut('submit');" style="width:80px;">
      </form>
    </center>
<?php
      }
    }
    catch (Exception $e)
    {

    }
  }
?>
  </body>
</html>
