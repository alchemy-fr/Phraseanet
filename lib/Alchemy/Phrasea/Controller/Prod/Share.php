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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Share implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireNotGuest();
        });

        /**
         * Share a record
         *
         * name         : share_record
         *
         * description  : Share a record
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/record/{base_id}/{record_id}/', $this->call('shareRecord'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas(\phrasea::sbasFromBas($app, $request->attributes->get('base_id')), 'bas_chupub');
            })
            ->bind('share_record');

        return $controllers;
    }

    /**
     *  Share a record
     *
     * @param   Application     $app
     * @param   Request         $request
     * @param   integer         $base_id
     * @param   integer         $record_id
     * @return  Response
     */
    public function shareRecord(Application $app, Request $request, $base_id, $record_id)
    {
        $record = new \record_adapter($app, \phrasea::sbasFromBas($app, $base_id), $record_id);

        if (!$app['phraseanet.user']->ACL()->has_access_to_subdef($record, 'preview')) {
            $app->abort(403);
        }

        return new Response($app['twig']->render('prod/Share/record.html.twig', array(
            'record' => $record,
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
