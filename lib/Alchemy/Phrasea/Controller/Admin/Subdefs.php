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

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Subdefs implements ControllerProviderInterface
{

    public function connect(Application $app)
    {

        $controllers = $app['controllers_factory'];

        $controllers->get('/{sbas_id}/', function(Application $app, $sbas_id) {
                $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

                return new Response($app['twig']->render(
                            'admin/subdefs.html.twig', array(
                            'databox' => $databox,
                            'subdefs' => $databox->get_subdef_structure()
                            )
                        )
                );
            })->assert('sbas_id', '\d+');

        $controllers->post('/{sbas_id}/', function(Application $app, Request $request, $sbas_id) {
                $delete_subdef = $request->request->get('delete_subdef');
                $toadd_subdef = $request->request->get('add_subdef');
                $Parmsubdefs = $request->request->get('subdefs', array());

                $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

                $add_subdef = array('class' => null, 'name'  => null, 'group' => null);
                foreach ($add_subdef as $k => $v) {
                    if ( ! isset($toadd_subdef[$k]) || trim($toadd_subdef[$k]) === '')
                        unset($add_subdef[$k]);
                    else
                        $add_subdef[$k] = $toadd_subdef[$k];
                }

                if ($delete_subdef) {

                    $delete_subef = explode('_', $delete_subdef);
                    $group = $delete_subef[0];
                    $name = $delete_subef[1];
                    $subdefs = $databox->get_subdef_structure();
                    $subdefs->delete_subdef($group, $name);
                } elseif (count($add_subdef) === 3) {

                    $subdefs = $databox->get_subdef_structure();
                    $UnicodeProcessor = new \unicode();

                    $group = $add_subdef['group'];
                    $name = $UnicodeProcessor->remove_nonazAZ09($add_subdef['name'], false);
                    $class = $add_subdef['class'];

                    $subdefs->add_subdef($group, $name, $class);
                } else {

                    $subdefs = $databox->get_subdef_structure();

                    foreach ($Parmsubdefs as $post_sub) {

                        $options = array();

                        $post_sub_ex = explode('_', $post_sub);

                        $group = array_shift($post_sub_ex);
                        $name = implode('_', $post_sub_ex);

                        $class = $request->request->get($post_sub . '_class');
                        $downloadable = $request->request->get($post_sub . '_downloadable');

                        $defaults = array('path', 'meta', 'mediatype');

                        foreach ($defaults as $def) {
                            $parm_loc = $request->request->get($post_sub . '_' . $def);

                            if ($def == 'meta' && ! $parm_loc) {
                                $parm_loc = "no";
                            }

                            $options[$def] = $parm_loc;
                        }

                        $mediatype = $request->request->get($post_sub . '_mediatype');
                        $media = $request->request->get($post_sub . '_' . $mediatype, array());

                        foreach ($media as $option => $value) {

                            if ($option == 'resolution' && $mediatype == 'image') {
                                $option = 'dpi';
                            }

                            $options[$option] = $value;
                        }

                        $subdefs->set_subdef($group, $name, $class, $downloadable, $options);
                    }
                }

                return $app->redirect('/admin/subdefs/' . $databox->get_sbas_id() . '/');
            })->assert('sbas_id', '\d+');

        return $controllers;
    }
}
