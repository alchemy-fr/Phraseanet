<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Sphinx implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAdmin();
        });

        /**
         * Sphinx configuration
         *
         * name         : sphinx_display_configuration
         *
         * description  : Display sphinx configuration
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/configuration/', $this->call('getConfiguration'))
            ->bind('sphinx_display_configuration');

        /**
         * Sphinx configuration
         *
         * name         : sphinx_submit_configuration
         *
         * description  : Submit new sphinx configuration
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/configuration/', $this->call('submitConfiguration'))->bind('sphinx_submit_configuration');

        return $controllers;
    }

    /**
     * Get current sphinx configuration
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  Response
     */
    public function getConfiguration(Application $app, Request $request)
    {
        $selected_charsets = $app['phraseanet.registry']->get('sphinx_charset_tables');
        $selected_libstemmer = $app['phraseanet.registry']->get('sphinx_user_stemmer');

        $options = array(
            'charset_tables' => (!is_array($selected_charsets) ? array() : $selected_charsets),
            'libstemmer' => (!is_array($selected_libstemmer) ? array() : $selected_libstemmer)
        );

        return $app['twig']->render('admin/sphinx/configuration.html.twig', array(
            'configuration' => new \sphinx_configuration($app),
            'options'       => $options
        ));
    }

    /**
     * Submit a new sphinx configuration
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  RedirectResponse
     */
    public function submitConfiguration(Application $app, Request $request)
    {
        $app['phraseanet.registry']->set(
            'sphinx_charset_tables', $request->request->get('charset_tables', array()), \registry::TYPE_ARRAY
        );

        $app['phraseanet.registry']->set(
            'sphinx_user_stemmer', $request->request->get('libstemmer', array()), \registry::TYPE_ARRAY
        );

        return $app->redirect('/admin/sphinx/configuration/?success=1');
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
