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

$request = http_request::getInstance();
$parm = $request->get_parms('token', 'get', 'type');

ob_start(null, 0);
try
{
  $datas = ((random::helloToken($parm['token'])));
}
catch(Exception_NotFound $e)
{
  phrasea::headers(204);
}
if (!is_string($datas['datas']))
  phrasea::headers(204);

if (($list = @unserialize($datas['datas'])) == false)
{
  phrasea::headers(500);
}

$registry = registry::get_instance();
$zipFile = $registry->get('GV_RootPath') . 'tmp/download/' . $datas['value'] . '.zip';

if (isset($parm['get']) && $parm['get'] == '1')
{
  $response = set_export::stream_file($zipFile, $list['export_name'], 'application/zip');
  $response->send();
  set_export::log_download($list, $parm['type'], true, (isset($list['email']) ? $list['email'] : ''));

  return;
}

$files = $list['files'];


$zip_done = $zip_building = false;


if (isset($list['complete']))
{
  if ($list['complete'] == true)
    $zip_done = true;
  else
    $zip_building = true;
}

phrasea::headers();
?>
<html>
  <head>
    <title><?php echo _('phraseanet:: Telechargement de documents'); ?> anonyme</title>
    <meta content="<?php echo $registry->get('GV_metaDescription') ?>" name="description"/>
    <meta content="<?php echo $registry->get('GV_metaKeywords') ?>" name="keywords"/>
    <link rel="shortcut icon" type="image/x-icon" href="/prod/favicon.ico" />
    <style type="text/css">
      *,body{
        margin:0;
        padding:0;
        font-family:Helvetica, Arial, sans-serif;
        font-size:1em;
        color:white;
      }
      body{
        background-color:#212121;
        height:100%;
      }
      h1{
        font-size:26px;
        font-weight:bold;
        padding:50px 0 20px;
      }
      #page{
        width:860px;
        background-color:#414141;
        padding:0 20px;
        margin:0 auto;
        height:100%
      }
      p{
        margin:10px 0;
      }
      .loader{
        width:100%;
        height:40px;
        background-image:url(/skins/icons/loader414141.gif);
        background-position:center center;
        background-repeat:no-repeat;
      }
    </style>
    <script type="text/javascript" src="/include/minify/?f=include/jslibs/jquery-1.5.2.js"></script>
  </head>
  <body>
    <div id="page">
      <h1><?php echo _('phraseanet:: Telechargement de documents'); ?></h1>
<?php
if (!$zip_done)
{
?>
      <p><?php echo _('telechargement::Veuillez patienter, vos fichiers sont en train d\'etre rassembles pour le telechargement, cette operation peut prendre quelques minutes.'); ?></p>
      <div class="loader"></div>
<?php
    }
    else
    {
?>
      <p><?php echo sprintf(_('telechargement::Vos documents sont prets. Si le telechargement ne demarre pas, %s cliquez ici %s'), '<a href="/mail-export/' . $parm['token'] . '/get" target="_self">', '</a>'); ?></p>
      <?php
    }
      ?>
      <div style="margin:20px 0;">
        <p><?php echo _('telechargement::Le fichier contient les elements suivants'); ?></p>
        <table style="width:90%;margin:10px auto;text-align:center;">
          <thead>
            <tr>
              <th><?php echo _('phrseanet:: base'); ?></th>
              <th><?php echo _('document:: nom'); ?></th>
              <th><?php echo _('phrseanet:: sous definition'); ?></th>
              <th><?php echo _('poids'); ?></th>
            </tr>
          </thead>
<?php
      $total_size = 0;
      foreach ($files as $file)
      {
        $size = 0;
?>
          <tr valign="middle">
            <td><?php echo phrasea::sbas_names(phrasea::sbasFromBas($file['base_id'])) ?> (<?php echo phrasea::bas_names($file['base_id']) ?>)</td>
            <td><?php echo $file['original_name'] ?></td>
            <td><?php foreach ($file['subdefs'] as $k => $v)
          {
            echo $v['label'] . '<br/>';
            $size += $v['size'];
          } ?></td>
            <td><?php echo p4string::format_octets($size) ?></td>
          </tr>
          <?php
          $total_size += $size;
        }

        $time = round($total_size / (1024 * 1024 * 3));
        $time = $time < 1 ? 2 : ($time > 10 ? 10 : $time);
          ?>
        </table>
        <?php
        if (!$zip_done)
        {
          if (!$zip_building)
          {
        ?>
            <script type="text/javascript">
              alert("<?php echo str_replace('"', '\"', _('Votre lien est corrompu')); ?>");
            </script>
        <?php
          }
          else
          {
        ?>
            <script type="text/javascript">
              setTimeout("document.location.href = document.location.href",<?php echo $time; ?>000);
            </script>
        <?php
          }
        }
        else
        {
        ?>
          <script type="text/javascript">
            $(document).ready(function(){
              $('form[name=download]').submit();
            });
          </script>
        <?php
        }
        ?>

        <form name="download" action="/mail-export/<?php echo $parm['token'] ?>/get" method="post" target="get_file">

        </form>
        <iframe style="display:none;" name="get_file"></iframe>
      </div>
    </div>
  </body>
</html>



