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

use Alchemy\Phrasea\Core\Response\CSVFileResponse;
use Goodby\CSV\Export\Standard\Collection\CallbackCollection;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Root implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function () use ($app) {
            $app['firewall']->requireAccessToModule('report');
        });

        $controllers->get('/', function (Application $app) {
            return $app->redirectPath('report_dashboard');
        })->bind('report');

        $controllers->get('/dashboard', $this->call('getDashboard'))
            ->bind('report_dashboard');

        $controllers->post('/init', $this->call('initReport'))
            ->bind('report_init');

        $controllers->post('/connexions', $this->call('doReportConnexions'))
            ->bind('report_connexions');

        $controllers->post('/questions', $this->call('doReportQuestions'))
            ->bind('report_questions');

        $controllers->post('/downloads', $this->call('doReportDownloads'))
            ->bind('report_downloads');

        $controllers->post('/documents', $this->call('doReportDocuments'))
            ->bind('report_documents');

        $controllers->post('/clients', $this->call('doReportClients'))
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
            \User_Adapter::updateClientInfos($app, 4);

            $dashboard = new \module_report_dashboard($app, $app['authentication']->getUser());

            $dmin = $request->query->get('dmin');
            $dmax = $request->query->get('dmax');

            if ($dmin && $dmax) {
                $dashboard->setDate($dmin, $dmax);
            }

            $dashboard->execute();

            return $app->json(array('html' => $app['twig']->render('report/ajax_dashboard_content_child.html.twig', array(
                'dashboard' => $dashboard
            ))));
        }

        $granted = array();
        foreach ($app['authentication']->getUser()->acl()->get_granted_base(array('canreport')) as $collection) {
            if (!isset($granted[$collection->get_sbas_id()])) {
                $granted[$collection->get_sbas_id()] = array(
                    'id' => $collection->get_sbas_id(),
                    'name' => $collection->get_databox()->get_viewname(),
                    'collections' => array()
                );
            }
            $granted[$collection->get_sbas_id()]['collections'][] = array(
                'id' => $collection->get_coll_id(),
                'base_id' => $collection->get_base_id(),
                'name' => $collection->get_name()
            );
        }

        return $app['twig']->render('report/report_layout_child.html.twig', array(
            'ajax_dash'     => true,
            'dashboard'     => null,
            'granted_bases' => $granted,
            'home_title'    => $app['phraseanet.registry']->get('GV_homeTitle'),
            'module'        => 'report',
            'module_name'   => 'Report',
            'anonymous'     => $app['phraseanet.registry']->get('GV_anonymousReport'),
            'g_anal'        => $app['phraseanet.registry']->get('GV_googleAnalytics'),
            'ajax'          => false,
            'ajax_chart'    => false
        ));
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
        $popbases = $request->request->get('popbases', array());

        if ('' === $dmin = $request->request->get('dmin', '')) {
            $dmin = date('Y') . '-' . date('m') . '-01';
        }

        if ('' === $dmax = $request->request->get('dmax', '')) {
            $dmax = date('Y') . '-' . date('m') . '-' . date('d');
        }

        $dmin = \DateTime::createFromFormat('Y-m-d H:i:s', sprintf('%s 00:00:00', $dmin));
        $dmax = \DateTime::createFromFormat('Y-m-d H:i:s', sprintf('%s 23:59:59', $dmax));

        //get user's sbas & collections selection from popbases
        $selection = array();
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

        return $app['twig']->render('report/ajax_report_content.html.twig', array(
            'selection' => $selection,
            'anonymous' => $app['phraseanet.registry']->get('GV_anonymousReport'),
            'ajax'      => true,
            'dmin'      => $dmin->format('Y-m-d H:i:s'),
            'dmax'      => $dmax->format('Y-m-d H:i:s'),
        ));
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

        $conf = array(
            'user'      => array(_('phraseanet::utilisateurs'), 1, 1, 1, 1),
            'ddate'     => array(_('report:: date'), 1, 0, 1, 1),
            'ip'        => array(_('report:: IP'), 1, 0, 0, 0),
            'appli'     => array(_('report:: modules'), 1, 0, 0, 0),
            'fonction'  => array(_('report::fonction'), 1, 1, 1, 1),
            'activite'  => array(_('report::activite'), 1, 1, 1, 1),
            'pays'      => array(_('report::pays'), 1, 1, 1, 1),
            'societe'   => array(_('report::societe'), 1, 1, 1, 1)
        );

        if ($request->request->get('printcsv') == 'on') {
            $cnx->setHasLimit(false);
            $cnx->setPrettyString(false);

            $this->doReport($app, $request, $cnx, $conf);

            return $this->getCSVResponse($app, $cnx, 'connections');
        }

        $report = $this->doReport($app, $request, $cnx, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            )),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ));
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

        $conf = array(
            'user'      => array(_('report:: utilisateur'), 1, 1, 1, 1),
            'search'    => array(_('report:: question'), 1, 0, 1, 1),
            'ddate'     => array(_('report:: date'), 1, 0, 1, 1),
            'fonction'  => array(_('report:: fonction'), 1, 1, 1, 1),
            'activite'  => array(_('report:: activite'), 1, 1, 1, 1),
            'pays'      => array(_('report:: pays'), 1, 1, 1, 1),
            'societe'   => array(_('report:: societe'), 1, 1, 1, 1)
        );

        if ($request->request->get('printcsv') == 'on') {
            $questions->setHasLimit(false);
            $questions->setPrettyString(false);

            $this->doReport($app, $request, $questions, $conf);

            return $this->getCSVResponse($app, $questions, 'questions');
        }

        $report = $this->doReport($app, $request, $questions, $conf);

        if ($report instanceof Response) {
            return $report;
        }

        return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            )),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ));
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

        // no_// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);


        $download = new \module_report_download(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        $conf_pref = array();

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        foreach (\module_report::getPreff($app, $request->request->get('sbasid')) as $field) {
//            $conf_pref[strtolower($field)] = array($field, 0, 0, 0, 0);
            $conf_pref[$field] = array($field, 0, 0, 0, 0);
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        $conf = array_merge(array(
            'user'      => array(_('report:: utilisateurs'), 1, 1, 1, 1),
            'ddate'     => array(_('report:: date'), 1, 0, 1, 1),
            'record_id' => array(_('report:: record id'), 1, 1, 1, 1),
            'final'     => array(_('phrseanet:: sous definition'), 1, 0, 1, 1),
            'coll_id'   => array(_('report:: collections'), 1, 0, 1, 1),
            'comment'   => array(_('report:: commentaire'), 1, 0, 0, 0),
            'fonction'  => array(_('report:: fonction'), 1, 1, 1, 1),
            'activite'  => array(_('report:: activite'), 1, 1, 1, 1),
            'pays'      => array(_('report:: pays'), 1, 1, 1, 1),
            'societe'   => array(_('report:: societe'), 1, 1, 1, 1)
        ), $conf_pref);

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        if ($request->request->get('printcsv') == 'on') {
            $download->setHasLimit(false);
            $download->setPrettyString(false);

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

            $this->doReport($app, $request, $download, $conf);

            $r = $this->getCSVResponse($app, $download, 'download');
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s) %s\n\n", __FILE__, __LINE__, var_export($r, true)), FILE_APPEND);

            return $r;
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        $report = $this->doReport($app, $request, $download, $conf);

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        if ($report instanceof Response) {
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

            return $report;
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            )),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ));
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

        $conf_pref = array();

        foreach (\module_report::getPreff($app, $request->request->get('sbasid')) as $field) {
//            $conf_pref[strtolower($field)] = array($field, 0, 0, 0, 0);
            $conf_pref[$field] = array($field, 0, 0, 0, 0);
        }

        $conf = array_merge(array(
            'telechargement'    => array(_('report:: telechargements'), 1, 0, 0, 0),
            'record_id'         => array(_('report:: record id'), 1, 1, 1, 0),
            'final'             => array(_('phraseanet:: sous definition'), 1, 0, 1, 1),
            'file'              => array(_('report:: fichier'), 1, 0, 0, 1),
            'mime'              => array(_('report:: type'), 1, 0, 1, 1),
            'size'              => array(_('report:: taille'), 1, 0, 1, 1)
        ), $conf_pref);

        if ($request->request->get('printcsv') == 'on') {
            $document->setHasLimit(false);
            $document->setPrettyString(false);

            $this->doReport($app, $request, $document, $conf, 'record_id');

            $r = $this->getCSVResponse($app, $document, 'documents');

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s) %s\n\n", __FILE__, __LINE__, var_export($r, true)), FILE_APPEND);

            return $r;
        }

        $report = $this->doReport($app, $request, $document, $conf, 'record_id');

        if ($report instanceof Response) {
            return $report;
        }

        return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => true
            )),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        ));
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

        $conf_nav = array(
            'nav'       => array(_('report:: navigateur'), 0, 1, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0),
            'pourcent'  => array(_('report:: pourcentage'), 0, 0, 0, 0)
        );

        $conf_combo = array(
            'combo'     => array(_('report:: navigateurs et plateforme'), 0, 0, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0),
            'pourcent'  => array(_('report:: pourcentage'), 0, 0, 0, 0)
        );
        $conf_os = array(
            'os'        => array(_('report:: plateforme'), 0, 0, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0),
            'pourcent'  => array(_('report:: pourcentage'), 0, 0, 0, 0)
        );
        $conf_res = array(
            'res'       => array(_('report:: resolution'), 0, 0, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0),
            'pourcent'  => array(_('report:: pourcentage'), 0, 0, 0, 0)
        );
        $conf_mod = array(
            'appli'     => array(_('report:: module'), 0, 0, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0),
            'pourcent'  => array(_('report:: pourcentage'), 0, 0, 0, 0)
        );

        $report = array(
            'nav'   => $nav->buildTabNav($conf_nav),
            'os'    => $nav->buildTabOs($conf_os),
            'res'   => $nav->buildTabRes($conf_res),
            'mod'   => $nav->buildTabModule($conf_mod),
            'combo' => $nav->buildTabCombo($conf_combo)
        );

        if ($request->request->get('printcsv') == 'on') {
            $result = array();

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

            $filename = sprintf('report_export_info_%s.csv', date('Ymd'));
            $response = new CSVFileResponse($filename, function () use ($app, $result) {
                $app['csv.exporter']->export('php://output', $result);
            });
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

            return $response;
        }

        return $app->json(array(
            'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => true,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            )),
            'display_nav' => false,
            'title'       => false
        ));
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
        if ($app['phraseanet.registry']->get('GV_anonymousReport') == true) {
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
            return $app->json(array('liste' => $app['twig']->render('report/listColumn.html.twig', array(
                'conf'  => $base_conf
            )), 'title' => _('configuration')));
        }

        //set order
        if (('' !== $order = $request->request->get('order', '')) && ('' !== $field = $request->request->get('champ', ''))) {
            $report->setOrder($field, $order);
        }

        //work on filters
        $mapColumnTitleToSqlField = $report->getTransQueryString();

        $currentfilter = array();

        if ('' !== $serializedFilter = $request->request->get('liste_filter', '')) {
            $currentfilter = @unserialize(urldecode($serializedFilter));
        }

        $filter = new \module_report_filter($app, $currentfilter, $mapColumnTitleToSqlField);

        if ('' !== $filterColumn = $request->request->get('filter_column', '')) {
            $field = current(explode(' ', $filterColumn));
            $value = $request->request->get('filter_value', '');

            if ($request->request->get('liste') == 'on') {
                return $app->json(array('diag'  => $app['twig']->render('report/colFilter.html.twig', array(
                    'result' => $report->colFilter($field),
                    'field'  => $field
                )), 'title'  => sprintf(_('filtrer les resultats sur la colonne %s'), $field)));
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

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        //set filters to current report
        $report->setFilter($filter->getTabFilter());
        $report->setActiveColumn($filter->getActiveColumn());
        $report->setPostingFilter($filter->getPostingFilter());

        // display a new arraywhere results are group
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        if ('' !== $groupby = $request->request->get('groupby', '')) {

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

            $report->setConfig(false);
            $groupby = current(explode(' ', $groupby));

            $reportArray = $report->buildReport(false, $groupby);

            if (count($reportArray['allChamps']) > 0 && count($reportArray['display']) > 0) {
                $groupField = isset($reportArray['display'][$reportArray['allChamps'][0]]['title']) ? $reportArray['display'][$reportArray['allChamps'][0]]['title'] : '';
            } else {
                $groupField = isset($conf[strtolower($groupby)]['title']) ? $conf[strtolower($groupby)]['title'] : '';
            }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

            return $app->json(array(
                'rs' => $app['twig']->render('report/ajax_data_content.html.twig', array(
                    'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => true,
                    'is_plot'     => false,
                    'is_doc'      => false
                )),
                'display_nav' => false,
                'title'       => _(sprintf('Groupement des resultats sur le champ %s',  $groupField))
            ));
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        //set Limit
        if ($report->getEnableLimit()
                && ('' !== $page = $request->request->get('page', ''))
                && ('' !== $limit = $request->request->get('limit', ''))) {
            $report->setLimit($page, $limit);
        } else {
            $report->setLimit(false, false);
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

        //time to build our report
        if (false === $what) {
            $reportArray = $report->buildReport($conf);
        } else {
            $reportArray = $report->buildReport($conf, $what, $request->request->get('tbl', false));
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n\n", __FILE__, __LINE__), FILE_APPEND);

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

    private function getCSVResponse(Application $app, \module_report $report, $type)
    {
        // set headers
        $headers = array();
        foreach (array_keys($report->getDisplay()) as $k) {
            $headers[$k] = $k;
        }
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s) %s \n\n", __FILE__, __LINE__, var_export($headers, true)), FILE_APPEND);

        // set headers as first row
        $result = $report->getResult();

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s) %s \n\n", __FILE__, __LINE__, var_export($result[0], true)), FILE_APPEND);

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

        $cb = function () use ($app, $collection) {
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s) %s\n\n", __FILE__, __LINE__, var_export($collection, true)), FILE_APPEND);
            $app['csv.exporter']->export('php://output', $collection);
        };

        $response = new CSVFileResponse($filename, $cb);

        return $response;
    }
}
