<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
use Alchemy\Phrasea\Application;
use Silex\Application as SilexApplication;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Setup implements ControllerProviderInterface
{
    public function connect(SilexApplication $app)
    {
        $app['controller.admin.setup'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAdmin();
        });

        $controllers->get('/', 'controller.admin.setup:getGlobals')
            ->bind('setup_display_globals');

        $controllers->post('/', 'controller.admin.setup:postGlobals')
            ->bind('setup_submit_globals');

        return $controllers;
    }

    /**
     * Display global values
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function getGlobals(Application $app, Request $request)
    {
        $GV = require_once __DIR__ . "/../../../../conf.d/_GV_template.inc";

        if (null !== $update = $request->query->get('update')) {
            if (!!$update) {
                $update = _('Update succeed');
            } else {
                $update = _('Update failed');
            }
        }

        return $app['twig']->render('admin/setup.html.twig', [
            'GV'                => $GV,
            'update_post_datas' => $update,
            'listTimeZone'      => \DateTimeZone::listAbbreviations()
        ]);
    }

    /**
     * Submit global values
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function postGlobals(Application $app, Request $request)
    {
        if (\setup::create_global_values($app, $request->request->all())) {
            return $app->redirectPath('setup_display_globals', [
                'success' => 1
            ]);
        }

        return $app->redirectPath('setup_display_globals', [
            'success' => 0
        ]);
    }
}
