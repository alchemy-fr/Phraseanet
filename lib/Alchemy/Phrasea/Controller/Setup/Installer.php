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
                , 'available_templates' => array('en', 'fr')
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

        $abConn = $dbConn = null;

        $hostname = $request->request->get('ab_hostname');
        $port = $request->request->get('ab_port');
        $user_ab = $request->request->get('ab_user');
        $ab_password = $request->request->get('ab_password');

        $appbox_name = $request->request->get('ab_name');
        $databox_name = $request->request->get('db_name');
        $setupRegistry = new \Setup_Registry();

        try {
            $abConn = new \connection_pdo('appbox', $hostname, $port, $user_ab, $ab_password, $appbox_name, array(), $app['debug']);
        } catch (\Exception $e) {
            return $app->redirect('/setup/installer/step2/?error=' . _('Appbox is unreachable'));
        }

        try {
            if ($databox_name) {
                $dbConn = new \connection_pdo('databox', $hostname, $port, $user_ab, $ab_password, $databox_name, array(), $app['debug']);
            }
        } catch (\Exception $e) {
            return $app->redirect('/setup/installer/step2/?error=' . _('Databox is unreachable'));
        }

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $template = $request->request->get('db_template');
        $dataPath = $request->request->get('datapath_noweb');

        try {
            $installer = new \Alchemy\Phrasea\Setup\Installer($app, $email, $password, $abConn, $servername, $dataPath, $dbConn, $template);
            $installer->setPhraseaIndexerPath($request->request->get('binary_phraseanet_indexer'));

            foreach (array(
            'GV_cli'           => $request->request->get('binary_php'),
            'GV_imagick'       => $request->request->get('binary_convert'),
            'GV_pathcomposite' => $request->request->get('binary_composite'),
            'GV_swf_extract'   => $request->request->get('binary_swfextract'),
            'GV_pdf2swf'       => $request->request->get('binary_pdf2swf'),
            'GV_swf_render'    => $request->request->get('binary_swfrender'),
            'GV_unoconv'       => $request->request->get('binary_unoconv'),
            'GV_ffmpeg'        => $request->request->get('binary_ffmpeg'),
            'GV_mp4box'        => $request->request->get('binary_MP4Box'),
            'GV_pdftotext'     => $request->request->get('binary_xpdf'),
            ) as $key => $path) {
                $installer->addRegistryData($key, $path);
            }

            $installer->install();

            $redirection = '/admin/?section=taskmanager&notice=install_success';

            return $app->redirect($redirection);
        } catch (\Exception $e) {

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
