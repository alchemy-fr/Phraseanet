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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Setup implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
                return $app['phraseanet.core']['Firewall']->requireAdmin($app);
            });

        /**
         * Get globals values
         *
         * name         : setup_display_globals
         *
         * description  : Display globals values
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('getGlobals'))
            ->bind('setup_display_globals');

        /**
         * Submit global values
         *
         * name         : setup_submit_globals
         *
         * description  : Change globals values
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/', $this->call('postGlobals'))
            ->bind('setup_submit_globals');

        return $controllers;
    }

    /**
     * Display global values
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  Response
     */
    public function getGlobals(Application $app, Request $request)
    {
        \phrasea::start($app['phraseanet.core']);

        require_once __DIR__ . "/../../../../conf.d/_GV_template.inc";

        if (null !== $update = $request->get('update')) {
            if ('ok' === $update) {
                $update = _('Update succeed');
            } else {
                $update = _('Update failed');
            }
        }

        return new Response($app['twig']->render('admin/setup.html.twig', array(
                    'GV'                => $GV,
                    'update_post_datas' => $update,
                    'listTimeZone'      => \DateTimeZone::listAbbreviations()
                )));
    }

    /**
     * Submit global values
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  RedirectResponse
     */
    public function postGlobals(Application $app, Request $request)
    {
        if (\setup::create_global_values($app['phraseanet.core']['Registry'], $request->request->all())) {
            return $app->redirect('/admin/globals/?update=ok');
        }

        return $app->redirect('/admin/globals/?update=ko');
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
