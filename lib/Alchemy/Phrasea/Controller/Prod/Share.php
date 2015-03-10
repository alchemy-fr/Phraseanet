<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
        $app['controller.prod.share'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireNotGuest();
        });

        $controllers->get('/record/{base_id}/{record_id}/', 'controller.prod.share:shareRecord')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRightOnSbas(\phrasea::sbasFromBas($app, $request->attributes->get('base_id')), 'bas_chupub');
            })
            ->bind('share_record');

        return $controllers;
    }

    /**
     *  Share a record
     *
     * @param  Application $app
     * @param  Request     $request
     * @param  integer     $base_id
     * @param  integer     $record_id
     * @return Response
     */
    public function shareRecord(Application $app, Request $request, $base_id, $record_id)
    {
        $record = new \record_adapter($app, \phrasea::sbasFromBas($app, $base_id), $record_id);

        if (!$app['acl']->get($app['authentication']->getUser())->has_access_to_subdef($record, 'preview')) {
            $app->abort(403);
        }

        return new Response($app['twig']->render('prod/Share/record.html.twig', [
            'record' => $record,
        ]));
    }
}
