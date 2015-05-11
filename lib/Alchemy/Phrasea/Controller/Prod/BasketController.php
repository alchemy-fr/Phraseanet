<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Model\Entities\Basket as BasketEntity;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BasketController extends Controller
{
    public function displayBasket(Application $app, Request $request, BasketEntity $basket)
    {
        if ($basket->getIsRead() === false) {
            $basket->setIsRead(true);
            $app['orm.em']->flush();
        }

        if ($basket->getValidation()) {
            if ($basket->getValidation()->getParticipant($app['authentication']->getUser())->getIsAware() === false) {
                $basket->getValidation()->getParticipant($app['authentication']->getUser())->setIsAware(true);
                $app['orm.em']->flush();
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

        $app['orm.em']->persist($Basket);

        $n = 0;

        $records = RecordsRequest::fromRequest($app, $request, true);

        foreach ($records as $record) {
            if ($Basket->hasRecord($app, $record)) {
                continue;
            }

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket_element->setBasket($Basket);

            $app['orm.em']->persist($basket_element);

            $Basket->addElement($basket_element);

            $n++;
        }

        $app['orm.em']->flush();

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
        $app['orm.em']->remove($basket);
        $app['orm.em']->flush();

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
        $basketElement = $app['orm.em']->getRepository('Phraseanet:BasketElement')->find($basket_element_id);
        $ord = $basketElement->getOrd();

        foreach ($basket->getElements() as $basket_element) {
            if ($basket_element->getOrd() > $ord) {
                $basket_element->setOrd($basket_element->getOrd() - 1);
            }
            if ($basket_element->getId() === (int) $basket_element_id) {
                $basket->removeElement($basket_element);
                $app['orm.em']->remove($basket_element);
            }
        }

        $app['orm.em']->persist($basket);
        $app['orm.em']->flush();

        $data = ['success' => true, 'message' => $app->trans('Record removed from basket')];

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

            $app['orm.em']->merge($basket);
            $app['orm.em']->flush();

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

                    $app['orm.em']->merge($basketElement);
                }
            }

            $app['orm.em']->flush();
            $ret = ['success' => true, 'message' => $app->trans('Basket updated')];
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    public function archiveBasket(Application $app, Request $request, BasketEntity $basket)
    {
        $archive_status = (Boolean) $request->query->get('archive');

        $basket->setArchived($archive_status);

        $app['orm.em']->merge($basket);
        $app['orm.em']->flush();

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

            $app['orm.em']->persist($basket_element);

            $basket->addElement($basket_element);

            if (null !== $validationSession = $basket->getValidation()) {

                $participants = $validationSession->getParticipants();

                foreach ($participants as $participant) {
                    $validationData = new ValidationData();
                    $validationData->setParticipant($participant);
                    $validationData->setBasketElement($basket_element);

                    $app['orm.em']->persist($validationData);
                }
            }

            $n++;
        }

        $app['orm.em']->flush();

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
                $basket_element = $app['repo.basket-elements']->findUserElement($bask_element_id, $app['authentication']->getUser());
            } catch (\Exception $e) {
                continue;
            }

            $basket_element->getBasket()->removeElement($basket_element);
            $basket_element->setBasket($basket);
            $basket->addElement($basket_element);
            $n++;
        }

        $app['orm.em']->flush();

        $data = ['success' => true, 'message' => $app->trans('%quantity% records moved', ['%quantity%' => $n])];

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
