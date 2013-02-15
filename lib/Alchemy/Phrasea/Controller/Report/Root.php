<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Root implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAccessToModule('report');
        });

        $controllers->get('/', function(Application $app) {
            return $app->redirect($app->path('report_dashboard'));
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

        $controllers->post('/activity/users/connexions', $this->call('doReportConnexionsByUsers'))
            ->bind('report_activity_users_connexions');

        $controllers->post('/activity/users/downloads', $this->call('doReportDownloadsByUsers'))
            ->bind('report_activity_users_downloads');;

        $controllers->post('/activity/questions/best-of', $this->call('doReportBestOfQuestions'))
            ->bind('report_activity_questions_bestof');

        $controllers->post('/activity/questions/no-best-of', $this->call('doReportNoBestOfQuestions'))
            ->bind('report_activity_questions_nobestof');

        $controllers->post('/activity/instance/hours', $this->call('doReportSiteActiviyPerHours'))
            ->bind('report_activity_instance_hours');

        $controllers->post('/activity/instance/days', $this->call('doReportSiteActiviyPerDays'))
            ->bind('report_activity_instance_days');

        $controllers->post('/activity/documents/pushed', $this->call('doReportPushedDocuments'))
            ->bind('report_activity_documents_pushed');

        $controllers->post('/activity/documents/added', $this->call('doReportAddedDocuments'))
            ->bind('report_activity_documents_added');

        $controllers->post('/activity/documents/edited', $this->call('doReportEditedDocuments'))
            ->bind('report_activity_documents_edited');

        $controllers->post('/activity/documents/validated', $this->call('doReportValidatedDocuments'))
            ->bind('report_activity_documents_validated');

        $controllers->post('/informations/user', $this->call('doReportInformationsUser'))
            ->bind('report_infomations_user');

        $controllers->post('/informations/browser', $this->call('doReportInformationsBrowser'))
            ->bind('report_infomations_browser');

        $controllers->post('/informations/document', $this->call('doReportInformationsDocument'))
            ->bind('report_infomations_document');

        $controllers->post('/export/csv', $this->call('exportCSV'))
            ->bind('report_export_csv');

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
        $dashboard = new \module_report_dashboard($app, $app['phraseanet.user']);

        if ('json' !== $request->getRequestFormat()) {
            \User_Adapter::updateClientInfos($app, 4);

            $dashboard->execute();

            return $app['twig']->render('report/report_layout_child.html.twig', array(
                'ajax_dash'   => true,
                'dashboard'   => $dashboard,
                'home_title'  => $app['phraseanet.registry']->get('GV_homeTitle'),
                'module'      => "report",
                "module_name" => "Report",
                'anonymous'   => $app['phraseanet.registry']->get('GV_anonymousReport'),
                'g_anal'      => $app['phraseanet.registry']->get('GV_googleAnalytics'),
                'ajax'        => false,
                'ajax_chart'  => false
            ));
        }

        $dmin = $request->request->get('dmin');
        $dmax = $request->request->get('dmax');

        if ($dmin && $dmax) {
            $dashboard->setDate($dmin, $dmax);
        }

        $dashboard->execute();

        return $app->json(array('html' => $app['twig']->render("report/ajax_dashboard_content_child.html.twig", array(
                'dashboard' => $dashboard
        ))));
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

        if ('' !== $dmin = $request->request->get('dmin', '')) {
            $dmin = '01-' . date('m') . '-' . date('Y');
        }

        if ('' !== $dmax = $request->request->get('dmax', '')) {
            $dmax = date("d") . "-" . date("m") . "-" . date("Y");
        }

        $dmin = \DateTime::createFromFormat('d-m-Y H:i:s', sprintf('%s 00:00:00', $dmin));
        $dmax = \DateTime::createFromFormat('d-m-Y H:i:s', sprintf('%s 23:59:59', $dmax));

        //get user's sbas & collections selection from popbases
        $selection = array();
        $liste = $id_sbas = '';
        $i = 0;
        foreach (array_fill_keys($popbases, 0) as $key => $val) {
            $exp = explode("_", $key);
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

            try {
                $csv = \format::arr_to_csv($cnx->getResult(), $cnx->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
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

            try {
                $csv = \format::arr_to_csv($questions->getResult(), $questions->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
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
        $download = new \module_report_download(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $conf_pref = array();

        foreach (\module_report::getPreff($app, $request->request->get('sbasid')) as $field) {
            $conf_pref[strtolower($field)] = array($field, 0, 0, 0, 0);
        }

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

        if ($request->request->get('printcsv') == 'on') {
            $download->setHasLimit(false);
            $download->setPrettyString(false);

            try {
                $csv = \format::arr_to_csv($download->getResult(), $download->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        }

        $report = $this->doReport($app, $request, $download, $conf);

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
            $conf_pref[strtolower($field)] = array($field, 0, 0, 0, 0);
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

            try {
                $csv = \format::arr_to_csv($document->getResult(), $document->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
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
     * Display informations about client (browser, resolution etc ..)
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
             return $app->json(array(
                'nav'   => \format::arr_to_csv($report['nav']['result'], $conf_nav),
                'os'    => \format::arr_to_csv($report['os']['result'], $conf_os),
                'res'   => \format::arr_to_csv($report['res']['result'], $conf_res),
                'mod'   => \format::arr_to_csv($report['mod']['result'], $conf_mod),
                'combo' => \format::arr_to_csv($report['combo']['result'], $conf_combo)
            ));
         }

         return $app->json(array(
            'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
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
     * Display connexions report group by user
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportConnexionsByUsers(Application $app, Request $request)
    {
        $activity = new \module_report_activity(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);
        $activity->setBound("user", true);

        //set Limit
        if ($activity->getEnableLimit()
                && ('' !== $page = $request->request->get('page', ''))
                && ('' !== $limit = $request->request->get('limit', ''))) {
            $activity->setLimit($page, $limit);
        } else {
            $activity->setLimit(false, false);
        }

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->getConnexionBase(false, $request->request->get('on', 'user'));

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        } else {
            $report = $activity->getConnexionBase(false, $request->request->get('on', 'user'));

            return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                    'result'      => isset($report['report']) ? $report['report'] : $report,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => false,
                    'is_plot'     => false,
                    'is_doc'      => false
                )),
                'display_nav' => false,
                'title'       => false
            ));
        }
    }

    /**
     * Display download report group by user
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportDownloadsByUsers(Application $app, Request $request)
    {
        $conf = array(
            'user'      => array(_('report:: utilisateur'), 0, 1, 0, 0),
            'nbdoc'     => array(_('report:: nombre de documents'), 0, 0, 0, 0),
            'poiddoc'   => array(_('report:: poids des documents'), 0, 0, 0, 0),
            'nbprev'    => array(_('report:: nombre de preview'), 0, 0, 0, 0),
            'poidprev'  => array(_('report:: poids des previews'), 0, 0, 0, 0)
        );

        $activity = new \module_report_activity(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        //set Limit
        if ($activity->getEnableLimit()) {
            ('' !== $page = $request->request->get('page', '')) &&  ('' !== $limit = $request->request->get('limit', '')) ?
                    $activity->setLimit($page, $limit) : $activity->setLimit(false, false);
        }

        $report = $activity->getDetailDownload($conf, $request->request->get('on'));

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        } else {
            return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                    'result'      => isset($report['report']) ? $report['report'] : $report,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => false,
                    'is_plot'     => false,
                    'is_doc'      => false
                )),
                'display_nav' => false,
                'title'       => false
            ));
        }
    }

    /**
     * Display the most asked question
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportBestOfQuestions(Application $app, Request $request)
    {
        $conf = array(
            'search'    => array(_('report:: question'), 0, 0, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0),
            'nb_rep'    => array(_('report:: nombre de reponses'), 0, 0, 0, 0)
        );

        $activity = new \module_report_activity(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setLimit(1, $request->request->get('limit', 20));
        $activity->setTop(20);
        $activity->setConfig(false);

       if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $activity->getTopQuestion($conf);

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        } else {
            $report = $activity->getTopQuestion($conf);

            return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                    'result'      => isset($report['report']) ? $report['report'] : $report,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => false,
                    'is_plot'     => false,
                    'is_doc'      => false
                )),
                'display_nav' => false,
                'title'       => false
            ));
        }
    }

    /**
     * Display report about questions that return no result
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportNoBestOfQuestions(Application $app, Request $request)
    {
        $conf = array(
            'search'    => array(_('report:: question'), 0, 0, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0),
            'nb_rep'    => array(_('report:: nombre de reponses'), 0, 0, 0, 0)
        );

        $activity = new \module_report_activity(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        //set Limit
        if ($activity->getEnableLimit()) {
            ('' !== $page = $request->request->get('page', '')) &&  ('' !== $limit = $request->request->get('limit', '')) ?
                    $activity->setLimit($page, $limit) : $activity->setLimit(false, false);
        }

        $activity->setConfig(false);

       if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $activity->getTopQuestion($conf, true);

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        } else {
            $report = $activity->getTopQuestion($conf, true);

            return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                    'result'      => isset($report['report']) ? $report['report'] : $report,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => false,
                    'is_plot'     => false,
                    'is_doc'      => false
                )),
                'display_nav' => false,
                'title'       => false
            ));
        }
    }

    /**
     * Display an overview of connexion among hours of the da
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportSiteActiviyPerHours(Application $app, Request $request)
    {
        $activity = new \module_report_activity(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        $report = $activity->getActivityPerHours();

        if ($request->request->get('printcsv') == 'on') {
             $activity->setHasLimit(false);
             $activity->setPrettyString(false);

             try {
                 $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
             } catch (\Exception $e) {
                 $csv = '';
             }

             return $app->json(array('rs' => $csv));
         } else {
             return $app->json(array(
                 'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                     'result'      => isset($report['report']) ? $report['report'] : $report,
                     'is_infouser' => false,
                     'is_nav'      => false,
                     'is_groupby'  => false,
                     'is_plot'     => true,
                     'is_doc'      => false
                 )),
                 'display_nav' => false,
                 'title'       => false
             ));
         }
    }

    /**
     * Display an overview of downloaded document grouped by day
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportSiteActiviyPerDays(Application $app, Request $request)
    {
        $conf = array(
            'ddate'     => array(_('report:: jour'), 0, 0, 0, 0),
            'total'     => array(_('report:: total des telechargements'), 0, 0, 0, 0),
            'preview'   => array(_('report:: preview'), 0, 0, 0, 0),
            'document'  => array(_('report:: document original'), 0, 0, 0, 0)
        );

        $activity = new \module_report_activity(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        //set Limit
        if ($activity->getEnableLimit()) {
            ('' !== $page = $request->request->get('page', '')) &&  ('' !== $limit = $request->request->get('limit', '')) ?
                    $activity->setLimit($page, $limit) : $activity->setLimit(false, false);
        }

        $activity->setConfig(false);

        $report = $activity->getDownloadByBaseByDay($conf);

        if ($request->request->get('printcsv') == 'on') {
             $activity->setHasLimit(false);
             $activity->setPrettyString(false);

             try {
                 $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
             } catch (\Exception $e) {
                 $csv = '';
             }

             return $app->json(array('rs' => $csv));
         } else {
             return $app->json(array(
                 'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                     'result'      => isset($report['report']) ? $report['report'] : $report,
                     'is_infouser' => false,
                     'is_nav'      => false,
                     'is_groupby'  => false,
                     'is_plot'     => false,
                     'is_doc'      => false
                 )),
                 'display_nav' => false,
                 'title'       => false
             ));
         }
    }

    /**
     * Display report about pushed documents
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportPushedDocuments(Application $app, Request $request)
    {
        $conf = array(
            'user'      => array("", 1, 0, 1, 1),
            'getter'    => array("Destinataire", 1, 0, 1, 1),
            'date'      => array("", 1, 0, 1, 1),
            'record_id' => array("", 1, 1, 1, 1),
            'file'      => array("", 1, 0, 1, 1),
            'mime'      => array("", 1, 0, 1, 1),
        );

        $activity = new \module_report_push(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        }

        $report = $this->doReport($app, $request, $activity, $conf);

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
     * Display report about added documents
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportAddedDocuments(Application $app, Request $request)
    {
        $conf = array(
            'user'      => array("", 1, 0, 1, 1),
            'date'      => array("", 1, 0, 1, 1),
            'record_id' => array("", 1, 1, 1, 1),
            'file'      => array("", 1, 0, 1, 1),
            'mime'      => array("", 1, 0, 1, 1),
        );

        $activity = new \module_report_add(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        }

        $report = $this->doReport($app, $request, $activity, $conf);

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
     * Display report about edited documents
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportEditedDocuments(Application $app, Request $request)
    {
        $conf = array(
            'user'      => array("", 1, 0, 1, 1),
            'date'      => array("", 1, 0, 1, 1),
            'record_id' => array("", 1, 1, 1, 1),
            'file'      => array("", 1, 0, 1, 1),
            'mime'      => array("", 1, 0, 1, 1),
        );

        $activity = new \module_report_edit(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        }

        $report = $this->doReport($app, $request, $activity, $conf);

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
     * Display report about validated documents
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportValidatedDocuments(Application $app, Request $request)
    {
        $conf = array(
            'user'      => array("", 1, 0, 1, 1),
            'getter'    => array("Destinataire", 1, 0, 1, 1),
            'date'      => array("", 1, 0, 1, 1),
            'record_id' => array("", 1, 1, 1, 1),
            'file'      => array("", 1, 0, 1, 1),
            'mime'      => array("", 1, 0, 1, 1),
        );

        $activity = new \module_report_validate(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            try {
                $csv = \format::arr_to_csv($activity->getResult(), $activity->getDisplay());
            } catch (\Exception $e) {
                $csv = '';
            }

            return $app->json(array('rs' => $csv));
        }

        $report = $this->doReport($app, $request, $activity, $conf);

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
     * Display informations about a user
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportInformationsUser(Application $app, Request $request)
    {
        $conf = array(
            'config'    => array(
                'photo'     => array(_('report:: document'), 0, 0, 0, 0),
                'record_id' => array(_('report:: record id'), 0, 0, 0, 0),
                'date'      => array(_('report:: date'), 0, 0, 0, 0),
                'type'      => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
                'titre'     => array(_('report:: titre'), 0, 0, 0, 0),
                'taille'    => array(_('report:: poids'), 0, 0, 0, 0)
            ),
            'conf'  => array(
                'identifiant'   => array(_('report:: identifiant'), 0, 0, 0, 0),
                'nom'           => array(_('report:: nom'), 0, 0, 0, 0),
                'mail'          => array(_('report:: email'), 0, 0, 0, 0),
                'adresse'       => array(_('report:: adresse'), 0, 0, 0, 0),
                'tel'           => array(_('report:: telephone'), 0, 0, 0, 0)
            ),
            'config_cnx'    => array(
                'ddate'     => array(_('report:: date'), 0, 0, 0, 0),
                'appli'     => array(_('report:: modules'), 0, 0, 0, 0),
            ),
            'config_dl' => array(
                'ddate'     => array(_('report:: date'), 0, 0, 0, 0),
                'record_id' => array(_('report:: record id'), 0, 1, 0, 0),
                'final'     => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
                'coll_id'   => array(_('report:: collections'), 0, 0, 0, 0),
                'comment'   => array(_('report:: commentaire'), 0, 0, 0, 0),
            ),
            'config_ask' => array(
                'search'    => array(_('report:: question'), 0, 0, 0, 0),
                'ddate'     => array(_('report:: date'), 0, 0, 0, 0)
            )
        );

        $report = null;
        $html = $html_info = '';
        $from = $request->request->get('from', '');
        $on = $request->request->get('on', '');
        $selectValue = $request->request->get('user', '');

        if ('' === $selectValue) {
            $app->abort(400);
        }

        if ('' !== $on && $app['phraseanet.registry']->get('GV_anonymousReport') == true) {
            $conf['conf'] = array(
                 $on   => array($on, 0, 0, 0, 0),
                'nb'   => array(_('report:: nombre'), 0, 0, 0, 0)
            );
        }

        if ($from == 'CNXU' || $from == 'CNX') {
            $report = new \module_report_connexion(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection'
            ));
            $conf_array = $conf['config_cnx'];
            $title = _("report:: historique des connexions");
        } elseif ($from == "USR" || $from == "GEN") {
            $report = new \module_report_download(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_dl'];
            $title = _("report:: historique des telechargements");
        } elseif ($from == "ASK") {
            $report = new \module_report_question(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_ask'];
            $title = _("report:: historique des questions");
        }

        if ($report) {
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
                    )), "title"  => sprintf(_('filtrer les resultats sur la colonne %s'), $field)));
                }

                if ($field === $value) {
                    $filter->removeFilter($field);
                } else {
                    $filter->addFilter($field, '=', $value);
                }
            }

            if ('' !== $selectValue && '' !== $from) {
                $filter->addfilter('usrid', '=', $selectValue);
            } elseif ('' !== $on && '' !== $selectValue) {
                $filter->addfilter($on, '=', $selectValue);
            }

            if ($report instanceof \module_report_download) {
                $report->setIsInformative(true);
            }

            $report->setFilter($filter->getTabFilter());
            $report->setOrder('ddate', 'DESC');
            $report->setConfig(false);
            $report->setTitle($title);
            $report->setHasLimit(false);

            $reportArray = $report->buildReport($conf_array);

            if ($request->request->get('printcsv') == 'on') {
                $report->setPrettyString(false);

                try {
                    $csv = \format::arr_to_csv($report->getResult(), $report->getDisplay());
                } catch (\Exception $e) {
                    $csv = '';
                }

                return $app->json(array('rs' => $csv));
            }

            $html = $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => $report instanceof \module_report_download,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ));
        }

        $info = new \module_report_nav(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $info->setPeriode('');
        $info->setCsv(false);

        $infoArray = $info->buildTabGrpInfo(
            null !== $report ? $report->getReq() : '',
            null !== $report ? $report->getParams() : array(),
            $selectValue,
            $conf['conf'],
            $on
        );

        if (false == $app['phraseanet.registry']->get('GV_anonymousReport')) {
            $html_info = $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($infoArray['report']) ? $infoArray['report'] : $infoArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ));

            $title = ('' === $on && isset($infoArray['result'])) ? $infoArray['result'][0]['identifiant'] : $selectValue;
        } else {
            $title = $selectValue;
        }

        return $app->json(array(
            'rs'          => sprintf('%s%s', $html_info, $html),
            'display_nav' => false,
            'title'       => $title
        ));
    }

    /**
     * Display a browser version
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportinformationsBrowser(Application $app, Request $request)
    {
        $conf = array(
            'version'   => array(_('report::version '), 0, 0, 0, 0),
            'nb'        => array(_('report:: nombre'), 0, 0, 0, 0)
        );

        $info = new \module_report_nav(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $info->setCsv(false);
        $info->setConfig(false);

        if ('' === $browser = $request->request->get('user', '')) {
            $app->abort(400);
        }

        $reportArray = $info->buildTabInfoNav($conf, $browser);

        return $app->json(array(
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
                    'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => false,
                    'is_plot'     => false,
                    'is_doc'      => false
                )),
                'display_nav' => false,
                'title'       => $browser
            ));
    }

    /**
     * Display informations about a document
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportInformationsDocument(Application $app, Request $request)
    {
        $config = array(
            'photo'     => array(_('report:: document'), 0, 0, 0, 0),
            'record_id' => array(_('report:: record id'), 0, 0, 0, 0),
            'date'      => array(_('report:: date'), 0, 0, 0, 0),
            'type'      => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
            'titre'     => array(_('report:: titre'), 0, 0, 0, 0),
            'taille'    => array(_('report:: poids'), 0, 0, 0, 0)
        );

        $config_dl = array(
            'ddate'     => array(_('report:: date'), 0, 0, 0, 0),
            'user'      => array(_('report:: utilisateurs'), 0, 0, 0, 0),
            'final'     => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
            'coll_id'   => array(_('report:: collections'), 0, 0, 0, 0),
            'comment'   => array(_('report:: commentaire'), 0, 0, 0, 0),
            'fonction'  => array(_('report:: fonction'), 0, 0, 0, 0),
            'activite'  => array(_('report:: activite'), 0, 0, 0, 0),
            'pays'      => array(_('report:: pays'), 0, 0, 0, 0),
            'societe'   => array(_('report:: societe'), 0, 0, 0, 0)
        );

        //format conf according user preferences
        if ('' !== $columnsList = $request->request->get('list_column', '')) {
            $new_conf = $config_dl;
            $columns = explode(",", $columnsList);

            foreach (array_keys($config_dl) as $col) {
                if (!in_array($col, $columns)) {
                    unset($new_conf[$col]);
                }
            }

            $config_dl = $new_conf;
        }

        try {
            $record = new \record_adapter(
                $app,
                $request->request->get('sbasid'),
                $request->request->get('rid')
            );
        } catch (\Exception $e) {
            $app->abort(404);
        }

        $what = new \module_report_nav(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $what->setPeriode('');
        $what->setCsv(false);
        $what->setPrint(false);

        $reportArray = $what->buildTabUserWhat(
            $record->get_base_id(),
            $record->get_record_id(),
            $config
        );

        $title = $what->getTitle();

        $html = $app['twig']->render('report/ajax_data_content.html.twig', array(
            'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
            'is_infouser' => false,
            'is_nav'      => false,
            'is_groupby'  => false,
            'is_plot'     => false,
            'is_doc'      => false
        ));

        $from = $request->request->get('from', '');

        if ('TOOL' === $from) {
            $what->setTitle('');

            return $app->json(array(
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ));
        }

        if ('DASH' === $from) {
            $download = new \module_report_download(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );

            $mapColumnTitleToSqlField = $download->getTransQueryString();

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
                    )), "title"  => sprintf(_('filtrer les resultats sur la colonne %s'), $field)));
                }

                if ($field === $value) {
                    $filter->removeFilter($field);
                } else {
                    $filter->addFilter($field, '=', $value);
                }
            }

            $filter->addfilter('record_id', '=', $record->get_record_id());

            $download->setFilter($filter->getTabFilter());
            $download->setOrder('ddate', 'DESC');
            $download->setTitle(_("report:: historique des telechargements"));
            $download->setConfig(false);

            $reportArray = $download->buildReport($config_dl);

            if ($request->request->get('printcsv') == 'on') {
                $download->setPrettyString(false);

                try {
                    $csv = \format::arr_to_csv($download->getResult(), $download->getDisplay());
                } catch (\Exception $e) {
                    $csv = '';
                }

                return $app->json(array('rs' => $csv));
            }

            $html = $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ));

            return $app->json(array(
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ));
        }

        if ($app['phraseanet.registry']->get('GV_anonymousReport') == false && $from !== 'DOC' && $from !== 'DASH' && $from !== "GEN" && $from !== "PUSHDOC") {
            $conf = array(
                'identifiant'   => array(_('report:: identifiant'), 0, 0, 0, 0),
                'nom'           => array(_('report:: nom'), 0, 0, 0, 0),
                'mail'          => array(_('report:: email'), 0, 0, 0, 0),
                'adresse'       => array(_('report:: adresse'), 0, 0, 0, 0),
                'tel'           => array(_('report:: telephone'), 0, 0, 0, 0)
            );

            $info = new \module_report_nav(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );

            $info->setPeriode("");
            $info->setConfig(false);
            $info->setTitle(_('report:: utilisateur'));

            $reportArray = $info->buildTabGrpInfo(false, array(),  $request->request->get('user'), $conf, false);

            if ($request->request->get('printcsv') == 'on') {
                $download->setPrettyString(false);

                try {
                    $csv = \format::arr_to_csv($download->getResult(), $download->getDisplay());
                } catch (\Exception $e) {
                    $csv = '';
                }

                return $app->json(array('rs' => $csv));
            }

            $html = $app['twig']->render('report/ajax_data_content.html.twig', array(
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ));

            return $app->json(array(
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ));
        }

        return $app->json(array(
            'rs'          => $html,
            'display_nav' => false,
            'title'       => $title
        ));
    }

    /**
     * Export data to a csv file
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function exportCSV(Application $app, Request $request)
    {
        $name = $request->request->get('name', 'export');

        if (null === $data = $request->request->get('csv')) {
            $app->abort(400);
        }

        $filename = mb_strtolower('report_' . $name . '_' . date('dmY') . '.csv');
        $data = preg_replace('/[ \t\r\f]+/', '', $data);

        $response = new Response($data, 200, array(
            'Expires'           => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified'     => gmdate("D, d M Y H:i:s"). ' GMT',
            'Cache-Control'     => 'no-store, no-cache, must-revalidate',
            'Cache-Control'     => 'post-check=0, pre-check=0',
            'Pragma'            => 'no-cache',
            'Content-Type'      => 'text/csv',
            'Content-Length'    => strlen($data),
            'Cache-Control'     => 'max-age=3600, must-revalidate',
        ));

        $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
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
            $columns = explode(",", $columnsList);

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
            )), "title" => _("configuration")));
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
                )), "title"  => sprintf(_('filtrer les resultats sur la colonne %s'), $field)));
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

            return $app->json(array(
                'rs'          => $app['twig']->render('report/ajax_data_content.html.twig', array(
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

        //set Limit
        if ($report->getEnableLimit()) {
            ('' !== $page = $request->request->get('page', '')) &&  ('' !== $limit = $request->request->get('limit', '')) ?
                    $report->setLimit($page, $limit) : $report->setLimit(false, false);
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
}
