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
use Symfony\Component\HttpFoundation\JsonResponse;

class Informations implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function () use ($app) {
            $app['firewall']->requireAuthentication();
            $app['firewall']->requireAccessToModule('report');
        });

        $controllers->post('/user', $this->call('doReportInformationsUser'))
            ->bind('report_infomations_user');

        $controllers->post('/browser', $this->call('doReportInformationsBrowser'))
            ->bind('report_infomations_browser');

        $controllers->post('/document', $this->call('doReportInformationsDocument'))
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
            $title = _('report:: historique des connexions');
        } elseif ($from == 'USR' || $from == 'GEN') {
            $report = new \module_report_download(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_dl'];
            $title = _('report:: historique des telechargements');
        } elseif ($from == 'ASK') {
            $report = new \module_report_question(
                $app,
                $request->request->get('dmin'),
                $request->request->get('dmax'),
                $request->request->get('sbasid'),
                $request->request->get('collection')
            );
            $conf_array = $conf['config_ask'];
            $title = _('report:: historique des questions');
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
                    )), 'title'  => sprintf(_('filtrer les resultats sur la colonne %s'), $field)));
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

        if ('DASH' !== $from && 'PUSHDOC' !== $from) {
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
                        'result' => $download->colFilter($field),
                        'field'  => $field
                    )), 'title'  => sprintf(_('filtrer les resultats sur la colonne %s'), $field)));
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
            $download->setTitle(_('report:: historique des telechargements'));
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

            $html .= $app['twig']->render('report/ajax_data_content.html.twig', array(
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

        if ($app['phraseanet.registry']->get('GV_anonymousReport') == false && $from !== 'DOC' && $from !== 'DASH' && $from !== 'GEN' && $from !== 'PUSHDOC') {
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

            $info->setPeriode('');
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

            $html .= $app['twig']->render('report/ajax_data_content.html.twig', array(
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
