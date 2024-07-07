<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use ACL;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\ExportFailureEvent;
use Alchemy\Phrasea\Core\Event\ExportMailEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\WorkerManager\Event\ExportFtpEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use DOMDocument;
use DOMXPath;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    use DispatcherAware;
    use FilesystemAware;
    use NotifierAware;

    /**
     * @return ACL
     */
    private function getAclForConnectedUser()
    {
        return $this->getAclForUser($this->getAuthenticatedUser());
    }


    /**
     * Display form to export documents
     *
     * @param  Request     $request
     * @return Response
     */
    public function displayMultiExport(Request $request)
    {
        $download = new \set_export(
            $this->app,
            $request->request->get('lst', ''),
            $request->request->get('ssel', ''),
            $request->request->get('story')
        );

        // we must propose "do not stamp" when at least one collection is stamped AND the user has right to
        // remove stamp on this collection
        $removeable_stamp = false;          // true if at least one coll is "unstampable"
        $removeable_stamp_by_base = [];     // unset: no stamp ; false: stamp not "unstampable" ; true: stamp "unstampable"

        $colls_manageable   = array_keys($this->getAclForConnectedUser()->get_granted_base([ACL::COLL_MANAGE]) ?? []);
        $colls_editable     = array_keys($this->getAclForConnectedUser()->get_granted_base([ACL::CANMODIFRECORD]) ?? []);
        $colls_imgtoolsable = array_keys($this->getAclForConnectedUser()->get_granted_base([ACL::IMGTOOLS]) ?? []);
        $dbox_manageable    = array_keys($this->getAclForConnectedUser()->get_granted_sbas([ACL::BAS_MANAGE]) ?? []);

        foreach($download->get_elements() as $recordAdapter) {
            // check collection only once
            if(array_key_exists($bid = $recordAdapter->getCollection()->get_base_id(), $removeable_stamp_by_base)) {
                continue;
            }
            // check stamp
            $domprefs = new DOMDocument();
            if ( !$domprefs->loadXML($recordAdapter->getCollection()->get_prefs()) ) {
                continue;
            }
            $xpprefs = new DOMXPath($domprefs);
            if ($xpprefs->query('/baseprefs/stamp')->length == 0) {
                // the collection has no stamp settings
                continue;
            }
            unset($domprefs);
            // the collection has stamp, check user's right to remove it
            $removeable_stamp_by_base[$bid] = false;
            switch ((string)$this->getConf()->get(['registry', 'actions', 'export-stamp-choice'], false)) {
                case '1':   // == (string)true
                    // everybody can remove stamp (bc)
                    $removeable_stamp_by_base[$bid] = $removeable_stamp = true;
                    break;
                case 'manage_collection':
                    if (in_array($bid, $colls_manageable)) {
                        $removeable_stamp_by_base[$bid] = $removeable_stamp = true;
                    }
                    break;
                case 'record_edit':
                    if (in_array($bid, $colls_editable)) {
                        $removeable_stamp_by_base[$bid] = $removeable_stamp = true;
                    }
                    break;
                case 'image_tools':
                    if (in_array($bid, $colls_imgtoolsable)) {
                        $removeable_stamp_by_base[$bid] = $removeable_stamp = true;
                    }
                    break;
                case 'manage_databox':
                    if (in_array($recordAdapter->getDatabox()->get_sbas_id(), $dbox_manageable)) {
                        $removeable_stamp_by_base[$bid] = $removeable_stamp = true;
                    }
                    break;
            }
        }

        $this->setSessionFormToken('prodExportDownload');
        $this->setSessionFormToken('prodExportEmail');
        $this->setSessionFormToken('prodExportFTP');
        $this->setSessionFormToken('prodExportOrder');

        return new Response($this->render('common/dialog_export.html.twig', [
            'download'                  => $download,
            'ssttid'                    => $request->request->get('ssel'),
            'lst'                       => $download->serialize_list(),
            'default_export_title'      => $this->getConf()->get(['registry', 'actions', 'default-export-title']),
            'choose_export_title'       => $this->getConf()->get(['registry', 'actions', 'export-title-choice']),
            'removeable_stamp'          => $removeable_stamp,
            'removeable_stamp_by_base'  => $removeable_stamp_by_base,
        ]));
    }

    /**
     * Test a FTP connexion
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function testFtpConnexion(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $success = false;
        $msg = $this->app->trans('Error while connecting to FTP');

        try {
            /** @var \ftpclient $ftpClient */
            $ftpClient = $this->app['phraseanet.ftp.client']($request->request->get('address', ''), 21, 90, !!$request->request->get('ssl'));
            $ftpClient->login($request->request->get('login', 'anonymous'), $request->request->get('password', 'anonymous'));
            $ftpClient->close();
            $msg = $this->app->trans('Connection to FTP succeed');
            $success = true;
        }
        catch (Exception $e) {
            // no-op
        }

        return $this->app->json([
            'success' => $success,
            'message' => $msg
        ]);
    }

    /**
     * @param  Request      $request
     * @return JsonResponse
     */
    public function exportFtp(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodExportFTP')) {
            return $this->app->json(['message' => 'invalid export ftp form'], 403);
        }

        $download = new \set_exportftp($this->app, $request->request->get('lst'), $request->request->get('ssttid'));

        $mandatoryParameters = ['address', 'login', 'obj'];

        foreach ($mandatoryParameters as $parameter) {
            if (!$request->request->get($parameter)) {
                $this->app->abort(400, sprintf('required parameter `%s` is missing', $parameter));
            }
        }

        if (count($download->get_display_ftp()) == 0) {
            return $this->app->json([
                'success' => false,
                'message' => $this->app->trans("You do not have required rights to send these documents over FTP")
            ]);
        }

        try {
            $download->prepare_export(
                $this->getAuthenticatedUser(),
                $this->getFilesystem(),
                $request->request->get('obj'),
                false,
                $request->request->get('businessfields'),
                \set_export::STAMP_ASYNC,
                $request->request->get('stamp_choice') === "REMOVE_STAMP",
                false
            );

            $exportFtpId = $download->export_ftp(
                $request->request->get('user_dest'),
                $request->request->get('address'),
                $request->request->get('login'),
                $request->request->get('password', ''),
                $request->request->get('ssl'),
                3,
                $request->request->get('passive'),
                $request->request->get('dest_folder'),
                $request->request->get('prefix_folder'),
                $request->request->get('logfile'),
                true
            );

            $this->dispatch(WorkerEvents::EXPORT_FTP, new ExportFtpEvent($exportFtpId));

            return $this->app->json([
                'success' => true,
                'message' => $this->app->trans('Export saved in the waiting queue')
            ]);
        }
        catch (Exception $e) {
            return $this->app->json([
                'success' => false,
                'message' => $e->getMessage()//$this->app->trans('Something went wrong')
            ]);
        }
    }

    /**
     * Export document by mail
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function exportMail(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodExportEmail')) {
            return $this->app->json(['message' => 'invalid export mail form'], 403);
        }

        set_time_limit(0);
        session_write_close();
        ignore_user_abort(true);

        $lst = $request->request->get('lst', '');
        $ssttid = $request->request->get('ssttid', '');

        //prepare export
        $download = new \set_export($this->app, $lst, $ssttid);
        $list = $download->prepare_export(
            $this->getAuthenticatedUser(),
            $this->getFilesystem(),
            (array) $request->request->get('obj'),
            $request->request->get("type") == "title" ? : false,
            $request->request->get('businessfields'),
            \set_export::STAMP_ASYNC,
            $request->request->get('stamp_choice') === "REMOVE_STAMP",
            true
        );

        $list['export_name'] = sprintf("%s.zip", $download->getExportName());

        $separator = '/\ |\;|\,/';
        // add PREG_SPLIT_NO_EMPTY to only return non-empty values
        $list['email'] = implode(',', preg_split($separator, $request->request->get("taglistdestmail", ""), -1, PREG_SPLIT_NO_EMPTY));

        $destMails = [];
        //get destination mails
        foreach (explode(",", $list['email']) as $mail) {
            if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $destMails[] = $mail;
            } else {
                $this->dispatch(PhraseaEvents::EXPORT_MAIL_FAILURE, new ExportFailureEvent(
                    $this->getAuthenticatedUser(),
                    $ssttid,
                    $lst,
                    \eventsmanager_notify_downloadmailfail::MAIL_NO_VALID,
                    $mail
                ));
            }
        }

        $token = $this->getTokenManipulator()->createEmailExportToken(serialize($list));

        if (count($destMails) > 0) {
            $emitterId = $this->getAuthenticatedUser()->getId();

            $tokenValue = $token->getValue();

            $url = $this->app->url('prepare_download', ['token' => $token->getValue(), 'anonymous' => false, 'type' => \Session_Logger::EVENT_EXPORTMAIL]);

            $params = [
                'url'               =>  $url,
                'textmail'          =>  $request->request->get('textmail'),
                'reading_confirm'   =>  !!$request->request->get('reading_confirm', false),
                'ssttid'            =>  $ssttid = $request->request->get('ssttid', ''),
                'lst'               =>  $lst = $request->request->get('lst', ''),
            ];

            $this->dispatch(PhraseaEvents::EXPORT_MAIL_CREATE, new ExportMailEvent(
                $emitterId,
                $tokenValue,
                $destMails,
                $params
            ));
        }

        return $this->app->json([
            'success' => true,
            'message' => ''
        ]);
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }
}
