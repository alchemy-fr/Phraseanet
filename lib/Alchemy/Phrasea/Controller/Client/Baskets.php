<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Client;

use Entities\Basket;
use Entities\BasketElement;
use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Baskets implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAuthentication();
        });

        /**
         * Gets client baskets
         *
         * name         : get_client_baskets
         *
         * description  : fetch current user baskets
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->match('/', $this->call('getBaskets'))
            ->method('POST|GET')
            ->bind('get_client_baskets');

        /**
         * Creates a new basket
         *
         * name         : client_new_basket
         *
         * description  : Create a new basket
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/new/', $this->call('createBasket'))
            ->bind('client_new_basket');

        /**
         * Deletes a basket
         *
         * name         : client_delete_basket
         *
         * description  : Delete a basket
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/delete/', $this->call('deleteBasket'))
            ->bind('client_delete_basket');

       /**
         * Checks if client basket should be updated
         *
         * name         : client_basket_check
         *
         * description  : Update basket client
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/check/', $this->call('checkBaskets'))
            ->bind('client_basket_check');

       /**
         * Adds an element to a basket
         *
         * name         : client_basket_add_element
         *
         * description  : Add an element to a basket
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/add-element/', $this->call('addElementToBasket'))
            ->bind('client_basket_add_element');

        /**
         * Deletes an element from a basket
         *
         * name         : client_basket_delete_element
         *
         * description  : Delete an element from a basket
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : REDIRECT Response
         */
        $controllers->post('/delete-element/', $this->call('deleteBasketElement'))
            ->bind('client_basket_delete_element');

        return $controllers;
    }

    /**
     * Deletes a basket element
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function deleteBasketElement(Application $app, Request $request)
    {
        try {
            $repository = $app['EM']->getRepository('\Entities\BasketElement');
            $basketElement = $repository->findUserElement($request->request->get('p0'), $app['phraseanet.user']);
            $app['EM']->remove($basketElement);
            $app['EM']->flush();
        } catch (\Exception $e) {

        }

        return $app->redirect($app['url_generator']->generate('get_client_baskets', array(
            'courChuId' => $request->request->get('courChuId', '')
        )));
    }

    /**
     * Deletes a basket
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function deleteBasket(Application $app, Request $request)
    {
        try {
            $repository = $app['EM']->getRepository('\Entities\Basket');
            /* @var $repository \Repositories\BasketRepository */
            $basket = $repository->findUserBasket($app, $request->request->get('courChuId'), $app['phraseanet.user'], true);

            $app['EM']->remove($basket);
            $app['EM']->flush();
            unset($basket);
        } catch (\Exception $e) {

        }

        return $app->redirect($app['url_generator']->generate('get_client_baskets'));
    }

    /**
     * Creates a new basket
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function createBasket(Application $app, Request $request)
    {
        $basket = null;

        try {
            $basket = new Basket();
            $basket->setName($request->request->get('p0'));
            $basket->setOwner($app['phraseanet.user']);

            $app['EM']->persist($basket);
            $app['EM']->flush();

        } catch (\Exception $e) {

        }

        return $app->redirect($app['url_generator']->generate('get_client_baskets', array(
            'courChuId' => null !== $basket ? $basket->getId() : ''
        )));
    }

    /**
     * Adds an element to a basket
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function addElementToBasket(Application $app, Request $request)
    {
        $repository = $app['EM']->getRepository('\Entities\Basket');
        /* @var $repository \Repositories\BasketRepository */
        $basket = $repository->findUserBasket($app, $request->request->get('courChuId'), $app['phraseanet.user'], true);

        if ($basket) {
            try {
                $record = new \record_adapter($app, $request->request->get('sbas'), $request->request->get('p0'));

                $basketElement = new BasketElement();
                $basketElement->setRecord($record);
                $basketElement->setBasket($basket);
                $basket->addBasketElement($basketElement);

                $app['EM']->persist($basket);

                $app['EM']->flush();
            } catch (\Exception $e) {

            }
        }

        return $app->redirect($app['url_generator']->generate('get_client_baskets', array(
            'courChuId' => $basket ? $basket->getId() : ''
        )));
    }

    /**
     * Fetchs current user baskets
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function getBaskets(Application $app, Request $request)
    {
        $selectedBasketId = trim($request->get('courChuId', ''));
        $baskets = new ArrayCollection($app['EM']->getRepository('\Entities\Basket')->findActiveByUser($app['phraseanet.user']));
        $selectedBasket = null;

        if ('' === $selectedBasketId && $baskets->count() > 0) {
            $selectedBasketId = $baskets->first()->getId();
        }

        if ('' !== $selectedBasketId) {
            $selectedBasket = $app['EM']->getRepository('\Entities\Basket')->findUserBasket($app, $selectedBasketId, $app['phraseanet.user'], true);
        }

        $basketCollections = $baskets->partition(function($key, $basket) {
            return (Boolean) $basket->getPusherId();
        });

        return $app['twig']->render('client/baskets.html.twig', array(
            'total_baskets'            => $baskets->count(),
            'user_baskets'             => $basketCollections[1],
            'recept_user_basket'       => $basketCollections[0],
            'selected_basket'          => $selectedBasket,
            'selected_basket_elements' => $selectedBasket ? $selectedBasket->getElements() : new ArrayCollection()
        ));
    }

    /**
     * Checks Update basket client
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function checkBaskets(Application $app, Request $request)
    {
        $noview = 0;
        $repository = $app['EM']->getRepository('\Entities\Basket');

        /* @var $repository \Repositories\BasketRepository */
        $baskets = $repository->findActiveByUser($app['phraseanet.user']);

        foreach ($baskets as $basket) {
            if (!$basket->getIsRead()) {
                $noview++;
            }
        }

        return $app->json(array(
            'success' => true,
            'message' => '',
            'no_view' => $noview
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
