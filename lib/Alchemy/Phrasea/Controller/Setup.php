<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;
use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;
use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhraseaRequirements;
use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;
use Silex\ControllerProviderInterface;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;

class Setup implements ControllerProviderInterface
{
    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app) {
            return $app->redirect($app->path('install_root'));
        });

        $controllers->get('/installer/', $this->call('rootInstaller'))
            ->bind('install_root');

        $controllers->get('/installer/step2/', $this->call('getInstallForm'))
            ->bind('install_step2');

        $controllers->post('/installer/install/', $this->call('doInstall'))
            ->bind('install_do_install');

        return $controllers;
    }

    public function rootInstaller(Application $app, Request $request)
    {
        $requirementsCollection = $this->getRequirementsCollection();

        return $app['twig']->render('/setup/index.html.twig', array(
            'locale'                 => $app['locale'],
            'available_locales'      => Application::getAvailableLanguages(),
            'current_servername'     => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'requirementsCollection' => $requirementsCollection,
        ));
    }

    private function getRequirementsCollection()
    {
        return array(
            new BinariesRequirements(),
            new FilesystemRequirements(),
            new LocalesRequirements(),
            new PhpRequirements(),
            new PhraseaRequirements(),
            new SystemRequirements(),
        );
    }

    public function getInstallForm(Application $app, Request $request)
    {
        $warnings = array();

        $requirementsCollection = $this->getRequirementsCollection();

        foreach ($requirementsCollection as $requirements) {
            foreach ($requirements->getRequirements() as $requirement) {
                if (!$requirement->isFulfilled() && !$requirement->isOptional()) {
                    $warnings[] = $requirement->getTestMessage();
                }
            }
        }

        if ($request->getScheme() == 'http') {
            $warnings[] = _('It is not recommended to install Phraseanet without HTTPS support');
        }

        return $app['twig']->render('/setup/step2.html.twig', array(
            'locale'              => $app['locale'],
            'available_locales'   => Application::getAvailableLanguages(),
            'available_templates' => array('en', 'fr'),
            'warnings'            => $warnings,
            'error'               => $request->query->get('error'),
            'current_servername'  => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'discovered_binaries' => \setup::discover_binaries(),
            'rootpath'            => realpath(__DIR__ . '/../../../../'),
        ));
    }

    public function doInstall(Application $app, Request $request)
    {
        set_time_limit(360);

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
            return $app->redirect($app->path('install_step2', array(
                'error' => _('Appbox is unreachable'),
            )));
        }

        try {
            if ($databox_name) {
                $dbConn = new \connection_pdo('databox', $hostname, $port, $user_ab, $ab_password, $databox_name, array(), $app['debug']);
            }
        } catch (\Exception $e) {
            return $app->redirect($app->path('install_step2', array(
                'error' => _('Databox is unreachable'),
            )));
        }

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $template = $request->request->get('db_template');
        $dataPath = $request->request->get('datapath_noweb');

        try {
            $installer = $app['phraseanet.installer'];
            $installer->setPhraseaIndexerPath($request->request->get('binary_phraseanet_indexer'));

            $binaryData = array();
            foreach (array(
                'php_binary'         => $request->request->get('binary_php'),
                'convert_binary'     => $request->request->get('binary_convert'),
                'composite_binary'   => $request->request->get('binary_composite'),
                'swf_extract_binary' => $request->request->get('binary_swfextract'),
                'pdf2swf_binary'     => $request->request->get('binary_pdf2swf'),
                'swf_render_binary'  => $request->request->get('binary_swfrender'),
                'unoconv_binary'     => $request->request->get('binary_unoconv'),
                'ffmpeg_binary'      => $request->request->get('binary_ffmpeg'),
                'mp4box_binary'      => $request->request->get('binary_MP4Box'),
                'pdftotext_binary'   => $request->request->get('binary_xpdf'),
            ) as $key => $path) {
                $binaryData[$key] = $path;
            }

            $user = $installer->install($email, $password, $abConn, $servername, $dataPath, $dbConn, $template, $binaryData);

            $app->openAccount(new \Session_Authentication_None($user));

            return $app->redirect($app->path('admin', array(
                'section' => 'taskmanager',
                'notice'  => 'install_success',
            )));
        } catch (\Exception $e) {

        }

        return $app->redirect($app->path('install_step2', array(
            'error' => sprintf(_('an error occured : %s'), $e->getMessage()),
        )));
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
