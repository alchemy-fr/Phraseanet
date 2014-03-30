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
use Symfony\Component\HttpFoundation\JsonResponse;

class Informations implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.report.informations'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function () use ($app) {
            $app['firewall']->requireAccessToModule('report');
        });

        $controllers->post('/user', 'controller.report.informations:doReportInformationsUser')
            ->bind('report_infomations_user');

        $controllers->post('/browser', 'controller.report.informations:doReportInformationsBrowser')
            ->bind('report_infomations_browser');

        $controllers->post('/document', 'controller.report.informations:doReportInformationsDocument')
            ->bind('report_infomations_document');

        return $controllers;
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
        $conf = [
            'config'    => [
                'photo'     => [$app->trans('report:: document'), 0, 0, 0, 0],
                'record_id' => [$app->trans('report:: record id'), 0, 0, 0, 0],
                'date'      => [$app->trans('report:: date'), 0, 0, 0, 0],
                'type'      => [$app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
                'titre'     => [$app->trans('report:: titre'), 0, 0, 0, 0],
                'taille'    => [$app->trans('report:: poids'), 0, 0, 0, 0]
            ],
            'conf'  => [
                'identifiant'   => [$app->trans('report:: identifiant'), 0, 0, 0, 0],
                'nom'           => [$app->trans('report:: nom'), 0, 0, 0, 0],
                'mail'          => [$app->trans('report:: email'), 0, 0, 0, 0],
                'adresse'       => [$app->trans('report:: adresse'), 0, 0, 0, 0],
                'tel'           => [$app->trans('report:: telephone'), 0, 0, 0, 0]
            ],
            'config_cnx'    => [
                'ddate'     => [$app->trans('report:: date'), 0, 0, 0, 0],
                'appli'     => [$app->trans('report:: modules'), 0, 0, 0, 0],
            ],
            'config_dl' => [
                'ddate'     => [$app->trans('report:: date'), 0, 0, 0, 0],
                'record_id' => [$app->trans('report:: record id'), 0, 1, 0, 0],
                'final'     => [$app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
                'coll_id'   => [$app->trans('report:: collections'), 0, 0, 0, 0],
                'comment'   => [$app->trans('report:: commentaire'), 0, 0, 0, 0],
            ],
            'config_ask' => [
                'search'    => [$app->trans('report:: question'), 0, 0, 0, 0],
                'ddate'     => [$app->trans('report:: date'), 0, 0, 0, 0]
            ]
        ];

        $report = null;
        $html = $html_info = '';
        $from = $request->request->get('from', '');
        $on = $request->request->get('on', '');
        $selectValue = $request->request->get('user', '');

        if ('' === $selectValue) {
            $app->abort(400);
        }

        if ('' !== $on && $app['conf']->get(['registry', 'modules', 'anonymous-report']) == true) {
            $conf['conf'] = [
                 $on   => [$on, 0, 0, 0, 0],
                'nb'   => [$app->trans('report:: nombre'), 0, 0, 0, 0]
            ];
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
            $title = $app->trans('report:: historique des connexions');
        } elseif ($from == 'USR' || $from == 'GEN') {
            $report = new \module_report_download(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_dl'];
            $title = $app->trans('report:: historique des telechargements');
        } elseif ($from == 'ASK') {
            $report = new \module_report_question(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_ask'];
            $title = $app->trans('report:: historique des questions');
        }

        if ($report) {
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

                return $this->getCSVResponse($app, $report, 'info_user');
            }

            $html = $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => $report instanceof \module_report_download,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]);
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
            null !== $report ? $report->getParams() : [],
            $selectValue,
            $conf['conf'],
            $on
        );

        if (false == $app['conf']->get(['registry', 'modules', 'anonymous-report'])) {
            $html_info = $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($infoArray['report']) ? $infoArray['report'] : $infoArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]);

            $title = ('' === $on && isset($infoArray['result'])) ? $infoArray['result'][0]['identifiant'] : $selectValue;
        } else {
            $title = $selectValue;
        }

        return $app->json([
            'rs'          => sprintf('%s%s', $html_info, $html),
            'display_nav' => false,
            'title'       => $title
        ]);
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
        $conf = [
            'version'   => [$app->trans('report::version'), 0, 0, 0, 0],
            'nb'        => [$app->trans('report:: nombre'), 0, 0, 0, 0]
        ];

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

        return $app->json([
                'rs'          =>  $app['twig']->render('report/ajax_data_content.html.twig', [
                    'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                    'is_infouser' => false,
                    'is_nav'      => false,
                    'is_groupby'  => false,
                    'is_plot'     => false,
                    'is_doc'      => false
                ]),
                'display_nav' => false,
                'title'       => $browser
            ]);
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
        $config = [
            'photo'     => [$app->trans('report:: document'), 0, 0, 0, 0],
            'record_id' => [$app->trans('report:: record id'), 0, 0, 0, 0],
            'date'      => [$app->trans('report:: date'), 0, 0, 0, 0],
            'type'      => [$app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
            'titre'     => [$app->trans('report:: titre'), 0, 0, 0, 0],
            'taille'    => [$app->trans('report:: poids'), 0, 0, 0, 0]
        ];

        $config_dl = [
            'ddate'     => [$app->trans('report:: date'), 0, 0, 0, 0],
            'user'      => [$app->trans('report:: utilisateurs'), 0, 0, 0, 0],
            'final'     => [$app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
            'coll_id'   => [$app->trans('report:: collections'), 0, 0, 0, 0],
            'comment'   => [$app->trans('report:: commentaire'), 0, 0, 0, 0],
            'fonction'  => [$app->trans('report:: fonction'), 0, 0, 0, 0],
            'activite'  => [$app->trans('report:: activite'), 0, 0, 0, 0],
            'pays'      => [$app->trans('report:: pays'), 0, 0, 0, 0],
            'societe'   => [$app->trans('report:: societe'), 0, 0, 0, 0]
        ];

        //format conf according user preferences
        if ('' !== $columnsList = $request->request->get('list_column', '')) {
            $new_conf = $config_dl;
            $columns = explode(',', $columnsList);

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

        $html = $app['twig']->render('report/ajax_data_content.html.twig', [
            'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
            'is_infouser' => false,
            'is_nav'      => false,
            'is_groupby'  => false,
            'is_plot'     => false,
            'is_doc'      => false
        ]);

        $from = $request->request->get('from', '');

        if ('TOOL' === $from) {
            $what->setTitle('');

            return $app->json([
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ]);
        }

        if ('DASH' !== $from && 'PUSHDOC' !== $from) {
            $download = new \module_report_download(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );

            $mapColumnTitleToSqlField = $download->getTransQueryString();

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
                        'result' => $download->colFilter($field),
                        'field'  => $field
                    ]), 'title'  => $app->trans('filtrer les resultats sur la colonne %colonne%', ['%colonne%' => $field])]);
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
            $download->setTitle($app->trans('report:: historique des telechargements'));
            $download->setConfig(false);

            $reportArray = $download->buildReport($config_dl);

            if ($request->request->get('printcsv') == 'on') {
                $download->setPrettyString(false);

                return $this->getCSVResponse($app, $download, 'info_document');
            }

            $html .= $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]);

            return $app->json([
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ]);
        }

        if ($app['conf']->get(['registry', 'modules', 'anonymous-report']) == false && $from !== 'DOC' && $from !== 'DASH' && $from !== 'GEN' && $from !== 'PUSHDOC') {
            $conf = [
                'identifiant'   => [$app->trans('report:: identifiant'), 0, 0, 0, 0],
                'nom'           => [$app->trans('report:: nom'), 0, 0, 0, 0],
                'mail'          => [$app->trans('report:: email'), 0, 0, 0, 0],
                'adresse'       => [$app->trans('report:: adresse'), 0, 0, 0, 0],
                'tel'           => [$app->trans('report:: telephone'), 0, 0, 0, 0]
            ];

            $info = new \module_report_nav(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );

            $info->setPeriode('');
            $info->setConfig(false);
            $info->setTitle($app->trans('report:: utilisateur'));

            $reportArray = $info->buildTabGrpInfo(false, [],  $request->request->get('user'), $conf, false);

            if ($request->request->get('printcsv') == 'on' && isset($download)) {

                return $this->getCSVResponse($app, $info, 'info_user');
            }

            $html .= $app['twig']->render('report/ajax_data_content.html.twig', [
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]);

            return $app->json([
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ]);
        }

        return $app->json([
            'rs'          => $html,
            'display_nav' => false,
            'title'       => $title
        ));
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

        $collection = new CallbackCollection($result, function($row) use ($report) {
            // restrict fields to the displayed ones
            return array_map('strip_tags', array_intersect_key($row, $report->getDisplay()));
        });

        $filename = sprintf('report_export_%s_%s.csv', $type, date('Ymd'));
        $response = new CSVFileResponse($filename, function() use ($app, $collection) {
            $app['csv.exporter']->export('php://output', $collection);
        });

        return $response;
    }
}
