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
$registry = $appbox->get_registry();
$request = http_request::getInstance();

$parm = $request->get_parms(
                "p0", 'TOU', 'test', 'valid'
);

if (is_null($parm['p0']))
  phrasea::headers(400);

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if (!$user->ACL()->has_right_on_sbas($parm['p0'], 'bas_modify_struct'))
{
  phrasea::headers(403);
}

phrasea::headers();

$databox = databox::get_instance((int) $parm['p0']);
if ((int) $parm['p0'] > 0 && is_array($parm['TOU']))
{
  foreach ($parm['TOU'] as $loc => $terms)
  {
    $databox->update_cgus($loc, $terms, $parm['valid']);
  }
}
$avLanguages = User_Adapter::detectlanguage($registry, Session_Handler::get_locale());

$TOU = $databox->get_cgus();
?>

<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,skins/admin/admincolor.css,include/jslibs/jquery-ui-1.8.12/css/ui-lightness/jquery-ui-1.8.12.custom.css"/>
    <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js"></script>
    <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    <script type="text/javascript" src="/include/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
    <script type="text/javascript">
      tinyMCE.init({
        mode : "textareas",
        theme : "advanced",
        plugins : "paste,searchreplace",
        paste_auto_cleanup_on_paste : true,
        paste_remove_styles: true,
        paste_strip_class_attributes:'all',
        paste_use_dialog : false,
        paste_convert_headers_to_strong : false,
        paste_remove_spans : true,
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,formatselect,|,cut,copy,paste,|,search,replace,|,bullist,numlist,undo,redo,|,link,unlink",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : "",
        theme_advanced_buttons4 : "",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom"
      });
      $(document).ready(function(){
        $('#tabs').tabs({
          selected:$("#tabs ul li").index($('#tabs ul li.selected'))
        });
      });
    </script>
  </head>
  <body>
    <h1><?php echo _('Terms Of Use'); ?></h1>
      
    <form target="_self" method="post" action="cgus.php">
      <div style="text-align:center;margin:10px 0;">
        <input type="submit" value="<?php echo _('Mettre a jour'); ?>" id="valid"/><input type="checkbox" value="1" name="valid"/><label for="valid"><?php echo _('admin::CGU Les utilisateurs doivent imperativement revalider ces conditions'); ?></label>
        <input type="hidden" name="p0" value="<?php echo $parm['p0']; ?>"/>
      </div>
      <div id="tabs" style="background:transparent;padding:0;">
        <ul style="background:transparent;border:none;border-bottom:1px solid #959595;">
          <?php
          foreach ($avLanguages as $lang)
          {
            foreach ($lang as $k => $v)
            {
              if (isset($TOU[$k]))
              {
                $s = ( $k == Session_Handler::get_locale() ? 'selected' : '' );
                echo '<li class="' . $s . '" style="border:none;"><a href="#terms-' . $k . '">' . $v['name'] . '</a></li>';
              }
            }
          }
          ?>
        </ul>
        <?php
        foreach ($avLanguages as $lang)
        {
          foreach ($lang as $k => $v)
          {
            if (isset($TOU[$k]))
            {
              ?>
              <div id="terms-<?php echo $k; ?>">
                <textarea name="TOU[<?php echo $k; ?>]" style="width:100%;height:600px;margin:0 auto;">
                  <?php echo $TOU[$k]['value']; ?>
                </textarea>
              </div>
              <?php
            }
          }
        }
        ?>
      </div>
    </form>
  </body>
</html>
