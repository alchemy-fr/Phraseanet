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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        /**
         * Deny terms of use
         *
         * name         : deny_tou
         *
         * description  : Deny terms of use
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/deny/{sbas_id}/', $this->call('denyTermsOfUse'))
            ->bind('deny_tou')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireAuthentication();
            });

        /**
         * Display Terms of use
         *
         * name         : get_tou
         *
         * description  : Display Terms of use
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('displayTermsOfUse'))
            ->bind('get_tou');

        return $controllers;
    }

    /**
     * Deny database terms of use
     *
     * @param Application   $app
     * @param Request       $request
     * @param integer       $sbas_id
     * @return JsonResponse
     */
    public function denyTermsOfUse(Application $app, Request $request, $sbas_id)
    {
        $ret = array('success' => false, 'message' => '');

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $app['phraseanet.user']->ACL()->revoke_access_from_bases(
                array_keys($app['phraseanet.user']->ACL()->get_granted_base(array(), array($databox->get_sbas_id())))
            );
            $app['phraseanet.user']->ACL()->revoke_unused_sbas_rights();

            $app->closeAccount();

            $ret['success'] = true;
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    /**
     * Display database terms of use
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  Response
     */
    public function displayTermsOfUse(Application $app, Request $request)
    {
        $toDisplay = $request->query->get('to_display', array());
        $data = array();

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
            if (count($toDisplay) > 0 && !in_array($databox->get_sbas_id(), $toDisplay)) {
                continue;
            }

            $cgus = $databox->get_cgus();

            if (!isset($cgus[$app['locale']])) {
                continue;
            }

            $data[$databox->get_viewname()] = $cgus[$app['locale']]['value'];
        }

        return new Response($app['twig']->render('/prod/TOU.html.twig', array(
            'TOUs'        => $data,
            'local_title' => _('Terms of use')
        )));
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
