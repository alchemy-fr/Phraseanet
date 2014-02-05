<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Subdefs implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.subdefs'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin')
                ->requireRightOnSbas($request->attributes->get('sbas_id'), 'bas_modify_struct');
        });

        $controllers->get('/{sbas_id}/', function (Application $app, $sbas_id) {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            return $app['twig']->render('admin/subdefs.html.twig', [
                'databox' => $databox,
                'subdefs' => $databox->get_subdef_structure()
            ]);
        })
            ->bind('admin_subdefs_subdef')
            ->assert('sbas_id', '\d+');

        $controllers->post('/{sbas_id}/', function (Application $app, Request $request, $sbas_id) {
            $delete_subdef = $request->request->get('delete_subdef');
            $toadd_subdef = $request->request->get('add_subdef');
            $Parmsubdefs = $request->request->get('subdefs', []);

            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $add_subdef = ['class' => null, 'name'  => null, 'group' => null];
            foreach ($add_subdef as $k => $v) {
                if (!isset($toadd_subdef[$k]) || trim($toadd_subdef[$k]) === '')
                    unset($add_subdef[$k]);
                else
                    $add_subdef[$k] = $toadd_subdef[$k];
            }

            if ($delete_subdef) {

                $delete_subef = explode('_', $delete_subdef, 2);
                $group = $delete_subef[0];
                $name = $delete_subef[1];
                $subdefs = $databox->get_subdef_structure();
                $subdefs->delete_subdef($group, $name);
            } elseif (count($add_subdef) === 3) {

                $subdefs = $databox->get_subdef_structure();

                $group = $add_subdef['group'];
                $name = $app['unicode']->remove_nonazAZ09($add_subdef['name'], false);
                $class = $add_subdef['class'];

                $subdefs->add_subdef($group, $name, $class);
            } else {

                $subdefs = $databox->get_subdef_structure();

                foreach ($Parmsubdefs as $post_sub) {

                    $options = [];

                    $post_sub_ex = explode('_', $post_sub, 2);

                    $group = $post_sub_ex[0];
                    $name = $post_sub_ex[1];

                    $class = $request->request->get($post_sub . '_class');
                    $downloadable = $request->request->get($post_sub . '_downloadable');

                    $defaults = ['path', 'meta', 'mediatype'];

                    foreach ($defaults as $def) {
                        $parm_loc = $request->request->get($post_sub . '_' . $def);

                        if ($def == 'meta' && !$parm_loc) {
                            $parm_loc = "no";
                        }

                        $options[$def] = $parm_loc;
                    }

                    $mediatype = $request->request->get($post_sub . '_mediatype');
                    $media = $request->request->get($post_sub . '_' . $mediatype, []);

                    foreach ($media as $option => $value) {

                        if ($option == 'resolution' && $mediatype == 'image') {
                            $option = 'dpi';
                        }

                        $options[$option] = $value;
                    }

                    $labels = $request->request->get($post_sub . '_label', []);

                    $subdefs->set_subdef($group, $name, $class, $downloadable, $options, $labels);
                }
            }

            return $app->redirectPath('admin_subdefs_subdef', ['sbas_id' => $databox->get_sbas_id()]);
        })
            ->bind('admin_subdefs_subdef_update')
            ->assert('sbas_id', '\d+');

        return $controllers;
    }
}
