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
$registry = $appbox->get_registry();
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms('token', 'get', 'type');

try {
    $datas = ((random::helloToken($parm['token'])));
} catch (Exception_NotFound $e) {
    phrasea::headers(204);
}

if ( ! is_string($datas['datas']))
    phrasea::headers(204);

if (($list = @unserialize($datas['datas'])) == false) {
    phrasea::headers(500);
}

try {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $auth = new Session_Authentication_Token($appbox, $parm['token']);
    $session->authenticate($auth);
} catch (Exception $e) {
    phrasea::headers(204);
}

$gatekeeper = gatekeeper::getInstance($Core);
$gatekeeper->require_session();

$unique_file = false;
$n_files = $list['count'];

$zip_done = $zip_building = false;

$export_name = $list['export_name'];

if ($n_files == 1) {
    $u_file = $list['files'];
    $u_file = array_pop($u_file);
    $export_name = $u_file["export_name"];
    $u_file = array_pop($u_file['subdefs']);
    $unique_file = true;
    $export_name .= $u_file["ajout"] . '.' . $u_file["exportExt"];
    $zipFile = p4string::addEndSlash($u_file['path']) . $u_file['file'];
    $mime = $u_file['mime'];
    $zip_done = true;
} else {
    $zipFile = $registry->get('GV_RootPath') . 'tmp/download/' . $datas['value'] . '.zip';
    $mime = 'application/zip';
}

$files = $list['files'];

if (isset($parm['get']) && $parm['get'] == '1') {
    $response = set_export::stream_file($zipFile, $export_name, $mime);
    $response->send();
    set_export::log_download($list, $parm['type']);

    return;
}



if (isset($list['complete'])) {
    if ($list['complete'] == true)
        $zip_done = true;
    else
        $zip_building = true;
}

phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title><?php echo _('phraseanet:: Telechargement de documents'); ?></title>
        <meta content="<?php echo $registry->get('GV_metaDescription'); ?>" name="description"/>
        <meta content="<?php echo $registry->get('GV_metaKeywords'); ?>" name="keywords"/>
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
        <script type="text/javascript" src="/include/minify/?f=include/jslibs/jquery-1.7.1.js"></script>
    </head>
    <body>
        <div id="page">
            <h1><?php echo _('phraseanet:: Telechargement de documents'); ?></h1>
            <div style="display:<?php echo $zip_done ? 'none' : 'block' ?>;" id="wait">
                <p><?php echo _('telechargement::Veuillez patienter, vos fichiers sont en train d\'etre rassembles pour le telechargement, cette operation peut prendre quelques minutes.'); ?></p>
                <div class="loader"></div>
            </div>
<?php ?>
            <div style="display:<?php echo $zip_done ? 'block' : 'none' ?>;" id="ready">
                <p><?php echo sprintf(_('telechargement::Vos documents sont prets. Si le telechargement ne demarre pas, %s cliquez ici %s'), '<a href="/download/' . $parm['token'] . '/get" target="_self">', '</a>'); ?></p>
            </div>
            <div style="margin:20px 0;">
                <p><?php echo _('telechargement::Le fichier contient les elements suivants'); ?></p>
                <table style="width:90%;margin:10px auto;text-align:center;">
                    <thead>
                        <tr>
                            <th><?php echo _('phrseanet:: base'); ?></th>
                            <th><?php echo _('document:: nom'); ?></th>
                            <th><?php echo _('phrseanet:: sous definition'); ?></th>
                            <th><?php echo _('poids'); ?></th>
                            <th style="width:200px;"></th>
                        </tr>
                    </thead>
<?php
$total_size = 0;
foreach ($files as $file) {
    $size = 0;
    ?>
                        <tr valign="middle">
                            <td><?php echo phrasea::sbas_names(phrasea::sbasFromBas($file['base_id'])) ?> (<?php echo phrasea::bas_names($file['base_id']) ?>)</td>
                            <td><?php echo $file['original_name'] ?></td>
                            <td><?php
    foreach ($file['subdefs'] as $k => $v) {
        echo $v['label'] . '<br/>';
        $size += $v['size'];
    }
    ?></td>
                            <td><?php echo p4string::format_octets($size) ?></td>
                            <td style="text-align:center;"><?php
                    $sbas_id = phrasea::sbasFromBas($file['base_id']);
                    $record = new record_adapter($sbas_id, $file['record_id']);
                    $thumbnail = $record->get_thumbnail();

                    if ($thumbnail->is_paysage()) {
                        $w = 140;
                        $h = round($w / ($thumbnail->get_width() / $thumbnail->get_height()));
                    } else {
                        $h = 105;
                        $w = round($h * ($thumbnail->get_width() / $thumbnail->get_height()));
                    }

                    echo '<img style="height:' . $h . 'px;width:' . $w . 'px;" src="' . $thumbnail->get_url() . '"/>';
                    ?></td>
                        </tr>
                                <?php
                                $total_size += $size;
                            }

                            $time = round($total_size / (1024 * 1024 * 3));
                            $time = $time < 1 ? 2 : ($time > 10 ? 10 : $time);
                            ?>
                </table>
                <script type="text/javascript">
                    $(document).ready(function(){
                            <?php
                            if ($zip_done === false && $zip_building === false && ! $unique_file) {
                                ?>
                      $.post("/include/download_prepare.exe.php", {
                          token: "<?php echo $parm['token']; ?>"
                      }, function(data){
                          if(data == '1')
                          {
                              $('#wait').hide();
                              $('#ready').show();
                              get_file();
                          }

                          return;
                      });
    <?php
} elseif ($zip_done === true) {
    ?>

                      get_file();
    <?php
} else {
    ?>
                      setTimeout("document.location.href = document.location.href",<?php echo $time; ?>000);
    <?php
}
?>
            });
                </script>
                <form name="download" action="/download/<?php echo $parm['token'] ?>/get" method="post" target="get_file">

                </form>
                <iframe style="display:none;" name="get_file"></iframe>

                <script type="text/javascript">
                    function get_file(){
                        $('form[name=download]').submit();
                    }
                </script>

            </div>
        </div>
    </body>
</html>

