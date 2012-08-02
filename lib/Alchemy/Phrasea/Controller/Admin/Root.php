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
class Root implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app, Request $request) {

                $Core = $app['phraseanet.core'];
                $appbox = $app['phraseanet.appbox'];
                $user = $Core->getAuthenticatedUser();

                \User_Adapter::updateClientInfos(3);

                $section = $request->query->get('section', false);

                $available = array(
                    'connected'
                    , 'registrations'
                    , 'taskmanager'
                    , 'base'
                    , 'bases'
                    , 'collection'
                    , 'user'
                    , 'users'
                );

                $feature = 'connected';
                $featured = false;
                $position = explode(':', $section);
                if (count($position) > 0) {
                    if (in_array($position[0], $available)) {
                        $feature = $position[0];

                        if (isset($position[1])) {
                            $featured = $position[1];
                        }
                    }
                }

                $databoxes = $off_databoxes = array();
                foreach ($appbox->get_databoxes() as $databox) {
                    try {
                        if ( ! $user->ACL()->has_access_to_sbas($databox->get_sbas_id())) {
                            continue;
                        }

                        $databox->get_connection();
                    } catch (\Exception $e) {
                        $off_databoxes[] = $databox;
                        continue;
                    }

                    $databoxes[] = $databox;
                }

                return new Response($app['twig']->render('admin/index.html.twig', array(
                            'module'        => 'admin'
                            , 'events'        => \eventsmanager_broker::getInstance($appbox, $Core)
                            , 'module_name'   => 'Admin'
                            , 'notice'        => $request->query->get("notice")
                            , 'feature'       => $feature
                            , 'featured'      => $featured
                            , 'databoxes'     => $databoxes
                            , 'off_databoxes' => $off_databoxes
                            , 'tree'          => \module_admin::getTree($section)
                        ))
                );
            });

        return $controllers;
    }
}
