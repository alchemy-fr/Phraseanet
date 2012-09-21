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
class ConnectedUsers implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {

                $response = $app['firewall']->requireAccessToModule('Admin');

                if ($response instanceof Response) {
                    return $response;
                }
            });


        $controllers->get('/', function(Application $app, Request $request) {


                $dql = 'SELECT s FROM Entities\Session s
                    LEFT JOIN s.modules m
                    WHERE
                        s.created > (CURRENT_TIMESTAMP() - 15 * 60)
                        OR m.created > (CURRENT_TIMESTAMP() - 5 * 60)
                    ORDER BY s.created DESC';

                $query = $app['EM']->createQuery($dql);
                $sessions = $query->getResult();

                $ret = array(
                    'sessions'     => $sessions,
                    'applications' => array(
                        '0' => 0,
                        '1' => 0,
                        '2' => 0,
                        '3' => 0,
                        '4' => 0,
                        '5' => 0,
                        '6' => 0,
                        '7' => 0,
                        '8' => 0,
                    )
                );

                foreach ($sessions as $session) {
                    foreach ($session->getModules() as $module) {
                        if (isset($ret['applications'][$module->getModuleId()])) {
                            $ret['applications'][$module->getModuleId()]++;
                        }
                    }
                }


//                    $datas = $app['geonames']->find_geoname_from_ip($row['ip']);
//
//                    if ($datas['city']) {
//                        $infos = $datas['city'] . ' (' . $datas['country'] . ')';
//                    } elseif ($datas['fips']) {
//                        $infos = $datas['fips'] . ' (' . $datas['country'] . ')';
//                    } elseif ($datas['country']) {
//                        $infos = $datas['country'];
//                    } else {
//                        $infos = '';
//                    }
//
//                    $session['ip_infos'] = $infos;


                return new Response($app['twig']->render('admin/connected-users.html.twig', array('data' => $ret)));
            });

        return $controllers;
    }

    /**
     * Return module name according to its ID
     *
     * @param integer $appId
     * @return string
     * @return null
     */
    public static function appName($appId)
    {
        $appRef = array(
            '0' => _('admin::monitor: module inconnu'),
            '1' => _('admin::monitor: module production'),
            '2' => _('admin::monitor: module client'),
            '3' => _('admin::monitor: module admin'),
            '4' => _('admin::monitor: module report'),
            '5' => _('admin::monitor: module thesaurus'),
            '6' => _('admin::monitor: module comparateur'),
            '7' => _('admin::monitor: module validation'),
            '8' => _('admin::monitor: module upload'),
        );

        return isset($appRef[$appId]) ? $appRef[$appId] : null;
    }
}
