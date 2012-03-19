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
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms("srt", "ord",
                "act",
                "p0", // base_id
                "str" // si act=CHGSTRUCTURE, structure en xml
);

$parm['p0'] = (int) $parm['p0'];

if ($parm['p0'] <= 0)
  phrasea::headers(400);

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if (!$user->ACL()->has_right_on_sbas($parm['p0'], 'bas_modify_struct'))
{
  phrasea::headers(403);
}


phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,skins/admin/admincolor.css" />
    <script type="text/javascript">
      function sizeTxtArea()
      {
        //alert(document.body.clientHeight);
        t = document.body.clientHeight;
        t= t*0.8;
        document.forms["chgStructure"].str.style.height = (t)+"px";
      }
      function saveStructure()
      {
        document.forms["chgStructure"].act.value = "CHGSTRUCTURE";
        document.forms["chgStructure"].target = "";
        document.forms["chgStructure"].submit();
      }
      function restoreStructure()
      {
        document.forms["chgStructure"].act.value = "";
        document.forms["chgStructure"].target = "";
        document.forms["chgStructure"].submit();
      }
    </script>
  </head>
  <body onResize="sizeTxtArea();" onLoad="sizeTxtArea();">


    <?php
    $out = "";
    $msg = "";
    $loadit = true;

    $out .= "<H4>" . p4string::MakeString(_('admin::base: structure')) . "</h4>\n";
    if ($parm["act"] == "CHGSTRUCTURE")
    {
      $domst = new DOMDocument('1.0', 'UTF-8');
      $domst->preserveWhiteSpace = false;
      $domst->formatOutput = true;

      $errors = databox::get_structure_errors($parm["str"]);
      if (count($errors) == 0 && $domst->loadXML($parm["str"])) // simplexml_load_string($parm["str"]))
      {
        $databox = databox::get_instance((int) $parm['p0']);
        $databox->saveStructure($domst);
      }
      else
      {
        $msg .= p4string::MakeString(_('admin::base: xml invalide, les changements ne seront pas appliques')."\n".implode("\n", $errors), 'js') . "";
        $loadit = false;
        $out .= "<div>" . implode("</div><div>", $errors) . "</div>";
        $out .= "<form method=\"post\" name=\"chgStructure\" action=\"./structure.php\" onsubmit=\"return(false);\" target=\"???\">\n";
        $out .= "  <input type=\"hidden\" name=\"act\" value=\"???\" />\n";
        $out .= "  <input type=\"hidden\" name=\"p0\" value=\"" . $parm["p0"] . "\" />\n";
        $out .= "  <TEXTAREA nowrap style=\"width:95%; height:450px; white-space:pre\" name=\"str\">" . p4string::MakeString($parm["str"], "form") . "</TEXTAREA>\n";
        $out .= "  <br/>\n";
        $out .= "</form>\n";
        $out .= "<br/>\n";
        $out .= "<br/>\n";
        $out .= "<center><a href=\"javascript:void(0);\" onclick=\"saveStructure();return(false);\">" . p4string::MakeString(_('boutton::valider')) . "</a></center>\n";
      }
      unset($domst);
    }
    else
    {
      $databox = databox::get_instance((int) $parm["p0"]);
      $parm["str"] = $databox->get_structure();
    }
    if ($loadit)
    {


      $errors = databox::get_structure_errors($parm["str"]);
      $out .= "<div>" . implode("</div><div>", $errors) . "</div>";
      $out .= "<form method=\"post\" name=\"chgStructure\" action=\"./structure.php\" onsubmit=\"return(false);\" target=\"???\">\n";
      $out .= "  <input type=\"hidden\" name=\"act\" value=\"???\" />\n";
      $out .= "  <input type=\"hidden\" name=\"p0\" value=\"" . $parm["p0"] . "\" />\n";
      $out .= "  <TEXTAREA nowrap style=\"width:95%; height:450px; white-space:pre\" name=\"str\">" . p4string::MakeString($parm["str"], "form") . "</TEXTAREA>\n";
      $out .= "  <br/>\n";
      $out .= "</form>\n";
      $out .= "<br/>\n";
      $out .= "<br/>\n";
      $out .= "<center><a href=\"javascript:void(0);\" onclick=\"saveStructure();return(false);\">" . p4string::MakeString(_('boutton::valider')) . "</a></center>\n";
    }

    print($out);
    ?>
    <script type="text/javascript">
<?php
    if ($msg)
      printf("alert(\"%s \");", $msg);
?>
    </script>
  </body>
</html>
