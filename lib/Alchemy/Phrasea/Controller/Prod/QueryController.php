<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\SearchEngineAware;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\DisplaySettingService;
use Alchemy\Phrasea\Model\Entities\ElasticsearchRecord;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContextFactory;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\Utilities\StringHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use unicode;

class QueryController extends Controller
{
    use SearchEngineAware;

    public function completion(Request $request)
    {
        /** @var unicode $unicode */
        $query = (string) $request->request->get('fake_qry');
        $selStart = (int) $request->request->get('_selectionStart');
        $selEnd   = (int) $request->request->get('_selectionEnd');

        // move the selection back to find the begining of the "word"
        for(;;) {
            $c = '';
            if($selStart>0) {
                $c = mb_substr($query, $selStart-1, 1);
            }
            if(in_array($c, ['', ' ', '"'])) {
                break;
            }
            $selStart--;
        }

        // move the selection up to find the end of the "word"
        for(;;) {
            $c = mb_substr($query, $selEnd, 1);
            if(in_array($c, ['', ' ', '"'])) {
                break;
            }
            $selEnd++;
        }
        $before = mb_substr($query, 0, $selStart);
        $word = mb_substr($query, $selStart, $selEnd-$selStart);
        $after = mb_substr($query, $selEnd);

        // since the query comes from a submited form, normalize crlf,cr,lf ...
        $word = StringHelper::crlfNormalize($word);
        $options = SearchEngineOptions::fromRequest($this->app, $request);

        $search_engine_structure = $this->app['search_engine.global_structure'];

        $query_context_factory = new QueryContextFactory(
            $search_engine_structure,
            array_keys($this->app['locales.available']),
            $this->app['locale']
        );

        $engine = new ElasticSearchEngine(
            $this->app,
            $search_engine_structure,
            $this->app['elasticsearch.client'],
            $query_context_factory,
            $this->app['elasticsearch.facets_response.factory'],
            $this->app['elasticsearch.options'],
            $this->app['translator']
        );

        $autocomplete = $engine->autocomplete($word, $options);

        $completions = [];
        foreach($autocomplete['text'] as $text) {
            $completions[] = [
                'label' => $text,
                'value' => [
                    'before' => $before,
                    'word' => $word,
                    'after' => $after,
                    'completion' => $text,
                    'completed' => $before . $text . $after
                ]
            ];
        }
        foreach($autocomplete['byField'] as $fieldName=>$values) {
            foreach($values as $value) {
                $completions[] = [
                    'label' => $value['query'],
                    'value' => [
                        'before' => $before,
                        'word' => $word,
                        'after' => $after,
                        'completion' => $value['query'],
                        'completed' => $before . $value['query'] . $after
                    ]
                ];
            }
        }

        return $this->app->json($completions);
    }

    /**
     * Query Phraseanet to fetch records
     *
     * @param  Request $request
     * @return Response
     */
    public function query(Request $request)
    {
        $query = (string) $request->request->get('qry');

        // since the query comes from a submited form, normalize crlf,cr,lf ...
        $query = StringHelper::crlfNormalize($query);

        $json = [
            'query' => $query
        ];

        $options = SearchEngineOptions::fromRequest($this->app, $request);

        $perPage = (int) $this->getSettings()->getUserSetting($this->getAuthenticatedUser(), 'images_per_page');

        $page = (int) $request->request->get('pag');
        $firstPage = $page < 1;

        $engine = $this->getSearchEngine();
        if ($page < 1) {
            $engine->resetCache();
            $page = 1;
        }

        $options->setFirstResult(($page - 1) * $perPage);
        $options->setMaxResults($perPage);

        $user = $this->getAuthenticatedUser();
        $userManipulator = $this->getUserManipulator();
        $userManipulator->logQuery($user, $query);

        try {
            $result = $engine->query($query, $options);

            if ($this->getSettings()->getUserSetting($user, 'start_page') === 'LAST_QUERY') {
                // try to save the "fulltext" query which will be restored on next session
                try {
                    // local code to find "FULLTEXT" value from jsonQuery
                    $findFulltext = function($clause) use(&$findFulltext) {
                        if(array_key_exists('_ux_zone', $clause) && $clause['_ux_zone']=='FULLTEXT') {
                            return $clause['value'];
                        }
                        if($clause['type']=='CLAUSES') {
                            foreach($clause['clauses'] as $c) {
                                if(($r = $findFulltext($c)) !== null) {
                                    return $r;
                                }
                            }
                        }
                        return null;
                    };

                    $userManipulator->setUserSetting($user, 'last_jsonquery', (string)$request->request->get('jsQuery'));
                    $jsQuery = @json_decode((string)$request->request->get('jsQuery'), true);
                    if(($ft = $findFulltext($jsQuery['query'])) !== null) {
                        $userManipulator->setUserSetting($user, 'start_page_query', $ft);
                    }
                }
                catch(\Exception $e) {
                    // no-op
                }
            }

            // log array of collectionIds (from $options) for each databox
            $collectionsReferencesByDatabox = $options->getCollectionsReferencesByDatabox();
            foreach ($collectionsReferencesByDatabox as $sbid => $references) {
                $databox = $this->findDataboxById($sbid);
                $collectionsIds = array_map(function(CollectionReference $ref){return $ref->getCollectionId();}, $references);
                $this->getSearchEngineLogger()->log($databox, $result->getQueryText(), $result->getTotal(), $collectionsIds);
            }

            $proposals = $firstPage ? $result->getProposals() : false;

            $npages = $result->getTotalPages($perPage);

            $page = $result->getCurrentPage($perPage);

            $queryESLib = $result->getQueryESLib();

            $string = '';

            if ($npages > 1) {
                $d2top = ($npages - $page);
                $d2bottom = $page;

                if (min($d2top, $d2bottom) < 4) {
                    if ($d2bottom < 4) {
                        if($page != 1){
                            $string .= "<a id='PREV_PAGE' class='btn btn-primary btn-mini icon-baseline-chevron_left-24px'></a>";
                        }
                        for ($i = 1; ($i <= 4 && (($i <= $npages) === true)); $i++) {
                            if ($i == $page)
                                $string .= '<input type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini search-navigate-input-action" data-initial-value="' . $i . '" data-total-pages="'.$npages.'"/>';
                            else
                                $string .= '<a class="btn btn-primary btn-mini search-navigate-action" data-page="'.$i.'">' . $i . '</a>';
                        }
                        if ($npages > 4)
                            $string .= "<a id='NEXT_PAGE' class='btn btn-primary btn-mini icon icon-baseline-chevron_right-24px'></a>";
                        $string .= '<a href="#" class="btn btn-primary btn-mini search-navigate-action icon icon-double-arrows" data-page="' . $npages . '" id="last"></a>';
                    } else {
                        $start = $npages - 4;
                        if (($start) > 0){
                            $string .= '<a class="btn btn-primary btn-mini search-navigate-action" data-page="1" id="first"><span class="icon icon-double-arrows icon-inverse"></span></a>';
                            $string .= '<a id="PREV_PAGE" class="btn btn-primary btn-mini icon icon-baseline-chevron_left-24px"></a>';
                        }else
                            $start = 1;
                        for ($i = ($start); $i <= $npages; $i++) {
                            if ($i == $page)
                                $string .= '<input type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini search-navigate-input-action" data-initial-value="' . $i . '" data-total-pages="'.$npages.'" />';
                            else
                                $string .= '<a class="btn btn-primary btn-mini search-navigate-action" data-page="'.$i.'">' . $i . '</a>';
                        }
                        if($page < $npages){
                            $string .= "<a id='NEXT_PAGE' class='btn btn-primary btn-mini icon icon-baseline-chevron_right-24px'></a>";
                        }
                    }
                } else {
                    $string .= '<a class="btn btn-primary btn-mini search-navigate-action" data-page="1" id="first"><span class="icon icon-double-arrows icon-inverse"></span></a>';

                    for ($i = ($page - 2); $i <= ($page + 2); $i++) {
                        if ($i == $page)
                            $string .= '<input type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" class="btn btn-mini search-navigate-input-action" data-initial-value="' . $i . '" data-total-pages="'.$npages.'" />';
                        else
                            $string .= '<a class="btn btn-primary btn-mini search-navigate-action" data-page="'.$i.'">' . $i . '</a>';
                    }

                    $string .= '<a href="#" class="btn btn-primary btn-mini search-navigate-action icon icon-double-arrows" data-page="' . $npages . '" id="last"></a>';
                }
            }
            $string .= '<div style="display:none;"><div id="NEXT_PAGE" class="icon icon-baseline-chevron_right-24px"></div><div id="PREV_PAGE" class="icon icon-baseline-chevron_left-24px"></div></div>';

            $explain = $this->render(
                "prod/results/infos.html.twig",
                [
                    'results'=> $result,
                    'esquery' => $this->getAclForUser()->is_admin() ?
                        json_encode($queryESLib['body'], JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) :
                        null
                ]
            );

            $infoResult = '<div id="docInfo">'
                . $this->app->trans('%number% documents<br/>selectionnes', ['%number%' => '<span id="nbrecsel"></span>'])
                . '<div class="detailed_info_holder"><img src="/assets/common/images/icons/dots.png" class="image-normal hidden"><img src="/assets/common/images/icons/dots-darkgreen-hover.png" class="image-hover">'
                . '<div class="detailed_info">
                    <table>
                        <thead>
                            <tr>
                                <th>Nb</th>
                                <th>Type</th>
                                <th>File size</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Audio</td>
                                <td>1 Mb</td>
                                <td>00:04:31</td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>Documents</td>
                                <td>20 Kb</td>
                                <td>N/A</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>Images</td>
                                <td>400 Kb</td>
                                <td>N/A</td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>Video</td>
                                <td>19 Mb</td>
                                <td>00:20:36</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>6</td>
                                <td>Total</td>
                                <td>24.20 Mb</td>
                                <td>00:25:17</td>
                            </tr>
                        </tfoot>
                    </table></div></div>'
                . '</div><a href="#" class="search-display-info" data-infos="' . str_replace('"', '&quot;', $explain) . '">'
                . $this->app->trans('%total% reponses', ['%total%' => '<span>'.number_format($result->getTotal(),null, null, ' ').'</span>']) . '</a>';

            $json['infos'] = $infoResult;
            $json['navigationTpl'] = $string;
            $json['navigation'] = [
                'page' => $page,
                'perPage' => $perPage
            ];

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
            }
            else {
                $template = 'prod/results/records.html.twig';
            }

            /** @var \Closure $filter */
            $filter = $this->app['plugin.filter_by_authorization'];

            $plugins = [
                'workzone' => $filter('workzone'),
                'actionbar' => $filter('actionbar'),
            ];

            $json['results'] = $this->render($template, ['results'=> $result, 'plugins'=>$plugins]);


            // add technical fields
            $fieldsInfosByName = [];
            foreach(ElasticsearchOptions::getAggregableTechnicalFields($this->app['translator']) as $k => $f) {
                $fieldsInfosByName[$k] = $f;
                $fieldsInfosByName[$k]['trans_label'] = $this->app->trans( /** @ignore */ $f['label']);
                $fieldsInfosByName[$k]['labels'] = [];
                foreach($this->app->getAvailableLanguages() as $locale => $lng) {
                    $fieldsInfosByName[$k]['labels'][$locale] = $this->app->trans( /** @ignore */ $f['label'], [], "messages", $locale);
                }
            }

            // add databox fields
            // get infos about fields, fusionned and by databox
            $fieldsInfos = [];  // by databox
            foreach ($this->app->getDataboxes() as $databox) {
                $sbasId = $databox->get_sbas_id();
                $fieldsInfos[$sbasId] = [];
                foreach ($databox->get_meta_structure() as $field) {
                    $name = $field->get_name();
                    $fieldsInfos[$sbasId][$name] = [
                      'label'    => $field->get_label($this->app['locale']),
                      'labels'   => $field->get_labels(),
                      'type'     => $field->get_type(),
                      'business' => $field->isBusiness(),
                      'multi'    => $field->is_multi(),
                    ];

                    // infos on the "same" field (by name) on multiple databoxes !!!
                    // label(s) can be inconsistants : the first databox wins
                    if (!isset($fieldsInfosByName[$name])) {
                        $fieldsInfosByName[$name] = [
                            'label'       => $field->get_label($this->app['locale']),
                            'labels'      => $field->get_labels(),
                            'type'        => $field->get_type(),
                            'field'       => $field->get_name(),
                            'trans_label' => $field->get_label($this->app['locale']),
                        ];
                        $field->get_label($this->app['locale']);
                    }
                }
            }

            // populates fileds infos
            $json['fields'] = $fieldsInfos;

            // populates rawresults
            // need acl so the result will not include business fields where not allowed
            $acl = $this->getAclForUser();
            $json['rawResults'] = [];
            /** @var ElasticsearchRecord $record */
            foreach($result->getResults() as $record) {
                $rawRecord = $record->asArray();

                $sbasId = $record->getDataboxId();
                $baseId = $record->getBaseId();

                $caption = $rawRecord['caption'];
                if($acl && $acl->has_right_on_base($baseId, \ACL::CANMODIFRECORD)) {
                    $caption = array_merge($caption, $rawRecord['privateCaption']);
                }

                // read the fields following the structure order
                $rawCaption = [];
                foreach($fieldsInfos[$sbasId] as $fieldName=>$fieldInfos) {
                    if(array_key_exists($fieldName, $caption)) {
                        $rawCaption[$fieldName] = $caption[$fieldName];
                    }
                }
                $rawRecord['caption'] = $rawCaption;
                unset($rawRecord['privateCaption']);

                $json['rawResults'][$record->getId()] = $rawRecord;
            }

            // populates facets (aggregates)
            $facets = [];
            foreach ($result->getFacets() as $facet) {
                $facetName = $facet['name'];

                if(array_key_exists($facetName, $fieldsInfosByName)) {
                    $f = $fieldsInfosByName[$facetName];
                    $facet['label'] = $f['trans_label'];
                    $facet['labels'] = $f['labels'];
                    $facet['type'] = strtoupper($f['type']) . "-AGGREGATE";
                    $facets[] = $facet;
                }
            }

            // $json['jsq'] = $facetClauses;

            $json['facets'] = $facets;
            $json['phrasea_props'] = $proposals;
            $json['total_answers'] = (int) $result->getAvailable();
            $json['next_page'] = ($page < $npages && $result->getAvailable() > 0) ? ($page + 1) : false;
            $json['prev_page'] = ($page > 1 && $result->getAvailable() > 0) ? ($page - 1) : false;
            $json['form'] = $options->serialize();
            $json['queryCompiled'] = $result->getQueryCompiled();
            $json['queryAST'] = $result->getQueryAST();
            $json['queryESLib'] = $queryESLib;
        }
        catch(\Exception $e) {
            // we'd like a message from the parser so get all the exceptions messages
            $msg = '';
            for(; $e; $e=$e->getPrevious()) {
                $msg .= ($msg ? "\n":"") . $e->getMessage();
            }
            $template = 'prod/results/help.html.twig';
            $result = [
                'error' => $msg
            ];
            $json['results'] = $this->render($template, ['results'=> $result]);
        }

        return $this->app->json($json);
    }

    /**
     * Get a preview answer train
     *
     * @param  Request $request
     * @return Response
     */
    public function queryAnswerTrain(Request $request)
    {
        if (null === $optionsSerial = $request->get('options_serial')) {
            $this->app->abort(400, 'Search engine options are missing');
        }

        try {
            $options = SearchEngineOptions::hydrate($this->app, $optionsSerial);
        } catch (\Exception $e) {
            $this->app->abort(400, 'Provided search engine options are not valid');
        }

        $pos = (int) $request->request->get('pos', 0);
        $query = $request->request->get('query', '');

        $record = new \record_preview($this->app, 'RESULT', $pos, '', $this->getSearchEngine(), $query, $options);

        $index = ($pos - 3) < 0 ? 0 : ($pos - 3);
        return $this->app->json([
            'current' => $this->render('prod/preview/result_train.html.twig', [
                'records'  => $record->get_train(),
                'index' => $index,
                'selected' => $pos,
                'recordsTotal' => $record->getTotal()
            ])
        ]);
    }

    /**
     * @return DisplaySettingService
     */
    private function getSettings()
    {
        return $this->app['settings'];
    }

    /**
     * @return mixed
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }
}
