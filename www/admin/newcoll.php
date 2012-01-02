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
$appbox = appbox::get_instance();
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms("act",
                "p0", // base_id
                "cnm", // si act=NEWCOLL, nom de la collection a creer
                "othcollsel",
                "ccusrothercoll"
);

$usr_id = $session->get_usr_id();
$user = User_Adapter::getInstance($usr_id, $appbox);


$msg = "";

phrasea::headers();

$sbasid = null;
$error = false;

if (trim($parm["cnm"]) == '' && $parm["act"] == "NEWCOLL")
  $error = _('admin:: La collection n\'a pas ete creee : vous devez donner un nom a votre collection');

if ($parm["act"] == "NEWCOLL" && !$error)
{

  try
  {
    $databox = $appbox->get_databox((int) $parm['p0']);
    $collection = collection::create($databox, $appbox, $parm['cnm'], $user);
    if ($collection && $parm["ccusrothercoll"] == "on" && $parm["othcollsel"] != null)
    {
      $query = new User_Query($appbox);
      $total = $query->on_base_ids(array($parm["othcollsel"]))->get_total();
      $n = 0;
      while($n < $total)
      {
        $results = $query->limit($n, 20)->execute()->get_results();
        foreach($results as $user)
        {
          $user->ACL()->duplicate_right_from_bas($parm["othcollsel"], $collection->get_base_id());
        }
        $n+=20;
      }
    }
  }
  catch (Exception $e)
  {
    $collection = false;
  }
}
?>

<html lang="<?php echo $session->get_I18n(); ?>">
  <head>

    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,skins/admin/admincolor.css" />
    <script type="text/javascript">
      function evt_submit()
      {
        return(true);
      }
      function saveColl()
      {
        document.forms["newColl"].target = "right";
        document.forms["newColl"].submit();
      }


      function clk_cc_coll()
      {
        if( document.getElementById("ccusrothercoll") )
        {
          if( document.getElementById("ccusrothercoll").checked )
          {
            // idspanothsel
            if( document.getElementById("idspanothsel") )
              document.getElementById("idspanothsel").style.color = "#000000";
            // othcollsel
            if( document.getElementById("othcollsel") )
              document.getElementById("othcollsel").disabled = false;
          }
          else
          {
            // idspanothsel
            if( document.getElementById("idspanothsel") )
              document.getElementById("idspanothsel").style.color = "#AAAAAA";
            // othcollsel
            if( document.getElementById("othcollsel") )
              document.getElementById("othcollsel").disabled = true;
          }
        }
      }
    </script>
  </head>
  <body>
    <?php
    $out = "";
    $out .= "<h4>" . _('admin::base:collection: Creer une collection') . "</h4>";

    if ($parm["act"] == "NEWCOLL")
    {
      $out .= $msg;
    }
    else
    {
      $out .= "<br>";
      $out .= "<br>";
      $out .= "<br>";
    }

    if ($error)
      $out .= "<div style='color:red;'>" . $error . "</div>";

    $out .= "  <form method=\"post\" name=\"newColl\" action=\"./newcoll.php\" onsubmit=\"return(false);\">\n";
    $out .= "  <input type=\"hidden\" name=\"act\" value=\"NEWCOLL\" />\n";
    $out .= "  <input type=\"hidden\" name=\"p0\" value=\"" . $parm["p0"] . "\" />\n";
    $out .= "  <center>\n";
    $out .= "    " . _('admin::base:collection: Nom de la nouvelle collection : ') . "<input type=\"text\" name=\"cnm\" value=\"\" /><br /><br />\n";
    $out .= "<br>";

    $user = User_Adapter::getInstance($usr_id, $appbox);

    $colls = $user->ACL()->get_granted_base(array('canadmin'));
    if (count($colls) > 0)
    {
      $out .= "<small>";
      $out .= "<input type=\"checkbox\" id=\"ccusrothercoll\" name=\"ccusrothercoll\" onclick=\"clk_cc_coll();\">";
      $out .= "<span id=\"idspanothsel\" style=\"color:#AAAAAA\">" . _('admin::base:collection: Vous pouvez choisir une collection de reference pour donenr des acces ') . " : </span>";
      $out .= "<select disabled id=\"othcollsel\" name=\"othcollsel\" style=\"font-size:9px\">";
      foreach ($colls as $collection)
        $out .= "<option  value=\"" . $collection->get_base_id() . "\">" . $collection->get_name() . "</option>";
      $out .= "</select>";
      $out .= "</small>";
    }
    $out .= "<br>";
    $out .= "<br>";

    $out .= "    <a href=\"javascript:void(0);\" onclick=\"saveColl();return(false);\">" . _('boutton::valider') . "</a>\n";
    $out .= " ";
    $out .= "    <a href='database.php?p0=" . $parm['p0'] . "'>" . _('boutton::annuler') . "</a>\n";
    $out .= "  </center>\n";
    $out .= "</form>\n";

    print($out);
    ?>
    <script type="text/javascript">
<?php
    if ($parm["act"] == "NEWCOLL")
    {
      print("parent.reloadTree('base:" . $parm['p0'] . "');");
    }
?>
    </script>
  </body>
</html>
