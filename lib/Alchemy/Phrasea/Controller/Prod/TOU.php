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

        $controllers->post('/deny/{sbas_id}/', function(Application $app, Request $request, $sbas_id) {
                $ret = array('success' => false, 'message' => '');

                try {
                    $user = $app['phraseanet.core']->getAuthenticatedUser();
                    $session = \Session_Handler::getInstance($app['phraseanet.appbox']);

                    $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

                    $user->ACL()->revoke_access_from_bases(
                        $user->ACL()->get_granted_base(array(), array($databox->get_sbas_id()))
                    );
                    $user->ACL()->revoke_unused_sbas_rights();

                    $session->logout();

                    $ret = array('success' => true, 'message' => '');
                } catch (\Exception $e) {

                }

                return $app->json($ret);
            });

        return $controllers;
    }
}
