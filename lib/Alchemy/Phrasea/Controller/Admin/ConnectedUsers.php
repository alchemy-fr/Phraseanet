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

use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class ConnectedUsers implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('Admin');
        });

        $controllers->get('/', $this->call('listConnectedUsers'))
            ->bind('admin_connected_users');

        return $controllers;
    }

    public function listConnectedUsers(Application $app, Request $request)
    {
        $dql = 'SELECT s FROM Entities\Session s
            WHERE
                s.updated > :date
            ORDER BY s.updated DESC';

        $date = new \DateTime('-2 hours');
        $params = array('date' => $date->format('Y-m-d h:i:s'));

        $query = $app['EM']->createQuery($dql);
        $query->setParameters($params);
        $sessions = $query->getResult();

        $result = array();

        foreach ($sessions as $session) {
            $info = '';
            try {
                $geoname = $app['geonames.connector']->ip($session->getIpAddress());
                $country = $geoname->get('country');
                $city = $geoname->get('city');
                $region = $geoname->get('region');

                $countryName = isset($country['name']) ? $country['name'] : null;
                $regionName = isset($region['name']) ? $region['name'] : null;

                if (null !== $city) {
                    $info = $city . ($countryName ? ' (' . $countryName . ')' : null);
                } elseif (null !== $regionName) {
                    $info = $regionName . ($countryName ? ' (' . $countryName . ')' : null);
                } elseif (null !== $countryName) {
                    $info = $countryName;
                } else {
                    $info = '';
                }
            } catch (GeonamesExceptionInterface $e) {
                $app['monolog']->error(sprintf(
                    "Unable to get IP information for %s : %s", $session->getIpAddress(), $e->getMessage()
                ));
            }

            $result[] = array(
                'session' => $session,
                'info' => $info,
            );
        }

        $ret = array(
            'sessions'     => $result,
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

        foreach ($result as $session) {
            foreach ($session['session']->getModules() as $module) {
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
     * @param  integer $appId
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
