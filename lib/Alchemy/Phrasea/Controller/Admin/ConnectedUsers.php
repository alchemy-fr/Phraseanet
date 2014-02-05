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
use Symfony\Component\Translation\TranslatorInterface;

class ConnectedUsers implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.connected-users'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('Admin');
        });

        $controllers->get('/', 'controller.admin.connected-users:listConnectedUsers')
            ->bind('admin_connected_users');

        return $controllers;
    }

    public function listConnectedUsers(Application $app, Request $request)
    {
        $dql = 'SELECT s FROM Alchemy\Phrasea\Model\Entities\Session s
            WHERE
                s.updated > :date
            ORDER BY s.updated DESC';

        $date = new \DateTime('-2 hours');
        $params = ['date' => $date->format('Y-m-d h:i:s')];

        $query = $app['EM']->createQuery($dql);
        $query->setParameters($params);
        $sessions = $query->getResult();

        $result = [];

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
                $app['monolog']->error(sprintf("Unable to get IP information for %s", $session->getIpAddress()), array('exception' => $e));
            }

            $result[] = [
                'session' => $session,
                'info' => $info,
            ];
        }

        $ret = [
            'sessions'     => $result,
            'applications' => [
                '0' => 0,
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
                '6' => 0,
                '7' => 0,
                '8' => 0,
            ]
        ];

        foreach ($result as $session) {
            foreach ($session['session']->getModules() as $module) {
                if (isset($ret['applications'][$module->getModuleId()])) {
                    $ret['applications'][$module->getModuleId()]++;
                }
            }
        }

        return $app['twig']->render('admin/connected-users.html.twig', ['data' => $ret]);
    }

    /**
     * Return module name according to its ID
     *
     * @param  integer $appId
     * @return string
     * @return null
     */
    public static function appName(TranslatorInterface $translator, $appId)
    {
        $appRef = [
            '0' => $translator->trans('admin::monitor: module inconnu'),
            '1' => $translator->trans('admin::monitor: module production'),
            '2' => $translator->trans('admin::monitor: module client'),
            '3' => $translator->trans('admin::monitor: module admin'),
            '4' => $translator->trans('admin::monitor: module report'),
            '5' => $translator->trans('admin::monitor: module thesaurus'),
            '6' => $translator->trans('admin::monitor: module comparateur'),
            '7' => $translator->trans('admin::monitor: module validation'),
            '8' => $translator->trans('admin::monitor: module upload'),
        ];

        return isset($appRef[$appId]) ? $appRef[$appId] : null;
    }
}
