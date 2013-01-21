<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConnectedUsers implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('Admin');
        });


        $controllers->get('/', $this->call('listConnectedUsers'));

        return $controllers;
    }

    public function listConnectedUsers(Application $app, Request $request)
    {
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

        return $app['twig']->render('admin/connected-users.html.twig', array('data' => $ret));
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
