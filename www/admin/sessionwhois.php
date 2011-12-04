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
$appbox = appbox::get_instance();
$session = $appbox->get_session();
?>
<style type="text/css">
  #tooltip{
    position:absolute;
  }
</style>
<script type="text/javascript">
  $(document).ready(
  function(){
    $('.usrTips').tooltip();
  }
);
</script>
<div style="margin:20px 0;text-align:center;">
  <div style="font-size:14px"><u><b><?php echo _('admin::utilisateurs: utilisateurs connectes') ?> - <?php echo date("G:i:s") ?></b></u></div>
</div>

<?php
$appLaunched = array(
    '0' => 0
    , '1' => 0
    , '2' => 0
    , '3' => 0
    , '4' => 0
    , '5' => 0
    , '6' => 0
    , '7' => 0
    , '8' => 0
);

$out = "";


$out.="<center>\n";

$out.="<table class='admintable' >\n";
$out.="  <thead><tr>\n";
$out.="    <th style=\"width:140px;\" >" . _('admin::monitor: utilisateur') . "</th>";
$out.="    <th style=\"width:100px;\" >" . _('admin::monitor: modules') . "</th>";
$out.="    <th style=\"width:120px;\" >" . _('phraseanet:: adresse') . "</th>";
$out.="    <th style=\"width:140px;\" >" . _('admin::monitor: date de connexion') . "</th>";
$out.="    <th style=\"width:140px;\" >" . _('admin::monitor: dernier access') . "</th>";
$out.="  </tr>
        </thead><tbody>";

$n = 0;

foreach (Session_Handler::get_active_sessions() as $row)
{
  $onedetail = "<span style=\"position:relative; top:0px;left:0px;\">";
  $onedetail.="  <table cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout:fixed;width:300px; border:#000000 1px solid;\" id=\"tabledescexp\" >";
  $onedetail.="    <tr class=\"noborder\" style=\"border:0px\">";
  $onedetail.="      <td class=\"noborder\" style=\"border:0px;width:160px;\" valign=\"center\" />";
  $onedetail.="      <td class=\"noborder\" style=\"border:0px;width:200px;\" valign=\"center\" />";
  $onedetail.="    </tr>";
  $onedetail.="    <tr style=\"border:0px\">";
  $onedetail.="      <td  colspan=\"2\" class=\"noborder\" style=\"height:20px;text-align:center;background-color:#666666; color:#FFFFFF;font-size:12px\" valign=\"center\" >usr_id : <b>" . $row['usr_id']->get_id() . "</b></td>";
  $onedetail.="    </tr>";

  $onedetail.="    <tr style=\"border:0px\">";
  $onedetail.="      <td   class=\"noborder\" style=\"border:0px;\" valign=\"top\" />";
  $onedetail.="        <table class=\"noborder\" valign=\"top\" >";
  $onedetail.="          <tr  class=\"noborder\" >";
  $onedetail.="            <td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >" . _('admin::compte-utilisateur nom') . ' : ' . $row['usr_id']->get_display_name() . "</td>";
  $onedetail.="          </tr>";
  $onedetail.="          <tr  class=\"noborder\" >";
  $onedetail.="            <td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >" . _('admin::compte-utilisateur societe') . ' : ' . $row['usr_id']->get_company() . "</td>";
  $onedetail.="          </tr>";
  $onedetail.="          <tr  class=\"noborder\" >";
  $onedetail.="            <td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >" . _('admin::compte-utilisateur telephone') . ' : ' . $row['usr_id']->get_tel() . "</td>";
  $onedetail.="          </tr>";
  $onedetail.="          <tr  class=\"noborder\" >";
  $onedetail.="            <td  class=\"noborder\"class=\"noborder\" style=\"text-align:left\" >" . _('admin::compte-utilisateur email') . ' : ' . $row['usr_id']->get_email() . "</td>";
  $onedetail.="          </tr>";
  $onedetail.="        </table>";
  $onedetail.="      </td>";

  $onedetail.="      <td  style=\"border:0px;width:160px;border-left:#cccccc 1px solid;\" valign=\"top\" />";
  $onedetail.="        <table class=\"noborder\" valign=\"top\" >";
  $onedetail.="          <tr>";
  $onedetail.="            <td class=\"noborder\" style=\"text-align:left\" >" . _('admin::monitor: bases sur lesquelles l\'utilisateur est connecte : ') . "</td>";
  $onedetail.="          </tr>";

  foreach ($row['usr_id']->ACL()->get_granted_sbas() as $databox)
  {
    $onedetail.="          <tr>";
    $onedetail.="            <td class=\"noborder\" style=\"text-align:left;width:160px;overflow:hidden;\"  >" . $databox->get_viewname() . "</td>";
    $onedetail.="          </tr>";
  }
  $onedetail.="        </table>";
  $onedetail.="      </td>";
  $onedetail.="    </tr>";

  $onedetail.="    <tr style=\"border:0px\">";
  $onedetail.="      <td  colspan=\"2\" style=\"height:20px;text-align:center;background-color:#666666; color:#FFFFFF\" valign=\"center\" >" .
          $row['platform'] . ' / ' . $row['browser'] . ' - ' . $row['browser_version'] . '<br/>' . ($row['token'] ? _('Session persistente') : '') .
          "</td>";
  $onedetail.="    </tr>";

  $onedetail.="  </table>";
  $onedetail.="</span>";



  $out.="<tr title=\"" . str_replace('"', '&quot;', $onedetail) . "\" class='".($n % 2 == 0 ? 'even' : 'odd')." usrTips' id=\"TREXP_" . $row["session_id"] . "\">";


  if ($row["session_id"] == $session->get_ses_id())
    $out.=sprintf("<td style=\"color:#ff0000\"><i>" . $row['usr_id']->get_display_name() . "</i></td>\n");
  else
    $out.=sprintf("<td>" . $row['usr_id']->get_display_name() . "</td>\n");

  $appRef = array(
      '0' => _('admin::monitor: module inconnu')
      , '1' => _('admin::monitor: module production')
      , '2' => _('admin::monitor: module client')
      , '3' => _('admin::monitor: module admin')
      , '4' => _('admin::monitor: module report')
      , '5' => _('admin::monitor: module thesaurus')
      , '6' => _('admin::monitor: module comparateur')
      , '7' => _('admin::monitor: module validation')
      , '8' => _('admin::monitor: module upload')
  );


  $row["app"] = unserialize($row["app"]);

  $out.= "<td>";
  foreach ($row["app"] as $app)
  {
    if (isset($appLaunched[$app]))
      $appLaunched[$app]++;
    if ($app == '0')
      continue;
    $out .= ( isset($appRef[$app]) ? $appRef[$app] : $appRef[0]) . '<br>';
  }
  $out .= "</td>\n";

  $out.=sprintf("<td>" . $row["ip"] . '<br/>' . $row["ip_infos"] . "</td>\n");
  $out.=sprintf("<td>" . phraseadate::getDate($row['created_on']) . "</td>\n");
  $out.=sprintf("<td>" . phraseadate::getPrettyString($row['lastaccess']) . "</td>\n");

  $out.="</tr>\n";
  $n++;
}
$out.="</tbody></table>\n";


echo "<center>";

echo "<table style=\"table-layout:fixed;border:#000000 1px solid\">";

echo "<tr>";
echo "    <td class=\"colTitle\"  nowrap style=\"width:120px;text-align:left;\" >" . _('admin::monitor: module production') . "</td>";
echo "    <td class=\"noborder\"  nowrap style=\"width:60px;text-align:right\" >" . $appLaunched[1] . "</td>";
echo "</tr>";
echo "<tr  class=\"noborder\">";
echo "  <td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "    <td class=\"colTitle\"  nowrap style=\"width:120px;text-align:left;\" >" . _('admin::monitor: module client') . "</td>";
echo "  <td  class=\"noborder\" style=\"text-align:right\">" . $appLaunched[2] . "</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"colTitle\" style=\"text-align:left\">" . _('admin::monitor: module admin') . "</td>";
echo "  <td  class=\"noborder\" style=\"text-align:right\">" . $appLaunched[3] . "</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"colTitle\" style=\"text-align:left\">" . _('admin::monitor: module report') . "</td>";
echo "  <td  class=\"noborder\" style=\"text-align:right\">" . $appLaunched[4] . "</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"colTitle\" style=\"text-align:left\">" . _('admin::monitor: module thesaurus') . "</td>";
echo "  <td  class=\"noborder\" style=\"text-align:right\">" . $appLaunched[5] . "</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"colTitle\" style=\"text-align:left\">" . _('admin::monitor: module comparateur') . "</td>";
echo "  <td  class=\"noborder\" style=\"text-align:right\">" . $appLaunched[6] . "</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"colTitle\" style=\"text-align:left\">" . _('admin::monitor: module validation') . "</td>";
echo "  <td  class=\"noborder\" style=\"text-align:right\">" . $appLaunched[7] . "</td>";
echo "</tr>";

echo "<tr  class=\"noborder\">";
echo "  <td  class=\"noborder\" colspan=\"2\" style=\"position:relative; background-color:#333333; height:1px; top:0px; overflow:none;\" />";
echo "</tr>";

if ($appLaunched[0] > 0)
{

  echo "<tr  class=\"noborder\">";
  echo "<td  class=\"noborder\" colspan=\"2\"/>";
  echo "</tr>";

  echo "<tr  class=\"noborder\">";
  echo "<td  class=\"colTitle\" style=\"text-align:left\">" . _('admin::monitor: total des utilisateurs uniques : ');
  echo "  <td  class=\"noborder\" style=\"text-align:right\">" . $appLaunched[0] . "</td>";
  echo "</tr>";
}


echo "</table>";
echo "</center>";


echo "<br><br><hr><br><br>";
echo $out;
