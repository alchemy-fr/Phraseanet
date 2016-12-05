<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Report;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Response\CSVFileResponse;
use Goodby\CSV\Export\Standard\Collection\CallbackCollection;
use Goodby\CSV\Export\Standard\Exporter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RootController extends Controller
{
    public function indexAction()
    {
        return $this->app->redirectPath('report_dashboard');
    }

    /**
     * Display dashboard information
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function getDashboard(Request $request)
    {
        if ('json' === $request->getRequestFormat()) {
            \Session_Logger::updateClientInfos($this->app, 4);

            $dashboard = new \module_report_dashboard($this->app, $this->getAuthenticatedUser());

            $dmin = $request->query->get('dmin');
            $dmax = $request->query->get('dmax');

            if ($dmin && $dmax) {
                $dashboard->setDate($dmin, $dmax);
            }

            $dashboard->execute();

            return $this->app->json(['html' => $this->render('report/ajax_dashboard_content_child.html.twig', [
                'dashboard' => $dashboard
            ])]);
        }

        $granted = [];

        foreach ($this->getAclForUser()->get_granted_base([\ACL::CANREPORT]) as $collection) {
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

        $conf = $this->getConf();
        return $this->render('report/report_layout_child.html.twig', [
            'ajax_dash'     => true,
            'dashboard'     => null,
            'granted_bases' => $granted,
            'home_title'    => $conf->get(['registry', 'general', 'title']),
            'module'        => 'report',
            'module_name'   => 'Report',
            'anonymous'     => $conf->get(['registry', 'modules', 'anonymous-report']),
            'g_anal'        => $conf->get(['registry', 'general', 'analytics']),
            'ajax'          => false,
            'ajax_chart'    => false
        ]);
    }

    /**
     * Gets available collections where current user can see report and
     * format date
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function initReport(Request $request)
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

        return $this->render('report/ajax_report_content.html.twig', [
            'selection' => $selection,
            'anonymous' => $this->getConf()->get(['registry', 'modules', 'anonymous-report']),
            'ajax'      => true,
            'dmin'      => $dmin->format('Y-m-d H:i:s'),
            'dmax'      => $dmax->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Display instance connexion report
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportConnexions(Request $request)
    {
        $cnx = new \module_report_connexion(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf = [
            'user'      => [$this->app->trans('phraseanet::utilisateurs'), 1, 1, 1, 1],
            'ddate'     => [$this->app->trans('report:: date'), 1, 0, 1, 1],
            'ip'        => [$this->app->trans('report:: IP'), 1, 0, 0, 0],
            'appli'     => [$this->app->trans('report:: modules'), 1, 0, 0, 0],
            'fonction'  => [$this->app->trans('report::fonction'), 1, 1, 1, 1],
            'activite'  => [$this->app->trans('report::activite'), 1, 1, 1, 1],
            'pays'      => [$this->app->trans('report::pays'), 1, 1, 1, 1],
            'societe'   => [$this->app->trans('report::societe'), 1, 1, 1, 1]
        ];

        if ($request->request->get('printcsv') == 'on') {
            $cnx->setHasLimit(false);
            $cnx->setPrettyString(false);

            $this->doReport($request, $cnx, $conf);

            return $this->getCSVResponse($cnx, 'connections');
        }

        $report = $this->doReport($request, $cnx, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $this->app->json([
            'rs'          =>  $this->render('report/ajax_data_content.html.twig', [
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
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportQuestions(Request $request)
    {
        $questions = new \module_report_question(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf = [
            'user'      => [$this->app->trans('report:: utilisateur'), 1, 1, 1, 1],
            'search'    => [$this->app->trans('report:: question'), 1, 0, 1, 1],
            'ddate'     => [$this->app->trans('report:: date'), 1, 0, 1, 1],
            'fonction'  => [$this->app->trans('report:: fonction'), 1, 1, 1, 1],
            'activite'  => [$this->app->trans('report:: activite'), 1, 1, 1, 1],
            'pays'      => [$this->app->trans('report:: pays'), 1, 1, 1, 1],
            'societe'   => [$this->app->trans('report:: societe'), 1, 1, 1, 1]
        ];

        if ($request->request->get('printcsv') == 'on') {
            $questions->setHasLimit(false);
            $questions->setPrettyString(false);

            $this->doReport($request, $questions, $conf);

            return $this->getCSVResponse($questions, 'questions');
        }

        $report = $this->doReport($request, $questions, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $this->app->json([
            'rs'          =>  $this->render('report/ajax_data_content.html.twig', [
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
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportDownloads(Request $request)
    {
        $download = new \module_report_download(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf_pref = [];

        foreach (\module_report::getPreff($this->app, $request->request->get('sbasid')) as $field) {
            $conf_pref[strtolower($field)] = [$field, 0, 0, 0, 0];
        }

        $conf = array_merge([
            'user'      => [$this->app->trans('report:: utilisateurs'), 1, 1, 1, 1],
            'ddate'     => [$this->app->trans('report:: date'), 1, 0, 1, 1],
            'record_id' => [$this->app->trans('report:: record id'), 1, 1, 1, 1],
            'final'     => [$this->app->trans('phrseanet:: sous definition'), 1, 0, 1, 1],
            'coll_id'   => [$this->app->trans('report:: collections'), 1, 0, 1, 1],
            'comment'   => [$this->app->trans('report:: commentaire'), 1, 0, 0, 0],
            'fonction'  => [$this->app->trans('report:: fonction'), 1, 1, 1, 1],
            'activite'  => [$this->app->trans('report:: activite'), 1, 1, 1, 1],
            'pays'      => [$this->app->trans('report:: pays'), 1, 1, 1, 1],
            'societe'   => [$this->app->trans('report:: societe'), 1, 1, 1, 1]
        ], $conf_pref);

        if ($request->request->get('printcsv') == 'on') {
            $download->setHasLimit(false);
            $download->setPrettyString(false);

            $this->doReport($request, $download, $conf);

            $r = $this->getCSVResponse($download, 'download');

            return $r;
        }

        $report = $this->doReport($request, $download, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $this->app->json([
            'rs'          =>  $this->render('report/ajax_data_content.html.twig', [
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
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportDocuments(Request $request)
    {
        $document = new \module_report_download(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf_pref = [];

        foreach (\module_report::getPreff($this->app, $request->request->get('sbasid')) as $field) {
            $conf_pref[$field] = array($field, 0, 0, 0, 0);
        }

        $conf = array_merge([
            'telechargement'    => [$this->app->trans('report:: telechargements'), 1, 0, 0, 0],
            'record_id'         => [$this->app->trans('report:: record id'), 1, 1, 1, 0],
            'final'             => [$this->app->trans('phraseanet:: sous definition'), 1, 0, 1, 1],
            'file'              => [$this->app->trans('report:: fichier'), 1, 0, 0, 1],
            'mime'              => [$this->app->trans('report:: type'), 1, 0, 1, 1],
            'size'              => [$this->app->trans('report:: taille'), 1, 0, 1, 1]
        ], $conf_pref);

        if ($request->request->get('printcsv') == 'on') {
            $document->setHasLimit(false);
            $document->setPrettyString(false);

            $this->doReport($request, $document, $conf, 'record_id');

            $r = $this->getCSVResponse($document, 'documents');

            return $r;
        }

        $report = $this->doReport($request, $document, $conf, 'record_id');

        if ($report instanceof Response) {
            return $report;
        }

        return $this->app->json([
            'rs'          =>  $this->render('report/ajax_data_content.html.twig', [
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
     * Display information about client (browser, resolution etc ...)
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportClients(Request $request)
    {
        $nav = new \module_report_nav(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf_nav = [
            'nav'       => [$this->app->trans('report:: navigateur'), 0, 1, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$this->app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];

        $conf_combo = [
            'combo'     => [$this->app->trans('report:: navigateurs et plateforme'), 0, 0, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$this->app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];
        $conf_os = [
            'os'        => [$this->app->trans('report:: plateforme'), 0, 0, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$this->app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];
        $conf_res = [
            'res'       => [$this->app->trans('report:: resolution'), 0, 0, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$this->app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];
        $conf_mod = [
            'appli'     => [$this->app->trans('report:: module'), 0, 0, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0],
            'pourcent'  => [$this->app->trans('report:: pourcentage'), 0, 0, 0, 0]
        ];

        $report = [
            'nav'   => $nav->buildTabNav($conf_nav),
            'os'    => $nav->buildTabOs($conf_os),
            'res'   => $nav->buildTabRes($conf_res),
            'mod'   => $nav->buildTabModule($conf_mod),
            'combo' => $nav->buildTabCombo($conf_combo)
        ];

        if ($request->request->get('printcsv') == 'on') {
            $result = [];

            $result[] = array_keys($conf_nav);
            foreach ($report['nav']['result'] as $row) {
                $result[] =  array_values($row);
            };
            $result[] = array_keys($conf_os);
            foreach ($report['os']['result'] as $row) {
                $result[] =  array_values($row);
            };
            $result[] = array_keys($conf_res);
            foreach ($report['res']['result'] as $row) {
                $result[] =  array_values($row);
            };
            $result[] = array_keys($conf_mod);
            foreach ($report['mod']['result'] as $row) {
                $result[] =  array_values($row);
            };
            $result[] = array_keys($conf_combo);
            foreach ($report['combo']['result'] as $row) {
                $result[] =  array_values($row);
            };

            /** @var Exporter $exporter */
            $exporter = $this->app['csv.exporter'];
            $filename = sprintf('report_export_info_%s.csv', date('Ymd'));
            $response = new CSVFileResponse($filename, function () use ($exporter, $result) {
                $exporter->export('php://output', $result);
            });

            return $response;
        }

        return $this->app->json([
            'rs' =>  $this->render('report/ajax_data_content.html.twig', [
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
     * @param  Request        $request A request instance
     * @param  \module_report $report  A report instance
     * @param  Array          $conf    A report column configuration
     * @param  Boolean        $what    Whether to group on a particular field or not
     * @return Array
     */
    private function doReport(Request $request, \module_report $report, $conf, $what = false)
    {
        if ($this->getConf()->get(['registry', 'modules', 'anonymous-report']) == true) {
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
            return $this->app->json(['liste' => $this->render('report/listColumn.html.twig', [
                'conf'  => $base_conf
            ]), 'title' => $this->app->trans('configuration')]);
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

        $filter = new \module_report_filter($this->app, $currentfilter, $mapColumnTitleToSqlField);

        if ('' !== $filterColumn = $request->request->get('filter_column', '')) {
            $field = current(explode(' ', $filterColumn));
            $value = $request->request->get('filter_value', '');

            if ($request->request->get('liste') == 'on') {
                return $this->app->json(['diag'  => $this->render('report/colFilter.html.twig', [
                    'result' => $report->colFilter($field),
                    'field'  => $field
                ]), 'title'  => $this->app->trans('filtrer les resultats sur la colonne %colonne%', ['%colonne%' => $field])]);
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

            return $this->app->json([
                'rs' => $this->render('report/ajax_data_content.html.twig', [
                    'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => true,
                    'is_plot'     => false,
                    'is_doc'      => false
                ]),
                'display_nav' => false,
                'title'       => $this->app->trans('Groupement des resultats sur le champ %name%', ['%name%' => $groupField])
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

    private function getCSVResponse(\module_report $report, $type)
    {
        // set headers
        $headers = [];
        foreach (array_keys($report->getDisplay()) as $k) {
            $headers[$k] = $k;
        }
        // set headers as first row
        $result = $report->getResult();

        array_unshift($result, $headers);

        $collection = new CallbackCollection($result, function ($row) use ($headers) {
            // restrict fields to the displayed ones
            //    return array_map("strip_tags", array_intersect_key($row, $report->getDisplay()));
            $ret = array();
            foreach($headers as $f) {
                $ret[$f] = array_key_exists($f, $row) ? strip_tags($row[$f]) : '';
            }
            return $ret;
        });

        $filename = sprintf('report_export_%s_%s.csv', $type, date('Ymd'));

        /** @var Exporter $exporter */
        $exporter = $this->app['csv.exporter'];
        $cb = function () use ($exporter, $collection) {
            $exporter->export('php://output', $collection);
        };

        $response = new CSVFileResponse($filename, $cb);

        return $response;
    }
}
