<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
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

class ActivityController extends Controller
{
    /**
     * Display connexions report group by user
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportConnexionsByUsers(Request $request)
    {
        $activity = new \module_report_activity(
            $this->app,
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

            return $this->getCSVResponse($activity, 'activity_connection_base');
        }

        $report = $activity->getConnexionBase(false, $request->request->get('on', 'user'));

        return $this->app->json([
            'rs' =>  $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false,
            ]),
            'display_nav' => false,
            'title'       => false,
        ]);
    }

    /**
     * Display download report group by user
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportDownloadsByUsers(Request $request)
    {
        $conf = [
            'user'      => [$this->app->trans('report:: utilisateur'), 0, 1, 0, 0],
            'nbdoc'     => [$this->app->trans('report:: nombre de documents'), 0, 0, 0, 0],
            'nbprev'    => [$this->app->trans('report:: nombre de preview'), 0, 0, 0, 0],
        ];

        $activity = new \module_report_activity(
            $this->app,
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

            return $this->getCSVResponse($activity, 'activity_detail_download');
        }

        return $this->app->json([
            'rs' =>  $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false,
            ]),
            'display_nav' => false,
            'title'       => false,
        ]);
    }

    /**
     * Display the most asked question
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportBestOfQuestions(Request $request)
    {
        $conf = [
            'search'    => [$this->app->trans('report:: question'), 0, 0, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0],
            'nb_rep'    => [$this->app->trans('report:: nombre de reponses'), 0, 0, 0, 0]
        ];

        $activity = new \module_report_activity(
            $this->app,
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

            return $this->getCSVResponse($activity, 'activity_questions_best_of');
        }

        $report = $activity->getTopQuestion($conf);

        return $this->app->json([
            'rs' =>  $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]),
            'display_nav' => false,
            'title'       => false
        ]);
    }

    /**
     * Display report about questions that return no result
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportNoBestOfQuestions(Request $request)
    {
        $conf = [
            'search'    => [$this->app->trans('report:: question'), 0, 0, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0],
            'nb_rep'    => [$this->app->trans('report:: nombre de reponses'), 0, 0, 0, 0]
        ];

        $activity = new \module_report_activity(
            $this->app,
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

            return $this->getCSVResponse($activity, 'activity_top_ten_questions');
        }

        $report = $activity->getTopQuestion($conf, true);

        return $this->app->json([
            'rs' =>  $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]),
            'display_nav' => false,
            'title'       => false
        ]);
    }

    /**
     * Display an overview of connexion among hours of the da
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportSiteActiviyPerHours(Request $request)
    {
        $activity = new \module_report_activity(
            $this->app,
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

            return $this->getCSVResponse($activity, 'activity_per_hours');
        }

        $report = $activity->getActivityPerHours();

        return $this->app->json([
            'rs' =>  $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => true,
                'is_doc'      => false
            ]),
            'display_nav' => false,
            'title'       => false
        ]);
    }

    /**
     * Display an overview of downloaded document grouped by day
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportSiteActivityPerDays(Request $request)
    {
        $conf = [
            'ddate'     => [$this->app->trans('report:: jour'), 0, 0, 0, 0],
            'total'     => [$this->app->trans('report:: total des telechargements'), 0, 0, 0, 0],
            'preview'   => [$this->app->trans('report:: preview'), 0, 0, 0, 0],
            'document'  => [$this->app->trans('report:: document'), 0, 0, 0, 0]
        ];

        $activity = new \module_report_activity(
            $this->app,
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

            return $this->getCSVResponse($activity, 'activity_db_by_base_by_day');
        }

        $report = $activity->getDownloadByBaseByDay($conf);

        return $this->app->json([
            'rs' =>  $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($report['report']) ? $report['report'] : $report,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false,
            ]),
            'display_nav' => false,
            'title'       => false,
        ]);
    }

    /**
     * Display report about pushed documents
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportPushedDocuments(Request $request)
    {
        $conf = [
            'user'      => ['', 1, 0, 1, 1],
            'getter'    => ["Destinataire", 1, 0, 1, 1],
            'date'      => ['', 1, 0, 1, 1],
            'record_id' => ['', 1, 1, 1, 1],
            'file'      => ['', 1, 0, 1, 1],
            'mime'      => ['', 1, 0, 1, 1],
        ];

        $activity = new \module_report_push(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $this->doReport($request, $activity, $conf);

            return $this->getCSVResponse($activity, 'activity_pushed_documents');
        }

        $report = $this->doReport($request, $activity, $conf);

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
                'is_doc'      => false,
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record'],
        ]);
    }

    /**
     * Display report about added documents
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportAddedDocuments(Request $request)
    {
        $conf = [
            'user'      => ['', 1, 0, 1, 1],
            'date'      => ['', 1, 0, 1, 1],
            'record_id' => ['', 1, 1, 1, 1],
            'file'      => ['', 1, 0, 1, 1],
            'mime'      => ['', 1, 0, 1, 1],
        ];

        $activity = new \module_report_add(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $this->doReport($request, $activity, $conf);

            return $this->getCSVResponse($activity, 'activity_added_documents');
        }

        $report = $this->doReport($request, $activity, $conf);

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
                'is_doc'      => false,
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record'],
        ]);
    }

    /**
     * Display report about edited documents
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportEditedDocuments(Request $request)
    {
        $conf = [
            'user'      => ['', 1, 0, 1, 1],
            'date'      => ['', 1, 0, 1, 1],
            'record_id' => ['', 1, 1, 1, 1],
            'file'      => ['', 1, 0, 1, 1],
            'mime'      => ['', 1, 0, 1, 1],
        ];

        $activity = new \module_report_edit(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $this->doReport($request, $activity, $conf);

            return $this->getCSVResponse($activity, 'activity_edited_documents');
        }

        $report = $this->doReport($request, $activity, $conf);

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
                'is_doc'      => false,
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record'],
        ]);
    }

    /**
     * Display report about validated documents
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportValidatedDocuments(Request $request)
    {
        $conf = [
            'user'      => ['', 1, 0, 1, 1],
            'getter'    => ["Destinataire", 1, 0, 1, 1],
            'date'      => ['', 1, 0, 1, 1],
            'record_id' => ['', 1, 1, 1, 1],
            'file'      => ['', 1, 0, 1, 1],
            'mime'      => ['', 1, 0, 1, 1],
        ];

        $activity = new \module_report_validate(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $this->doReport($request, $activity, $conf);

            return $this->getCSVResponse($activity, 'activity_validated_documents');
        }

        $report = $this->doReport($request, $activity, $conf);

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
                'is_doc'      => false,
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record'],
        ]);
    }

    /**
     * Display report about documents sent by mail
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportSentDocuments(Request $request)
    {
        $conf = [
            'user'      => ['', 1, 0, 1, 1],
            'date'      => ['', 1, 0, 1, 1],
            'record_id' => ['', 1, 1, 1, 1],
            'file'      => ['', 1, 0, 1, 1],
            'mime'      => ['', 1, 0, 1, 1],
            'comment'   => [$this->app->trans('Receiver'), 1, 0, 1, 1],
        ];

        $activity = new \module_report_sent(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $activity->setConfig(false);

        if ($request->request->get('printcsv') == 'on') {
            $activity->setHasLimit(false);
            $activity->setPrettyString(false);

            $this->doReport($request, $activity, $conf);

            return $this->getCSVResponse($activity, 'activity_send_documents');
        }

        $report = $this->doReport($request, $activity, $conf);

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
                'is_doc'      => false,
            ]),
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ''), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record'],
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
        if ($this->getConf()->get(['registry', 'modules', 'anonymous-report'])) {
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
            return $this->app->json(['liste' => $this->render('report/listColumn.html.twig', [
                'conf'  => $base_conf
            ]), "title" => $this->app->trans("configuration")]);
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
                ]), "title"  => $this->app->trans('filtrer les resultats sur la colonne %colonne%', ['%colonne%' => $field])]);
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
                    'is_doc'      => false,
                ]),
                'display_nav' => false,
                'title'       => $this->app->trans('Groupement des resultats sur le champ %name%', ['%name%' => $groupField]),
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

        $collection = new CallbackCollection($result, function ($row) use ($report) {
            // restrict to displayed fields
            return array_map('strip_tags', array_intersect_key($row, $report->getDisplay()));
        });

        $filename = sprintf('report_export_%s_%s.csv', $type, date('Ymd'));
        /** @var Exporter $exporter */
        $exporter = $this->app['csv.exporter'];
        $response = new CSVFileResponse($filename, function () use ($exporter, $collection) {
            $exporter->export('php://output', $collection);
        });

        return $response;
    }
}
