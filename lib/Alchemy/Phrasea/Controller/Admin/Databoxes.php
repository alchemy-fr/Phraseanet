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
                return $app['firewall']->requireAdmin($app);
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

        return new Response($app['twig']->render('admin/databases.html.twig', array(
                    'files'             => new \DirectoryIterator($app['phraseanet.registry']->get('GV_RootPath') . 'lib/conf.d/data_templates'),
                    'sbas'              => $sbas,
                    'upgrade_available' => $upgradeAvailable,
                    'error_msg'         => $errorMsg,
                    'recommendations'   => $upgrader->getRecommendations(),
                    'advices'           => $request->query->get('advices', array()),
                )));
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
