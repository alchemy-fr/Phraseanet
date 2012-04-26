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
require_once __DIR__ . '/../../lib/classes/http/request.class.php';

$request = http_request::getInstance();
$parm = $request->get_parms('session', 'coll', 'status');

if ($parm["session"]) {
    session_id($parm["session"]);
}
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance(\bootstrap::getCore());
$session = $appbox->get_session();
define("DEFAULT_MIMETYPE", "application/octet-stream");

if ($request->comes_from_flash())
    define("UPLOADER", "FLASH");
else
    define("UPLOADER", "HTML");

if ( ! isset($_FILES['Filedata'])) {
    if (UPLOADER == 'FLASH')
        header('HTTP/1.1 500 Internal Server Error');
    else
        echo '<script type="text/javascript">parent.classic_uploaded("' . _("Internal Server Error") . '")</script>';
    exit;
}

if ($_FILES['Filedata']['error'] > 0) {
    if (UPLOADER == 'FLASH')
        header('HTTP/1.1 500 Internal Server Error');
    else
        echo '<script type="text/javascript">parent.classic_uploaded("' . _("Internal Server Error") . '")</script>';
    exit(0);
}

$sbas_id = false;
$usr_id = $Core->getAuthenticatedUser()->get_id();

$sbas_id = phrasea::sbasFromBas($parm['coll']);

$base_id = $parm['coll'];

$chStatus = User_Adapter::getInstance($usr_id, $appbox)->ACL()->has_right_on_base($base_id, 'chgstatus');



$ext = pathinfo($_FILES['Filedata']["name"]);

$newname = $_FILES['Filedata']['tmp_name'] . '.' . (isset($ext['extension']) ? $ext['extension'] : '');

if ($newname !== $_FILES['Filedata']['tmp_name'])
    if (rename($_FILES['Filedata']['tmp_name'], $newname)
    )
        ;
$_FILES['Filedata']['tmp_name'] = $newname;

$filename = new system_file($_FILES['Filedata']['tmp_name']);

$mask_oui = '0000000000000000000000000000000000000000000000000000000000000000';
$mask_non = '1111111111111111111111111111111111111111111111111111111111111111';
if ($sbas_id !== false && is_array($parm['status'])) {
    $mask_oui = '0000000000000000000000000000000000000000000000000000000000000000';
    $mask_non = '1111111111111111111111111111111111111111111111111111111111111111';

    foreach ($parm['status'] as $k => $v) {
        if ((int) $k <= 63 && (int) $k >= 4) {
            if ($v == '0')
                $mask_non[63 - (int) $k] = $v;
            elseif ($v == '1')
                $mask_oui[63 - (int) $k] = $v;
        }
    }
}

try {
    $sha256 = $filename->get_sha256();

    $uuid = false;
    if ( ! $filename->has_uuid()) {
        try {
            $tmp_record = record_adapter::get_record_by_sha($sbas_id, $sha256);

            if ( ! $tmp_record)
                throw new Exception('bad luck');
            if (is_array($tmp_record))
                $tmp_record = array_shift($tmp_record);

            $tmp_uuid = $tmp_record->get_uuid();

            if ($tmp_uuid && uuid::is_valid($tmp_uuid)) {
                $uuid = $tmp_uuid;
            }
        } catch (Exception $e) {

        }
    }

    $filename->write_uuid($uuid);

    $error_file = p4file::check_file_error($filename->getPathname(), $sbas_id, $_FILES['Filedata']["name"]);
    $status_2 = databox_status::operation_and($mask_oui, $mask_non);
    if ( ! $filename->is_new_in_base(phrasea::sbasFromBas($base_id)) || count($error_file) > 0) {
        if ( ! lazaretFile::move_uploaded_to_lazaret($filename, $base_id, $_FILES['Filedata']["name"], implode("\n", $error_file), $status_2)) {
            if (UPLOADER == 'FLASH')
                header('HTTP/1.1 500 Internal Server Error');
            else
                echo '<script type="text/javascript">parent.classic_uploaded("' . _("erreur lors de l'archivage") . '")</script>';
        }
        else
            exit(_('Document ajoute a la quarantaine'));

        if (UPLOADER == 'HTML')
            echo '<script type="text/javascript">parent.classic_uploaded("' . _("Fichier uploade, en attente") . '")</script>';
        exit;
    }
} catch (Exception $e) {

}



if (($record_id = p4file::archiveFile($filename, $base_id, true, $_FILES['Filedata']["name"])) === false) {
    unlink($filename->getPathname());
    if (UPLOADER == 'FLASH')
        header('HTTP/1.1 500 Internal Server Error');
    else
        echo '<script type="text/javascript">parent.classic_uploaded("' . _("erreur lors de l'archivage") . '")</script>';
    exit(0);
}


if ($chStatus === true && $sbas_id !== false && is_array($parm['status'])) {

    try {
        $record = new record_adapter($sbas_id, $record_id);
        $status = databox_status::operation_or($record->get_status(), $mask_oui);
        $record->set_binary_status(databox_status::operation_and($status, $mask_non));
    } catch (Exception $e) {

    }
}
if (file_exists($filename->getPathname()))
    unlink($filename->getPathname());

if (UPLOADER == 'HTML')
    echo '<script type="text/javascript">parent.classic_uploaded("' . _("Fichier uploade !") . '")</script>';
exit(0);
