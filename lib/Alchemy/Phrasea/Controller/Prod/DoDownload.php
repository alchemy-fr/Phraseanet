<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Http\DeliverDataInterface;
use Alchemy\Phrasea\Model\Entities\Token;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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
            ->before($app['middleware.token.converter'])
            ->bind('prepare_download')
            ->assert('token', '[a-zA-Z0-9]{8,32}');

        $controllers->match('/{token}/get/', 'controller.prod.do-download:downloadDocuments')
            ->before($app['middleware.token.converter'])
            ->bind('document_download')
            ->assert('token', '[a-zA-Z0-9]{8,32}');

        $controllers->post('/{token}/execute/', 'controller.prod.do-download:downloadExecute')
            ->before($app['middleware.token.converter'])
            ->bind('execute_download')
            ->assert('token', '[a-zA-Z0-9]{8,32}');

        return $controllers;
    }

    /**
     * Prepare a set of documents for download
     *
     * @param Application $app
     * @param Request     $request
     * @param Token       $token
     *
     * @return Response
     */
    public function prepareDownload(Application $app, Request $request, Token $token)
    {
<<<<<<< HEAD
        if (false === $list = @unserialize($token->getData())) {
            $app->abort(500, 'Invalid datas');
        }
        if (!is_array($list)) {
=======


        $datas = $app['tokens']->helloToken($token);

        if (false === $list = @unserialize((string) $datas['datas'])) {
>>>>>>> 3.8
            $app->abort(500, 'Invalid datas');
        }

        foreach (['export_name', 'files'] as $key) {
            if (!isset($list[$key])) {
                $app->abort(500, 'Invalid datas');
            }
        }

        $records = [];

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
            '/prod/actions/Download/prepare.html.twig', [
            'module_name'   => $app->trans('Export'),
            'module'        => $app->trans('Export'),
            'list'          => $list,
            'records'       => $records,
            'token'         => $token,
            'anonymous'     => $request->query->get('anonymous', false),
            'type'          => $request->query->get('type', \Session_Logger::EVENT_EXPORTDOWNLOAD)
        ]));
    }

    /**
     * Download a set of documents
     *
     * @param Application $app
     * @param Request     $request
     * @param Token       $token
     *
     * @return Response
     */
    public function downloadDocuments(Application $app, Request $request, Token $token)
    {
        if (false === $list = @unserialize($token->getData())) {
            $app->abort(500, 'Invalid datas');
        }
        if (!is_array($list)) {
            $app->abort(500, 'Invalid datas');
        }

        foreach (['export_name', 'files'] as $key) {
            if (!isset($list[$key])) {
                $app->abort(500, 'Invalid datas');
            }
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
            $exportFile = $app['tmp.download.path'].'/'.$token->getValue() . '.zip';
            $mime = 'application/zip';
        }

        if (!$app['filesystem']->exists($exportFile)) {
            $app->abort(404, 'Download file not found');
        }

        $app['dispatcher']->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($list, $app) {
            \set_export::log_download(
                $app,
                $list,
                $event->getRequest()->get('type'),
                !!$event->getRequest()->get('anonymous', false),
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
     * @param Token       $token
     *
     * @return Response
     */
    public function downloadExecute(Application $app, Request $request, Token $token)
    {
        if (false === $list = @unserialize($token->getData())) {
            return $app->json([
                'success' => false,
                'message' => 'Invalid datas'
            ]);
        }

        set_time_limit(0);
        // Force the session to be saved and closed.
        $app['session']->save();
        ignore_user_abort(true);

<<<<<<< HEAD
        \set_export::build_zip(
            $app,
            $token,
            $list,
            sprintf($app['tmp.download.path'].'/%s.zip', $token->getValue()) // Dest file
        );
=======
        if ($list['count'] > 1) {
            \set_export::build_zip($app, $token, $list, sprintf($app['root.path'] . '/tmp/download/%s.zip', $datas['value']));
        } else {
            $list['complete'] = true;
            $app['tokens']->updateToken($token, serialize($list));
        }
>>>>>>> 3.8

        return $app->json([
            'success' => true,
            'message' => ''
        ]);
    }
}
