<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Setup;

use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Core\Service\Builder as ServiceBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Installer implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', $this->call('rootInstaller'));

        $controllers->get('/step2/', $this->call('getInstallForm'));

        $controllers->post('/install/', $this->call('doInstall'));

        return $controllers;
    }

    public function rootInstaller(Application $app, Request $request)
    {
        $php_constraint = \setup::check_php_version();
        $writability_constraints = \setup::check_writability(new \Setup_Registry());
        $extension_constraints = \setup::check_php_extension();
        $opcode_constraints = \setup::check_cache_opcode();
        $php_conf_constraints = \setup::check_php_configuration();
        $locales_constraints = \setup::check_system_locales($app);

        $constraints_coll = array(
            'php_constraint'          => $php_constraint
            , 'writability_constraints' => $writability_constraints
            , 'extension_constraints'   => $extension_constraints
            , 'opcode_constraints'      => $opcode_constraints
            , 'php_conf_constraints'    => $php_conf_constraints
            , 'locales_constraints'     => $locales_constraints
        );
        $redirect = true;

        foreach ($constraints_coll as $key => $constraints) {
            $unset = true;
            foreach ($constraints as $constraint) {
                if (!$constraint->is_ok() && $constraint->is_blocker())
                    $redirect = $unset = false;
            }
            if ($unset === true) {
                unset($constraints_coll[$key]);
            }
        }

        if ($redirect) {
            return $app->redirect('/setup/installer/step2/');
        }

        $app['twig.loader.filesystem']->setPaths(array(
            __DIR__ . '/../../../../../templates/web'
        ));

        return $app['twig']->render(
            '/setup/index.html.twig'
            , array_merge($constraints_coll, array(
                'locale'             => $app['locale']
                , 'available_locales'  => $app->getAvailableLanguages()
                , 'version_number'     => $app['phraseanet.version']->getNumber()
                , 'version_name'       => $app['phraseanet.version']->getName()
                , 'current_servername' => $request->getScheme() . '://' . $request->getHttpHost() . '/'
            ))
        );
    }

    public function getInstallForm(Application $app, Request $request)
    {
        \phrasea::use_i18n($app['locale']);

        $ld_path = array(__DIR__ . '/../../../../../templates/web');
        $loader = new \Twig_Loader_Filesystem($ld_path);

        $twig = new \Twig_Environment($loader);
        $twig->addExtension(new \Twig_Extensions_Extension_I18n());

        $warnings = array();

        $php_constraint = \setup::check_php_version();
        $writability_constraints = \setup::check_writability(new \Setup_Registry());
        $extension_constraints = \setup::check_php_extension();
        $opcode_constraints = \setup::check_cache_opcode();
        $php_conf_constraints = \setup::check_php_configuration();
        $locales_constraints = \setup::check_system_locales($app);

        $constraints_coll = array(
            'php_constraint'          => $php_constraint
            , 'writability_constraints' => $writability_constraints
            , 'extension_constraints'   => $extension_constraints
            , 'opcode_constraints'      => $opcode_constraints
            , 'php_conf_constraints'    => $php_conf_constraints
            , 'locales_constraints'     => $locales_constraints
        );

        foreach ($constraints_coll as $key => $constraints) {
            $unset = true;
            foreach ($constraints as $constraint) {
                if (!$constraint->is_ok() && !$constraint->is_blocker()) {
                    $warnings[] = $constraint->get_message();
                }
            }
        }

        if ($request->getScheme() == 'http') {
            $warnings[] = _('It is not recommended to install Phraseanet without HTTPS support');
        }

        return $twig->render(
            '/setup/step2.html.twig'
            , array(
            'locale'              => $app['locale']
            , 'available_locales'   => $app->getAvailableLanguages()
            , 'available_templates' => \appbox::list_databox_templates()
            , 'version_number'      => $app['phraseanet.version']->getNumber()
            , 'version_name'        => $app['phraseanet.version']->getName()
            , 'warnings'            => $warnings
            , 'error'               => $request->query->get('error')
            , 'current_servername'  => $request->getScheme() . '://' . $request->getHttpHost() . '/'
            , 'discovered_binaries' => \setup::discover_binaries()
            , 'rootpath'            => dirname(dirname(dirname(dirname(__DIR__)))) . '/'
        ));
    }

    public function doInstall(Application $app, Request $request)
    {
        set_time_limit(360);
        \phrasea::use_i18n($app['locale']);

        $servername = $request->getScheme() . '://' . $request->getHttpHost() . '/';

        $conn = $connbas = null;

        $hostname = $request->request->get('ab_hostname');
        $port = $request->request->get('ab_port');
        $user_ab = $request->request->get('ab_user');
        $password = $request->request->get('ab_password');

        $appbox_name = $request->request->get('ab_name');
        $databox_name = $request->request->get('db_name');
        $setupRegistry = new \Setup_Registry();

        try {
            $conn = new \connection_pdo('appbox', $hostname, $port, $user_ab, $password, $appbox_name, array(), $setupRegistry);
        } catch (\Exception $e) {
            return $app->redirect('/setup/installer/step2/?error=' . _('Appbox is unreachable'));
        }

        try {
            if ($databox_name) {
                $connbas = new \connection_pdo('databox', $hostname, $port, $user_ab, $password, $databox_name, array(), $setupRegistry);
            }
        } catch (\Exception $e) {
            return $app->redirect('/setup/installer/step2/?error=' . _('Databox is unreachable'));
        }

        \setup::rollback($conn, $connbas);

        $app['phraseanet.registry'] = $setupRegistry;

        try {

            $appbox = \appbox::create($app, $conn, $appbox_name, $app['phraseanet.appbox'], true);

            $configuration = Configuration::build();

            if ($configuration->isInstalled()) {
                $serviceName = $configuration->getOrm();
                $confService = $configuration->getService($serviceName);

                $ormService = ServiceBuilder::create($app, $confService);

                if ($ormService->getType() === 'doctrine') {
                    /* @var $em \Doctrine\ORM\EntityManager */

                    $em = $ormService->getDriver();

                    $metadatas = $em->getMetadataFactory()->getAllMetadata();

                    if (!empty($metadatas)) {
                        // Create SchemaTool
                        $tool = new SchemaTool($em);
                        // Create schema
                        $tool->dropSchema($metadatas);
                        $tool->createSchema($metadatas);
                    }
                }
            }

            \setup::create_global_values($app);

//                    $appbox->set_registry($registry);

            $app['phraseanet.registry']->set('GV_base_datapath_noweb', \p4string::addEndSlash($request->request->get('datapath_noweb')), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_ServerName', $servername, \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_cli', $request->request->get('binary_php'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_imagick', $request->request->get('binary_convert'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_pathcomposite', $request->request->get('binary_composite'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_swf_extract', $request->request->get('binary_swfextract'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_pdf2swf', $request->request->get('binary_pdf2swf'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_swf_render', $request->request->get('binary_swfrender'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_unoconv', $request->request->get('binary_unoconv'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_ffmpeg', $request->request->get('binary_ffmpeg'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_mp4box', $request->request->get('binary_MP4Box'), \registry::TYPE_STRING);
            $app['phraseanet.registry']->set('GV_pdftotext', $request->request->get('binary_xpdf'), \registry::TYPE_STRING);

            $user = \User_Adapter::create($app, $request->request->get('email'), $request->request->get('password'), $request->request->get('email'), true);

            \phrasea::start($app['phraseanet.configuration']);

            $auth = new \Session_Authentication_None($user);

            $app->openAccount($auth);

            if ($databox_name && !\p4string::hasAccent($databox_name)) {
                $template = new \SplFileInfo(__DIR__ . '/../../../../conf.d/data_templates/' . $request->request->get('db_template') . '.xml');
                $databox = \databox::create($app, $connbas, $template, $app['phraseanet.registry']);
                $user->ACL()
                    ->give_access_to_sbas(array($databox->get_sbas_id()))
                    ->update_rights_to_sbas(
                        $databox->get_sbas_id(), array(
                        'bas_manage'        => 1, 'bas_modify_struct' => 1,
                        'bas_modif_th'      => 1, 'bas_chupub'        => 1
                        )
                );

                $a = \collection::create($app, $databox, $appbox, 'test', $user);

                $user->ACL()->give_access_to_base(array($a->get_base_id()));
                $user->ACL()->update_rights_to_base($a->get_base_id(), array(
                    'canpush'         => 1, 'cancmd'          => 1
                    , 'canputinalbum'   => 1, 'candwnldhd'      => 1, 'candwnldpreview' => 1, 'canadmin'        => 1
                    , 'actif'           => 1, 'canreport'       => 1, 'canaddrecord'    => 1, 'canmodifrecord'  => 1
                    , 'candeleterecord' => 1, 'chgstatus'       => 1, 'imgtools'        => 1, 'manage'          => 1
                    , 'modify_struct'   => 1, 'nowatermark'     => 1
                    )
                );

                $tasks = $request->request->get('create_task', array());
                foreach ($tasks as $task) {
                    switch ($task) {
                        case 'cindexer';
                        case 'subdef';
                        case 'writemeta';
                            $class_name = sprintf('task_period_%s', $task);
                            if ($task === 'cindexer') {
                                $credentials = $databox->get_connection()->get_credentials();

                                $host = $credentials['hostname'];
                                $port = $credentials['port'];
                                $user_ab = $credentials['user'];
                                $password = $credentials['password'];

                                $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<binpath>"
                                    . str_replace('/phraseanet_indexer', '', $request->request->get('binary_phraseanet_indexer'))
                                    . "</binpath><host>" . $host . "</host><port>"
                                    . $port . "</port><base>"
                                    . $appbox_name . "</base><user>"
                                    . $user_ab . "</user><password>"
                                    . $password . "</password><socket>25200</socket>"
                                    . "<use_sbas>1</use_sbas><nolog>0</nolog><clng></clng>"
                                    . "<winsvc_run>0</winsvc_run><charset>utf8</charset></tasksettings>";
                            } else {
                                $settings = null;
                            }

                            \task_abstract::create($app, $class_name, $settings);
                            break;
                        default:
                            break;
                    }
                }
            }

            $redirection = '/admin/?section=taskmanager&notice=install_success';

            return $app->redirect($redirection);
        } catch (\Exception $e) {
            \setup::rollback($conn, $connbas);
        }

        return $app->redirect('/setup/installer/step2/?error=' . sprintf(_('an error occured : %s'), $e->getMessage()));
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
