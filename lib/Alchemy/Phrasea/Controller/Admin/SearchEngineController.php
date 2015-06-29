<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchSettingFormType;
use Alchemy\Phrasea\SearchEngine\Elastic\GlobalElasticOptions;
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
        $options = $this->getElasticSearchOptions();
        $form = $this->getConfigurationForm($options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->saveElasticSearchOptions($form->getData());

            return $this->app->redirectPath('admin_searchengine_form');
        }

        return $this->render('admin/search-engine/elastic-search.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return GlobalElasticOptions
     */
    private function getElasticSearchOptions()
    {
        return $this->app['elasticsearch.options'];
    }

    /**
     * @param GlobalElasticOptions $configuration
     * @return void
     */
    private function saveElasticSearchOptions(GlobalElasticOptions $configuration)
    {
        $this->getConf()->set(['main', 'search-engine', 'options'], $configuration->toArray());
    }

    /**
     * @param GlobalElasticOptions $options
     * @return FormInterface
     */
    private function getConfigurationForm(GlobalElasticOptions $options)
    {
        return $this->app->form(new ElasticSearchSettingFormType(), $options, [
            'action' => $this->app->url('admin_searchengine_form'),
        ]);
    }
}
