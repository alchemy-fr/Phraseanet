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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class TOU implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->post('/deny/{sbas_id}/', function(Application $app, Request $request, $sbas_id) {
            $ret = array('success' => false, 'message' => '');

            try {
                $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

                $app['phraseanet.user']->ACL()->revoke_access_from_bases(
                    $app['phraseanet.user']->ACL()->get_granted_base(array(), array($databox->get_sbas_id()))
                );
                $app['phraseanet.user']->ACL()->revoke_unused_sbas_rights();

                $app->closeAccount();

                $ret = array('success' => true, 'message' => '');
            } catch (\Exception $e) {

            }

            return $app->json($ret);
        });

        $controllers->get('/', function(Application $app, Request $request) {

                $data = array();

                foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

                    $cgus = $databox->get_cgus();

                    if (!isset($cgus[$app['locale']])) {
                        continue;
                    }

                    $data[$databox->get_viewname()] = $cgus[$app['locale']]['value'];
                }

                return new Response($app['twig']->render('/prod/TOU.html.twig', array('TOUs' => $data)));
            });

        return $controllers;
    }
}
