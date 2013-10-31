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
use Alchemy\Phrasea\Model\Entities\Basket as BasketEntity;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class BasketController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.basket'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->get('/{basket_id}/', 'controller.prod.basket:displayBasket')
            ->bind('prod_baskets_basket')
            ->assert('basket_id', '\d+');

        $controllers->post('/', 'controller.prod.basket:createBasket')
            ->bind('prod_baskets');

        $controllers->post('/{basket_id}/delete/', 'controller.prod.basket:deleteBasket')
            ->assert('basket_id', '\d+')
            ->bind('basket_delete');

        $controllers->post('/{basket_id}/delete/{basket_element_id}/', 'controller.prod.basket:removeBasketElement')
            ->bind('prod_baskets_basket_element_remove')
            ->assert('basket_id', '\d+')
            ->assert('basket_element_id', '\d+');

        $controllers->post('/{basket_id}/update/', 'controller.prod.basket:updateBasket')
            ->bind('prod_baskets_basket_update')
            ->assert('basket_id', '\d+');

        $controllers->get('/{basket_id}/update/', 'controller.prod.basket:displayUpdateForm')
            ->assert('basket_id', '\d+');

        $controllers->get('/{basket_id}/reorder/', 'controller.prod.basket:displayReorderForm')
            ->assert('basket_id', '\d+')
            ->bind('prod_baskets_basket_reorder');

        $controllers->post('/{basket_id}/reorder/', 'controller.prod.basket:reorder')
            ->assert('basket_id', '\d+');

        $controllers->post('/{basket_id}/archive/', 'controller.prod.basket:archiveBasket')
            ->bind('prod_baskets_basket_archive')
            ->assert('basket_id', '\d+');

        $controllers->post('/{basket_id}/addElements/', 'controller.prod.basket:addElements')
            ->assert('basket_id', '\d+');

        $controllers->post('/{basket_id}/stealElements/', 'controller.prod.basket:stealElements')
            ->assert('basket_id', '\d+');

        $controllers->get('/create/', 'controller.prod.basket:displayCreateForm')
            ->bind('prod_baskets_create');

        return $controllers;
    }

    public function displayBasket(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), false);

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

        $params = array(
            'basket' => $basket,
            'ordre'  => $request->query->get('order')
        );

        return $app['twig']->render('prod/WorkZone/Basket.html.twig', $params);
    }

    public function createBasket(Application $app, Request $request)
    {
        $request = $app['request'];
        /* @var $request \Symfony\Component\HttpFoundation\Request */

        $Basket = new BasketEntity();

        $Basket->setName($request->request->get('name', ''));
        $Basket->setOwner($app['authentication']->getUser());
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

        if ($request->getRequestFormat() == 'json') {
            $data = array(
                'success' => true
                , 'message' => _('Basket created')
                , 'basket'  => array(
                    'id' => $Basket->getId()
                )
            );

            return $app->json($data);
        } else {
            return $app->redirectPath('prod_baskets_basket', array('basket_id' => $Basket->getId()));
        }
    }

    public function deleteBasket(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        $app['EM']->remove($basket);
        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Basket has been deleted')
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function removeBasketElement(Application $app, Request $request, $basket_id, $basket_element_id)
    {
        $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        foreach ($basket->getElements() as $basket_element) {
            /* @var $basket_element BasketElement */
            if ($basket_element->getId() == $basket_element_id) {
                $app['EM']->remove($basket_element);
            }
        }

        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => _('Record removed from basket')
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function updateBasket(Application $app, Request $request, $basket_id)
    {
        $success = false;

        try {
            $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
                ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

            $basket->setName($request->request->get('name', ''));
            $basket->setDescription($request->request->get('description'));

            $app['EM']->merge($basket);
            $app['EM']->flush();

            $success = true;
            $msg = _('Basket has been updated');
        } catch (NotFoundHttpException $e) {
            $msg = _('The requested basket does not exist');
        } catch (AccessDeniedHttpException $e) {
            $msg = _('You do not have access to this basket');
        } catch (\Exception $e) {
            $msg = _('An error occurred');
        }

        $data = array(
            'success' => $success
            , 'message' => $msg
            , 'basket'  => array('id' => $basket_id)
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function displayUpdateForm(Application $app, $basket_id)
    {
        $basket = $app['EM']
            ->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        return $app['twig']->render('prod/Baskets/Update.html.twig', array('basket' => $basket));
    }

    public function displayReorderForm(Application $app, $basket_id)
    {
        $basket = $app['EM']
            ->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        return $app['twig']->render('prod/Baskets/Reorder.html.twig', array('basket' => $basket));
    }

    public function reorder(Application $app, $basket_id)
    {
        $ret = array('success' => false, 'message' => _('An error occured'));
        try {
            $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
                ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

            $order = $app['request']->request->get('element');

            /* @var $basket BasketEntity */
            foreach ($basket->getElements() as $basketElement) {
                if (isset($order[$basketElement->getId()])) {
                    $basketElement->setOrd($order[$basketElement->getId()]);

                    $app['EM']->merge($basketElement);
                }
            }

            $app['EM']->flush();
            $ret = array('success' => true, 'message' => _('Basket updated'));
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    public function archiveBasket(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        $archive_status = (Boolean) $request->query->get('archive');

        $basket->setArchived($archive_status);

        $app['EM']->merge($basket);
        $app['EM']->flush();

        if ($archive_status) {
            $message = _('Basket has been archived');
        } else {
            $message = _('Basket has been unarchived');
        }

        $data = array(
            'success' => true
            , 'archive' => $archive_status
            , 'message' => $message
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function addElements(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

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

        $data = array(
            'success' => true
            , 'message' => sprintf(_('%d records added'), $n)
        );

        if ($request->getRequestFormat() == 'json') {
            return $app->json($data);
        } else {
            return $app->redirectPath('prod_workzone_show');
        }
    }

    public function stealElements(Application $app, Request $request, $basket_id)
    {
        $basket = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), true);

        $n = 0;

        foreach ($request->request->get('elements') as $bask_element_id) {
            try {
                $basket_element = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\BasketElement')
                    ->findUserElement($bask_element_id, $app['authentication']->getUser());
            } catch (\Exception $e) {
                continue;
            }

            $basket_element->setBasket($basket);
            $basket->addElement($basket_element);
            $n++;
        }

        $app['EM']->flush();

        $data = array(
            'success' => true
            , 'message' => sprintf(_('%d records moved'), $n)
        );

        if ($request->getRequestFormat() == 'json') {
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
