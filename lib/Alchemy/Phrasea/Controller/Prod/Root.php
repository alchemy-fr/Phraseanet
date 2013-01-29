<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Silex\Application as SilexApplication;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Alchemy\Phrasea\Helper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Root implements ControllerProviderInterface
{

    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {

            if (!$app->isAuthenticated() && null !== $request->query->get('nolog') && \phrasea::guest_allowed($app)) {
                $auth = new Session_Authentication_Guest($app);
                $app->openAccount($auth);

                return $app->redirect('/prod/');
            }

            $app['firewall']->requireAuthentication();
        });

        $controllers->get('/', function(Application $app) {
            try {
                \User_Adapter::updateClientInfos($app, 1);
            } catch (SessionNotFound $e) {
                return $app->redirect($app['url_generator']->generate('logout'));
            }

            $cssPath = $app['phraseanet.registry']->get('GV_RootPath') . 'www/skins/prod/';

            $css = array();
            $cssfile = false;

            $finder = new Finder();

            $iterator = $finder
                ->directories()
                ->depth(0)
                ->filter(function(\SplFileInfo $fileinfo) {
                        return ctype_xdigit($fileinfo->getBasename());
                    })
                ->in($cssPath);

            foreach ($iterator as $dir) {
                $baseName = $dir->getBaseName();
                $css[$baseName] = $baseName;
            }

            $cssfile = $app['phraseanet.user']->getPrefs('css');

            if (!$cssfile && isset($css['000000'])) {
                $cssfile = '000000';
            }

            $user_feeds = \Feed_Collection::load_all($app, $app['phraseanet.user']);
            $feeds = array_merge(array($user_feeds->get_aggregate()), $user_feeds->get_feeds());

            $thjslist = "";

            $queries_topics = '';

            if ($app['phraseanet.registry']->get('GV_client_render_topics') == 'popups') {
                $queries_topics = \queries::dropdown_topics($app['locale.I18n']);
            } elseif ($app['phraseanet.registry']->get('GV_client_render_topics') == 'tree') {
                $queries_topics = \queries::tree_topics($app['locale.I18n']);
            }

            $sbas = $bas2sbas = array();

            foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                $sbas_id = $databox->get_sbas_id();

                $sbas['s' + $sbas_id] = array(
                    'sbid'   => $sbas_id,
                    'seeker' => null);

                foreach ($databox->get_collections() as $coll) {
                    $bas2sbas['b' . $coll->get_base_id()] = array(
                        'sbid'  => $sbas_id,
                        'ckobj' => array('checked'    => false),
                        'waschecked' => false
                    );
                }
            }

            return $app['twig']->render('prod/index.html.twig', array(
                'module_name'          => 'Production',
                'WorkZone'             => new Helper\WorkZone($app, $app['request']),
                'module_prod'          => new Helper\Prod($app, $app['request']),
                'cssfile'              => $cssfile,
                'module'               => 'prod',
                'events'               => $app['events-manager'],
                'GV_defaultQuery_type' => $app['phraseanet.registry']->get('GV_defaultQuery_type'),
                'GV_multiAndReport'    => $app['phraseanet.registry']->get('GV_multiAndReport'),
                'GV_thesaurus'         => $app['phraseanet.registry']->get('GV_thesaurus'),
                'cgus_agreement'       => \databox_cgu::askAgreement($app),
                'css'                  => $css,
                'feeds'                => $feeds,
                'GV_google_api'        => $app['phraseanet.registry']->get('GV_google_api'),
                'queries_topics'       => $queries_topics,
                'search_status'        => \databox_status::getSearchStatus($app),
                'queries_history'      => \queries::history($app['phraseanet.appbox'], $app['phraseanet.user']->get_id()),
                'thesau_js_list'       => $thjslist,
                'thesau_json_sbas'     => json_encode($sbas),
                'thesau_json_bas2sbas' => json_encode($bas2sbas),
                'thesau_languages'     => $app->getAvailableLanguages(),
            ));
        })->bind('prod');

        return $controllers;
    }
}
