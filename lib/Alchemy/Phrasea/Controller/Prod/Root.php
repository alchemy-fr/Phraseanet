<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Feed\Aggregate;
use Silex\Application as SilexApplication;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Alchemy\Phrasea\Helper;

class Root implements ControllerProviderInterface
{
    public function connect(SilexApplication $app)
    {
        $app['controller.prod'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            if (!$app['authentication']->isAuthenticated() && null !== $request->query->get('nolog')) {
                return $app->redirectPath('login_authenticate_as_guest');
            }

            if (null !== $response = $app['firewall']->requireAuthentication()) {
                return $response;
            }
        });

        $controllers->get('/', function (Application $app) {
            try {
                \Session_Logger::updateClientInfos($app, 1);
            } catch (SessionNotFound $e) {
                return $app->redirectPath('logout');
            }

            $cssPath = $app['root.path'] . '/www/skins/prod/';

            $css = [];
            $cssfile = false;

            $finder = new Finder();

            $iterator = $finder
                ->directories()
                ->depth(0)
                ->filter(function (\SplFileInfo $fileinfo) {
                        return ctype_xdigit($fileinfo->getBasename());
                    })
                ->in($cssPath);

            foreach ($iterator as $dir) {
                $baseName = $dir->getBaseName();
                $css[$baseName] = $baseName;
            }

            $cssfile = $app['authentication']->getUser()->getPrefs('css');

            if (!$cssfile && isset($css['000000'])) {
                $cssfile = '000000';
            }

            $feeds = $app['EM']->getRepository('Phraseanet:Feed')->getAllForUser($app['acl']->get($app['authentication']->getUser()));
            $aggregate = Aggregate::createFromUser($app, $app['authentication']->getUser());

            $thjslist = "";

            $queries_topics = '';

            if ($app['conf']->get(['registry', 'classic', 'render-topics']) == 'popups') {
                $queries_topics = \queries::dropdown_topics($app['translator'], $app['locale']);
            } elseif ($app['conf']->get(['registry', 'classic', 'render-topics']) == 'tree') {
                $queries_topics = \queries::tree_topics($app['locale']);
            }

            $sbas = $bas2sbas = [];

            foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                $sbas_id = $databox->get_sbas_id();

                $sbas['s' + $sbas_id] = [
                    'sbid'   => $sbas_id,
                    'seeker' => null];

                foreach ($databox->get_collections() as $coll) {
                    $bas2sbas['b' . $coll->get_base_id()] = [
                        'sbid'  => $sbas_id,
                        'ckobj' => ['checked'    => false],
                        'waschecked' => false
                    ];
                }
            }

            return $app['twig']->render('prod/index.html.twig', [
                'module_name'          => 'Production',
                'WorkZone'             => new Helper\WorkZone($app, $app['request']),
                'module_prod'          => new Helper\Prod($app, $app['request']),
                'cssfile'              => $cssfile,
                'module'               => 'prod',
                'events'               => $app['events-manager'],
                'GV_defaultQuery_type' => $app['conf']->get(['registry', 'searchengine', 'default-query-type']),
                'GV_multiAndReport'    => $app['conf']->get(['registry', 'modules', 'stories']),
                'GV_thesaurus'         => $app['conf']->get(['registry', 'modules', 'thesaurus']),
                'cgus_agreement'       => \databox_cgu::askAgreement($app),
                'css'                  => $css,
                'feeds'                => $feeds,
                'aggregate'            => $aggregate,
                'GV_google_api'        => $app['conf']->get(['registry', 'webservices', 'google-charts-enabled']),
                'queries_topics'       => $queries_topics,
                'search_status'        => \databox_status::getSearchStatus($app),
                'queries_history'      => \queries::history($app, $app['authentication']->getUser()->get_id()),
                'thesau_js_list'       => $thjslist,
                'thesau_json_sbas'     => json_encode($sbas),
                'thesau_json_bas2sbas' => json_encode($bas2sbas),
                'thesau_languages'     => $app['locales.available'],
            ]);
        })->bind('prod');

        return $controllers;
    }
}
