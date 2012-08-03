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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Core;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Database implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
                return $app['phraseanet.core']['Firewall']->requireAdmin($app);
            });

        /**
         * Get admin dashboard
         *
         * name         : admin_databases
         *
         * description  : Display admin dashboard
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('getDatabase'))->bind('admin_databases');

        /**
         * Reset cache
         *
         * name         : admin_database_new
         *
         * description  : Reset all cache
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/', $this->call('createDatabase'))->bind('admin_database_new');

        /**
         * mount a database
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
        $controllers->post('/mount/', $this->call('databaseMount'))->bind('admin_database_mount');

        /**
         * Get database CGU
         *
         * name         : admin_database_cgu
         *
         * description  : Get database CGU
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{databox_id}/cgus/', $this->call('getDatabaseCGU'))
            ->assert('databox_id', '\d+')
            ->bind('admin_database_cgu');

        /**
         * Update database CGU
         *
         * name         : admin_update_database_cgu
         *
         * description  : Update database CGU
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/{databox_id}/cgus/', $this->call('updateDatabaseCGU'))
            ->assert('databox_id', '\d+')
            ->bind('admin_update_database_cgu');

        return $controllers;
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDatabase(Application $app, Request $request)
    {

    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
            $app->abort(403);
        }

        return new Response($app['twig']->render('admin/databox/cgus.html.twig', array(
                    'languages'      => Core::getAvailableLanguages(),
                    'cgus'           => $app['phraseanet.appbox']->get_databox($databox_id)->get_cgus(),
                    'current_locale' => \Session_Handler::get_locale(),
                )));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateDatabaseCGU(Application $app, Request $request, $databox_id)
    {
        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_sbas($databox_id, 'bas_modify_struct')) {
            $app->abort(403);
        }

        $databox = $app['phraseanet.appbox']->get_databox($databox_id);

        foreach ($request->get('TOU', array()) as $loc => $terms) {
            $databox->update_cgus($loc, $terms,  ! ! $request->get('valid', false));
        }

        return $app->redirect('/admin/database/' .$databox_id. '/cgus/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createDatabase(Application $app, Request $request)
    {
        if ('' !== $dbName = $request->get('new_dbname', '')) {

            return $app->redirect('/admin/databases/?error=no-empty');
        }

        if (\p4string::hasAccent($dbName)) {

            return $app->redirect('/admin/databases/?error=special-chars');
        }

        $appbox = $app['phraseanet.appbox'];
        $registry = $app['phraseanet.core']['Registry'];

        if ((null === $request->get('new_settings')) && (null !== $dataTemplate = $request->get('new_data_template'))) {
            try {
                $configuration = Configuration::build();
                $choosenConnexion = $configuration->getPhraseanet()->get('database');
                $connexion = $configuration->getConnexion($choosenConnexion);

                $hostname = $connexion->get('host');
                $port = $connexion->get('port');
                $user = $connexion->get('user');
                $password = $connexion->get('password');

                $dataTemplate = new \SplFileInfo($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $dataTemplate . '.xml');
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $user, $password, $dbName, array(), $registry);

                try {
                    $base = \databox::create($appbox, $connbas, $dataTemplate, $registry);
                    $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());

                    return $app->redirect('/admin/databases/?success=base-ok&sbas-id=' . $base->get_sbas_id());
                } catch (\Exception $e) {

                    return $app->redirect('/admin/databases/?error=base-failed');
                }
            } catch (\Exception $e) {

                return $app->redirect('/admin/databases/?error=database-failed');
            }
        }

        if (
            null !== $request->get('new_settings')
            && (null !== $hostname = $request->get('new_hostname'))
            && (null !== $port = $request->get('new_port'))
            && (null !== $userDb = $request->get('new_user'))
            && (null !== $passwordDb = $request->get('new_password'))
            && (null !== $dataTemplate = $request->get('new_data_template'))) {

            try {
                $data_template = new \SplFileInfo($registry->get('GV_RootPath') . 'lib/conf.d/data_templates/' . $dataTemplate . '.xml');
                $connbas = new \connection_pdo('databox_creation', $hostname, $port, $userDb, $passwordDb, $dbName, array(), $registry);
                try {
                    $base = \databox::create($appbox, $connbas, $data_template, $registry);
                    $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());

                    return $app->redirect('/admin/databases/?success=base-ok&sbas-id=' . $base->get_sbas_id());
                } catch (\Exception $e) {

                    return $app->redirect('/admin/databases/?error=base-failed');
                }
            } catch (\Exception $e) {

                return $app->redirect('/admin/databases/?error=database-failed');
            }
        }
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function databaseMount(Application $app, Request $request)
    {
        if ('' !== $dbName = $request->get('new_dbname', '')) {

            return $app->redirect('/admin/databases/?error=no-empty');
        }

        if (\p4string::hasAccent($dbName)) {

            return $app->redirect('/admin/databases/?error=special-chars');
        }

        $appbox = $app['phraseanet.appbox'];
        $registry = $app['phraseanet.core']['Registry'];

        if ((null === $request->get('new_settings'))) {
            try {
                $configuration = Configuration::build();
                $connexion = $configuration->getConnexion();

                $hostname = $connexion->get('host');
                $port = $connexion->get('port');
                $user = $connexion->get('user');
                $password = $connexion->get('password');

                $appbox->get_connection()->beginTransaction();
                $base = \databox::mount($appbox, $hostname, $port, $user, $password, $dbName, $registry);
                $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());
                $appbox->get_connection()->commit();

                return $app->redirect('/admin/databases/?success=mount-ok&sbas-id=' . $base->get_sbas_id());
            } catch (\Exception $e) {
                $appbox->get_connection()->rollBack();

                return $app->redirect('/admin/databases/?error=mount-failed');
            }
        }

        if (
            null !== $request->get('new_settings')
            && (null !== $hostname = $request->get('new_hostname'))
            && (null !== $port = $request->get('new_port'))
            && (null !== $userDb = $request->get('new_user'))
            && (null !== $passwordDb = $request->get('new_password'))) {

            try {
                $appbox->get_connection()->beginTransaction();
                $base = \databox::mount($appbox, $hostname, $port, $userDb, $passwordDb, $dbName, $registry);
                $base->registerAdmin($app['phraseanet.core']->getAuthenticatedUser());
                $appbox->get_connection()->commit();

                return $app->redirect('/admin/databases/?success=mount-ok&sbas-id=' . $base->get_sbas_id());
            } catch (\Exception $e) {
                $appbox->get_connection()->rollBack();

                return $app->redirect('/admin/databases/?error=mount-failed');
            }
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
