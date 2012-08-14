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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

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

        $controllers->get('/configuration/', function(Application $app, Request $request) {
                $registry = $app['phraseanet.core']['Registry'];

                $sphinxConf = new \sphinx_configuration();

                $selected_charsets = $registry->get('sphinx_charset_tables');
                $selected_libstemmer = $registry->get('sphinx_user_stemmer');

                $options = array(
                    'charset_tables' => ( ! is_array($selected_charsets) ? array() : $selected_charsets),
                    'libstemmer' => ( ! is_array($selected_libstemmer) ? array() : $selected_libstemmer)
                );

                return new Response($app['twig']->render('admin/sphinx/configuration.html.twig', array(
                            'configuration' => $sphinxConf,
                            'options'       => $options
                        )));
            });

        $controllers->post('/configuration/', function(Application $app, Request $request) {
                $registry = $app['phraseanet.core']['Registry'];

                $registry->set(
                    'sphinx_charset_tables', $request->get('charset_tables', array()), \registry::TYPE_ARRAY
                );

                $registry->set(
                    'sphinx_user_stemmer', $request->get('libstemmer', array()), \registry::TYPE_ARRAY
                );

                return $app->redirect('/admin/sphinx/configuration/?update=ok');
            });

        return $controllers;
    }
}
