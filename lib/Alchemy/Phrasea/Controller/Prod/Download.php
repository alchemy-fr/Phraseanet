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

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Download implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.prod.download'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->post('/', 'controller.prod.download:checkDownload')
            ->bind('check_download');

        return $controllers;
    }

    /**
     * Download a set of documents
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function checkDownload(Application $app, Request $request)
    {
        $lst = $request->request->get('lst');
        $ssttid = $request->request->get('ssttid', '');
        $subdefs = $request->request->get('obj', array());

        $download = new \set_export($app, $lst, $ssttid);

        if (0 === $download->get_total_download()) {
            $app->abort(403);
        }

        $list = $download->prepare_export(
            $app['authentication']->getUser(),
            $app['filesystem'],
            $subdefs,
            $request->request->get('title') === 'title' ? true : false,
            $request->request->get('businessfields')
        );

        $list['export_name'] = sprintf('%s.zip', $download->getExportName());

        $token = $app['tokens']->getUrlToken(
            \random::TYPE_DOWNLOAD,
            $app['authentication']->getUser()->get_id(),
            new \DateTime('+3 hours'), // Token lifetime
            serialize($list)
        );

        if (!$token) {
            throw new \RuntimeException('Download token could not be generated');
        }

        $app['events-manager']->trigger('__DOWNLOAD__', array(
            'lst'         => $lst,
            'downloader'  => $app['authentication']->getUser()->get_id(),
            'subdefs'     => $subdefs,
            'from_basket' => $ssttid,
            'export_file' => $download->getExportName()
        ));

        return $app->redirectPath('prepare_download', array('token' => $token));
    }
}
