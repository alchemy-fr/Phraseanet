<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
$parm = $request->get_parms("srt", "ord", "act", "p0", // base_id
                            "bit", "nam", // name
                            "searchable", // name
                            "printable", // name
                            "dic0", // ccoch delete icon 0
                            "dic1", // ccoch delete icon 1
                            "labelon", // ccoch delete icon 1
                            "labeloff" // ccoch delete icon 1
);

if (is_null($parm['p0']))
    phrasea::headers(400);

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if ( ! $user->ACL()->has_right_on_sbas($parm['p0'], 'bas_modify_struct')) {
    phrasea::headers(403);
}

$sbas_id = (int) $parm['p0'];
$databox = databox::get_instance($sbas_id);

phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title></title>
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,skins/admin/admincolor.css" />
        <style type="text/css">
            BODY
            {

            }
            H4
            {
                MARGIN-TOP: 0px;
                FONT-WEIGHT: normal;
                FONT-SIZE: 18px;
                MARGIN-BOTTOM: 0px;
                MARGIN-LEFT: 5px
            }
            img.editer, img.adder, img.deleter{
                cursor:pointer;
            }
        </style>
    </head>
    <body>

<?php
$loadit = true;
$errors = array();

if ($parm["act"] == "DELETE") {
    databox_status::deleteStatus($parm['p0'], $parm["bit"]);
}

if ($parm["act"] == "APPLY") {
    $searchable = ($parm["searchable"] == 'on') ? '1' : '0';
    $printable = ($parm["printable"] == 'on') ? '1' : '0';

    $properties = array('searchable' => $searchable,
        'printable'  => $printable,
        'name'       => $parm["nam"],
        'labelon'    => $parm["labelon"],
        'labeloff'   => $parm["labeloff"]
    );

    databox_status::updateStatus($parm['p0'], $parm["bit"], $properties);

    if ($parm["dic0"]) {
        databox_status::deleteIcon($parm['p0'], $parm['bit'], 'off');
    }
    if (isset($_FILES['ic0']) && $_FILES['ic0']['name']) {
        try {
            databox_status::updateIcon($parm['p0'], $parm['bit'], 'off', $_FILES['ic0']);
        } catch (Exception_Forbidden $e) {
            $errors[] = _('You do not enough rights to update status');
        } catch (Exception_InvalidArgument $e) {
            $errors[] = _('Something wrong happend');
        } catch (Exception_Upload_FileTooBig $e) {
            $errors[] = _('File is too big : 64k max');
        } catch (Exception_Upload_Error $e) {
            $errors[] = _('Status icon upload failed : upload error');
        } catch (Exception_Upload_CannotWriteFile $e) {
            $errors[] = _('Status icon upload failed : can not write on disk');
        } catch (Exception $e) {
            $errors[] = _('An error occured');
        }
    }


    if ($parm["dic1"]) {
        databox_status::deleteIcon($parm['p0'], $parm['bit'], 'on');
    }
    if (isset($_FILES['ic1']) && $_FILES['ic1']['name']) {
        try {
            databox_status::updateIcon($parm['p0'], $parm['bit'], 'on', $_FILES['ic1']);
        } catch (Exception_Forbidden $e) {
            $errors[] = _('You do not enough rights to update status');
        } catch (Exception_InvalidArgument $e) {
            $errors[] = _('Something wrong happend');
        } catch (Exception_Upload_FileTooBig $e) {
            $errors[] = _('File is too big : 64k max');
        } catch (Exception_Upload_Error $e) {
            $errors[] = _('Status icon upload failed : upload error');
        } catch (Exception_Upload_CannotWriteFile $e) {
            $errors[] = _('Status icon upload failed : can not write on disk');
        } catch (Exception $e) {
            $errors[] = _('An error occured');
        }
    }
}



if ($parm["act"] == "ADD" || $parm["act"] == "EDIT") {
    $status = $databox->get_statusbits();

    $status = isset($status[$parm['bit']]) ? $status[$parm['bit']] : array('name'       => '', 'labelon'    => '', 'labeloff'   => '', 'img_on'     => '', 'img_off'    => '', 'searchable' => '0', 'printable'  => '0')
    ?>

            <form enctype="multipart/form-data" method="post" name="chgStatbits" action="./statbits.php" target="_self">
                <table class="admintable">
                    <tr style="text-align:center;">
                        <td colspan="2"><h4><?php echo _('phraseanet::status bit'); ?></h4></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td colspan="2"><?php echo _('admin::status: nom du status : '); ?> <input type="text" name="nam" value="<?php echo str_replace('"', '&quot;', $status['name']); ?>" /></td>
                    </tr>
                    <tr style="text-align:center;">
                        <td><h4><?php echo _('admin::status: case A') ?></h4></td>
                        <td><h4><?php echo _('admin::status: case B') ?></h4></td>
                    </tr>
                    <tr>
                        <td><?php echo _('admin::status: parametres si decoche') ?></td>
                        <td><?php echo _('admin::status: parametres si coche') ?></td>
                    </tr>
                    <tr>
                        <td><?php echo _('admin::status: texte a afficher') ?><input type="text" name="labeloff" value="<?php echo $status['labeloff'] ?>" /></td>
                        <td><?php echo _('admin::status: texte a afficher') ?><input type="text" name="labelon" value="<?php echo $status['labelon'] ?>" /></td>
                    </tr>
                    <tr>
                        <td><?php echo _('admin::status: symboliser par') ?> <?php echo ($status['img_off'] ? '<img src="' . $status['img_off'] . '" />' : _('admin::status: aucun symlboler')); ?></td>
                        <td><?php echo _('admin::status: symboliser par') ?> <?php echo ($status['img_on'] ? '<img src="' . $status['img_on'] . '" />' : _('admin::status: aucun symlboler')); ?></td>
                    </tr>
                    <tr>
                        <td><input type="file" name="ic0" /></td>
                        <td><input type="file" name="ic1" /></td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" name="dic0" /><?php echo _('admin::status:: aucun symbole'); ?></td>
                        <td><input type="checkbox" name="dic1" /><?php echo _('admin::status:: aucun symbole'); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="checkbox" name="printable" <?php echo ($status['printable'] == '1' ? "checked" : "") ?> />  <?php echo _('status:: Afficher le status dans les feuilles de reponses pour tous les utilisateurs') ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="checkbox" name="searchable" <?php echo ($status['searchable'] == '1' ? "checked" : "") ?> /> <?php echo _('status:: retrouver sous forme de filtre dans la recherche') ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align:center;"><input type="button" onclick="document.location.replace('./statbits.php?p0=<?php echo $parm["p0"]; ?>')" value="<?php echo _('boutton::annuler'); ?>"/> <input type="submit" value="<?php echo _('boutton::valider'); ?>"/></td>
                    </tr>

                </table>
                <input type="hidden" name="act" value="APPLY" />
                <input type="hidden" name="p0" value="<?php echo $parm["p0"]; ?>" />
                <input type="hidden" name="bit" value="<?php echo $parm["bit"]; ?>" />
            </form>
        </body>
    </html>

    <?php
    exit;
}


foreach ($errors as $error) {
    echo "<p style='color:red;'>" . $error . "</p>";
}

$status = $databox->get_statusbits();
?>

<h1><?php echo _('phraseanet::status bit'); ?></h1>

<form enctype="multipart/form-data" method="post" name="chgStatbits" action="./statbits.php" onsubmit="return(false);" target="_self">
    <input type="hidden" name="p0" value="<?php echo $parm["p0"]; ?>" />
    <input type="hidden" name="bit" value="???" />
    <input type="hidden" name="act" value="???" />
</form>

<table class="ulist admintable" style="table-layout:fixed;width:640px" cellspacing="0" cellpadding="0">
    <thead>
    <th style="width:50px;"><?php echo _('status:: numero de bit'); ?></th>
    <th colspan="2"  style="width:40px;"/>
    <th style="width:150px;"><?php echo _('status:: nom'); ?></th>
    <th style="width:100px;"><?php echo _('status:: icone A'); ?></th>
    <th style="width:100px;"><?php echo _('status:: icone B'); ?></th>
    <th style="width:100px;"><?php echo _('status:: cherchable par tous'); ?></th>
    <th style="width:100px;"><?php echo _('status:: Affichable pour tous'); ?></th>
</thead>

<?php
for ($bit = 4; $bit < 64; $bit ++ ) {
    ?>
    <tr class="<?php echo $bit % 2 == 0 ? "odd" : "even" ?>">
        <td style="text-align:center"><?php echo $bit; ?></td>

    <?php
    if (isset($status[$bit])) {
        ?>
            <td style="text-align:center">
                <form id="editer_<?php echo $bit; ?>">
                    <input type="hidden" name="p0" value="<?php echo $parm['p0']; ?>" />
                    <input type="hidden" name="bit" value="<?php echo $bit; ?>" />
                    <input type="hidden" name="act" value="EDIT" />
                    <img class="editer" src="/skins/icons/edit_0.gif" onclick="document.getElementById('editer_<?php echo $bit; ?>').submit();">
                </form>
            </td>
            <td style="text-align:center">
                <form id="deleter_<?php echo $bit; ?>">
                    <input type="hidden" name="p0" value="<?php echo $parm['p0']; ?>" />
                    <input type="hidden" name="bit" value="<?php echo $bit; ?>" />
                    <input type="hidden" name="act" value="DELETE" />
                    <img class="deleter" src="/skins/icons/delete_0.gif" onclick="if(confirm('<?php echo str_replace("'", "\'", _('admin::status: confirmer la suppression du status ?')) ?>'))document.getElementById('deleter_<?php echo $bit; ?>').submit();">
                </form>
            </td>
        <?php
    } else {
        ?>
            <td colspan="2" style="text-align:center">
                <form id="adder_<?php echo $bit; ?>">
                    <input type="hidden" name="p0" value="<?php echo $parm['p0']; ?>" />
                    <input type="hidden" name="bit" value="<?php echo $bit; ?>" />
                    <input type="hidden" name="act" value="ADD" />
                    <img class="adder" src="/skins/icons/light_new.gif" onclick="document.getElementById('adder_<?php echo $bit; ?>').submit();">
                </form>
        <?php
    }

    if (isset($status[$bit])) {
        ?>
            <td style="text-align:center"><?php echo $status[$bit]["name"] ?></td>

            <td style="text-align:center">
        <?php echo $status[$bit]["img_off"] ? "<img title='" . $status[$bit]["labeloff"] . "' src='" . $status[$bit]["img_off"] . "'/>" : ""; ?>
            </td>

            <td style="text-align:center">
            <?php echo $status[$bit]["img_on"] ? "<img title='" . $status[$bit]["labelon"] . "' src='" . $status[$bit]["img_on"] . "'/>" : ""; ?>
            </td>

            <td><?php echo (isset($status[$bit]['searchable']) && $status[$bit]['searchable'] == '1') ? 'oui' : 'non'; ?></td>
            <td><?php echo (isset($status[$bit]['printable']) && $status[$bit]['printable'] == '1') ? 'oui' : 'non'; ?></td>

        <?php
    } else {
        ?>
            <td/><td/><td/><td/><td/>
        <?php
    }
    ?>
    </tr>
    <?php
}
?>
</table>
</body>
</html>
