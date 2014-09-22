<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Query implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.query'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->post('/', 'controller.prod.query:query')
            ->bind('prod_query');

        $controllers->post('/answer-train/', 'controller.prod.query:queryAnswerTrain')
            ->bind('preview_answer_train');

        $controllers->post('/reg-train/', 'controller.prod.query:queryRegTrain')
            ->bind('preview_reg_train');

        return $controllers;
    }

    /**
     * Query Phraseanet to fetch records
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function query(Application $app, Request $request)
    {
        $query = (string) $request->request->get('qry');

        $mod = $app['settings']->getUserSetting($app['authentication']->getUser(), 'view');

        $json = [];

        $options = SearchEngineOptions::fromRequest($app, $request);

        $form = $options->serialize();

        $perPage = (int) $app['settings']->getUserSetting($app['authentication']->getUser(), 'images_per_page');

        $page = (int) $request->request->get('pag');
        $firstPage = $page < 1;

        if ($page < 1) {
            $app['phraseanet.SE']->resetCache();
            $page = 1;
        }

        $result = $app['phraseanet.SE']->query($query, (($page - 1) * $perPage), $perPage, $options);

        $app['manipulator.user']->logQuery($app['authentication']->getUser(), $result->getQuery());

        if ($app['settings']->getUserSetting($app['authentication']->getUser(), 'start_page') === 'LAST_QUERY') {
            $app['manipulator.user']->setUserSetting($app['authentication']->getUser(), 'start_page_query', $result->getQuery());
        }

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function (\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function (\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $app['phraseanet.SE.logger']->log($databox, $result->getQuery(), $result->getTotal(), $colls);
        }

        $proposals = $firstPage ? $result->getProposals() : false;

        $npages = $result->getTotalPages($perPage);

        $page = $result->getCurrentPage($perPage);

        $string = '';

        if ($npages > 1) {

            $d2top = ($npages - $page);
            $d2bottom = $page;

            if (min($d2top, $d2bottom) < 4) {
                if ($d2bottom < 4) {
                    for ($i = 1; ($i <= 4 && (($i <= $npages) === true)); $i++) {
                        if ($i == $page)
                            $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                        else
                            $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                    }
                    if ($npages > 4)
                        $string .= "<a onclick='gotopage(" . ($npages) . ");return false;' class='btn btn-primary btn-mini'>&gt;&gt;</a>";
                } else {
                    $start = $npages - 4;
                    if (($start) > 0)
                        $string .= "<a onclick='gotopage(1);return false;' class='btn btn-primary btn-mini'>&lt;&lt;</a>";
                    else
                        $start = 1;
                    for ($i = ($start); $i <= $npages; $i++) {
                        if ($i == $page)
                            $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                        else
                            $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                    }
                }
            } else {
                $string .= "<a onclick='gotopage(1);return false;' class='btn btn-primary btn-mini'>&lt;&lt;</a>";

                for ($i = ($page - 2); $i <= ($page + 2); $i++) {
                    if ($i == $page)
                        $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini" />';
                    else
                        $string .= "<a onclick='gotopage(" . $i . ");return false;' class='btn btn-primary btn-mini'>" . $i . "</a>";
                }

                $string .= "<a onclick='gotopage(" . ($npages) . ");return false;' class='btn btn-primary btn-mini'>&gt;&gt;</a>";
            }
        }
        $string .= '<div style="display:none;"><div id="NEXT_PAGE"></div><div id="PREV_PAGE"></div></div>';

        $explain = "<div id=\"explainResults\" class=\"myexplain\">";

        $explain .= "<img src=\"/skins/icons/answers.gif\" /><span><b>";

        if ($result->getTotal() != $result->getAvailable()) {
            $explain .= $app->trans('reponses:: %available% Resultats rappatries sur un total de %total% trouves', ['available' => $result->getAvailable(), '%total%' => $result->getTotal()]);
        } else {
            $explain .= $app->trans('reponses:: %total% Resultats', ['%total%' => $result->getTotal()]);
        }

        $explain .= " </b></span>";
        $explain .= '<br><div>' . $result->getDuration() . ' s</div>dans index ' . $result->getIndexes();
        $explain .= "</div>";

        $infoResult = '<a href="#" class="infoDialog" infos="' . str_replace('"', '&quot;', $explain) . '">' . $app->trans('reponses:: %total% reponses', ['%total%' => $result->getTotal()]) . '</a> | ' . $app->trans('reponses:: %number% documents selectionnes', ['%number%' => '<span id="nbrecsel"></span>']);

        $json['infos'] = $infoResult;
        $json['navigation'] = $string;

        $prop = null;

        if ($firstPage) {
            $propals = $result->getSuggestions();
            if (count($propals) > 0) {
                foreach ($propals as $prop_array) {
                    if ($prop_array->getSuggestion() !== $query && $prop_array->getHits() > $result->getTotal()) {
                        $prop = $prop_array->getSuggestion();
                        break;
                    }
                }
            }
        }

        if ($result->getTotal() === 0) {
            $template = 'prod/results/help.html.twig';
        } else {
            if ($mod == 'thumbs') {
                $template = 'prod/results/answergrid.html.twig';
            } else {
                $template = 'prod/results/answerlist.html.twig';
            }
        }

        $json['results'] = $app['twig']->render($template, [
            'results'         => $result,
            'highlight'       => $result->getQuery(),
            'searchEngine'    => $app['phraseanet.SE'],
            'searchOptions'   => $options,
            'suggestions'     => $prop
            ]
        );

        $json['query'] = $query;
        $json['parsed_query'] = $result->getQuery();
        $json['phrasea_props'] = $proposals;
        $json['total_answers'] = (int) $result->getAvailable();
        $json['next_page'] = ($page < $npages && $result->getAvailable() > 0) ? ($page + 1) : false;
        $json['prev_page'] = ($page > 1 && $result->getAvailable() > 0) ? ($page - 1) : false;
        $json['form'] = $form;

        return $app->json($json);
    }

    /**
     * Get a preview answer train
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function queryAnswerTrain(Application $app, Request $request)
    {
        if (null === $optionsSerial = $request->request->get('options_serial')) {
            $app->abort(400, 'Search engine options are missing');
        }

        try {
            $options = SearchEngineOptions::hydrate($app, $optionsSerial);
        } catch (\Exception $e) {
            $app->abort(400, 'Provided search engine options are not valid');
        }

        $pos = (int) $request->request->get('pos', 0);
        $query = $request->request->get('query', '');

        $record = new \record_preview($app, 'RESULT', $pos, '', $app['phraseanet.SE'], $query, $options);

        return $app->json([
            'current' => $app['twig']->render('prod/preview/result_train.html.twig', [
                'records'  => $record->get_train($pos, $query, $app['phraseanet.SE']),
                'selected' => $pos
            ])
        ]);
    }

    /**
     * Get a preview reg train
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function queryRegTrain(Application $app, Request $request)
    {
        $record = new \record_preview($app, 'REG', $request->request->get('pos'), $request->request->get('cont'));

        return new Response($app['twig']->render('prod/preview/reg_train.html.twig', [
            'container_records' => $record->get_container()->get_children(),
            'record'            => $record
        ]));
    }
}
