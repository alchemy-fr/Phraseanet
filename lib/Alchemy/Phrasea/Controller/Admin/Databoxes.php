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

use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Databoxes implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.databoxes'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin');
        });

        $controllers->get('/', 'controller.admin.databoxes:getDatabases')
            ->bind('admin_databases');

        $controllers->post('/', 'controller.admin.databoxes:createDatabase')
            ->bind('admin_database_new')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireAdmin();
            });

        $controllers->post('/mount/', 'controller.admin.databoxes:databaseMount')
            ->bind('admin_database_mount')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireAdmin();
            });

        $controllers->post('/upgrade/', 'controller.admin.databoxes:databasesUpgrade')
            ->bind('admin_databases_upgrade')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireAdmin();
            });

        return $controllers;
    }

    /**
     * Get Databases control panel
     *
     * @param           $app     Application $app
     * @param           $request Request $request
     * @return Response
     */
    public function getDatabases(Application $app, Request $request)
    {
        $sbasIds = array_merge(
            array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_sbas(['bas_manage']))
            , array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_sbas(['bas_modify_struct']))
        );

        $sbas = [];
        foreach ($sbasIds as $sbasId) {
            $sbas[$sbasId] = [
                'version'     => 'unknown',
                'image'       => '/skins/icons/db-remove.png',
                'server_info' => '',
                'name'        => $app->trans('Unreachable server')
            ];

            try {
                $databox = $app['phraseanet.appbox']->get_databox($sbasId);

                $sbas[$sbasId] = [
                    'version'     => $databox->get_version(),
                    'image'       => '/skins/icons/foldph20close_0.gif',
                    'server_info' => $databox->get_connection()->server_info(),
                    'name'        => \phrasea::sbas_labels($sbasId, $app)
                ];
            } catch (\Exception $e) {

            }
        }

        switch ($errorMsg = $request->query->get('error')) {
            case 'scheduler-started' :
                $errorMsg = $app->trans('Veuillez arreter le planificateur avant la mise a jour');
                break;
            case 'already-started' :
                $errorMsg = $app->trans('The upgrade is already started');
                break;
            case 'unknow' :
                $errorMsg = $app->trans('An error occured');
                break;
            case 'bad-email' :
                $errorMsg = $app->trans('Please fix the database before starting');
                break;
            case 'special-chars' :
                $errorMsg = $app->trans('Database name can not contains special characters');
                break;
            case 'base-failed' :
                $errorMsg = $app->trans('Base could not be created');
                break;
            case 'database-failed' :
                $errorMsg = $app->trans('Database does not exists or can not be accessed');
                break;
            case 'no-empty' :
                $errorMsg = $app->trans('Database can not be empty');
                break;
            case 'mount-failed' :
                $errorMsg = $app->trans('Database could not be mounted');
                break;
        }

        $upgrader = new \Setup_Upgrade($app);

        return $app['twig']->render('admin/databases.html.twig', [
            'files'             => new \DirectoryIterator($app['root.path'] . '/lib/conf.d/data_templates'),
            'sbas'              => $sbas,
            'error_msg'         => $errorMsg,
            'recommendations'   => $upgrader->getRecommendations(),
            'advices'           => $request->query->get('advices', []),
            'reloadTree'        => (Boolean) $request->query->get('reload-tree'),
        ]);
    }

    /**
     * Create a new databox
     *
     * @param Application $app     The silex application
     * @param Request     $request The current HTTP request
     *
     * @return RedirectResponse
     */
    public function createDatabase(Application $app, Request $request)
    {
        if ('' === $dbName = $request->request->get('new_dbname', '')) {
            return $app->redirectPath('admin_databases', ['error' => 'no-empty']);
        }

        if (\p4string::hasAccent($dbName)) {
            return $app->redirectPath('admin_databases', ['error' => 'special-chars']);
        }

        if ((null === $request->request->get('new_settings')) && (null !== $dataTemplate = $request->request->get('new_data_template'))) {
            $connexion = $app['conf']->get(['main', 'database']);

            $hostname = $connexion['host'];
            $port = $connexion['port'];
            $user = $connexion['user'];
            $password = $connexion['password'];

            $dataTemplate = new \SplFileInfo($app['root.path'] . '/lib/conf.d/data_templates/' . $dataTemplate . '.xml');

            try {
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $user, $password, $dbName, [], $app['debug']);
            } catch (\PDOException $e) {
                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'database-failed']);
            }

            try {
                $base = \databox::create($app, $connbas, $dataTemplate, $app['phraseanet.registry']);
                $base->registerAdmin($app['authentication']->getUser());
                $app['acl']->get($app['authentication']->getUser())->delete_data_from_cache();

                return $app->redirectPath('admin_database', ['databox_id' => $base->get_sbas_id(), 'success' => 1, 'reload-tree' => 1]);
            } catch (\Exception $e) {
                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'base-failed']);
            }
        }

        if (
            null !== $request->request->get('new_settings')
            && (null !== $hostname = $request->request->get('new_hostname'))
            && (null !== $port = $request->request->get('new_port'))
            && (null !== $userDb = $request->request->get('new_user'))
            && (null !== $passwordDb = $request->request->get('new_password'))
            && (null !== $dataTemplate = $request->request->get('new_data_template'))) {

            try {
                $data_template = new \SplFileInfo($app['root.path'] . '/lib/conf.d/data_templates/' . $dataTemplate . '.xml');
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $userDb, $passwordDb, $dbName, [], $app['debug']);
                try {
                    $base = \databox::create($app, $connbas, $data_template, $app['phraseanet.registry']);
                    $base->registerAdmin($app['authentication']->getUser());

                    return $app->redirectPath('admin_database', ['databox_id' => $base->get_sbas_id(), 'success' => 1, 'reload-tree' => 1]);
                } catch (\Exception $e) {
                    return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'base-failed']);
                }
            } catch (\Exception $e) {
                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'database-failed']);
            }
        }
    }

    /**
     * Mount a databox
     *
     * @param  Application      $app     The silex application
     * @param  Request          $request The current HTTP request
     * @return RedirectResponse
     */
    public function databaseMount(Application $app, Request $request)
    {
        if ('' === $dbName = trim($request->request->get('new_dbname', ''))) {
            return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'no-empty']);
        }

        if (\p4string::hasAccent($dbName)) {
            return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'special-chars']);
        }

        if ((null === $request->request->get('new_settings'))) {
            try {
                $connexion = $app['conf']->get(['main', 'database']);

                $hostname = $connexion['host'];
                $port = $connexion['port'];
                $user = $connexion['user'];
                $password = $connexion['password'];

                $app['phraseanet.appbox']->get_connection()->beginTransaction();
                $base = \databox::mount($app, $hostname, $port, $user, $password, $dbName, $app['phraseanet.registry']);
                $base->registerAdmin($app['authentication']->getUser());
                $app['phraseanet.appbox']->get_connection()->commit();

                return $app->redirectPath('admin_database', ['databox_id' => $base->get_sbas_id(), 'success' => 1, 'reload-tree' => 1]);
            } catch (\Exception $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();

                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'mount-failed']);
            }
        }

        if (
            null !== $request->request->get('new_settings')
            && (null !== $hostname = $request->request->get('new_hostname'))
            && (null !== $port = $request->request->get('new_port'))
            && (null !== $userDb = $request->request->get('new_user'))
            && (null !== $passwordDb = $request->request->get('new_password'))) {

            try {
                $app['phraseanet.appbox']->get_connection()->beginTransaction();
                $base = \databox::mount($app, $hostname, $port, $userDb, $passwordDb, $dbName, $app['phraseanet.registry']);
                $base->registerAdmin($app['authentication']->getUser());
                $app['phraseanet.appbox']->get_connection()->commit();

                return $app->redirectPath('admin_database', ['databox_id' => $base->get_sbas_id(), 'success' => 1, 'reload-tree' => 1]);
            } catch (\Exception $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();

                return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'mount-failed']);
            }
        }
    }

    /**
     * Upgrade all databases
     *
     * @param                   $app     Application $app
     * @param                   $request Request $request
     * @return RedirectResponse
     */
    public function databasesUpgrade(Application $app, Request $request)
    {
        $info = $app['task-manager.live-information']->getManager();
        if (TaskManagerStatus::STATUS_STARTED === $info['actual']) {
            return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'scheduler-started']);
        }

        try {
            $upgrader = new \Setup_Upgrade($app);
            $advices = $app['phraseanet.appbox']->forceUpgrade($upgrader, $app);

            return $app->redirectPath('admin_databases', ['success' => 1, 'notice' => 'restart', 'advices' => $advices]);
        } catch (\Exception_Setup_UpgradeAlreadyStarted $e) {
            return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'already-started']);
        } catch (\Exception_Setup_FixBadEmailAddresses $e) {
            return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'bad-email']);
        } catch (\Exception $e) {
            return $app->redirectPath('admin_databases', ['success' => 0, 'error' => 'unknow']);
        }
    }
}
