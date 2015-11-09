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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BasketController extends Controller
{
    public function displayBasket(Request $request, Basket $basket)
    {
        if ($basket->getIsRead() === false) {
            $basket->setIsRead(true);
            $this->getEntityManager()->flush();
        }

        if ($basket->getValidation()) {
            if ($basket->getValidation()->getParticipant($this->getAuthenticatedUser())->getIsAware() === false) {
                $basket->getValidation()->getParticipant($this->getAuthenticatedUser())->setIsAware(true);
                $this->getEntityManager()->flush();
            }
        }

        /** @var \Closure $filter */
        $filter = $this->app['plugin.filter_by_authorization'];

        return $this->render('prod/WorkZone/Basket.html.twig', [
            'basket' => $basket,
            'ordre'  => $request->query->get('order'),
            'plugins' => [
                'actionbar' => $filter('workzone.basket.actionbar'),
            ],
        ]);
    }

    public function createBasket(Request $request)
    {
        $Basket = new Basket();

        $Basket->setName($request->request->get('name', ''));
        $Basket->setUser($this->getAuthenticatedUser());
        $Basket->setDescription($request->request->get('desc'));

        $this->getEntityManager()->persist($Basket);

        $n = 0;

        $records = RecordsRequest::fromRequest($this->app, $request, true);

        foreach ($records as $record) {
            if ($Basket->hasRecord($this->app, $record)) {
                continue;
            }

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket_element->setBasket($Basket);

            $this->getEntityManager()->persist($basket_element);

            $Basket->addElement($basket_element);

            $n++;
        }

        $this->getEntityManager()->flush();

        if ($request->getRequestFormat() === 'json') {
            $data = [
                'success' => true,
                'message' => $this->app->trans('Basket created'),
                'basket'  => [
                    'id' => $Basket->getId(),
                ]
            ];

            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_baskets_basket', ['basket' => $Basket->getId()]);
    }

    public function deleteBasket(Request $request, Basket $basket)
    {
        $this->getEntityManager()->remove($basket);
        $this->getEntityManager()->flush();

        $data = [
            'success' => true
            , 'message' => $this->app->trans('Basket has been deleted')
        ];

        if ($request->getRequestFormat() === 'json') {
            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    public function removeBasketElement(Request $request, Basket $basket, $basket_element_id)
    {
        /** @var BasketElement $basketElement */
        $basketElement = $this->getEntityManager()->getRepository('Phraseanet:BasketElement')->find($basket_element_id);
        $ord = $basketElement->getOrd();

        foreach ($basket->getElements() as $basket_element) {
            if ($basket_element->getOrd() > $ord) {
                $basket_element->setOrd($basket_element->getOrd() - 1);
            }
            if ($basket_element->getId() === (int) $basket_element_id) {
                $basket->removeElement($basket_element);
                $this->getEntityManager()->remove($basket_element);
            }
        }

        $this->getEntityManager()->persist($basket);
        $this->getEntityManager()->flush();

        $data = ['success' => true, 'message' => $this->app->trans('Record removed from basket')];

        if ($request->getRequestFormat() === 'json') {
            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    public function updateBasket(Request $request, Basket $basket)
    {
        $success = false;

        try {
            $basket->setName($request->request->get('name', ''));
            $basket->setDescription($request->request->get('description'));

            $this->getEntityManager()->merge($basket);
            $this->getEntityManager()->flush();

            $success = true;
            $msg = $this->app->trans('Basket has been updated');
        } catch (NotFoundHttpException $e) {
            $msg = $this->app->trans('The requested basket does not exist');
        } catch (AccessDeniedHttpException $e) {
            $msg = $this->app->trans('You do not have access to this basket');
        } catch (\Exception $e) {
            $msg = $this->app->trans('An error occurred');
        }

        $data = [
            'success' => $success,
            'message' => $msg,
            'basket'  => ['id' => $basket->getId()],
        ];

        if ($request->getRequestFormat() === 'json') {
            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    public function displayUpdateForm(Basket $basket)
    {
        return $this->render('prod/Baskets/Update.html.twig', ['basket' => $basket]);
    }

    public function displayReorderForm(Basket $basket)
    {
        return $this->render('prod/Baskets/Reorder.html.twig', ['basket' => $basket]);
    }

    public function reorder(Request $request, Basket $basket)
    {
        $ret = ['success' => false, 'message' => $this->app->trans('An error occured')];
        try {
            $order = $request->request->get('element');

            foreach ($basket->getElements() as $basketElement) {
                if (isset($order[$basketElement->getId()])) {
                    $basketElement->setOrd($order[$basketElement->getId()]);

                    $this->getEntityManager()->merge($basketElement);
                }
            }

            $this->getEntityManager()->flush();
            $ret = ['success' => true, 'message' => $this->app->trans('Basket updated')];
        } catch (\Exception $e) {

        }

        return $this->app->json($ret);
    }

    public function archiveBasket(Request $request, Basket $basket)
    {
        $archive_status = (Boolean) $request->query->get('archive');

        $basket->setArchived($archive_status);

        $this->getEntityManager()->merge($basket);
        $this->getEntityManager()->flush();

        if ($archive_status) {
            $message = $this->app->trans('Basket has been archived');
        } else {
            $message = $this->app->trans('Basket has been unarchived');
        }

        $data = [
            'success' => true,
            'archive' => $archive_status,
            'message' => $message,
        ];

        if ($request->getRequestFormat() === 'json') {
            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    public function addElements(Request $request, Basket $basket)
    {
        $n = 0;

        $records = RecordsRequest::fromRequest($this->app, $request, true);

        $em = $this->getEntityManager();
        foreach ($records as $record) {
            if ($basket->hasRecord($this->app, $record))
                continue;

            $basket_element = new BasketElement();
            $basket_element->setRecord($record);
            $basket_element->setBasket($basket);

            $em->persist($basket_element);

            $basket->addElement($basket_element);

            if (null !== $validationSession = $basket->getValidation()) {

                $participants = $validationSession->getParticipants();

                foreach ($participants as $participant) {
                    $validationData = new ValidationData();
                    $validationData->setParticipant($participant);
                    $validationData->setBasketElement($basket_element);

                    $em->persist($validationData);
                }
            }

            $n++;
        }

        $em->flush();

        $data = [
            'success' => true,
            'message' => $this->app->trans('%quantity% records added', ['%quantity%' => $n]),
        ];

        if ($request->getRequestFormat() === 'json') {
            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    public function stealElements(Request $request, Basket $basket)
    {
        $n = 0;

        $user = $this->getAuthenticatedUser();
        /** @var BasketElementRepository $repository */
        $repository = $this->app['repo.basket-elements'];
        foreach ($request->request->get('elements') as $bask_element_id) {
            try {
                $basket_element = $repository->findUserElement($bask_element_id, $user);
            } catch (\Exception $e) {
                continue;
            }

            $basket_element->getBasket()->removeElement($basket_element);
            $basket_element->setBasket($basket);
            $basket->addElement($basket_element);
            $n++;
        }

        $this->getEntityManager()->flush();

        $data = ['success' => true, 'message' => $this->app->trans('%quantity% records moved', ['%quantity%' => $n])];

        if ($request->getRequestFormat() === 'json') {
            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    public function displayCreateForm()
    {
        return $this->render('prod/Baskets/Create.html.twig');
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->app['orm.em'];
    }
}
