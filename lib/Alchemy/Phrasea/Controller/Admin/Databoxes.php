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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Databoxes implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $response = $app['firewall']->requireAdmin();

            if ($response instanceof Response) {
                return $response;
            }
        });


        /**
         * Get Databases control panel
         *
         * name         : admin_databases
         *
         * description  : Get Databases control panel
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('getDatabases'))
            ->bind('admin_databases');


        /**
         * Create Database
         *
         * name         : admin_database_new
         *
         * description  : Create Database
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/', $this->call('createDatabase'))
            ->bind('admin_database_new');

        /**
         * Mount a database
         *
         * name         : admin_database_mount
         *
         * description  : Upgrade all databases
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/mount/', $this->call('databaseMount'))
            ->bind('admin_database_mount');

        /**
         * Upgrade all databases
         *
         * name         : admin_databases_upgrade
         *
         * description  : Upgrade all databases
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/upgrade/', $this->call('databasesUpgrade'))
            ->bind('admin_databases_upgrade');

        return $controllers;
    }

    /**
     * Get Databases control panel
     *
     * @param   $app        Application $app
     * @param   $request    Request $request
     * @return  Response
     */
    public function getDatabases(Application $app, Request $request)
    {
        $createBase = $mountBase = $upgradeAvailable = false;

        if ($app['phraseanet.appbox']->upgradeavailable()) {
            $upgradeAvailable = true;
        }

        $user = $app['phraseanet.user'];

        $sbasIds = array_merge(
            array_keys($user->ACL()->get_granted_sbas(array('bas_manage')))
            , array_keys($user->ACL()->get_granted_sbas(array('bas_modify_struct')))
        );

        $sbas = array();
        foreach ($sbasIds as $sbasId) {
            $sbas[$sbasId] = array(
                'version'     => 'unknown',
                'image'       => '/skins/icons/db-remove.png',
                'server_info' => '',
                'name'        => _('Unreachable server')
            );

            try {
                $databox = $app['phraseanet.appbox']->get_databox($sbasId);
                if ($databox->upgradeavailable()) {
                    $upgradeAvailable = true;
                }

                $sbas[$sbasId] = array(
                    'version'     => $databox->get_version(),
                    'image'       => '/skins/icons/foldph20close_0.gif',
                    'server_info' => $databox->get_connection()->server_info(),
                    'name'        => \phrasea::sbas_names($sbasId, $app)
                );
            } catch (\Exception $e) {

            }
        }

        switch ($errorMsg = $request->query->get('error')) {
            case 'scheduler-started' :
                $errorMsg = _('Veuillez arreter le planificateur avant la mise a jour');
                break;
            case 'already-started' :
                $errorMsg = _('The upgrade is already started');
                break;
            case 'unknow' :
                $errorMsg = _('An error occured');
                break;
            case 'bad-email' :
                $errorMsg = _('Please fix the database before starting');
                break;
            case 'special-chars' :
                $errorMsg = _('Database name can not contains special characters');
                break;
            case 'base-failed' :
                $errorMsg = _('Base could not be created');
                break;
            case 'database-failed' :
                $errorMsg = _('Database does not exists or can not be accessed');
                break;
            case 'no-empty' :
                $errorMsg = _('Database can not be empty');
                break;
            case 'mount-failed' :
                $errorMsg = _('Database could not be mounted');
                break;
        }

        $upgrader = new \Setup_Upgrade($app);

        return $app['twig']->render('admin/databases.html.twig', array(
            'files'             => new \DirectoryIterator($app['phraseanet.registry']->get('GV_RootPath') . 'lib/conf.d/data_templates'),
            'sbas'              => $sbas,
            'upgrade_available' => $upgradeAvailable,
            'error_msg'         => $errorMsg,
            'recommendations'   => $upgrader->getRecommendations(),
            'advices'           => $request->query->get('advices', array()),
        ));
    }

    /**
     * Create a new databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @param    integer     $databox_id The requested databox
     * @return   RedirectResponse
     */
    public function createDatabase(Application $app, Request $request)
    {
        if ('' === $dbName = $request->request->get('new_dbname', '')) {
            return $app->redirect('/admin/databoxes/?error=no-empty');
        }

        if (\p4string::hasAccent($dbName)) {
            return $app->redirect('/admin/databoxes/?error=special-chars');
        }

        if ((null === $request->request->get('new_settings')) && (null !== $dataTemplate = $request->request->get('new_data_template'))) {

            $configuration = $app['phraseanet.configuration'];
            $choosenConnexion = $configuration->getPhraseanet()->get('database');
            $connexion = $configuration->getConnexion($choosenConnexion);

            $hostname = $connexion->get('host');
            $port = $connexion->get('port');
            $user = $connexion->get('user');
            $password = $connexion->get('password');

            $dataTemplate = new \SplFileInfo($app['phraseanet.registry']->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $dataTemplate . '.xml');

            try {
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $user, $password, $dbName, array(), $app['phraseanet.registry']);
            } catch (\PDOException $e) {
                return $app->redirect('/admin/databoxes/?success=0&error=database-failed');
            }

            try {
                $base = \databox::create($app, $connbas, $dataTemplate, $app['phraseanet.registry']);
                $base->registerAdmin($app['phraseanet.user']);
                $app['phraseanet.user']->ACL()->delete_data_from_cache();

                return $app->redirect('/admin/databox/' . $base->get_sbas_id() . '/?success=1&reload-tree=1');
            } catch (\Exception $e) {
                return $app->redirect('/admin/databoxes/?success=0&error=base-failed');
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
                $data_template = new \SplFileInfo($app['phraseanet.registry']->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $dataTemplate . '.xml');
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $userDb, $passwordDb, $dbName, array(), $app['phraseanet.registry']);
                try {
                    $base = \databox::create($app, $connbas, $data_template, $app['phraseanet.registry']);
                    $base->registerAdmin($app['phraseanet.user']);

                    return $app->redirect('/admin/databox/' . $base->get_sbas_id() . '/?success=1&reload-tree=1');
                } catch (\Exception $e) {
                    return $app->redirect('/admin/databoxes/?success=0&error=base-failed');
                }
            } catch (\Exception $e) {
                return $app->redirect('/admin/databoxes/?success=0&error=database-failed');
            }
        }
    }

    /**
     * Mount a databox
     *
     * @param    Application $app        The silex application
     * @param    Request     $request    The current HTTP request
     * @return   RedirectResponse
     */
    public function databaseMount(Application $app, Request $request)
    {
        if ('' === $dbName = trim($request->request->get('new_dbname', ''))) {
            return $app->redirect('/admin/databoxes/?success=0&error=no-empty');
        }

        if (\p4string::hasAccent($dbName)) {
            return $app->redirect('/admin/databoxes/?success=0&error=special-chars');
        }

        if ((null === $request->request->get('new_settings'))) {
            try {
                $configuration = $app['phraseanet.configuration'];
                $connexion = $configuration->getConnexion();

                $hostname = $connexion->get('host');
                $port = $connexion->get('port');
                $user = $connexion->get('user');
                $password = $connexion->get('password');

                $app['phraseanet.appbox']->get_connection()->beginTransaction();
                $base = \databox::mount($app, $hostname, $port, $user, $password, $dbName, $app['phraseanet.registry']);
                $base->registerAdmin($app['phraseanet.user']);
                $app['phraseanet.appbox']->get_connection()->commit();

                return $app->redirect('/admin/databox/' . $base->get_sbas_id() . '/?success=1&reload-tree=1');
            } catch (\Exception $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();
                return $app->redirect('/admin/databoxes/?success=0&error=mount-failed');
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
                $base->registerAdmin($app['phraseanet.user']);
                $app['phraseanet.appbox']->get_connection()->commit();

                return $app->redirect('/admin/databox/' . $base->get_sbas_id() . '/?success=1&reload-tree=1');
            } catch (\Exception $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();

                return $app->redirect('/admin/databoxes/?success=0&error=mount-failed');
            }
        }
    }

    /**
     * Upgrade all databases
     *
     * @param   $app        Application $app
     * @param   $request    Request $request
     * @return  RedirectResponse
     */
    public function databasesUpgrade(Application $app, Request $request)
    {
        if (\phrasea::is_scheduler_started($app)) {

            return $app->redirect('/admin/databoxes/?success=0&error=scheduler-started');
        }

        try {
            $upgrader = new \Setup_Upgrade($app);
            $advices = $app['phraseanet.appbox']->forceUpgrade($upgrader, $app);

            return $app->redirect('/admin/databoxes/?success=1&notice=restart&' . http_build_query(array('advices' => $advices)));
        } catch (\Exception_Setup_UpgradeAlreadyStarted $e) {
            return $app->redirect('/admin/databoxes/?success=0&error=already-started');
        } catch (\Exception_Setup_FixBadEmailAddresses $e) {
            return $app->redirect('/admin/databoxes/?success=0&error=bad-email');
        } catch (\Exception $e) {
            return $app->redirect('/admin/databoxes/?success=0&error=unknow');
        }
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
