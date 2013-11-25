<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Client;

use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Model\Entities\UserQuery;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Root implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.client'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            if (!$app['authentication']->isAuthenticated() && null !== $request->query->get('nolog')) {
                return $app->redirectPath('login_authenticate_as_guest', ['redirect' => 'client']);
            }
            $app['firewall']->requireAuthentication();
        });

        $controllers->get('/', 'controller.client:getClient')
            ->bind('get_client');

        $controllers->get('/language/', 'controller.client:getClientLanguage')
            ->bind('get_client_language');

        $controllers->get('/publications/', 'controller.client:getClientPublications')
            ->bind('client_publications_start_page');

        $controllers->get('/help/', 'controller.client:getClientHelp')
            ->bind('client_help_start_page');

        $controllers->post('/query/', 'controller.client:query')
            ->bind('client_query');

        return $controllers;
    }

    /**
     * Queries database to fetch documents
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function query(Application $app, Request $request)
    {
        $query = $this->buildQueryFromRequest($request);
        $displayMode = explode('X', $request->request->get('mod', '3X6'));

        if (count($displayMode) === 1) {
            $modRow = (int) ($displayMode[0]);
            $modCol = 1;
        } else {
            $modRow = (int) ($displayMode[0]);
            $modCol = (int) ($displayMode[1]);
        }

        $perPage = $modCol * $modRow;

        $options = SearchEngineOptions::fromRequest($app, $request);
        $app['phraseanet.SE']->setOptions($options);

        $currentPage = (int) $request->request->get('pag', 0);

        if ($currentPage < 1) {
            $app['phraseanet.SE']->resetCache();
            $currentPage = 1;
        }

        $result = $app['phraseanet.SE']->query($query, ($currentPage - 1) * $perPage, $perPage);

        $userQuery = new UserQuery();
        $userQuery->setUsrId($app['authentication']->getUser()->get_id());
        $userQuery->setQuery($query);

        $app['EM']->persist($userQuery);
        $app['EM']->flush();

        if ($app['authentication']->getUser()->getPrefs('start_page') === 'LAST_QUERY') {
            $app['authentication']->getUser()->setPrefs('start_page_query', $query);
        }

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function (\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function (\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $app['phraseanet.SE.logger']->log($databox, $result->getQuery(), $result->getTotal(), $colls);
        }

        $searchData = $result->getResults();

        if (count($searchData) === 0 ) {
            return new Response($app['twig']->render("client/help.html.twig"));
        }

        $resultData = [];

        foreach ($searchData as $record) {
            try {
                $record->get_subdef('document');
                $lightInfo = $app['twig']->render('common/technical_datas.html.twig', ['record' => $record]);
            } catch (\Exception $e) {
                $lightInfo = '';
            }

            $caption = $app['twig']->render('common/caption.html.twig', ['view'   => 'answer', 'record' => $record]);

            $docType = $record->get_type();
            $isVideo = ($docType == 'video');
            $isAudio = ($docType == 'audio');
            $isImage = ($docType == 'image');
            $isDocument = ($docType == 'document');

            if (!$isVideo && !$isAudio) {
                $isImage = true;
            }

            $canDownload = $app['acl']->get($app['authentication']->getUser())->has_right_on_base($record->get_base_id(), 'candwnldpreview') ||
            $app['acl']->get($app['authentication']->getUser())->has_right_on_base($record->get_base_id(), 'candwnldhd') ||
            $app['acl']->get($app['authentication']->getUser())->has_right_on_base($record->get_base_id(), 'cancmd');

            try {
                $previewExists = $record->get_preview()->is_physically_present();
            } catch (\Exception $e) {
                $previewExists = false;
            }

            $resultData[] = [
                'record'            => $record,
                'mini_logo'         => \collection::getLogo($record->get_base_id(), $app),
                'preview_exists'    => $previewExists,
                'light_info'        => $lightInfo,
                'caption'           => $caption,
                'is_video'          => $isVideo,
                'is_audio'          => $isAudio,
                'is_image'          => $isImage,
                'is_document'       => $isDocument,
                'can_download'      => $canDownload,
                'can_add_to_basket' => $app['acl']->get($app['authentication']->getUser())->has_right_on_base($record->get_base_id(), 'canputinalbum')
            ];
        }

        return new Response($app['twig']->render("client/answers.html.twig", [
            'mod_col'              => $modCol,
            'mod_row'              => $modRow,
            'result_data'          => $resultData,
            'per_page'             => $perPage,
            'search_engine'        =>  $app['phraseanet.SE'],
            'search_engine_option' => $options->serialize(),
            'history'              => \queries::history($app, $app['authentication']->getUser()->get_id()),
            'result'               => $result,
            'proposals'            => $currentPage === 1 ? $result->getProposals() : null,
            'help'                 => count($resultData) === 0 ? $this->getHelpStartPage($app) : '',
        ]));
    }

    /**
     * Gets help start page
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function getClientHelp(Application $app, Request $request)
    {
        return new Response($this->getHelpStartPage($app));
    }

    /**
     * Gets client publication start page
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function getClientPublications(Application $app, Request $request)
    {
        return new Response($this->getPublicationStartPage($app));
    }

    /**
     * Gets client language
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getClientLanguage(Application $app, Request $request)
    {
        $out = [];
        $out['createWinInvite'] = $app->trans('paniers:: Quel nom souhaitez vous donner a votre panier ?');
        $out['chuNameEmpty'] = $app->trans('paniers:: Quel nom souhaitez vous donner a votre panier ?');
        $out['noDLok'] = $app->trans('export:: aucun document n\'est disponible au telechargement');
        $out['confirmRedirectAuth'] = $app->trans('invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez');
        $out['serverName'] = $app['phraseanet.registry']->get('GV_ServerName');
        $out['serverError'] = $app->trans('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique');
        $out['serverTimeout'] = $app->trans('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible');
        $out['serverDisconnected'] = $app->trans('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier');
        $out['confirmDelBasket'] = $app->trans('paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?');
        $out['annuler'] = $app->trans('boutton::annuler');
        $out['fermer'] = $app->trans('boutton::fermer');
        $out['renewRss'] = $app->trans('boutton::renouveller');
        $out['print'] = $app->trans('Print');
        $out['no_basket'] = $app->trans('Please create a basket before adding an element');

        return $app->json($out);
    }

    /**
     * Gets client main page
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function getClient(Application $app, Request $request)
    {
        try {
            \Session_Logger::updateClientInfos($app, 2);
        } catch (SessionNotFound $e) {
            return $app->redirectPath('logout');
        }
        $renderTopics = '';

        if ($app['phraseanet.registry']->get('GV_client_render_topics') == 'popups') {
            $renderTopics = \queries::dropdown_topics($app['translator'], $app['locale.I18n']);
        } elseif ($app['phraseanet.registry']->get('GV_client_render_topics') == 'tree') {
            $renderTopics = \queries::tree_topics($app['locale.I18n']);
        }

        return new Response($app['twig']->render('client/index.html.twig', [
            'last_action'       => !$app['authentication']->getUser()->is_guest() && false !== $request->cookies->has('last_act') ? $request->cookies->has('last_act') : null,
            'phrasea_home'      => $this->getDefaultClientStartPage($app),
            'render_topics'     => $renderTopics,
            'grid_properties'   => $this->getGridProperty(),
            'search_order'      => SearchEngineOptions::SORT_MODE_DESC,
            'storage_access'    => $this->getDocumentStorageAccess($app),
            'tabs_setup'        => $this->getTabSetup($app),
            'module'            => 'client',
            'menubar'           => $app['twig']->render('common/menubar.html.twig', ['module'           => 'client']),
            'css_file'          => $this->getCssFile($app),
            'basket_status'     => null !== $app['authentication']->getUser()->getPrefs('client_basket_status') ? $app['authentication']->getUser()->getPrefs('client_basket_status') : "1",
            'mod_pres'          => null !== $app['authentication']->getUser()->getPrefs('client_view') ? $app['authentication']->getUser()->getPrefs('client_view') : '',
            'start_page'        => $app['authentication']->getUser()->getPrefs('start_page'),
            'start_page_query'  => null !== $app['authentication']->getUser()->getPrefs('start_page_query') ? $app['authentication']->getUser()->getPrefs('start_page_query') : ''
        ]));
    }

    /**
     * Gets display grid property
     *
     * @return array
     */
    private function getGridProperty()
    {
        return [
            ['w'        => '3', 'h'        => '2', 'name'      => '3*2', 'selected'        => '0'],
            ['w'        => '5', 'h'        => '4', 'name'      => '5*4', 'selected'        => '0'],
            ['w'        => '4', 'h'        => '10', 'name'     => '4*10', 'selected'       => '0'],
            ['w'        => '6', 'h'        => '3', 'name'      => '6*3', 'selected'        => '1'],
            ['w'        => '8', 'h'        => '4', 'name'      => '8*4', 'selected'        => '0'],
            ['w'        => '1', 'h'        => '10', 'name'     => 'list*10', 'selected'    => '0'],
            ['w'        => '1', 'h'        => '100', 'name'    => 'list*100', 'selected'   => '0']
        ];
    }

    /**
     * Gets databoxes and collections the current user can access
     *
     * @param  Application $app
     * @return array
     */
    private function getDocumentStorageAccess(Application $app)
    {
        $allDataboxes = $allCollections = [];

        foreach ($app['acl']->get($app['authentication']->getUser())->get_granted_sbas() as $databox) {
            if (count($app['phraseanet.appbox']->get_databoxes()) > 0) {
                $allDataboxes[$databox->get_sbas_id()] = ['databox'     => $databox, 'collections' => []];
            }

            if (count($databox->get_collections()) > 0) {
                foreach ($app['acl']->get($app['authentication']->getUser())->get_granted_base([], [$databox->get_sbas_id()]) as $coll) {
                    $allDataboxes[$databox->get_sbas_id()]['collections'][$coll->get_base_id()] = $coll;
                    $allCollections[$coll->get_base_id()] = $coll;
                }
            }
        }

        return ['databoxes'   => $allDataboxes, 'collections' => $allCollections];
    }

    /**
     * Gets Client Tab Setup
     *
     * @param  Application $app
     * @return array
     */
    private function getTabSetup(Application $app)
    {
        $tong = [
            $app['phraseanet.registry']->get('GV_ong_search')    => 1,
            $app['phraseanet.registry']->get('GV_ong_advsearch') => 2,
            $app['phraseanet.registry']->get('GV_ong_topics')    => 3
        ];

        unset($tong[0]);

        if (count($tong) == 0) {
            $tong = [1 => 1];
        }

        ksort($tong);

        return $tong;
    }

    /**
     * Returns the CSS file used by end user
     *
     * @param  Application $app
     * @return string
     */
    private function getCssFile(Application $app)
    {
        $cssPath = __DIR__ . '/../../../../../www/skins/client/';

        $css = [];
        $cssFile = $app['authentication']->getUser()->getPrefs('client_css');

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

        if ((!$cssFile || !isset($css[$cssFile])) && isset($css['000000'])) {
            $cssFile = '000000';
        }

        return sprintf('skins/client/%s/clientcolor.css', $cssFile);
    }

    /**
     * Forges query from request parameters
     *
     * @param  Request $request
     * @return string
     */
    private function buildQueryFromRequest(Request $request)
    {
        $query = '';

        if ('' !== $clientQuery = trim($request->request->get('qry', ''))) {
            $query .= $clientQuery;
        }

        $opAdv = $request->request->get('opAdv', []);
        $queryAdv = $request->request->get('qryAdv', []);

        if (count($opAdv) > 0 && count($opAdv) == count($queryAdv)) {
            foreach ($opAdv as $opId => $op) {
                if (isset($queryAdv[$opId]) && ($advancedQuery = trim($queryAdv[$opId]) !== '')) {
                    if ($query === $clientQuery) {
                        $query = '(' . $clientQuery . ')';
                    }

                    $query .= ' ' . $op . ' (' . $advancedQuery . ')';
                }
            }
        }

        if (empty($query)) {
            $query = 'all';
        }

        return $query;
    }

    /**
     * Gets default start home page for client
     *
     * @param  Application $app
     * @return string
     */
    private function getDefaultClientStartPage(Application $app)
    {
        $startPage = strtoupper($app['authentication']->getUser()->getPrefs('start_page'));

        if ($startPage === 'PUBLI') {
            return $this->getPublicationStartPage($app);
        }

        if (in_array($startPage, ['QUERY', 'LAST_QUERY'])) {
            return $this->getQueryStartPage($app);
        }

        return $this->getHelpStartPage($app);
    }

    /**
     * Gets query start home page for client
     *
     * @param  Application $app
     * @return string
     */
    private function getQueryStartPage(Application $app)
    {
        $collections = $queryParameters = [];

        $searchSet = json_decode($app['authentication']->getUser()->getPrefs('search'));

        if ($searchSet && isset($searchSet->bases)) {
            foreach ($searchSet->bases as $bases) {
                $collections = array_merge($collections, $bases);
            }
        } else {
            $collections = array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base());
        }

        $queryParameters["mod"] = $app['authentication']->getUser()->getPrefs('client_view') ?: '3X6';
        $queryParameters["bas"] = $collections;
        $queryParameters["qry"] = $app['authentication']->getUser()->getPrefs('start_page_query') ?: 'all';
        $queryParameters["pag"] = 0;
        $queryParameters["search_type"] = SearchEngineOptions::RECORD_RECORD;
        $queryParameters["qryAdv"] = '';
        $queryParameters["opAdv"] = [];
        $queryParameters["status"] = [];
        $queryParameters["recordtype"] = SearchEngineOptions::TYPE_ALL;
        $queryParameters["sort"] = $app['phraseanet.registry']->get('GV_phrasea_sort', '');
        $queryParameters["infield"] = [];
        $queryParameters["ord"] = SearchEngineOptions::SORT_MODE_DESC;

        $subRequest = Request::create('/client/query/', 'POST', $queryParameters);

        return $this->query($app, $subRequest)->getContent();
    }

     /**
     * Gets publications start home page for client
     *
     * @param  Application $app
     * @return string
     */
    private function getPublicationStartPage(Application $app)
    {
        return $app['twig']->render('client/home_inter_pub_basket.html.twig', [
            'feeds'         => Aggregate::createFromUser($app, $app['authentication']->getUser()),
            'image_size'    => (int) $app['authentication']->getUser()->getPrefs('images_size')
        ]);
    }

     /**
     * Get help start home page for client
     *
     * @param  Application $app
     * @return string
     */
    private function getHelpStartPage(Application $app)
    {
        return $app['twig']->render('client/help.html.twig');
    }
}
