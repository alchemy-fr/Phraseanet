<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../lib/bootstrap.php";
$app = new Application();

$user = $app['phraseanet.user'];

$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action');

$action = $parm['action'];

switch ($action) {
    case 'search':
        $engine = new searchEngine_adapter_sphinx_engine();

        $parm = $request->get_parms("bas", "term"
            , "stemme"
            , "search_type", "recordtype", "status", "fields", "datemin", "datemax", "datefield");

        $options = new searchEngine_options();


        $parm['bas'] = is_array($parm['bas']) ? $parm['bas'] : array_keys($user->ACL()->get_granted_base());

        /* @var $user \User_Adapter */
        if ($user->ACL()->has_right('modifyrecord')) {
            $options->set_business_fields(array());

            $BF = array();

            foreach ($user->ACL()->get_granted_base(array('canmodifrecord')) as $collection) {
                if (count($params['bases']) === 0 || in_array($collection->get_base_id(), $params['bases'])) {
                    $BF[] = $collection->get_base_id();
                }
            }
            $options->set_business_fields($BF);
        } else {
            $options->set_business_fields(array());
        }


        $options->set_bases($parm['bas'], $user->ACL());
        if (!!is_array($parm['fields']))
            $parm['fields'] = array();
        $options->set_fields($parm['fields']);
        if (!is_array($parm['status']))
            $parm['status'] = array();
        $options->set_status($parm['status']);
        $options->set_search_type($parm['search_type']);
        $options->set_record_type($parm['recordtype']);
        $options->set_min_date($parm['datemin']);
        $options->set_max_date($parm['datemax']);
        $options->set_date_fields(explode('|', $parm['datefield']));
        $options->set_use_stemming($parm['stemme']);

        $engine->set_options($options);
        $result = $engine->results($parm['term'], 0, 1);
        $res = $engine->get_suggestions($app['locale.I18n'], true);
        $output = p4string::jsonencode($res);

        break;

    case 'CSS':
        require ($app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
        $parm = $request->get_parms('color');
        $output = $user->setPrefs('css', $parm['color']);
        break;

    case 'SAVETEMPPREF':
        $parm = $request->get_parms('prop', 'value');
        $app['session']->set('user-pref-' . $parm['prop'], $parm['value']);
        $output = 1;
        break;

    case 'DELETE':
        require ($app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
        $parm = $request->get_parms('lst');
        $output = whatCanIDelete($app, $parm['lst']);
        break;
    case 'DODELETE':
        require ($app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
        $parm = $request->get_parms('lst', 'del_children');
        $output = deleteRecord($app, $parm['lst'], $parm['del_children']);
        break;

    case 'READ_NOTIFICATIONS':
        try {
            $evt_mngr = $app['events-manager'];
            $parm = $request->get_parms('notifications');
            $output = $evt_mngr->read(explode('_', $parm['notifications']), $app['phraseanet.user']->get_id());
            $output = p4string::jsonencode(array('error'   => false, 'message' => ''));
        } catch (Exception $e) {
            $output = p4string::jsonencode(array('error'   => true, 'message' => $e->getMessage()));
        }
        break;
    case 'NOTIFICATIONS_FULL':
        $evt_mngr = $app['events-manager'];
        $parm = $request->get_parms('page');
        $output = $evt_mngr->get_json_notifications($parm['page']);
        break;





    case 'VIDEOTOKEN':
        $parm = $request->get_parms(!'sbas_id', 'record_id');
        $sbas_id = (int) $parm['sbas_id'];
        $record = new record_adapter($app, $sbas_id, $parm['record_id']);

        $output = p4string::jsonencode(array('url' => $record->get_preview()->renew_url()));
        break;



    case 'ANSWERTRAIN':
        $parm = $request->get_parms('pos', 'record_id', 'options_serial', 'query');

        $search_engine = null;
        if (($options = unserialize($parm['options_serial'])) !== false) {
            $search_engine = new searchEngine_adapter($app);
            $search_engine->set_options($options);
        }

        $record = new record_preview($app, 'RESULT', $parm['pos'], '', '', $search_engine, $parm['query']);
        $records = $record->get_train($parm['pos'], $parm['query'], $search_engine);
        $output = p4string::jsonencode(
                array('current' =>
                    $app['twig']->render(
                        'prod/preview/result_train.html.twig', array(
                        'records'  => $records
                        , 'selected' => $parm['pos'])
                    )
                )
        );
        break;


    case 'REGTRAIN':
        $parm = $request->get_parms('cont', 'pos');
        $record = new record_preview($app, 'REG', $parm['pos'], $parm['cont']);
        $output = $twig->render('prod/preview/reg_train.html.twig', array('container_records' => $record->get_container()->get_children(),
            'record'            => $record));
        break;

    case "FTP_EXPORT":

        $request = http_request::getInstance();
        $parm = $request->get_parms(
            "addr"   // addr du srv ftp
            , "login" // login ftp
            , "pwd"  // pwd ftp
            , "passif" // mode passif ou non
            , "nbretry" // nb retry
            , "ssl" // nb retry
            , "obj" // les types d'obj a exporter
            , "destfolder"// le folder de destination
            , "usr_dest"  // le mail dudestinataire ftp
            , "lst"  // la liste des objets
            , "ssttid"
            , "sendermail"
            , "namecaract"
            , "NAMMKDFOLD"
            , "logfile"
            , "businessfields"
        );

        $download = new set_exportftp($parm['lst'], $parm['ssttid']);

        if (count($download->get_display_ftp()) == 0) {
            $output = p4string::jsonencode(array('error'   => true, 'message' => _('Les documents ne peuvent etre envoyes par FTP')));
        } else {
            try {
                $download->prepare_export($app['phraseanet.user'], $app['filesystem'], $parm['obj'], false, $parm['businessfields']);
                $download->export_ftp(
                    $parm['usr_dest']
                    , $parm['addr']
                    , $parm['login']
                    , $parm['pwd']
                    , $parm['ssl']
                    , $parm['nbretry']
                    , $parm['passif']
                    , $parm['destfolder']
                    , $parm['NAMMKDFOLD']
                    , $parm['logfile']
                );

                $out = array(
                    'error'   => false
                    , 'message' => _('Export enregistre dans la file dattente')
                );

                $output = p4string::jsonencode($out);
            } catch (Exception $e) {
                $output = p4string::jsonencode(array('error'   => true, 'message' => $e->getMessage()));
            }
        }
        break;
    case "FTP_TEST":

        $request = http_request::getInstance();
        $parm = $request->get_parms(
            "addr"   // addr du srv ftp
            , "login" // login ftp
            , "pwd"  // pwd ftp
            , "ssl" // nb retry
        );

        $ssl = $parm['ssl'] == '1';

        try {
            $ftp_client = new ftpclient($parm['addr'], 21, 90, $ssl = false);
            $ftp_client->login($parm['login'], $parm['pwd']);
            $ftp_client->close();
            $output = _('Connection au FTP avec succes');
        } catch (Exception $e) {
            $output = sprintf(_('Erreur lors de la connection au FTP : %s'), $e->getMessage());
        }

        break;
}
echo $output;


