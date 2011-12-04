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
phrasea::headers();

$usr_id = $session->get_usr_id();
$user = User_Adapter::getInstance($usr_id, $appbox);
$request = http_request::getInstance();

if ($request->has_post_datas())
{
  $parm = $request->get_parms('name', 'type', 'desc', 'lst', 'coll');

  if (trim($parm['name']) != '')
  {
    if (is_null($parm['type']) || $parm['type'] == "CHU")
    {
      try
      {
        $basket = basket_adapter::create($appbox, $parm['name'], $user, $parm['desc']);

        if (trim($parm['lst']) != '')
        {
          $basket->push_list($parm['lst'], false);
        }
        echo "<script type='text/javascript'>
          parent.refreshBaskets('" . $ssel_id . "');
          parent.hideDwnl();
          </script>";
        exit();
      }
      catch (Exception $e)
      {

      }
    }
    elseif ($parm['type'] == "REG")
    {
      try
      {
        $basket = basket_adapter::create($appbox, $parm['name'], $user, $parm['desc'], null, $parm['coll']);
        if ($parm['lst'] != '')
          $basket->push_list($parm['lst'], false);
        echo "<script type='text/javascript'>
          parent.refreshBaskets('" . $basket->get_ssel_id() . "');
          parent.hideDwnl();
          </script>";
        exit();
      }
      catch (Exception $e)
      {
?>
        <script type="text/javascript">
          alert("<?php echo _('panier:: erreur en creant le reportage') ?>");
          parent.hideDwnl();
        </script>
<?php
      }
?>
      <script type="text/javascript">
<?php
      if (isset($add['error']) && trim($add['error']) !== '')
      {
?>alert("<?php echo str_replace('"', '&quot;', $add['error']); ?>");<?php
      }
?>
        parent.hideDwnl();
      </script>
<?php
    }
  }
}

$user = User_Adapter::getInstance($usr_id, $appbox);
$ACL = $user->ACL();
$html = array();

$list = $ACL->get_granted_base(array('canaddrecord'));
$current_sbas_id = false;
foreach ($list as $base_id=>$collection)
{
  $sbas_id = $collection->get_databox()->get_sbas_id();
  if (!isset($html[$sbas_id]))
    $html[$sbas_id] = '';
  $html[$sbas_id] .= '<option  value="' . $base_id . '">' . $collection->get_name() . '</option>';
}

$menu = '';
foreach ($html as $sbas_id => $sbas)
  $menu .= '<optgroup label="' . phrasea::sbas_names($sbas_id) . '">' . $sbas . '</optgroup>';

$parm = $request->get_parms('type');
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <title><?php echo _('action:: nouveau panier') ?></title>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,include/jslibs/jquery-ui-1.8.12/css/dark-hive/jquery-ui-1.8.12.custom.css,skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
    <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js"></script>
    <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    <script type="text/javascript">


      function chgMode(val)
      {
        if(val)
          $('#sbaspopup').slideUp();
        else
          $('#sbaspopup').slideDown();

      }
      $(document).ready(function(){
        $('#tabs').tabs();
        if(parent.p4.sel.length > 0)
        {
          $('#sel_adder').show();
          $('#add_sel').val(parent.p4.sel.join(';'));
        }
        $('input.input-button').hover(
        function(){parent.$(this).addClass('hover')},
        function(){parent.$(this).removeClass('hover')}
      );

<?php
$basket_coll = new basketCollection($appbox, $usr_id);
$baskets = $basket_coll->get_baskets();

$n = count($baskets['baskets']) + count($baskets['recept']);

$limit_bask = 150;

if ($n > $limit_bask)
{
?>
      alert('<?php echo str_replace("'", "\'", sprintf(_('panier::Votre zone de panier ne peux contenir plus de %d paniers, supprimez-en pour en creer un nouveau'), $limit_bask)) ?>');
      parent.hideDwnl();
<?php
}
?>

  });

    </script>

  </head>


  <body class="bodyprofile">
    <div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer') ?></div>
    <div id="tabs">
      <ul>
        <li><a href="#baskets"><?php echo _('panier::nouveau') ?></a></li>
      </ul>
      <div id="baskets">
        <form name="myform" method="post" action="newtemporary.php">
          <table border="0" cellpadding="0" cellspacing="0" style="text-align:left;margin:0 auto;" >
<?php
if ($menu != '')
{
?>

            <tr>
              <td colspan="2">
<?php echo _('Quel type de panier souhaitez vous creer ?'); ?> :
              </td>
            </tr>
            <tr>
              <td colspan="2" style="height:5px">
              </td>
            </tr>

            <tr>
              <td style="width:25px"></td>
              <td>
                <input type="radio" name="type" id="typeTemp0" onClick="chgMode(true);" <?php echo ($parm['type'] == 'CHU' ? 'checked' : '') ?> value="CHU"><label for="typeTemp0"><?php echo _('phraseanet:: panier') ?></label>
              </td>
            </tr>
<?php
}
?>

<?php
?>

            <tr>
              <td style="width:25px"></td>
              <td>
<?php
if ($menu != "")
{
?>
                <input type="radio" name="type" id="typeTemp1" onClick="chgMode(false);" <?php echo ($parm['type'] == 'REG' ? 'checked' : '') ?> value="REG"><label for="typeTemp1"><?php echo _('phraseanet::type:: reportages') ?></label>
<?php
              }
?>
              </td>
            </tr>

            <tr>
              <td></td>
              <td style="text-align:right">
<?php
              if ($menu != "")
              {
?>
                <select name="coll" id="sbaspopup" style="display:none;"><?php echo $menu ?></select>
<?php
              }
?>
              </td>
            </tr>
            <tr style="height:40px;display:none;" id="sel_adder">
              <td style="width:25px"></td>
              <td>
                <input type="checkbox" id="add_sel" name="lst" value=""/><label for="add_sel"><?php echo _('Ajouter ma selection courrante') ?></label>
              </td>
            </tr>

            <tr>
              <td colspan="2">
                <table border="0" style="width:100%" >
                  <tr>
                    <td style="text-align:right"><?php echo _('Nom du nouveau panier') ?>&nbsp;:</td>
                    <td><input type="text" name="name" id="IdName" style="width:175px" ></td>
                  </tr>
                  <tr>
                    <td style="text-align:right"><?php echo _('paniers::description du nouveau panier') ?>&nbsp;:</td>
                    <td><textarea name="desc" id="IdDesc" cols="20" rows="10"></textarea></td>
                  </tr>
                  <tr>
                    <td colspan="2" style="height:20px"></td>
                  </tr>
                </table>
              </td>
            </tr>

          </table>
          <div style="text-align:center;">
            <input type="submit" value="<?php echo _('boutton::valider'); ?>" class="input-button" />
            <input type="button" value="<?php echo _('boutton::annuler'); ?>" class="input-button" onclick="parent.hideDwnl();" />
          </div>
        </form>
      </div>
    </div>

  </body>
</html>
