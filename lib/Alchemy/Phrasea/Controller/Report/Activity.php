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

class Activity implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function () use ($app) {
            $app['firewall']->requireAccessToModule('report');
        });

        $controllers->post('/users/connexions', $this->call('doReportConnexionsByUsers'))
            ->bind('report_activity_users_connexions');

        $controllers->post('/users/downloads', $this->call('doReportDownloadsByUsers'))
            ->bind('report_activity_users_downloads');;

        $controllers->post('/questions/best-of', $this->call('doReportBestOfQuestions'))
            ->bind('report_activity_questions_bestof');

        $controllers->post('/questions/no-best-of', $this->call('doReportNoBestOfQuestions'))
            ->bind('report_activity_questions_nobestof');

        $controllers->post('/instance/hours', $this->call('doReportSiteActiviyPerHours'))
            ->bind('report_activity_instance_hours');

        $controllers->post('/instance/days', $this->call('doReportSiteActiviyPerDays'))
            ->bind('report_activity_instance_days');

        $controllers->post('/documents/pushed', $this->call('doReportPushedDocuments'))
            ->bind('report_activity_documents_pushed');

        $controllers->post('/documents/added', $this->call('doReportAddedDocuments'))
            ->bind('report_activity_documents_added');

        $controllers->post('/documents/edited', $this->call('doReportEditedDocuments'))
            ->bind('report_activity_documents_edited');

        $controllers->post('/documents/validated', $this->call('doReportValidatedDocuments'))
            ->bind('report_activity_documents_validated');

        $controllers->post('/documents/sent', $this->call('doReportSentDocuments'))
            ->bind('report_activity_documents_sent');

        return $controllers;
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

            return $this->getCSVResponse($app, $activity, 'activity_connection_base');
        }

        $report = $activity->getConnexionBase(false, $request->request->get('on', 'user'));

        return $app->json(array(
            'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
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
        if ($activity->getEnableLimit()
                && ('' !== $page = $request->request->get('page', ''))
                && ('' !== $limit = $request->request->get('limit', ''))) {
            $activity->setLimit($page, $limit);
        } else {
            $activity->setLimit(false, false);
        }

        $report = $activity->getDetailDownload($conf, $request->request->get('on'));

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);

            return $this->getCSVResponse($app, $activity, 'activity_detail_download');
        }

        return $app->json(array(
            'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
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

            return $this->getCSVResponse($app, $activity, 'activity_questions_best_of');
        }

        $report = $activity->getTopQuestion($conf);

        return $app->json(array(
            'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
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
        if ($activity->getEnableLimit()
                && ('' !== $page = $request->request->get('page', ''))
                && ('' !== $limit = $request->request->get('limit', ''))) {
            $activity->setLimit($page, $limit);
        } else {
            $activity->setLimit(false, false);
        }

        $activity->setConfig(false);

       if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $activity->getTopQuestion($conf, true);

            return $this->getCSVResponse($app, $activity, 'activity_top_ten_questions');
        }

        $report = $activity->getTopQuestion($conf, true);

        return $app->json(array(
            'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
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

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $activity->getActivityPerHours();

            return $this->getCSVResponse($app, $activity, 'activity_per_hours');
         }

         $report = $activity->getActivityPerHours();

         return $app->json(array(
             'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
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
            'document'  => array(_('report:: document'), 0, 0, 0, 0)
        );

        $activity = new \module_report_activity(
            $app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        //set Limit
        if ($activity->getEnableLimit()
                && ('' !== $page = $request->request->get('page', ''))
                && ('' !== $limit = $request->request->get('limit', ''))) {
            $activity->setLimit($page, $limit);
        } else {
            $activity->setLimit(false, false);
        }

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $activity->getDownloadByBaseByDay($conf);

            return $this->getCSVResponse($app, $activity, 'activity_db_by_base_by_day');
         }

         $report = $activity->getDownloadByBaseByDay($conf);

         return $app->json(array(
             'rs' =>  $app['twig']->render('report/ajax_data_content.html.twig', array(
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
            'user'      => array('', 1, 0, 1, 1),
            'getter'    => array("Destinataire", 1, 0, 1, 1),
            'date'      => array('', 1, 0, 1, 1),
            'record_id' => array('', 1, 1, 1, 1),
            'file'      => array('', 1, 0, 1, 1),
            'mime'      => array('', 1, 0, 1, 1),
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

            $this->doReport($app, $request, $activity, $conf);

            return $this->getCSVResponse($app, $activity, 'activity_pushed_documents');
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
            'user'      => array('', 1, 0, 1, 1),
            'date'      => array('', 1, 0, 1, 1),
            'record_id' => array('', 1, 1, 1, 1),
            'file'      => array('', 1, 0, 1, 1),
            'mime'      => array('', 1, 0, 1, 1),
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

            $this->doReport($app, $request, $activity, $conf);

            return $this->getCSVResponse($app, $activity, 'activity_added_documents');
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
            'user'      => array('', 1, 0, 1, 1),
            'date'      => array('', 1, 0, 1, 1),
            'record_id' => array('', 1, 1, 1, 1),
            'file'      => array('', 1, 0, 1, 1),
            'mime'      => array('', 1, 0, 1, 1),
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

            $this->doReport($app, $request, $activity, $conf);

            return $this->getCSVResponse($app, $activity, 'activity_edited_documents');
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
            'user'      => array('', 1, 0, 1, 1),
            'getter'    => array("Destinataire", 1, 0, 1, 1),
            'date'      => array('', 1, 0, 1, 1),
            'record_id' => array('', 1, 1, 1, 1),
            'file'      => array('', 1, 0, 1, 1),
            'mime'      => array('', 1, 0, 1, 1),
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

            $this->doReport($app, $request, $activity, $conf);

            return $this->getCSVResponse($app, $activity, 'activity_validated_documents');
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
     * Display report about documents sent by mail
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportSentDocuments(Application $app, Request $request)
    {
        $conf = array(
            'user'      => array('', 1, 0, 1, 1),
            'date'      => array('', 1, 0, 1, 1),
            'record_id' => array('', 1, 1, 1, 1),
            'file'      => array('', 1, 0, 1, 1),
            'mime'      => array('', 1, 0, 1, 1),
            'comment'   => array(_('Receiver'), 1, 0, 1, 1),
        );

        $activity = new \module_report_sent(
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

            $this->doReport($app, $request, $activity, $conf);

            return $this->getCSVResponse($app, $activity, 'activity_send_documents');
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

    private function getCSVResponse(Application $app, \module_report $report, $type)
    {
        // set headers
        $headers = array();
        foreach (array_keys($report->getDisplay()) as $k) {
            $headers[$k] = $k;
        }
        // set headers as first row
        $result = $report->getResult();
        array_unshift($result, $headers);

        $collection = new CallbackCollection($result, function ($row) use ($report) {
            // restrict to displayed fields
            return array_map('strip_tags', array_intersect_key($row, $report->getDisplay()));
        });

        $filename = sprintf('report_export_%s_%s.csv', $type, date('Ymd'));
        $response = new CSVFileResponse($filename, function () use ($app, $collection) {
            $app['csv.exporter']->export('php://output', $collection);
        });

        return $response;
    }
}
