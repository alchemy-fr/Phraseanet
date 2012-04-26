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
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class TOU implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = new ControllerCollection();

        $controllers->post('/deny/{sbas_id}/', function(Application $app, Request $request, $sbas_id) {
                $ret = array('success' => false, 'message' => '');

                try {
                    $user = $app['Core']->getAuthenticatedUser();
                    $session = \Session_Handler::getInstance(\appbox::get_instance($app['Core']));

                    $databox = \databox::get_instance((int) $sbas_id);

                    $user->ACL()->revoke_access_from_bases(
                        $user->ACL()->get_granted_base(array(), array($databox->get_sbas_id()))
                    );
                    $user->ACL()->revoke_unused_sbas_rights();

                    $session->logout();

                    $ret = array('success' => true, 'message' => '');
                } catch (\Exception $e) {

                }

                $Serializer = $app['Core']['Serializer'];
                $datas = $Serializer->serialize($ret, 'json');

                return new Response($datas, 200, array('Content-Type' => 'application/json'));
            });

        return $controllers;
    }
}
