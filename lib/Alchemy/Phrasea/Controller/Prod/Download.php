<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Download implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        /**
         * Download a set of documents
         *
         * name         : download
         *
         * description  : Download a set of documents
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/', $this->call('download'))
            ->bind('download');

        return $controllers;
    }

    /**
     * Download a set of documents
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  RedirectResponse
     */
    public function download(Application $app, Request $request)
    {
        $lst = $request->request->get('lst');
        $ssttid = $request->request->get('ssttid', '');
        $subdefs = $request->request->get('obj', array());

        $download = new \set_export($app, $lst, $ssttid);

        if(0 === $download->get_total_download()) {
            $app->abort(403);
        }

        $list = $download->prepare_export(
            $app['phraseanet.user'],
            $app['filesystem'],
            $subdefs,
            $request->request->get('title') === 'title' ? true : false,
            $request->request->get('businessfields')
        );

        $list['export_name'] = sprintf('%s.zip', $download->getExportName());

        $token = \random::getUrlToken(
            $app,
            \random::TYPE_DOWNLOAD,
            $app['phraseanet.user']->get_id(),
            new \DateTime('+3 hours'), // Token lifetime
            serialize($list)
        );

        if (!$token) {
            throw new \RuntimeException('Download token could not be generated');
        }

        $app['events-manager']->trigger('__DOWNLOAD__', array(
            'lst'         => $lst,
            'downloader'  => $app['phraseanet.user']->get_id(),
            'subdefs'     => $subdefs,
            'from_basket' => $ssttid,
            'export_file' => $download->getExportName()
        ));

        return $app->redirect('/prod/download/' . $token . '/');
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
