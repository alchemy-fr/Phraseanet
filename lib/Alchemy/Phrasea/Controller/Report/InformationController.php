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

class InformationController extends Controller
{
    /**
     * Display information about a user
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportInformationUser(Request $request)
    {
        $conf = [
            'config'    => [
                'photo'     => [$this->app->trans('report:: document'), 0, 0, 0, 0],
                'record_id' => [$this->app->trans('report:: record id'), 0, 0, 0, 0],
                'date'      => [$this->app->trans('report:: date'), 0, 0, 0, 0],
                'type'      => [$this->app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
                'titre'     => [$this->app->trans('report:: titre'), 0, 0, 0, 0],
                'taille'    => [$this->app->trans('report:: poids'), 0, 0, 0, 0]
            ],
            'conf'  => [
                'identifiant'   => [$this->app->trans('report:: identifiant'), 0, 0, 0, 0],
                'nom'           => [$this->app->trans('report:: nom'), 0, 0, 0, 0],
                'mail'          => [$this->app->trans('report:: email'), 0, 0, 0, 0],
                'adresse'       => [$this->app->trans('report:: adresse'), 0, 0, 0, 0],
                'tel'           => [$this->app->trans('report:: telephone'), 0, 0, 0, 0]
            ],
            'config_cnx'    => [
                'ddate'     => [$this->app->trans('report:: date'), 0, 0, 0, 0],
                'appli'     => [$this->app->trans('report:: modules'), 0, 0, 0, 0],
            ],
            'config_dl' => [
                'ddate'     => [$this->app->trans('report:: date'), 0, 0, 0, 0],
                'record_id' => [$this->app->trans('report:: record id'), 0, 1, 0, 0],
                'final'     => [$this->app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
                'coll_id'   => [$this->app->trans('report:: collections'), 0, 0, 0, 0],
                'comment'   => [$this->app->trans('report:: commentaire'), 0, 0, 0, 0],
            ],
            'config_ask' => [
                'search'    => [$this->app->trans('report:: question'), 0, 0, 0, 0],
                'ddate'     => [$this->app->trans('report:: date'), 0, 0, 0, 0]
            ]
        ];

        $report = null;
        $html = $html_info = '';
        $from = $request->request->get('from', '');
        $on = $request->request->get('on', '');
        $selectValue = $request->request->get('user', '');

        if ('' === $selectValue) {
            $this->app->abort(400);
        }

        if ('' !== $on && $this->getConf()->get(['registry', 'modules', 'anonymous-report']) == true) {
            $conf['conf'] = [
                $on   => [$on, 0, 0, 0, 0],
                'nb'   => [$this->app->trans('report:: nombre'), 0, 0, 0, 0]
            ];
        }

        if ($from == 'CNXU' || $from == 'CNX') {
            $report = new \module_report_connexion(
                $this->app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_cnx'];
            $title = $this->app->trans('report:: historique des connexions');
        } elseif ($from == 'USR' || $from == 'GEN') {
            $report = new \module_report_download(
                $this->app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_dl'];
            $title = $this->app->trans('report:: historique des telechargements');
        } elseif ($from == 'ASK') {
            $report = new \module_report_question(
                $this->app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_ask'];
            $title = $this->app->trans('report:: historique des questions');
        }

        if ($report) {
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
                    return $this->app->json([
                        'diag'  => $this->render('report/colFilter.html.twig', [
                            'result' => $report->colFilter($field),
                            'field'  => $field
                        ]),
                        'title'  => $this->app->trans('filtrer les resultats sur la colonne %colonne%', ['%colonne%' => $field])]);
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

                return $this->getCSVResponse($report, 'info_user');
            }

            $html = $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => $report instanceof \module_report_download,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]);
        }

        $info = new \module_report_nav(
            $this->app,
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

        if (false == $this->app['conf']->get(['registry', 'modules', 'anonymous-report'])) {
            $html_info = $this->render('report/ajax_data_content.html.twig', [
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

        return $this->app->json([
            'rs'          => sprintf('%s%s', $html_info, $html),
            'display_nav' => false,
            'title'       => $title
        ]);
    }

    /**
     * Display a browser version
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function doReportInformationBrowser(Request $request)
    {
        $conf = [
            'version'   => [$this->app->trans('report::version'), 0, 0, 0, 0],
            'nb'        => [$this->app->trans('report:: nombre'), 0, 0, 0, 0]
        ];

        $info = new \module_report_nav(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $info->setCsv(false);
        $info->setConfig(false);

        if ('' === $browser = $request->request->get('user', '')) {
            $this->app->abort(400);
        }

        $reportArray = $info->buildTabInfoNav($conf, $browser);

        return $this->app->json([
            'rs'          =>  $this->render('report/ajax_data_content.html.twig', [
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
     * Display information about a document
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doReportInformationDocument(Request $request)
    {
        $config = [
            'photo'     => [$this->app->trans('report:: document'), 0, 0, 0, 0],
            'record_id' => [$this->app->trans('report:: record id'), 0, 0, 0, 0],
            'date'      => [$this->app->trans('report:: date'), 0, 0, 0, 0],
            'type'      => [$this->app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
            'titre'     => [$this->app->trans('report:: titre'), 0, 0, 0, 0],
            'taille'    => [$this->app->trans('report:: poids'), 0, 0, 0, 0]
        ];

        $config_dl = [
            'ddate'     => [$this->app->trans('report:: date'), 0, 0, 0, 0],
            'user'      => [$this->app->trans('report:: utilisateurs'), 0, 0, 0, 0],
            'final'     => [$this->app->trans('phrseanet:: sous definition'), 0, 0, 0, 0],
            'coll_id'   => [$this->app->trans('report:: collections'), 0, 0, 0, 0],
            'comment'   => [$this->app->trans('report:: commentaire'), 0, 0, 0, 0],
            'fonction'  => [$this->app->trans('report:: fonction'), 0, 0, 0, 0],
            'activite'  => [$this->app->trans('report:: activite'), 0, 0, 0, 0],
            'pays'      => [$this->app->trans('report:: pays'), 0, 0, 0, 0],
            'societe'   => [$this->app->trans('report:: societe'), 0, 0, 0, 0]
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
                $this->app,
                $request->request->get('sbasid'),
                $request->request->get('rid')
            );
        } catch (\Exception $e) {
            $this->app->abort(404);
        }

        $what = new \module_report_nav(
            $this->app,
            $request->request->get('dmin'),
            $request->request->get('dmax'),
            $request->request->get('sbasid'),
            $request->request->get('collection')
        );

        $what->setPeriode('');
        $what->setCsv(false);
        $what->setPrint(false);

        /** @var \record_adapter $record */
        $reportArray = $what->buildTabUserWhat(
            $record->getBaseId(),
            $record->getRecordId(),
            $config
        );

        $title = $what->getTitle();

        $html = $this->render('report/ajax_data_content.html.twig', [
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

            return $this->app->json([
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ]);
        }

        if ('DASH' !== $from && 'PUSHDOC' !== $from) {
            $download = new \module_report_download(
                $this->app,
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

            $filter = new \module_report_filter($this->app, $currentfilter, $mapColumnTitleToSqlField);

            if ('' !== $filterColumn = $request->request->get('filter_column', '')) {
                $field = current(explode(' ', $filterColumn));
                $value = $request->request->get('filter_value', '');

                if ($request->request->get('liste') == 'on') {
                    return $this->app->json([
                        'diag'  => $this->render('report/colFilter.html.twig', [
                            'result' => $download->colFilter($field),
                            'field'  => $field
                        ]),
                        'title'  => $this->app->trans('filtrer les resultats sur la colonne %colonne%', ['%colonne%' => $field])
                    ]);
                }

                if ($field === $value) {
                    $filter->removeFilter($field);
                } else {
                    $filter->addFilter($field, '=', $value);
                }
            }

            $filter->addfilter('record_id', '=', $record->getRecordId());

            $download->setFilter($filter->getTabFilter());
            $download->setOrder('ddate', 'DESC');
            $download->setTitle($this->app->trans('report:: historique des telechargements'));
            $download->setConfig(false);

            $reportArray = $download->buildReport($config_dl);

            if ($request->request->get('printcsv') == 'on') {
                $download->setPrettyString(false);

                return $this->getCSVResponse($download, 'info_document');
            }

            $html .= $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]);

            return $this->app->json([
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ]);
        }

        if ($this->getConf()->get(['registry', 'modules', 'anonymous-report']) == false && $from !== 'DOC' && $from !== 'DASH' && $from !== 'GEN' && $from !== 'PUSHDOC') {
            $conf = [
                'identifiant'   => [$this->app->trans('report:: identifiant'), 0, 0, 0, 0],
                'nom'           => [$this->app->trans('report:: nom'), 0, 0, 0, 0],
                'mail'          => [$this->app->trans('report:: email'), 0, 0, 0, 0],
                'adresse'       => [$this->app->trans('report:: adresse'), 0, 0, 0, 0],
                'tel'           => [$this->app->trans('report:: telephone'), 0, 0, 0, 0]
            ];

            $info = new \module_report_nav(
                $this->app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );

            $info->setPeriode('');
            $info->setConfig(false);
            $info->setTitle($this->app->trans('report:: utilisateur'));

            $reportArray = $info->buildTabGrpInfo(false, [],  $request->request->get('user'), $conf, false);

            if ($request->request->get('printcsv') == 'on' && isset($download)) {
                return $this->getCSVResponse($this->app, $info, 'info_user');
            }

            $html .= $this->render('report/ajax_data_content.html.twig', [
                'result'      => isset($reportArray['report']) ? $reportArray['report'] : $reportArray,
                'is_infouser' => false,
                'is_nav'      => false,
                'is_groupby'  => false,
                'is_plot'     => false,
                'is_doc'      => false
            ]);

            return $this->app->json([
                'rs'          => $html,
                'display_nav' => false,
                'title'       => $title
            ]);
        }

        return $this->app->json([
            'rs'          => $html,
            'display_nav' => false,
            'title'       => $title
        ]);
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
            // restrict fields to the displayed ones
            return array_map('strip_tags', array_intersect_key($row, $report->getDisplay()));
        });

        /** @var Exporter $exporter */
        $exporter = $this->app['csv.exporter'];
        $filename = sprintf('report_export_%s_%s.csv', $type, date('Ymd'));
        $response = new CSVFileResponse($filename, function () use ($exporter, $collection) {
            $exporter->export('php://output', $collection);
        });

        return $response;
    }
}
