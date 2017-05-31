<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchSettingsFormType;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchEngineController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function formConfigurationPanelAction(Request $request)
    {
        $options = $this->getElasticsearchOptions();
        $form = $this->getConfigurationForm($options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->saveElasticSearchOptions($form->getData());

            return $this->app->redirectPath('admin_searchengine_form');
        }

        return $this->render('admin/search-engine/elastic-search.html.twig', [
            'form' => $form->createView(),
            'indexer' => $this->app['elasticsearch.indexer']
        ]);
    }

    public function dropIndexAction(Request $request)
    {
        $indexer = $this->app['elasticsearch.indexer'];
        if ($indexer->indexExists()) {
            $indexer->deleteIndex();
        }
        return $this->app->redirectPath('admin_searchengine_form');
    }

    public function createIndexAction(Request $request)
    {
        $indexer = $this->app['elasticsearch.indexer'];
        if (!$indexer->indexExists()) {
            $indexer->createIndex();

            $options = $this->app['elasticsearch.options'];
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "http://localhost:9200/" . $options->getIndexName() . "/_settings",
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => "{ \"index\" : { \"max_result_window\" : 500000 } }",
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err)
            {
                echo "cURL Error #:" . $err;
            }
            else
            {
                echo $response;
            }
        }
        return $this->app->redirectPath('admin_searchengine_form');
    }

    /**
     * @return ElasticsearchOptions
     */
    private function getElasticsearchOptions()
    {
        return $this->app['elasticsearch.options'];
    }

    /**
     * @param ElasticsearchOptions $configuration
     * @return void
     */
    private function saveElasticSearchOptions(ElasticsearchOptions $configuration)
    {
        $this->getConf()->set(['main', 'search-engine', 'options'], $configuration->toArray());
    }

    /**
     * @param ElasticsearchOptions $options
     * @return FormInterface
     */
    private function getConfigurationForm(ElasticsearchOptions $options)
    {
        return $this->app->form(new ElasticsearchSettingsFormType(), $options, [
            'action' => $this->app->url('admin_searchengine_form'),
        ]);
    }

    public function dumpResultIndexElasticsearchAction()
    {
        $options = $this->app['elasticsearch.options'];
        $indexName = $options->getIndexName();
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://localhost:9200/" . $indexName . "/_settings/index.number_*",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err)
        {
            return $this->app->json([
                'success' => false,
                'message' => implode("\n", $err),
            ]);
        }
        else
        {
            $resultat = json_decode($response);


            return $this->app->json([
                'success' => true,
                'response' => $resultat->$indexName->settings->index
            ]);
        }
    }
}
