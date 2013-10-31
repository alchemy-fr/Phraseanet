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

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Baskets implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.client.baskets'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function () use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->match('/', 'controller.client.baskets:getBaskets')
            ->method('POST|GET')
            ->bind('get_client_baskets');

        $controllers->post('/new/', 'controller.client.baskets:createBasket')
            ->bind('client_new_basket');

        $controllers->post('/delete/', 'controller.client.baskets:deleteBasket')
            ->bind('client_delete_basket');

        $controllers->post('/check/', 'controller.client.baskets:checkBaskets')
            ->bind('client_basket_check');

        $controllers->post('/add-element/', 'controller.client.baskets:addElementToBasket')
            ->bind('client_basket_add_element');

        $controllers->post('/delete-element/', 'controller.client.baskets:deleteBasketElement')
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
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\BasketElement');
            $basketElement = $repository->findUserElement($request->request->get('p0'), $app['authentication']->getUser());
            $app['EM']->remove($basketElement);
            $app['EM']->flush();
        } catch (\Exception $e) {

        }

        return $app->redirectPath('get_client_baskets', array(
            'courChuId' => $request->request->get('courChuId', '')
        ));
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
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket');
            /* @var $repository Alchemy\Phrasea\Model\Repositories\BasketRepository */
            $basket = $repository->findUserBasket($app, $request->request->get('courChuId'), $app['authentication']->getUser(), true);

            $app['EM']->remove($basket);
            $app['EM']->flush();
            unset($basket);
        } catch (\Exception $e) {

        }

        return $app->redirectPath('get_client_baskets');
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
            $basket->setOwner($app['authentication']->getUser());

            $app['EM']->persist($basket);
            $app['EM']->flush();

        } catch (\Exception $e) {

        }

        return $app->redirectPath('get_client_baskets', array(
            'courChuId' => null !== $basket ? $basket->getId() : ''
        ));
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
        $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket');
        /* @var $repository Alchemy\Phrasea\Model\Repositories\BasketRepository */
        $basket = $repository->findUserBasket($app, $request->request->get('courChuId'), $app['authentication']->getUser(), true);

        if ($basket) {
            try {
                $record = new \record_adapter($app, $request->request->get('sbas'), $request->request->get('p0'));

                $basketElement = new BasketElement();
                $basketElement->setRecord($record);
                $basketElement->setBasket($basket);
                $basket->addElement($basketElement);

                $app['EM']->persist($basket);

                $app['EM']->flush();
            } catch (\Exception $e) {

            }
        }

        return $app->redirectPath('get_client_baskets', array(
            'courChuId' => $basket ? $basket->getId() : ''
        ));
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
        $baskets = new ArrayCollection($app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')->findActiveByUser($app['authentication']->getUser()));
        $selectedBasket = null;

        if ('' === $selectedBasketId && $baskets->count() > 0) {
            $selectedBasketId = $baskets->first()->getId();
        }

        if ('' !== $selectedBasketId) {
            $selectedBasket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')->findUserBasket($app, $selectedBasketId, $app['authentication']->getUser(), true);
        }

        $basketCollections = $baskets->partition(function ($key, $basket) {
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
        $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket');

        /* @var $repository Alchemy\Phrasea\Model\Repositories\BasketRepository */
        $baskets = $repository->findActiveByUser($app['authentication']->getUser());

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
}
