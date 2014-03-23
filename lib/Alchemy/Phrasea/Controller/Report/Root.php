<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Report;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Root implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.report'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function () use ($app) {
            $app['firewall']->requireAccessToModule('report');
        });

        $controllers->get('/', function (Application $app) {
            return $app->redirectPath('report_dashboard');
        })->bind('report');

        $controllers->get('/dashboard', 'controller.report:getDashboard')
            ->bind('report_dashboard');

        $controllers->post('/init', 'controller.report:initReport')
            ->bind('report_init');

        $controllers->post('/connexions', 'controller.report:doReportConnexions')
            ->bind('report_connexions');

        $controllers->post('/questions', 'controller.report:doReportQuestions')
            ->bind('report_questions');

        $controllers->post('/downloads', 'controller.report:doReportDownloads')
            ->bind('report_downloads');

        $controllers->post('/documents', 'controller.report:doReportDocuments')
            ->bind('report_documents');

        $controllers->post('/clients', 'controller.report:doReportClients')
            ->bind('report_clients');

        return $controllers;
    }

    /**
     * Display dashboard informations
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getDashboard(Application $app, Request $request)
    {
       if ('json' === $request->getRequestFormat()) {
           \Session_Logger::updateClientInfos($app, 4);

           $dashboard = new \module_report_dashboard($app, $app['authentication']->getUser());

            $dmin = $request->query->get('dmin');
            $dmax = $request->query->get('dmax');

            if ($dmin && $dmax) {
                $dashboard->setDate($dmin, $dmax);
            }

            $dashboard->execute();

            return $app->json(['html' => $app['twig']->render('report/ajax_dashboard_content_child.html.twig', [
                'dashboard' => $dashboard
            ])]);
        }

        $granted = [];

        foreach ($app['acl']->get($app['authentication']->getUser())->get_granted_base(['canreport']) as $collection) {
            if (!isset($granted[$collection->get_sbas_id()])) {
                $granted[$collection->get_sbas_id()] = [
                    'id' => $collection->get_sbas_id(),
                    'name' => $collection->get_databox()->get_viewname(),
                    'collections' => []
                ];
            }
            $granted[$collection->get_sbas_id()]['collections'][] = [
                'id' => $collection->get_coll_id(),
                'base_id' => $collection->get_base_id(),
                'name' => $collection->get_name()
            ];
        }

        return $app['twig']->render('report/report_layout_child.html.twig', [
            'ajax_dash'     => true,
            'dashboard'     => null,
            'granted_bases' => $granted,
            'home_title'    => $app['conf']->get(['registry', 'general', 'title']),
            'module'        => 'report',
            'module_name'   => 'Report',
            'anonymous'     => $app['conf']->get(['registry', 'modules', 'anonymous-report']),
            'g_anal'        => $app['conf']->get(['registry', 'general', 'analytics']),
            'ajax'          => false,
            'ajax_chart'    => false
        ]);
    }

    /**
     * Gets available collections where current user can see report and
     * format date
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function initReport(Application $app, Request $request)
    {
        $popbases = $request->request->get('popbases', []);

        if ('' === $dmin = $request->request->get('dmin', '')) {
            $dmin = date('Y') . '-' . date('m') . '-01';
        }

        if ('' === $dmax = $request->request->get('dmax', '')) {
            $dmax = date('Y') . '-' . date('m') . '-' . date('d');
        }

        $dmin = \DateTime::createFromFormat('Y-m-d H:i:s', sprintf('%s 00:00:00', $dmin));
        $dmax = \DateTime::createFromFormat('Y-m-d H:i:s', sprintf('%s 23:59:59', $dmax));

        //get user's sbas & collections selection from popbases
        $selection = [];
        $liste = $id_sbas = '';
        $i = 0;
        foreach (array_fill_keys($popbases, 0) as $key => $val) {
            $exp = explode('_', $key);
            if ($exp[0] != $id_sbas && $i != 0) {
                $selection[$id_sbas]['liste'] = $liste;
                $liste = '';
            }
            $selection[$exp[0]][] = $exp[1];
            $liste .= (empty($liste) ? '' : ',') . $exp[1];
            $id_sbas = $exp[0];
            $i ++;
        }
        //fill the last entry
        $selection[$id_sbas]['liste'] = $liste;

        return $app['twig']->render('report/ajax_report_content.html.twig', [
            'selection' => $selection,
            'anonymous' => $app['conf']->get(['registry', 'modules', 'anonymous-report']),
            'ajax'      => true,
            'dmin'      => $dmin->format('Y-m-d H:i:s'),
            'dmax'      => $dmax->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Display instance connexion report
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportConnexions(Application $app, Request $request)
    {
        $cnx = new \module_report_connexion(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf = [
            'user'      => [$app->trans('phraseanet::utilisateurs'), 1, 1, 1, 1],
            'ddate'     => [$app->trans('report:: date'), 1, 0, 1, 1],
            'ip'        => [$app->trans('report:: IP'), 1, 0, 0, 0],
            'appli'     => [$app->trans('report:: modules'), 1, 0, 0, 0],
            'fonction'  => [$app->trans('report::fonction'), 1, 1, 1, 1],
            'activite'  => [$app->trans('report::activite'), 1, 1, 1, 1],
            'pays'      => [$app->trans('report::pays'), 1, 1, 1, 1],
            'societe'   => [$app->trans('report::societe'), 1, 1, 1, 1]
        ];

        if ($request->request->get('printcsv') == 'on') {
            $cnx->setHasLimit(false);
            $cnx->setPrettyString(false);

            $this->doReport($app, $request, $cnx, $conf);

            try {
                $csv = \format::arr_to_csv($cnx->getResult(), $cnx->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(['rs' => $csv]);
        }

        $report = $this->doReport($app, $request, $cnx, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $app->json([
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ]);
    }

    /**
     * Display instance questions report
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportQuestions(Application $app, Request $request)
    {
        $questions = new \module_report_question(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf = [
            'user'      => [$app->trans('report:: utilisateur'), 1, 1, 1, 1],
            'search'    => [$app->trans('report:: question'), 1, 0, 1, 1],
            'ddate'     => [$app->trans('report:: date'), 1, 0, 1, 1],
            'fonction'  => [$app->trans('report:: fonction'), 1, 1, 1, 1],
            'activite'  => [$app->trans('report:: activite'), 1, 1, 1, 1],
            'pays'      => [$app->trans('report:: pays'), 1, 1, 1, 1],
            'societe'   => [$app->trans('report:: societe'), 1, 1, 1, 1]
        ];

        if ($request->request->get('printcsv') == 'on') {
            $questions->setHasLimit(false);
            $questions->setPrettyString(false);

            $this->doReport($app, $request, $questions, $conf);

            try {
                $csv = \format::arr_to_csv($questions->getResult(), $questions->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(['rs' => $csv]);
        }

        $report = $this->doReport($app, $request, $questions, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $app->json([
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ]);
    }

    /**
     * Display instance download report
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportDownloads(Application $app, Request $request)
    {
        $download = new \module_report_download(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf_pref = [];

        foreach (\module_report::getPreff($app, $request->request->get('sbasid')) as $field) {
            $conf_pref[strtolower($field)] = [$field, 0, 0, 0, 0];
        }

        $conf = array_merge([
            'user'      => [$app->trans('report:: utilisateurs'), 1, 1, 1, 1],
            'ddate'     => [$app->trans('report:: date'), 1, 0, 1, 1],
            'record_id' => [$app->trans('report:: record id'), 1, 1, 1, 1],
            'final'     => [$app->trans('phrseanet:: sous definition'), 1, 0, 1, 1],
            'coll_id'   => [$app->trans('report:: collections'), 1, 0, 1, 1],
            'comment'   => [$app->trans('report:: commentaire'), 1, 0, 0, 0],
            'fonction'  => [$app->trans('report:: fonction'), 1, 1, 1, 1],
            'activite'  => [$app->trans('report:: activite'), 1, 1, 1, 1],
            'pays'      => [$app->trans('report:: pays'), 1, 1, 1, 1],
            'societe'   => [$app->trans('report:: societe'), 1, 1, 1, 1]
        ], $conf_pref);

        if ($request->request->get('printcsv') == 'on') {
            $download->setHasLimit(false);
            $download->setPrettyString(false);

            $this->doReport($app, $request, $download, $conf);

            try {
                $csv = \format::arr_to_csv($download->getResult(), $download->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(['rs' => $csv]);
        }

        $report = $this->doReport($app, $request, $download, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $app->json([
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ]);
    }

    /**
     * Display instance document report
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportDocuments(Application $app, Request $request)
    {
        $document = new \module_report_download(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf_pref = [];

        foreach (\module_report::getPreff($app, $request->request->get('sbasid')) as $field) {
            $conf_pref[strtolower($field)] = [$field, 0, 0, 0, 0];
        }

        $conf = array_merge([
            'telechargement'    => [$app->trans('report:: telechargements'), 1, 0, 0, 0],
            'record_id'         => [$app->trans('report:: record id'), 1, 1, 1, 0],
            'final'             => [$app->trans('phraseanet:: sous definition'), 1, 0, 1, 1],
            'file'              => [$app->trans('report:: fichier'), 1, 0, 0, 1],
            'mime'              => [$app->trans('report:: type'), 1, 0, 1, 1],
            'size'              => [$app->trans('report:: taille'), 1, 0, 1, 1]
        ], $conf_pref);

        if ($request->request->get('printcsv') == 'on') {
            $document->setHasLimit(false);
            $document->setPrettyString(false);

            $this->doReport($app, $request, $document, $conf, 'record_id');

            try {
                $csv = \format::arr_to_csv($document->getResult(), $document->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(['rs' => $csv]);
        }

        $report = $this->doReport($app, $request, $document, $conf, 'record_id');

        if ($report instanceof Response) {
            return $report;
        }

        return $app->json([
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => true
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ]);
    }

    /**
     * Display informations about client (browser, resolution etc ...)
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportClients(Application $app, Request $request)
    {
        $nav = new \module_report_nav(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf_nav = [
            'nav'       => [$app->trans('report:: navigateur'), 0, 1, 0, 0],
            'nb'        => [$app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];

        $conf_combo = [
            'combo'     => [$app->trans('report:: navigateurs et plateforme'), 0, 0, 0, 0],
            'nb'        => [$app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];
        $conf_os = [
            'os'        => [$app->trans('report:: plateforme'), 0, 0, 0, 0],
            'nb'        => [$app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];
        $conf_res = [
            'res'       => [$app->trans('report:: resolution'), 0, 0, 0, 0],
            'nb'        => [$app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];
        $conf_mod = [
            'appli'     => [$app->trans('report:: module'), 0, 0, 0, 0],
            'nb'        => [$app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];

        $report = [
            'nav'   => $nav->buildTabNav($conf_nav),
            'os'    => $nav->buildTabOs($conf_os),
            'res'   => $nav->buildTabRes($conf_res),
            'mod'   => $nav->buildTabModule($conf_mod),
            'combo' => $nav->buildTabCombo($conf_combo)
        ];

         if ($request->request->get('printcsv') == 'on') {
             return $app->json([
                'nav'   => \format::arr_to_csv($report['nav']['result'], $conf_nav),
                'os'    => \format::arr_to_csv($report['os']['result'], $conf_os),
                'res'   => \format::arr_to_csv($report['res']['result'], $conf_res),
                'mod'   => \format::arr_to_csv($report['mod']['result'], $conf_mod),
                'combo' => \format::arr_to_csv($report['combo']['result'], $conf_combo)
            ]);
         }

         return $app->json([
            'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => true,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]),
            'display_nav' => false,
            'title'       => false
        ]);
    }

    /**
     * Set Report configuration according to request parameters
     *
     * @param  Application    $app     An application instance
     * @param  Request        $request A request instance
     * @param  \module_report $report  A report instance
     * @param  Array          $conf    A report column configuration
     * @param  Boolean        $what    Whether to group on a particular field or not
     * @return Array
     */
    private function doReport(Application $app, Request $request, \module_report $report, $conf, $what = false)
    {
        if ($app['conf']->get(['registry', 'modules', 'anonymous-report']) == true) {
            if (isset($conf['user'])) {
                unset($conf['user']);
            }

            if (isset($conf['ip'])) {
                unset($conf['ip']);
            }
        }
        //save initial conf
        $base_conf = $conf;
        //format conf according user preferences
        if ('' !== $columnsList = $request->request->get('list_column', '')) {
            $new_conf = $conf;
            $columns = explode(',', $columnsList);

            foreach (array_keys($conf) as $col) {
                if (!in_array($col, $columns)) {
                    unset($new_conf[$col]);
                }
            }

            $conf = $new_conf;
        }

        //display content of a table column when user click on it
        if ($request->request->get('conf') == 'on') {
            return $app->json(['liste' => $app['twig']->render('report/listColumn.html.twig', [
                'conf'  => $base_conf
            ]), 'title' => $app->trans('configuration')]);
        }

        //set order
        if (('' !== $order = $request->request->get('order', '')) && ('' !== $field = $request->request->get('champ', ''))) {
            $report->setOrder($field, $order);
        }

        //work on filters
        $mapColumnTitleToSqlField = $report->getTransQueryString();

        $currentfilter = [];

        if ('' !== $serializedFilter = $request->request->get('liste_filter', '')) {
            $currentfilter = @unserialize(urldecode($serializedFilter));
        }

        $filter = new \module_report_filter($app, $currentfilter, $mapColumnTitleToSqlField);

        if ('' !== $filterColumn = $request->request->get('filter_column', '')) {
            $field = current(explode(' ', $filterColumn));
            $value = $request->request->get('filter_value', '');

            if ($request->request->get('liste') == 'on') {
                return $app->json(['diag'  => $app['twig']->render('report/colFilter.html.twig', [
                    'result' => $report->colFilter($field),
                    'field'  => $field
                ]), 'title'  => $app->trans('filtrer les resultats sur la colonne %colonne%', ['%colonne%' => $field])]);
            }

            if ($field === $value) {
                $filter->removeFilter($field);
            } else {
                $filter->addFilter($field, '=', $value);
            }
        }

        //set new request filter if user asking for them
        if ($request->request->get('precise') == 1) {
            $filter->addFilter('xml', 'LIKE', $request->request->get('word', ''));
        } elseif ($request->request->get('precise') == 2) {
            $filter->addFilter('record_id', '=', $request->request->get('word', ''));
        }

        //set filters to current report
        $report->setFilter($filter->getTabFilter());
        $report->setActiveColumn($filter->getActiveColumn());
        $report->setPostingFilter($filter->getPostingFilter());

        // display a new arraywhere results are group
        if ('' !== $groupby = $request->request->get('groupby', '')) {
            $report->setConfig(false);
            $groupby = current(explode(' ', $groupby));

            $reportArray = $report->buildReport(false, $groupby);

            if (count($reportArray['allChamps']) > 0 && count($reportArray['display']) > 0) {
                $groupField = isset($reportArray['display'][$reportArray['allChamps'][0]]['title']) ? $reportArray['display'][$reportArray['allChamps'][0]]['title'] : '';
            } else {
                $groupField = isset($conf[strtolower($groupby)]['title']) ? $conf[strtolower($groupby)]['title'] : '';
            }

            return $app->json([
                'rs' => $app['twig']->render('report/ajax_data_content.html.twig', [
                    'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => true,
                    'is_plot'     => false,
                    'is_doc'      => false
                ]),
                'display_nav' => false,
                'title'       => $app->trans('Groupement des resultats sur le champ %name%', ['%name%' => $groupField])
            ]);
        }

        //set Limit
        if ($report->getEnableLimit()
                && ('' !== $page = $request->request->get('page', ''))
                && ('' !== $limit = $request->request->get('limit', ''))) {
            $report->setLimit($page, $limit);
        } else {
            $report->setLimit(false, false);
        }

        //time to build our report
        if (false === $what) {
            $reportArray = $report->buildReport($conf);
        } else {
            $reportArray = $report->buildReport($conf, $what, $request->request->get('tbl', false));
        }

        return $reportArray;
    }
}
