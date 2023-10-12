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
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $lst = $request->request->get('lst');
        $ssttid = $request->request->get('ssttid', '');
        $subdefs = $request->request->get('obj', []);

        $download = new \set_export($this->app, $lst, $ssttid);

        if (0 === $download->get_total_download()) {
            $this->app->abort(403);
        }

        $list = $download->prepare_export(
            $this->getAuthenticatedUser(),
            $this->app['filesystem'],
            $subdefs,
            $request->request->get('type') === 'title' ? true : false,
            $request->request->get('businessfields'),
            $request->request->get('stamp_choice') === "NO_STAMP" ? \set_export::NO_STAMP : \set_export::STAMP_SYNC
        );

        $list['export_name'] = sprintf('%s.zip', $download->getExportName());
        $token = $this->getTokenManipulator()->createDownloadToken($this->getAuthenticatedUser(), serialize($list));

        $this->getDispatcher()->dispatch(PhraseaEvents::EXPORT_CREATE, new ExportEvent(
                $this->getAuthenticatedUser(),
                $ssttid,
                $lst,
                $subdefs,
                $download->getExportName()
            )
        );

        /** @see DoDownloadController::prepareDownload */
        return $this->app->redirectPath('prepare_download', ['token' => $token->getValue()]);
    }

    /**
     * Download a set of documents
     *
     * @param  Request          $request
     * @return Response
     */
    public function checkDownloadAsync(Request $request)
    {
        if (!$this->isCrsfValid($request, 'prodExportDownload')) {
            $this->app->abort(403);
        }

        $lst = $request->request->get('lst');
        $ssttid = $request->request->get('ssttid', '');
        $subdefs = $request->request->get('obj', []);

        $download = new \set_export($this->app, $lst, $ssttid);

        if (0 === $download->get_total_download()) {
            $this->app->abort(403);
        }

        /** @see \set_export::prepare_export */
        $list = $download->prepare_export(
            $this->getAuthenticatedUser(),
            $this->app['filesystem'],
            $subdefs,
            $request->request->get('type') === 'title' ? true : false,
            $request->request->get('businessfields'),
            \set_export::STAMP_ASYNC,
            true
        );
        $list['export_name'] = sprintf('%s.zip', $download->getExportName());

        $records = [];

        foreach ($list['files'] as $file) {
            if (!is_array($file) || !isset($file['base_id']) || !isset($file['record_id'])) {
                continue;
            }
            $sbasId = \phrasea::sbasFromBas($this->app, $file['base_id']);

            try {
                $record = new \record_adapter($this->app, $sbasId, $file['record_id']);
            } catch (\Exception $e) {
                continue;
            }

            $records[sprintf('%s_%s', $sbasId, $file['record_id'])] = $record;
        }

        $token = $this->getTokenManipulator()->createDownloadToken($this->getAuthenticatedUser(), serialize($list));

        $url = $this->app->url('prepare_download', ['token' => $token->getValue(), 'anonymous' => false, 'type' => \Session_Logger::EVENT_EXPORTMAIL]);

        // ask the worker to build the zip
        $this->dispatch(PhraseaEvents::DOWNLOAD_ASYNC_CREATE, new DownloadAsyncEvent(
            $this->getAuthenticatedUser()->getId(),
            $token->getValue(),
            [
                'url'               =>  $url,
                'ssttid'            =>  $ssttid,
                'lst'               =>  $lst,
            ]
        ));

        $pusher_auth_key =$this->getConf()->get(['download_async', 'enabled'], false) ? $this->getConf()->get(['pusher', 'auth_key'], '') : null;
        return new Response($this->render(
        /** @uses templates/web/prod/actions/Download/prepare_async.html.twig */
            '/prod/actions/Download/prepare_async.html.twig', [
            'module_name'   => $this->app->trans('Export'),
            'module'        => $this->app->trans('Export'),
            'list'          => $list,
            'records'       => $records,
            'token'         => $token,
            'anonymous'     => $request->query->get('anonymous', false),
            'type'          => $request->query->get('type', \Session_Logger::EVENT_EXPORTDOWNLOAD),
            'pusher_auth_key' => $pusher_auth_key
        ]));

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

}
