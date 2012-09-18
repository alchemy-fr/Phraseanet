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

use Alchemy\Phrasea\Controller\RecordsRequest;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MoveCollection implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/', $this->call('displayForm'));
        $controllers->post('/apply/', $this->call('apply'));

        return $controllers;
    }

    public function displayForm(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, false, array('candeleterecord'));

        $sbas_ids = array_map(function(\databox $databox) {
                return $databox->get_sbas_id();
            }, $records->databoxes());

        $collections = $app['phraseanet.user']->ACL()
            ->get_granted_base(array('canaddrecord'), $sbas_ids);

        $parameters = array(
            'records'     => $records,
            'message'     => '',
            'collections' => $collections,
        );

        return $app['twig']->render('prod/actions/collection_default.html.twig', $parameters);
    }

    public function apply(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, false, array('candeleterecord'));

        $datas = array(
            'success' => false,
            'message' => '',
        );

        try {
            $user = $app['phraseanet.user'];

            if (null === $request->request->get('base_id')) {
                $datas['message'] = _('Missing target collection');
                return $app->json($datas);
            }

            if ( ! $user->ACL()->has_right_on_base($request->request->get('base_id'), 'canaddrecord')) {
                $datas['message'] = sprintf(_("You do not have the permission to move records to %s"), \phrasea::bas_names($move->getBaseIdDestination(), $app));
                return $app->json($datas);
            }

            try {
                $collection = \collection::get_from_base_id($app, $request->request->get('base_id'));
            } catch (\Exception_Databox_CollectionNotFound $e) {
                $datas['message'] = _('Invalid target collection');
                return $app->json($datas);
            }

            foreach ($records as $record) {
                $record->move_to_collection($collection, $app['phraseanet.appbox']);

                if ($request->request->get("chg_coll_son") == "1") {
                    foreach ($record->get_children() as $child) {
                        if ($user->ACL()->has_right_on_base($child->get_base_id(), 'candeleterecord')) {
                            $child->move_to_collection($collection, $app['phraseanet.appbox']);
                        }
                    }
                }
            }

            $ret = array(
                'success' => true,
                'message' => _('Records have been successfuly moved'),
            );
        } catch (\Exception $e) {
            $ret = array(
                'success' => false,
                'message' => _('An error occured'),
            );
        }

        return $app->json($ret);
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
