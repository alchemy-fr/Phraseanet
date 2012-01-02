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
require_once __DIR__ . "/../../lib/bootstrap.php";

phrasea::headers(200, true);
$appbox = appbox::get_instance();
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms(
                "bid"
                , "piv"
                , "tid"
);
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <title><?php echo p4string::MakeString(_('thesaurus:: Lier la branche de thesaurus au champ')) ?></title>

    <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />

    <script type="text/javascript">
      function ckField()
      {
        fields = document.getElementsByName("field[]");
        changed = false;
        for(i=0; i<fields.length && !changed; i++)
        {
          if( (fields[i].checked?"1":"0") != fields[i].ck0)
            changed = true;
        }
        document.getElementById("submit_button").disabled = !changed;

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
      function loaded()
      {
        window.name="LINKFIELD";
        ckField();
      }
    </script>
  </head>
  <body onload="loaded();" class="dialog">
<?php
if ($parm["bid"] !== null)
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

      if ($parm["tid"] !== "")
        $q = "//te[@id='" . $parm["tid"] . "']";
      else
        $q = "//te[not(@id)]";
      $nodes = $xpathth->query($q);
      $fullBranch = "";
      if ($nodes->length == 1)
      {
        for ($n = $nodes->item(0); $n && $n->nodeType == XML_ELEMENT_NODE && $n->getAttribute("id") !== ""; $n = $n->parentNode)
        {
          $sy = $xpathth->query("sy", $n)->item(0);
          $sy = $sy ? $sy->getAttribute("v") : "";
          if (!$sy)
            $sy = $sy = "...";
          $fullBranch = " / " . $sy . $fullBranch;
        }
      }
?>
      <center>
        <form action="linkfield2.php" method="post" target="LINKFIELD">
          <input type="hidden" name="piv" value="<?php echo $parm["piv"] ?>">
          <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>">
          <input type="hidden" name="tid" value="<?php echo $parm["tid"] ?>">
<?php
      $fbhtml = "<br/><b>" . $fullBranch . "</b><br/>";
      printf(_('thesaurus:: Lier la branche de thesaurus au champ %s'), $fbhtml);
?>
        <div style="width:70%; height:200px; overflow:scroll;" class="x3Dbox">
<?php
      $nodes = $xpathstruct->query("/record/description/*");
      for ($i = 0; $i < $nodes->length; $i++)
      {
        $fieldname = $nodes->item($i)->nodeName;
        $tbranch = $nodes->item($i)->getAttribute("tbranch");
        $ck = "";
        if ($tbranch)
        {
          // ce champ a deje un tbranch, est-ce qu'il pointe sur la branche selectionnee ?
          $thnodes = $xpathth->query($tbranch);
          for ($j = 0; $j < $thnodes->length; $j++)
          {
            if ($thnodes->item($j)->getAttribute("id") == $parm["tid"])
            {
              $ck = "checked";
            }
          }
        }
        printf("\t\t<input type=\"checkbox\" name=\"field[]\" value=\"%s\" %s ck0=\"%s\" onclick=\"return(ckField());\">%s<br/>\n"
                , $fieldname, $ck, $ck ? "1" : "0", $fieldname);
      }
?>
        </div>
        <br/>
        <input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" onclick="clkBut('submit');">
        &nbsp;&nbsp;&nbsp;
        <input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');">
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
