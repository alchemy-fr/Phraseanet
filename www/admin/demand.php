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

$request = http_request::getInstance();
$parm = $request->get_parms("deny", "accept", "accept_hd", "watermark", "template");

$usr_id = $session->get_usr_id();

phrasea::headers();

$templates = array();
if (!is_null($parm['template']))
{
  foreach ($parm['template'] as $tmp)
  {
    if (trim($tmp) != '')
    {
      $tmp = explode('_', $tmp);

      if (count($tmp) == 2)
      {
        $templates[$tmp[0]] = $tmp[1];
      }
    }
  }
}
$deny = $accept = $options = array();

if (!is_null($parm['deny']))
{
  foreach ($parm['deny'] as $den)
  {
    $den = explode('_', $den);
    if (count($den) == 2 && !isset($templates[$den[0]]))
    {
      $deny[$den[0]][$den[1]] = $den[1];
    }
  }
}

if (!is_null($parm['accept']))
{
  foreach ($parm['accept'] as $acc)
  {
    $acc = explode('_', $acc);
    if (count($acc) == 2 && !isset($templates[$acc[0]]))
    {
      $accept[$acc[0]][$acc[1]] = $acc[1];
      $options[$acc[0]][$acc[1]] = array('HD' => false, 'WM' => false);
    }
  }
}

if (!is_null($parm['accept_hd']))
{
  foreach ($parm['accept_hd'] as $accHD)
  {
    $accHD = explode('_', $accHD);
    if (count($accHD) == 2 && isset($accept[$accHD[0]]) && isset($options[$accHD[0]][$accHD[1]]))
    {
      $options[$accHD[0]][$accHD[1]]['HD'] = true;
    }
  }
}
if (!is_null($parm['watermark']))
{
  foreach ($parm['watermark'] as $wm)
  {
    $wm = explode('_', $wm);
    if (count($wm) == 2 && isset($accept[$wm[0]]) && isset($options[$wm[0]][$wm[1]]))
    {
      $options[$wm[0]][$wm[1]]['WM'] = true;
    }
  }
}

if (!is_null($templates) || !is_null($parm['deny']) || !is_null($parm['accept']))
{
  $done = array();

  $cache_to_update = array();

  foreach ($templates as $usr => $template_id)
  {
    $user = User_Adapter::getInstance($usr, $appbox);
    $cache_to_update[$usr] = true;

    $user_template = User_Adapter::getInstance($template_id, $appbox);
    $base_ids = array_keys($user_template->ACL()->get_granted_base());

    $user->ACL()->apply_model($user_template, $base_ids);


    if (!isset($done[$usr]))
      $done[$usr] = array();
    foreach($base_ids as $base_id)
    {
        $done[$usr][$base_id] = true;
    }

    $sql = "DELETE FROM demand
            WHERE usr_id = :usr_id AND (base_id = ".implode(' OR base_id = ', $base_ids).")";
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':usr_id' => $usr));
    $stmt->closeCursor();

  }

  $sql = "UPDATE demand SET en_cours=0, refuser=1, date_modif=now()
              WHERE usr_id = :usr_id AND base_id = :base_id";

  $stmt = $appbox->get_connection()->prepare($sql);
  foreach ($deny as $usr => $bases)
  {
    $cache_to_update[$usr] = true;
    foreach ($bases as $bas)
    {
      $stmt->execute(array(':usr_id' => $usr, ':base_id' => $bas));

      if (!isset($done[$usr]))
        $done[$usr] = array();

      $done[$usr][$bas] = false;
    }
  }
  $stmt->closeCursor();

  foreach ($accept as $usr => $bases)
  {
    $user = User_Adapter::getInstance($usr, $appbox);
    $cache_to_update[$usr] = true;
    foreach ($bases as $bas)
    {
      $user->ACL()->give_access_to_sbas(array(phrasea::sbasFromBas($bas)));

      $rights = array(
          'canputinalbum'=>'1'
          ,'candwnldhd'=> ($options[$usr][$bas]['HD'] ? '1' : '0')
        ,'nowatermark'=>($options[$usr][$bas]['WM'] ? '0':'1')
        ,'candwnldpreview'=>'1'
        ,'actif'=>'1'
      );

      $user->ACL()->give_access_to_base(array($bas));
      $user->ACL()->update_rights_to_base($bas, $rights);

      if (!isset($done[$usr]))
        $done[$usr] = array();

      $done[$usr][$bas] = true;

      $sql = "DELETE FROM demand WHERE usr_id = :usr_id AND base_id = :base_id";
      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':usr_id' => $usr, ':base_id' => $bas));
      $stmt->closeCursor();
    }
  }

  foreach ($cache_to_update as $usr_id => $true)
  {
    $user = User_Adapter::getInstance($usr_id, $appbox);
    $user->ACL()->delete_data_from_cache();
    unset($user);
  }
  foreach ($done as $usr => $bases)
  {
    $sql = 'SELECT usr_mail FROM usr WHERE usr_id = :usr_id';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':usr_id' => $usr));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $accept = $deny = '';

    if ($row)
    {

      require_once(dirname (__FILE__) . '/../../lib/vendor/PHPMailer_v5.1/class.phpmailer.php');
      if (PHPMailer::ValidateAddress($row['usr_mail']))
      {
        foreach ($bases as $bas => $isok)
        {
          if ($isok === true)
            $accept .= '<li>' . phrasea::bas_names($bas) . "</li>\n";
          if ($isok === false)
            $deny .= '<li>' . phrasea::bas_names($bas) . "</li>\n";
        }
        if (($accept != '' || $deny != ''))
        {
          mail::register_confirm($row['usr_mail'], $accept, $deny);
        }
      }
    }
  }
}
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
    <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js">
    </script>
    <script type="text/javascript" src="/include/minify/f=include/jquery.tooltip.js">
    </script>
    <script type="text/javascript">
      var bodySize = {x:100,y:100};
      function resize(){

        bodySize.y = $(window).height();
        bodySize.x = $(window).width();
        $('#tab_demandes').height(bodySize.y-80)
      }
      $(document).ready(function(){

        resize();
        $(window).resize(function(){
          resize();
        });

      });
      function checkDeny(el)
      {
        if($(el)[0].checked)
        {
          $('.disabler_'+$(el).attr('id').substring(5)).removeAttr('checked');
        }
        else
        {
        }
      }

      function checkAdd(el)
      {
        if($(el)[0].checked)
        {
          $('#accept_'+$(el).attr('id').substring(10))[0].checked = true;
          $('#deny_'+$(el).attr('id').substring(10))[0].checked = false;
        }
      }
      function checkRemove(el)
      {
        if(!$(el)[0].checked)
          $('.disabler_'+$(el).attr('id').substring(7)).each(function(){$(this)[0].checked = false;});
        else
          $('#deny_'+$(el).attr('id').substring(7))[0].checked = false;
      }

      function modelChecker(usr)
      {
        var val = $('#model_'+usr)[0].value;

        var bool = false;
        if(!isNaN(val) && val!='')
          bool = true;

        if(bool)
          $('#sub_usr_'+usr).slideToggle('slow');
        else
          $('#sub_usr_'+usr).slideToggle('slow');

        if(bool)
          $('.checker_'+usr).attr('disabled','disabled');
        else
          $('.checker_'+usr).removeAttr('disabled');

      }

      function checkAll(that)
      {
        var bool = true;
        var first = true;
        $('.'+that+'_checker:not(:disabled)').each(function(){
          //        if(!$(this)[0].disabled)
          //        {
          if(first && $(this)[0].checked)
            bool = false;
          $(this)[0].checked = bool;
          first = false;
          if(that == 'deny')
          {
            checkDeny($(this));
          }
          if(that == 'accept_hd')
            checkAdd(this)
          if(that == 'watermark')
            checkAdd(this)
          if(that == 'accept')
            checkRemove(this)
          //        }
        });
      }
    </script>
    <style>
      #tooltip{
        background-color:black;
        color:white;
        position:absolute;
      }
    </style>

  </head>

  <body><form method='post' action='demand.php'>

      <?php
      $out = "";


      $lastMonth = time() - (3 * 4 * 7 * 24 * 60 * 60);

      $sql = "DELETE FROM demand WHERE date_modif < :date";
      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':date' => date('Y-m-d', $lastMonth)));
      $stmt->closeCursor();

      $usr_id = $session->get_usr_id();
      $user = User_Adapter::getInstance($usr_id, $appbox);
      $baslist = array_keys($user->ACL()->get_granted_base(array('canadmin')));


      $models = '<option value="">aucun</option>';
      $sql = 'SELECT usr_id, usr_login FROM usr WHERE model_of = :usr_id';

      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':usr_id' => $session->get_usr_id()));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        $models .= '<option value="%stemplate%s_' . $row['usr_id'] . '">' . $row['usr_login'] . '</option>';
      }

      $sql = "SELECT demand.date_modif,demand.base_id,usr.usr_id , usr.usr_login ,usr.usr_nom,usr.usr_prenom,
              usr.societe,CONCAT(usr.usr_nom,' ',usr.usr_prenom,'\n',fonction,' (',societe,')') AS info
        FROM (demand INNER JOIN usr on demand.usr_id=usr.usr_id AND demand.en_cours=1)
        WHERE (base_id='" . implode("' OR base_id='", $baslist) . "') ORDER BY demand.usr_id DESC,demand.base_id ASC";

      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute();
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();


      $out .= "<div id=\"top_box\" style='height:70px;overflow:hidden;'>";
      $out .= "<div id=\"title\"><h1>" . _('admin:: demandes en cours') . "</h1></div>";

      $out .= "<div>";
      $out .= "<table style='width:100%'>" .
              "<tr>" .
              "<td style='width:20px'><img onclick='checkAll(\"deny\")' style='cursor:pointer;' class='tipInfoUsr' title=\"" . _('admin:: refuser l\'acces') . "\" src='/skins/icons/delete.gif'/></td>" .
              "<td style='width:20px'><img onclick='checkAll(\"accept\")' style='cursor:pointer;' class='tipInfoUsr' title='" . _('admin:: donner les droits de telechargement et consultation de previews') . "' src='/skins/icons/cmdok.gif'/></td>" .
              "<td style='width:20px'><span onclick='checkAll(\"accept_hd\")' style='cursor:pointer;' class='tipInfoUsr' title='" . _('admin:: donner les droits de telechargements de preview et hd') . "'>HD</span></td>" .
              "<td style='width:20px'><span onclick='checkAll(\"watermark\")' style='cursor:pointer;' class='tipInfoUsr' title='" . _('admin:: watermarquer les documents') . "'>W</span></td>" .
              "<td style='width:120px'>" . _('admin::compte-utilisateur identifiant') . "</td>" .
              "<td style='width:auto'>" . _('admin::compte-utilisateur societe') . "</td>" .
              "<td style='width:130px'>" . _('admin::compte-utilisateur date d\'inscription') . "</td>" .
              "<td style='width:150px'>" . _('admin::collection') . "</td>" .
              "</tr>" .
              "</table>";


      $out .= "</div>";
      $out .= "</div><div  id=\"tab_demandes\" style='overflow-y:scroll;overflow-x:hidden'>";
      $out .= "<table style='width:100%' class='ulist' cellspacing='0' cellpading='0'>" .
              "<tr>" .
              "<td style='width:20px'></td>" .
              "<td style='width:20px'></td>" .
              "<td style='width:20px'></td>" .
              "<td style='width:20px'></td>" .
              "<td style='width:120px'></td>" .
              "<td style='width:auto'></td>" .
              "<td style='width:130px'></td>" .
              "<td style='width:150px'></td>" .
              "</tr>";
      $class = '';
      $currentUsr = null;
      $sql = "SELECT * FROM usr WHERE usr_id = :usr_id";
      $stmt = $appbox->get_connection()->prepare($sql);
      foreach ($rs as $row)
      {
        if ($row['usr_id'] != $currentUsr)
        {
          if ($currentUsr !== null)
          {

            $out .= '</table></div></td></tr>';
          }

          $currentUsr = $row['usr_id'];
          $class = $class == 'g' ? '' : 'g';

          $info = "";
          $stmt->execute(array(':usr_id' => $row['usr_id']));
          $rowInfo = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($rowInfo)
          {
            $info .= "<div><div>" . _('admin::compte-utilisateur identifiant') . " : " . ($rowInfo["usr_login"]) . "</div>";

            $info .= "<div>" . _('admin::compte-utilisateur nom') . "/" . _('admin::compte-utilisateur prenom') . " : ";
            $info .= ( $rowInfo["usr_nom"]) . " ";
            $info .= ( $rowInfo["usr_prenom"]);
            $info .= "</div>";

            $info .= "<div>" . _('admin::compte-utilisateur email') . " : ";
            $info .= ( $rowInfo["usr_mail"]);
            $info .= "</div>";

            $info .= "<div>" . _('admin::compte-utilisateur telephone') . " : ";
            $info .= ( $rowInfo["tel"]);
            $info .= "</div>";

            $info .= "<div>" . _('admin::compte-utilisateur poste') . " : ";
            $info .= ( $rowInfo["fonction"]);
            $info .= "</div>";

            $info .= "<div>" . _('admin::compte-utilisateur societe') . " : ";
            $info .= ( $rowInfo["societe"]);
            $info .= "</div>";

            $info .= "<div>" . _('admin::compte-utilisateur activite') . " : ";
            $info .= ( $rowInfo["activite"]);
            $info .= "</div>";

            $info .= "<div>" . _('admin::compte-utilisateur adresse') . " : ";
            $info .= "" . ($rowInfo["adresse"]);
            $info .= "</div>";

            $info .= "<div>";

            $info .= ( $rowInfo["cpostal"]) . " ";

            $info .= ( $rowInfo["ville"]);
            $info .= "</div>" . "</div>";
          }

          $info = "<div style='margin:5px;'>" . $info . "</div>";


          $out .= '<tr class="tipInfoUsr ' . $class . '" title="' . $info . '"  id="USER_' . $row['usr_id'] . '"' . '>';
          $out .= "<td>";
          $out .= " ";
          $out .= "</td>";
          $out .= "<td>";
          $out .= " ";
          $out .= "</td>";
          $out .= "<td>";
          $out .= " ";
          $out .= "</td>";
          $out .= "<td>";
          $out .= " ";
          $out .= "</td>";
          $out .= '<td>';
          $out .= '' . ($row["usr_login"]);
          $out .= '</td>';

          $tmp = $row["usr_nom"] . " " . $row["usr_prenom"] . ( $row["societe"] ? " (" . $row["societe"] . ")" : "" );
          $out .= '<td>' . (trim($tmp)) . '</td>';

          $out .= '<td colspan="2"> ' . _('admin:: appliquer le modele  ') . '  <select name="template[]" id="model_' . $row['usr_id'] . '" onchange="modelChecker(' . $row['usr_id'] . ')">' . str_replace('%stemplate%s', $row['usr_id'], $models) . '</select></td>';

          $out .= '</tr>';
          $out .= '<tr><td colspan="8"><div id="sub_usr_' . $row['usr_id'] . '"><table cellspacing="0" cellpading="0" style="width:100%">' .
                  "<tr style='height:0px;dispolay:none;'>" .
                  "<td style='width:20px'></td>" .
                  "<td style='width:20px'></td>" .
                  "<td style='width:20px'></td>" .
                  "<td style='width:20px'></td>" .
                  "<td style='width:120px'></td>" .
                  "<td style='width:auto'></td>" .
                  "<td style='width:130px'></td>" .
                  "<td style='width:150px'></td>" .
                  "</tr>";
        }

        $out .= '<tr class="' . $class . '">';
        $out .= "<td>";
        $out .= "<input name='deny[]' value='" . $row['usr_id'] . "_" . $row['base_id'] . "' onclick='checkDeny(this)' id='deny_" . $row['usr_id'] . "_" . $row['base_id'] . "' class='deny_checker tipInfoUsr checker_" . $row['usr_id'] . "' title=\"" . _('admin:: refuser l\'acces') . "\" class='' type=\"checkbox\"/>";
        $out .= "</td>";
        $out .= "<td>";
        $out .= "<input name='accept[]' value='" . $row['usr_id'] . "_" . $row['base_id'] . "' onclick='checkRemove(this)' id='accept_" . $row['usr_id'] . "_" . $row['base_id'] . "' class='disabler_" . $row['usr_id'] . "_" . $row['base_id'] . " accept_checker tipInfoUsr checker_" . $row['usr_id'] . "' title='" . _('admin:: donner les droits de telechargement et consultation de previews') . "' class='checker_" . $row['usr_id'] . "' type=\"checkbox\"/>";
        $out .= "</td>";
        $out .= "<td>";
        $out .= "<input name='accept_hd[]' value='" . $row['usr_id'] . "_" . $row['base_id'] . "' onclick='checkAdd(this)' id='accept_hd_" . $row['usr_id'] . "_" . $row['base_id'] . "' class='disabler_" . $row['usr_id'] . "_" . $row['base_id'] . " accept_hd_checker tipInfoUsr checker_" . $row['usr_id'] . "' title='" . _('admin:: donner les droits de telechargements de preview et hd') . "' class='checker_" . $row['usr_id'] . "' type=\"checkbox\"/>";
        $out .= "</td>";
        $out .= "<td>";
        $out .= "<input name='watermark[]' value='" . $row['usr_id'] . "_" . $row['base_id'] . "' onclick='checkAdd(this)' id='watermark_" . $row['usr_id'] . "_" . $row['base_id'] . "' class='disabler_" . $row['usr_id'] . "_" . $row['base_id'] . " watermark_checker tipInfoUsr checker_" . $row['usr_id'] . "' title='" . _('admin:: watermarquer les documents') . "' class='checker_" . $row['usr_id'] . "' type=\"checkbox\"/>";
        $out .= "</td>";
        $out .= "<td colspan='2'>";
        $out .= "</td>";

        $out .= '<td>' . ($row["date_modif"]) . '</td>';

        $out .= '<td>' . phrasea::bas_names($row['base_id']) . '</td>';

        $out .= '</tr>';
      }
      $stmt->closeCursor();

      $out .= "      </table><br />\n";
      $out .= "    </div>\n";

      $out .= "</table>";

      $out .= "</div>";

      $out .= "    <div id='bottom_box' style='height:40px;overflow:hidden;'>";
      $out .= "      <div id=\"divboutdemand\" style=\"text-align:center;\">";
      $out .= "        <input type='submit' value='" . _('boutton::valider') . "' />";
      $out .= "      </div>";
      $out .= "    </div></form>";
      $out .= "  </body>";
      $out .= "</html>";

      print($out);
      ?>
      <script>$('.tipInfoUsr').tooltip();</script>
  </body>
</html>
