<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Exception\HttpException,
    Symfony\Component\Finder\Finder,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Helper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Root implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = new ControllerCollection();

        $controllers->get('/', function(Application $app) {

                \User_Adapter::updateClientInfos(1);

                $appbox = \appbox::get_instance($app['Core']);
                $registry = $app['Core']->getRegistry();
                $user = $app['Core']->getAuthenticatedUser();
                $cssPath = $registry->get('GV_RootPath') . 'www/skins/prod/';

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

                $cssfile = $user->getPrefs('css');

                if ( ! $cssfile && isset($css['000000'])) {
                    $cssfile = '000000';
                }


                $user_feeds = \Feed_Collection::load_all($appbox, $user);
                $feeds = array_merge(array($user_feeds->get_aggregate()), $user_feeds->get_feeds());

                $thjslist = "";

                $queries_topics = '';

                if ($registry->get('GV_client_render_topics') == 'popups') {
                    $queries_topics = \queries::dropdown_topics();
                } elseif ($registry->get('GV_client_render_topics') == 'tree') {
                    $queries_topics = \queries::tree_topics();
                }

                $sbas = $bas2sbas = array();

                foreach ($appbox->get_databoxes() as $databox) {
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

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                $Serializer = $app['Core']['Serializer'];

                $out = $twig->render('prod/index.html.twig', array(
                    'module_name'          => 'Production',
                    'WorkZone'             => new Helper\WorkZone($app['Core'], $app['request']),
                    'module_prod'          => new Helper\Prod($app['Core'], $app['request']),
                    'cssfile'              => $cssfile,
                    'module'               => 'prod',
                    'events'               => \eventsmanager_broker::getInstance($appbox, $app['Core']),
                    'GV_defaultQuery_type' => $registry->get('GV_defaultQuery_type'),
                    'GV_multiAndReport'    => $registry->get('GV_multiAndReport'),
                    'GV_thesaurus'         => $registry->get('GV_thesaurus'),
                    'cgus_agreement'       => \databox_cgu::askAgreement(),
                    'css'                  => $css,
                    'feeds'                => $feeds,
                    'GV_google_api'        => $registry->get('GV_google_api'),
                    'queries_topics'       => $queries_topics,
                    'search_status'        => \databox_status::getSearchStatus(),
                    'queries_history'      => \queries::history(),
                    'thesau_js_list'       => $thjslist,
                    'thesau_json_sbas'     => $Serializer->serialize($sbas, 'json'),
                    'thesau_json_bas2sbas' => $Serializer->serialize($bas2sbas, 'json'),
                    'thesau_languages'     => \User_Adapter::avLanguages(),
                    'GV_bitly_user'        => $registry->get('GV_bitly_user'),
                    'GV_bitly_key'         => $registry->get('GV_bitly_key')
                    ));

                return new Response($out);
            });

        return $controllers;
    }
}
