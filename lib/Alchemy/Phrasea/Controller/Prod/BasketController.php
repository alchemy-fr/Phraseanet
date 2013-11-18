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

use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Model\Entities\Basket as BasketEntity;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BasketController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.basket'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers
            // Silex\Route::convert is not used as this should be done prior the before middleware
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access']);

        $controllers->get('/{basket}/', 'controller.prod.basket:displayBasket')
            ->bind('prod_baskets_basket')
            ->assert('basket', '\d+');

        $controllers->post('/', 'controller.prod.basket:createBasket')
            ->bind('prod_baskets');

        $controllers->post('/{basket}/delete/', 'controller.prod.basket:deleteBasket')
            ->assert('basket', '\d+')
            ->bind('basket_delete')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/delete/{basket_element_id}/', 'controller.prod.basket:removeBasketElement')
            ->bind('prod_baskets_basket_element_remove')
            ->assert('basket', '\d+')
            ->assert('basket_element_id', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/update/', 'controller.prod.basket:updateBasket')
            ->bind('prod_baskets_basket_update')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->get('/{basket}/update/', 'controller.prod.basket:displayUpdateForm')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->get('/{basket}/reorder/', 'controller.prod.basket:displayReorderForm')
            ->assert('basket', '\d+')
            ->bind('prod_baskets_basket_reorder')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/reorder/', 'controller.prod.basket:reorder')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/archive/', 'controller.prod.basket:archiveBasket')
            ->bind('prod_baskets_basket_archive')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/addElements/', 'controller.prod.basket:addElements')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/stealElements/', 'controller.prod.basket:stealElements')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->get('/create/', 'controller.prod.basket:displayCreateForm')
            ->bind('prod_baskets_create');

        return $controllers;
    }

    public function displayBasket(Application $app, Request $request, BasketEntity $basket)
    {
        if ($basket->getIsRead() === false) {
            $basket->setIsRead(true);
            $app['EM']->flush();
        }

        if ($basket->getValidation()) {
            if ($basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->getIsAware() === false) {
                $basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->setIsAware(true);
                $app['EM']->flush();
            }
        }

        $params = [
            'basket' => $basket,
            'ordre'  => $request->query->get('order')
        ];

        return $app['twig']->render('prod/WorkZone/Basket.html.twig', $params);
    }

    public function createBasket(Application $app, Request $request)
    {
        $Basket = new BasketEntity();

        $Basket->setName($request->request->get('name', ''));
        $Basket->setUser($app['authentication']->getUser());
        $Basket->setDescription($request->request->get('desc'));

        $app['EM']->persist($Basket);

        $n = 0;

        $records = RecordsRequest::fromRequest($app, $request, true);

        foreach ($records as $record) {
            if ($Basket->hasRecord($app, $record)) {
                continue;
            }

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket_element->setBasket($Basket);

            $app['EM']->persist($basket_element);

            $Basket->addElement($basket_element);

            $n++;
        }

        $app['EM']->flush();

        if ($request->getRequestFormat() === 'json') {
            $data = [
                'success' => true
                , 'message' => $app->trans('Basket created')
                , 'basket'  => [
                    'id' => $Basket->getId()
                ]
            ];

            return $app->json($data);
        } else {
            return $app->redirectPath('prod_baskets_basket', ['basket' => $Basket->getId()]);
        }
    }

    public function deleteBasket(Application $app, Request $request, BasketEntity $basket)
    {
        $app['EM']->remove($basket);
        $app['EM']->flush();

        $data = [
            'success' => true
            , 'message' => $app->trans('Basket has been deleted')
        ];

        if ($request->getRequestFormat() === 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function removeBasketElement(Application $app, Request $request, BasketEntity $basket, $basket_element_id)
    {
        foreach ($basket->getElements() as $basket_element) {
            /* @var $basket_element BasketElement */
            if ($basket_element->getId() === (int) $basket_element_id) {
                $app['EM']->remove($basket_element);
            }
        }

        $app['EM']->flush();

        $data = [
            'success' => true
            , 'message' => $app->trans('Record removed from basket')
        ];

        if ($request->getRequestFormat() === 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function updateBasket(Application $app, Request $request, BasketEntity $basket)
    {
        $success = false;

        try {
            $basket->setName($request->request->get('name', ''));
            $basket->setDescription($request->request->get('description'));

            $app['EM']->merge($basket);
            $app['EM']->flush();

            $success = true;
            $msg = $app->trans('Basket has been updated');
        } catch (NotFoundHttpException $e) {
            $msg = $app->trans('The requested basket does not exist');
        } catch (AccessDeniedHttpException $e) {
            $msg = $app->trans('You do not have access to this basket');
        } catch (\Exception $e) {
            $msg = $app->trans('An error occurred');
        }

        $data = [
            'success' => $success
            , 'message' => $msg
            , 'basket'  => ['id' => $basket->getId()]
        ];

        if ($request->getRequestFormat() === 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function displayUpdateForm(Application $app, BasketEntity $basket)
    {
        return $app['twig']->render('prod/Baskets/Update.html.twig', ['basket' => $basket]);
    }

    public function displayReorderForm(Application $app, BasketEntity $basket)
    {
        return $app['twig']->render('prod/Baskets/Reorder.html.twig', ['basket' => $basket]);
    }

    public function reorder(Application $app, BasketEntity $basket)
    {
        $ret = ['success' => false, 'message' => $app->trans('An error occured')];
        try {
            $order = $app['request']->request->get('element');

            /* @var $basket BasketEntity */
            foreach ($basket->getElements() as $basketElement) {
                if (isset($order[$basketElement->getId()])) {
                    $basketElement->setOrd($order[$basketElement->getId()]);

                    $app['EM']->merge($basketElement);
                }
            }

            $app['EM']->flush();
            $ret = ['success' => true, 'message' => $app->trans('Basket updated')];
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    public function archiveBasket(Application $app, Request $request, BasketEntity $basket)
    {
        $archive_status = (Boolean) $request->query->get('archive');

        $basket->setArchived($archive_status);

        $app['EM']->merge($basket);
        $app['EM']->flush();

        if ($archive_status) {
            $message = $app->trans('Basket has been archived');
        } else {
            $message = $app->trans('Basket has been unarchived');
        }

        $data = [
            'success' => true
            , 'archive' => $archive_status
            , 'message' => $message
        ];

        if ($request->getRequestFormat() === 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function addElements(Application $app, Request $request, BasketEntity $basket)
    {
        $n = 0;

        $records = RecordsRequest::fromRequest($app, $request, true);

        foreach ($records as $record) {
            if ($basket->hasRecord($app, $record))
                continue;

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket_element->setBasket($basket);

            $app['EM']->persist($basket_element);

            $basket->addElement($basket_element);

            if (null !== $validationSession = $basket->getValidation()) {

                $participants = $validationSession->getParticipants();

                foreach ($participants as $participant) {
                    $validationData = new ValidationData();
                    $validationData->setParticipant($participant);
                    $validationData->setBasketElement($basket_element);

                    $app['EM']->persist($validationData);
                }
            }

            $n++;
        }

        $app['EM']->flush();

        $data = [
            'success' => true
            , 'message' => $app->trans('%quantity% records added', ['%quantity%' => $n])
        ];

        if ($request->getRequestFormat() === 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function stealElements(Application $app, Request $request, BasketEntity $basket)
    {
        $n = 0;

        foreach ($request->request->get('elements') as $bask_element_id) {
            try {
                $basket_element = $app['EM']->getRepository('Phraseanet:BasketElement')
                    ->findUserElement($bask_element_id, $app['authentication']->getUser());
            } catch (\Exception $e) {
                continue;
            }

            $basket_element->getBasket()->removeElement($basket_element);
            $basket_element->setBasket($basket);
            $basket->addElement($basket_element);
            $n++;
        }

        $app['EM']->flush();

        $data = [
            'success' => true
            , 'message' => $app->trans('%quantity% records moved', ['%quantity%' => $n])
        ];

        if ($request->getRequestFormat() === 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function displayCreateForm(Application $app)
    {
        return $app['twig']->render('prod/Baskets/Create.html.twig');
    }
}
