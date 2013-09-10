<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Entities\Category as CategoryEntity;
use Entities\CategoryElement;
use Entities\CategoryTranslation;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Category implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication()
                ;
        });


        /**
         * Create a new category
         *
         * name         : prod_category_new
         *
         * description  : Create a new category
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/create/', $this->call('createCategory'))
            ->bind('prod_category_new');

        /**
         * Delete a category
         *
         * name         : prod_category_delete
         *
         * description  : Delete a category
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/{category_id}/delete/', $this->call('deleteCategory'))
            ->bind('prod_category_delete')
            ->assert('category_id', '\d+');

        /**
         * Update a category
         *
         * name         : prod_category_update
         *
         * description  : Update a category
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/{category_id}/update/', $this->call('updateCategory'))
            ->bind('prod_category_update')
            ->assert('category_id', '\d+');

        /**
         * Add categories to a record
         *
         * name         : prod_category_add
         *
         * description  : Add categories to a record
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/add/', $this->call('addCategories'))
            ->bind('prod_category_add');

        /**
         * Remove categories from a record
         *
         * name         : prod_category_remove
         *
         * description  : Remove categories from a record
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/remove/', $this->call('removeCategories'))
            ->bind('prod_category_remove');

        return $controllers;
    }

    /**
     * Create a new category
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return RedirectResponse|JsonResponse
     */
    public function createCategory(Application $app, Request $request)
    {
        $data = json_decode($request->getContent());

        if (!isset($data->title)
            || null !== $app['EM']->getRepository('Entities\Category')->findOneBy(array('title' => $data->title))) {
            $app->abort(400, 'Bad Request');
        }

        if (isset($data->parent_id)) {
            $parent = $app['EM']->getRepository('Entities\Category')->find($data->parent_id);
            if (null === $parent) {
                $app->abort(400, 'Bad Request');
            }
        }

        $category = new CategoryEntity();
        $category->setTitle($data->title);

        if (isset($data->parent_id)) {
            $category->setParent($parent);
        }

        if (isset($data->subtitle)) {
            $category->setSubtitle($data->subtitle);
        }

        if (isset($data->translation_title)) {
            $translationTitle = new CategoryTranslation();
            $translationTitle->setField('title');
            $translationTitle->setLocale($data->translation_title->locale);
            $translationTitle->setContent($data->translation_title->value);
            $category->addTranslation($translationTitle);
            $app['EM']->persist($translationTitle);
        }

        if (isset($data->translation_subtitle)) {
            $translationSubtitle = new CategoryTranslation();
            $translationSubtitle->setField('subtitle');
            $translationSubtitle->setLocale($data->translation_subtitle->locale);
            $translationSubtitle->setContent($data->translation_subtitle->value);
            $category->addTranslation($translationSubtitle);
            $app['EM']->persist($translationSubtitle);
        }

        $app['EM']->persist($category);
        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Category created')
            , 'category'  => json_decode($app['serializer']->serialize($category, 'json'))
        );

        return $app->json($data);
    }

    /**
     * Update a category
     *
     * @param Application $app
     * @param Request     $request
     * @param integer     $category_id
     *
     * @return RedirectResponse|JsonResponse
     */
    public function updateCategory(Application $app, Request $request, $category_id)
    {
        $data = json_decode($request->getContent());

        $category = $app['EM']->getRepository('Entities\Category')->find($category_id);
        if (null == $category) {
            $app->abort(404, 'Category not found');
        }

        if (isset($data->parent_id)) {
            $parent = $app['EM']->getRepository('Entities\Category')->find($data->parent_id);
            if (null === $parent) {
                $app->abort(400, 'Bad Request');
            }
            $category->setParent($parent);
        }

        if (isset($data->title)) {
            $category->setTitle($data->title);
        }

        if (isset($data->subtitle)) {
            $category->setSubtitle($data->subtitle);
        }

        if (isset($data->translation_title)) {
            foreach ($category->getTranslations() as $translation) {
                if ($translation->getLocale() === $data->translation_title->locale) {
                    $translationTitle = $translation;
                }
            }
            if (!isset($translationTitle)) {
                $translationTitle = new CategoryTranslation();
                $translationTitle->setField('title');
                $translationTitle->setLocale($data->translation_title->locale);
                $translationTitle->setContent($data->translation_title->value);
                $category->addTranslation($translationTitle);
            } else {
                $translationTitle->setContent($data->translation_title->value);
            }
            $app['EM']->persist($translationTitle);
        }

        if (isset($data->translation_subtitle)) {
            foreach ($category->getTranslations() as $translation) {
                if ($translation->getLocale() === $data->translation_title->locale) {
                    $translationSubtitle = $translation;
                }
            }
            if (!isset($translationSubtitle)) {
                $translationSubtitle = new CategoryTranslation();
                $translationSubtitle->setField('subtitle');
                $translationSubtitle->setLocale($data->translation_title->locale);
                $translationSubtitle->setContent($data->translation_title->value);
                $category->addTranslation($translationSubtitle);
            } else {
               $translationSubtitle->setContent($data->translation_title->value);
            }
            $app['EM']->persist($translationSubtitle);
        }

        $app['EM']->persist($category);
        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Category updated')
            , 'category'  => json_decode($app['serializer']->serialize($category, 'json')),
        );

        return $app->json($data);
    }

    /**
     * Delete a category
     *
     * @param Application $app
     * @param Request     $request
     * @param integer     $category_id
     *
     * @return RedirectResponse|JsonResponse
     */
    public function deleteCategory(Application $app, Request $request, $category_id)
    {
        $category = $app['EM']->getRepository('Entities\Category')->find($category_id);
        if (null === $category) {
            $app->abort(404, 'Category not found');
        }

        $app['EM']->remove($category);
        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Category has been deleted')
        );

        return $app->json($data);
    }

    /**
     * Add categories to selected records
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return RedirectResponse|JsonResponse
     */
    public function addCategories(Application $app, Request $request)
    {
        $data = json_decode($request->getContent());

        $categories = $data->category_ids;
        $records = RecordsRequest::fromRequest($app, $request, true);
        foreach ($records as $record) {
            foreach ($categories as $category_id) {
                $category = $app['EM']->getRepository('Entities\Category')->find($category_id);
                if (null === $category) {
                    $app->abort(404, 'Category not found');
                }
                $element = new CategoryElement();
                $element->setRecord($record);
                $element->setCategory($category);
                $category->addElement($element);

                $app['EM']->persist($category);
                $app['EM']->persist($element);
            }
        }

        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Categories added')
        );

        return $app->json($data);
    }

    /**
     * Remove categories from selected records
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return RedirectResponse|JsonResponse
     */
    public function removeCategories(Application $app, Request $request)
    {
        $data = json_decode($request->getContent());

        $categories = $data->category_ids;
        $records = RecordsRequest::fromRequest($app, $request, true);
        foreach ($records as $record) {
            foreach ($categories as $category_id) {
                $category = $app['EM']->getRepository('Entities\Category')->find($category_id);
                if (null === $category) {
                    $app->abort(404, 'Category not found');
                }
                $element = $app['EM']->getRepository('Entities\CategoryElement')
                            ->findBy(array('category' => $category,
                                           'recordId' => $record->get_id(),
                                           'sbasId' => $record->get_sbas_id()));
                if (null !== $element) {
                    $app['EM']->remove($element);
                }
            }
        }

        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Categories removed')
        );

        return $app->json($data);
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
