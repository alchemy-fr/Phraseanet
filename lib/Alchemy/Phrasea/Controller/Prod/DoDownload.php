<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Http\DeliverDataInterface;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DoDownload implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.prod.do-download'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->get('/{token}/prepare/', 'controller.prod.do-download:prepareDownload')
            ->bind('prepare_download')
            ->assert('token', '[a-zA-Z0-9]{8,16}');

        $controllers->match('/{token}/get/', 'controller.prod.do-download:downloadDocuments')
            ->bind('document_download')
            ->assert('token', '[a-zA-Z0-9]{8,16}');

        $controllers->post('/{token}/execute/', 'controller.prod.do-download:downloadExecute')
            ->bind('execute_download')
            ->assert('token', '[a-zA-Z0-9]{8,16}');

        return $controllers;
    }

    /**
     * Prepare a set of documents for download
     *
     * @param Application $app
     * @param Request     $request
     * @param String      $token
     *
     * @return Response
     */
    public function prepareDownload(Application $app, Request $request, $token)
    {
        $datas = $app['tokens']->helloToken($token);

        if (false === $list = @unserialize((string) $datas['datas'])) {
            $app->abort(500, 'Invalid datas');
        }

        $records = array();

        foreach ($list['files'] as $file) {
            if (!is_array($file) || !isset($file['base_id']) || !isset($file['record_id'])) {
                continue;
            }
            $sbasId = \phrasea::sbasFromBas($app, $file['base_id']);

            try {
                $record = new \record_adapter($app, $sbasId, $file['record_id']);
            } catch (\Exception $e) {
                continue;
            }

            $records[sprintf('%s_%s', $sbasId, $file['record_id'])] = $record;
        }

        return new Response($app['twig']->render(
            '/prod/actions/Download/prepare.html.twig', array(
            'module_name'   => _('Export'),
            'module'        => _('Export'),
            'list'          => $list,
            'records'       => $records,
            'token'         => $token,
            'anonymous'     => $request->query->get('anonymous', false)
        )));
    }

    /**
     * Download a set of documents
     *
     * @param Application $app
     * @param Request     $request
     * @param String      $token
     *
     * @return Response
     */
    public function downloadDocuments(Application $app, Request $request, $token)
    {
        $datas = $app['tokens']->helloToken($token);

        if (false === $list = @unserialize((string) $datas['datas'])) {
            $app->abort(500, 'Invalid datas');
        }

        $exportName = $list['export_name'];

        if ($list['count'] === 1) {
            $file = end($list['files']);
            $subdef = end($file['subdefs']);
            $exportName = sprintf('%s%s.%s', $file['export_name'], $subdef['ajout'], $subdef['exportExt']);
            $exportFile = \p4string::addEndSlash($subdef['path']) . $subdef['file'];
            $mime = $subdef['mime'];
            $list['complete'] = true;
        } else {
            $exportFile = $app['root.path'] . '/tmp/download/' . $datas['value'] . '.zip';
            $mime = 'application/zip';
        }

        if (!$app['filesystem']->exists($exportFile)) {
            $app->abort(404, 'Download file not found');
        }

        $app['dispatcher']->addListener(KernelEvents::TERMINATE, function (PostResponseEvent $event) use ($list, $app) {
            \set_export::log_download(
                $app,
                $list,
                $event->getRequest()->request->get('type'),
                (null !== $event->getRequest()->request->get('anonymous') ? true : false),
                (isset($list['email']) ? $list['email'] : '')
            );
        });

        return $app['phraseanet.file-serve']->deliverFile($exportFile, $exportName, DeliverDataInterface::DISPOSITION_ATTACHMENT, $mime);
    }

    /**
     * Build a zip of downloaded documents
     *
     * @param Application $app
     * @param Request     $request
     * @param String      $token
     *
     * @return Response
     */
    public function downloadExecute(Application $app, Request $request, $token)
    {
        try {
            $datas = $app['tokens']->helloToken($token);
        } catch (NotFoundHttpException $e) {
            return $app->json(array(
                'success' => false,
                'message' => 'Invalid token'
            ));
        }

        if (false === $list = @unserialize((string) $datas['datas'])) {
            return $app->json(array(
                'success' => false,
                'message' => 'Invalid datas'
            ));
        }

        set_time_limit(0);
        // Force the session to be saved and closed.
        $app['session']->save();
        ignore_user_abort(true);

        \set_export::build_zip(
            $app,
            $token,
            $list,
            sprintf($app['root.path'] . '/tmp/download/%s.zip', $datas['value']) // Dest file
        );

        return $app->json(array(
            'success' => true,
            'message' => ''
        ));
    }
}
