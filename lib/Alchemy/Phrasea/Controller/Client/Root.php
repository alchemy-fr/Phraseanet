<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Client;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Root implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAuthentication();
        });

        /**
         * Get client main page
         *
         * name         : get_client
         *
         * description  : Get client homepage
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('getClient'))
            ->bind('get_client');

         /**
         * Get client language
         *
         * name         : get_client_language
         *
         * description  : Get client language
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->get('/language/', $this->call('getClientLanguage'))
            ->bind('get_client_language');

         /**
         * Get client publication page
         *
         * name         : client_publications_start_page
         *
         * description  : Get client language
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->get('/publications/', $this->call('getClientPublications'))
            ->bind('client_publications_start_page');

         /**
         * Get client help page
         *
         * name         : client_help_start_page
         *
         * description  : Get client help
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/help/', $this->call('getClientHelp'))
            ->bind('client_help_start_page');

         /**
         * Query client for documents
         *
         * name         : client_query
         *
         * description  : Query client for documents
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/query/', $this->call('query'))
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

        $result = $app['phraseanet.SE']->query($query, $currentPage, $perPage);

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function(\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function(\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $app['phraseanet.SE.logger']->log($databox, $result->getQuery(), $result->getTotal(), $colls);
        }

        $searchData = $result->getResults();

        if (count($searchData) === 0 ) {
            return new Response($app['twig']->render("client/help.html.twig"));
        }

        $resultData = array();

        foreach ($searchData as $record) {
            try {
                $record->get_subdef('document');
                $lightInfo = $app['twig']->render('common/technical_datas.html.twig', array('record' => $record));
            } catch (\Exception $e) {
                $lightInfo = '';
            }

            $caption = $app['twig']->render('common/caption.html.twig', array('view'   => 'answer', 'record' => $record));

            $docType = $record->get_type();
            $isVideo = ($docType == 'video');
            $isAudio = ($docType == 'audio');
            $isImage = ($docType == 'image');
            $isDocument = ($docType == 'document');

            if (!$isVideo && !$isAudio) {
                $isImage = true;
            }

            $canDownload = $app['phraseanet.user']->ACL()->has_right_on_base($record->get_base_id(), 'candwnldpreview') ||
                $app['phraseanet.user']->ACL()->has_right_on_base($record->get_base_id(), 'candwnldhd') ||
                $app['phraseanet.user']->ACL()->has_right_on_base($record->get_base_id(), 'cancmd');

            try {
                $previewExists = $record->get_preview()->is_physically_present();
            } catch (\Exception $e) {
                $previewExists = false;
            }

            $resultData[] = array(
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
                'can_add_to_basket' => $app['phraseanet.user']->ACL()->has_right_on_base($record->get_base_id(), 'canputinalbum')
            );
        }

        return new Response($app['twig']->render("client/answers.html.twig", array(
            'mod_col'              => $modCol,
            'mod_row'              => $modRow,
            'result_data'          => $resultData,
            'per_page'             => $perPage,
            'search_engine'        =>  $app['phraseanet.SE'],
            'search_engine_option' => $options->serialize(),
            'history'              => \queries::history($app['phraseanet.appbox'], $app['phraseanet.user']->get_id()),
            'result'               => $result,
            'proposals'            => $currentPage === 1 ? $result->getProposals() : null,
            'help'                 => count($resultData) === 0 ? $this->getHelpStartPage($app) : '',
        )));
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
        $out = array();
        $out['createWinInvite'] = _('paniers:: Quel nom souhaitez vous donner a votre panier ?');
        $out['chuNameEmpty'] = _('paniers:: Quel nom souhaitez vous donner a votre panier ?');
        $out['noDLok'] = _('export:: aucun document n\'est disponible au telechargement');
        $out['confirmRedirectAuth'] = _('invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez');
        $out['serverName'] = $app['phraseanet.registry']->get('GV_ServerName');
        $out['serverError'] = _('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique');
        $out['serverTimeout'] = _('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible');
        $out['serverDisconnected'] = _('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier');
        $out['confirmDelBasket'] = _('paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?');
        $out['annuler'] = _('boutton::annuler');
        $out['fermer'] = _('boutton::fermer');
        $out['renewRss'] = _('boutton::renouveller');
        $out['print'] = _('Print');
        $out['no_basket'] = _('Please create a basket before adding an element');

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
            \User_Adapter::updateClientInfos($app, 2);
        } catch (SessionNotFound $e) {
            return $app->redirect($app['url_generator']->generate('logout'));
        }
        $renderTopics = '';

        if ($app['phraseanet.registry']->get('GV_client_render_topics') == 'popups') {
            $renderTopics = \queries::dropdown_topics($app['locale.I18n']);
        } elseif ($app['phraseanet.registry']->get('GV_client_render_topics') == 'tree') {
            $renderTopics = \queries::tree_topics($app['locale.I18n']);
        }

        return new Response($app['twig']->render('client/index.html.twig', array(
            'last_action'       => !$app['phraseanet.user']->is_guest() && false !== $request->cookies->has('last_act') ? $request->cookies->has('last_act') : null,
            'phrasea_home'      => $this->getDefaultClientStartPage($app),
            'render_topics'     => $renderTopics,
            'grid_properties'   => $this->getGridProperty(),
            'search_order'      => SearchEngineOptions::SORT_MODE_DESC,
            'storage_access'    => $this->getDocumentStorageAccess($app),
            'tabs_setup'        => $this->getTabSetup($app),
            'menubar'           => $app['twig']->render('common/menubar.html.twig', array('module'           => 'client')),
            'css_file'          => $this->getCssFile($app),
            'basket_status'     => null !== $app['phraseanet.user']->getPrefs('client_basket_status') ? $app['phraseanet.user']->getPrefs('client_basket_status') : "1",
            'mod_pres'          => null !== $app['phraseanet.user']->getPrefs('client_view') ? $app['phraseanet.user']->getPrefs('client_view') : '',
            'start_page'        => $app['phraseanet.user']->getPrefs('start_page'),
            'start_page_query'  => null !== $app['phraseanet.user']->getPrefs('start_page_query') ? $app['phraseanet.user']->getPrefs('start_page_query') : ''
        )));
    }

    /**
     * Gets display grid property
     *
     * @return array
     */
    private function getGridProperty()
    {
        return array(
            array('w'        => '3', 'h'        => '2', 'name'      => '3*2', 'selected'        => '0'),
            array('w'        => '5', 'h'        => '4', 'name'      => '5*4', 'selected'        => '0'),
            array('w'        => '4', 'h'        => '10', 'name'     => '4*10', 'selected'       => '0'),
            array('w'        => '6', 'h'        => '3', 'name'      => '6*3', 'selected'        => '1'),
            array('w'        => '8', 'h'        => '4', 'name'      => '8*4', 'selected'        => '0'),
            array('w'        => '1', 'h'        => '10', 'name'     => 'list*10', 'selected'    => '0'),
            array('w'        => '1', 'h'        => '100', 'name'    => 'list*100', 'selected'   => '0')
        );
    }

    /**
     * Gets databoxes and collections the current user can access
     *
     * @param  Application $app
     * @return array
     */
    private function getDocumentStorageAccess(Application $app)
    {
        $allDataboxes = $allCollections = array();

        foreach ($app['phraseanet.user']->ACL()->get_granted_sbas() as $databox) {
            if (count($app['phraseanet.appbox']->get_databoxes()) > 0) {
                $allDataboxes[$databox->get_sbas_id()] = array('databox'     => $databox, 'collections' => array());
            }

            if (count($databox->get_collections()) > 0) {
                foreach ($app['phraseanet.user']->ACL()->get_granted_base(array(), array($databox->get_sbas_id())) as $coll) {
                    $allDataboxes[$databox->get_sbas_id()]['collections'][$coll->get_base_id()] = $coll;
                    $allCollections[$coll->get_base_id()] = $coll;
                }
            }
        }

        return array('databoxes'   => $allDataboxes, 'collections' => $allCollections);
    }

    /**
     * Gets Client Tab Setup
     *
     * @param  Application $app
     * @return array
     */
    private function getTabSetup(Application $app)
    {
        $tong = array(
            $app['phraseanet.registry']->get('GV_ong_search')    => 1,
            $app['phraseanet.registry']->get('GV_ong_advsearch') => 2,
            $app['phraseanet.registry']->get('GV_ong_topics')    => 3
        );

        unset($tong[0]);

        if (count($tong) == 0) {
            $tong = array(1 => 1);
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

        $css = array();
        $cssFile = $app['phraseanet.user']->getPrefs('client_css');

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

        $opAdv = $request->request->get('opAdv', array());
        $queryAdv = $request->request->get('qryAdv', array());

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
        $startPage = strtoupper($app['phraseanet.user']->getPrefs('start_page'));

        if ($startPage === 'PUBLI') {
            return $this->getPublicationStartPage($app);
        }

        if (in_array($startPage, array('QUERY', 'LAST_QUERY'))) {
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
        $collections = $queryParameters = array();

        $searchSet = json_decode($app['phraseanet.user']->getPrefs('search'));

        if ($searchSet && isset($searchSet->bases)) {
            foreach ($searchSet->bases as $bases) {
                $collections = array_merge($collections, $bases);
            }
        } else {
            $collections = array_keys($app['phraseanet.user']->ACL()->get_granted_base());
        }

        $queryParameters["mod"] = $app['phraseanet.user']->getPrefs('client_view') ?: '3X6';
        $queryParameters["bas"] = $collections;
        $queryParameters["qry"] = $app['phraseanet.user']->getPrefs('start_page_query') ?: 'all';
        $queryParameters["pag"] = 0;
        $queryParameters["search_type"] = SearchEngineOptions::RECORD_RECORD;
        $queryParameters["qryAdv"] = '';
        $queryParameters["opAdv"] = array();
        $queryParameters["status"] = array();
        $queryParameters["recordtype"] = SearchEngineOptions::TYPE_ALL;
        $queryParameters["sort"] = $app['phraseanet.registry']->get('GV_phrasea_sort', '');
        $queryParameters["infield"] = array();
        $queryParameters["ord"] = SearchEngineOptions::SORT_MODE_DESC;

        $subRequest = Request::create('/client/query/', 'POST', $queryParameters);

        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST)->getContent();
    }

     /**
     * Gets publications start home page for client
     *
     * @param  Application $app
     * @return string
     */
    private function getPublicationStartPage(Application $app)
    {
        return $app['twig']->render('client/home_inter_pub_basket.html.twig', array(
            'feeds'         => \Feed_Collection::load_all($app, $app['phraseanet.user']),
            'image_size'    => (int) $app['phraseanet.user']->getPrefs('images_size')
        ));
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
