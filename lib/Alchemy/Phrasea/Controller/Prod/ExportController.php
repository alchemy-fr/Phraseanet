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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    use DispatcherAware;
    use FilesystemAware;
    use NotifierAware;

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

        return new Response($this->render('common/dialog_export.html.twig', [
            'download'             => $download,
            'ssttid'               => $request->request->get('ssel'),
            'lst'                  => $download->serialize_list(),
            'default_export_title' => $this->getConf()->get(['registry', 'actions', 'default-export-title']),
            'choose_export_title'  => $this->getConf()->get(['registry', 'actions', 'export-title-choice'])
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
        } catch (\Exception $e) {
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
                $request->request->get('stamp_choice') === "NO_STAMP" ? \set_export::NO_STAMP : \set_export::STAMP_ASYNC
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
        } catch (\Exception $e) {
            return $this->app->json([
                'success' => false,
                'message' => $e->getMessage()//$this->app->trans('Something went wrong')
            ]);
        }
    }

    /**
     * Export document by mail
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function exportMail(Request $request)
    {
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
            $request->request->get('stamp_choice') === "NO_STAMP" ? \set_export::NO_STAMP : \set_export::STAMP_ASYNC
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
