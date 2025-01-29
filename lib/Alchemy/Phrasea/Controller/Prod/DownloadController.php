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
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\DownloadAsyncEvent;
use Alchemy\Phrasea\Core\Event\ExportEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Exception;
use set_export;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DownloadController extends Controller
{
    use DispatcherAware;

    /**
     * Download a set of documents
     *
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function checkDownload(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodExportDownload')) {
            $this->app->abort(403);
        }

        if (!empty($request->request->get('type'))) {
            $this->app['session']->set('download_name_type', $request->request->get('type'));
        }

        $lst = $request->request->get('lst');
        $ssttid = $request->request->get('ssttid', '');
        $subdefs = $request->request->get('obj', []);

        $download = new set_export($this->app, $lst, $ssttid);

        if (0 === $download->get_total_download()) {
            $this->app->abort(403);
        }

        $list = $download->prepare_export(
            $this->getAuthenticatedUser(),
            $this->getFilesystem(),
            $subdefs,
            $request->request->get('type') === 'title' ? true : false,
            $request->request->get('businessfields'),
            set_export::STAMP_SYNC,
            $request->request->get('stamp_choice') === "REMOVE_STAMP",
            false
        );

        $list['export_name'] = sprintf('%s.zip', $download->getExportName());
        $list['include_report'] = $request->request->get('include_report') === 'INCLUDE_REPORT';
        $list['include_businessfields'] = (bool)$request->request->get('businessfields');

        $lst = [];
        foreach ($list['files'] as $file) {
            $lst[] = $this->getApplicationBox()->get_collection($file['base_id'])->get_databox()->get_sbas_id() . '_' . $file['record_id'];
        }
        $lst = join(';', $lst);

        $token = $this->getTokenManipulator()->createDownloadToken($this->getAuthenticatedUser(), serialize($list));

        $this->getDispatcher()->dispatch(PhraseaEvents::EXPORT_CREATE, new ExportEvent(
                $this->getAuthenticatedUser(),
                $ssttid,
                $lst,
                $subdefs,
                $download->getExportName()
            )
        );

        /** @uses DoDownloadController::prepareDownload */
        return $this->app->redirectPath('prepare_download', ['token' => $token->getValue()]);
    }

    /**
     * display the downloasAsync page
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function listDownloadAsync(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodExportDownload')) {
            $this->app->abort(403);
        }

        if (!empty($request->request->get('type'))) {
            $this->app['session']->set('download_name_type', $request->request->get('type'));
        }

        $lst = $request->request->get('lst');
        $ssttid = $request->request->get('ssttid', '');
        $subdefs = $request->request->get('obj', []);

        $download = new set_export($this->app, $lst, $ssttid);

        if (0 === $download->get_total_download()) {
            $this->app->abort(403);
        }

        $list = $download->prepare_export(
            $this->getAuthenticatedUser(),
            $this->getFilesystem(),
            $subdefs,
            $request->request->get('type') === 'title' ? true : false,
            $request->request->get('businessfields'),
            set_export::STAMP_ASYNC,
            $request->request->get('stamp_choice') === "REMOVE_STAMP",
            true
        );
        $list['export_name'] = sprintf('%s.zip', $download->getExportName());
        $list['include_report'] = $request->request->get('include_report') === 'INCLUDE_REPORT';
        $list['include_businessfields'] = (bool)$request->request->get('businessfields');

        $records = [];

        foreach ($list['files'] as $file) {
            if (!is_array($file) || !isset($file['base_id']) || !isset($file['record_id'])) {
                continue;
            }
            $sbasId = \phrasea::sbasFromBas($this->app, $file['base_id']);

            try {
                $record = new \record_adapter($this->app, $sbasId, $file['record_id']);
            } catch (Exception $e) {
                continue;
            }

            $records[sprintf('%s_%s', $sbasId, $file['record_id'])] = $record;
        }

        $lst = [];
        foreach ($list['files'] as $file) {
            $lst[] = $this->getApplicationBox()->get_collection($file['base_id'])->get_databox()->get_sbas_id() . '_' . $file['record_id'];
        }
        $lst = join(';', $lst);

        $token = $this->getTokenManipulator()->createDownloadToken($this->getAuthenticatedUser(), serialize($list));

        $pusher_auth_key =$this->getConf()->get(['download_async', 'enabled'], false) ? $this->getConf()->get(['externalservice', 'pusher', 'auth_key'], '') : null;

        $this->getDispatcher()->dispatch(PhraseaEvents::EXPORT_CREATE, new ExportEvent(
                $this->getAuthenticatedUser(),
                $ssttid,
                $lst,
                $subdefs,
                $download->getExportName()
            )
        );

        $this->getDispatcher()->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($list) {
            \set_export::log_download(
                $this->app,
                $list,
                $event->getRequest()->get('type'),
                !!$event->getRequest()->get('anonymous', false),
                (isset($list['email']) ? $list['email'] : '')
            );
        });

        return new Response($this->render(
        /** @uses templates/web/prod/actions/Download/prepare_async.html.twig */
            '/prod/actions/Download/prepare_async.html.twig', [
            'module_name'     => $this->app->trans('Export'),
            'module'          => $this->app->trans('Export'),
            'list'            => $list,
            'records'         => $records,
            'token'           => $token,
            'anonymous'       => $request->query->get('anonymous', false),
            'type'            => $request->query->get('type', \Session_Logger::EVENT_EXPORTDOWNLOAD),
            'pusher_auth_key' => $pusher_auth_key,
            'csrfToken'       => $this->getSession()->get('prodExportDownload_token'),
        ]));
    }


    /**
     * @param Request $request
     * @return JsonResponse|void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function startDownloadAsync(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodExportDownload')) {
            $this->app->abort(403);
        }

        try {
            $token = $this->getTokenManipulator()->findValidToken($request->request->get('token', ""));

            if ($token) {
                // ask the worker to build the zip
                $this->dispatch(PhraseaEvents::DOWNLOAD_ASYNC_CREATE, new DownloadAsyncEvent(
                    $token->getUser()->getId(),
                    $token->getValue(),
                    [
                    ]
                ));

                return new JsonResponse([
                    'success' => true,
                    'token'   => $token->getValue()
                ]);
            }
            else {
                throw new Exception("invalid or expired token");
            }
        }
        catch(Exception $e) {
            // no-op
            $this->app->abort(403, $e->getMessage());
        }
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }

    /**
     * @return PropertyAccess
     */
    protected function getConf()
    {
        return $this->app['conf'];
    }

    /**
     * @return PropertyAccess
     */
    protected function getSession()
    {
        return $this->app['session'];
    }
    /**
     * @return PhraseanetFilesystem
     */
    private function getFilesystem()
    {
        return $this->app['filesystem'];
    }
}
