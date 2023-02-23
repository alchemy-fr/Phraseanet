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

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Helper\WorkZone as WorkzoneHelper;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Manipulator\BasketManipulator;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoReminderFeedback;
use Alchemy\Phrasea\Notification\Receiver;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BasketController extends Controller
{
    use NotifierAware;

    public function getWip(Request $request, Basket $basket)
    {
        return $this->app->json([
            'basket_id' => $basket->getId(),
            'wip' => $basket->getWip()
        ]);
    }

    public function displayBasket(Request $request, Basket $basket)
    {
        $me = $this->getAuthenticatedUser();
        $ret = [
            'html' => '',
            'data' => null
        ];

        $workzoneHelper = new WorkzoneHelper($this->app, $request);
        $wzContent = $workzoneHelper->getContent(null, $basket->getId());    // same infos as "workzone", but only for this basket
        foreach ([WorkzoneHelper::BASKETS, WorkzoneHelper::VALIDATIONS] as $kBlock) {
            foreach ($wzContent[$kBlock] as $wzBasketExt) {
                /** @var Basket $wzBasket */
                $wzBasket = $wzBasketExt['object'];
                // only one should be found
                if($wzBasket->getId() === $basket->getId()) {
                    $ret['data'] = $wzBasketExt['data'];
                }
            }
        }

        $ouputFormat = $request->getRequestFormat();

        // if basket is wip, return now
        if($basket->getWip() !== NULL) {
            $ret['html'] = $this->render('prod/WorkZone/BasketWip.html.twig', [
                'basket' => $basket,
            ]);
            return $ouputFormat === 'json' ? $this->app->json($ret) : $ret['html'];
        }

        // basket is not wip, update som infos
        if ($basket->isRead() === false) {
            $basket->markRead();
            $this->getEntityManager()->flush();
        }

        if ($basket->isParticipant($me)) {
            if ($basket->getParticipant($me)->getIsAware() === false) {
                $basket->getParticipant($me)->setIsAware(true);
                $this->getEntityManager()->flush();
            }
        }

        /** @var Closure $filter */
        $filter = $this->app['plugin.filter_by_authorization'];

        $ret['html'] = $this->render('prod/WorkZone/Basket.html.twig', [
                'basket'  => $basket,
                // !!!!!!!!!!!!!!!!!!!!!!!! order is null when a "vote" (feedback) is deployed in wz
                'ordre'   => $request->query->get('order') ?: Basket::ELEMENTSORDER_NAT,
                'plugins' => [
                    'actionbar' => $filter('workzone.basket.actionbar'),
                ],
            ]
        );

        if($ouputFormat === "json") {
            // return advanced format containig share, feedback... infos and html
            return $this->app->json($ret);
        }
        // default return html
        return $ret['html'];
    }

    public function displayReminder(Request $request, Basket $basket)
    {
        if ($basket->isVoteBasket()) {
            if ($basket->getParticipant($this->getAuthenticatedUser())->getIsAware() === false) {
                $basket->getParticipant($this->getAuthenticatedUser())->setIsAware(true);
                $this->getEntityManager()->flush();
            }
        }

        return $this->render('prod/WorkZone/Reminder.html.twig', [
            'basket' => $basket,
        ]);
    }

    public function doReminder(Request $request, Basket $basket)
    {
        if ($basket->isVoteBasket()) {
            $userFrom = $basket->getVoteInitiator();
            $isFeedback = true;
        } else {
            // get the owner for share
            $userFrom = $basket->getUser();
            $isFeedback = false;
        }

        $expireDate = $basket->getVoteExpires();
        $emitter = Emitter::fromUser($userFrom);
        $localeFrom = $userFrom->getLocale();

        $params = $request->request->all();
        $message = $params['reminder_message'];

        $usersId = array_map(function ($value) {
            $t = explode("_", $value);

            return $t[1];
        }, preg_grep('/^participant/', array_keys($params)));

        /** @var UserRepository $userRepository */
        $userRepository = $this->app['repo.users'];

        foreach ($usersId as $userId) {
            $userTo = $userRepository->find($userId);

            // find the token if exists
            // nb : a validation may have not generated tokens if forcing auth was required upon creation
            $token = null;
            try {
                $token = $this->getTokenRepository()->findValidationToken($basket, $userTo);
            }
            catch (\Exception $e) {
                // not unique token ? should not happen
            }

            if ($request->get('send_new_token')) {
                // first, if token exist delete it
                if(!is_null($token)) {
                    $this->getTokenManipulator()->delete($token);
                    unset($token);
                }
                $token = $this->getTokenManipulator()->createBasketValidationToken($basket, $userTo, $expireDate);
            }

            if(!is_null($token)) {
                $url = $this->app->url('lightbox_validation', ['basket' => $basket->getId(), 'LOG' => $token->getValue()]);
            } else {
                $url = $this->app->url('lightbox_validation', ['basket' => $basket->getId()]);
            }

            $receiver = Receiver::fromUser($userTo);
            $mail = MailInfoReminderFeedback::create($this->app, $receiver, $emitter, $message);
            $mail->setTitle($basket->getName());
            $mail->setButtonUrl($url);
            $mail->setFeedback($isFeedback);

            if (($locale = $userTo->getLocale()) != null) {
                $mail->setLocale($locale);
            } elseif ($localeFrom != null) {
                $mail->setLocale($localeFrom);
            }

            $this->deliver($mail);
        }

        return $this->app->json(["success" => true]);
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->app['orm.em'];
    }

    public function createBasket(Request $request)
    {
        $basket = new Basket();

        $basket->setName($request->request->get('name', ''));
        $basket->setUser($this->getAuthenticatedUser());
        $basket->setDescription($request->request->get('description'));

        $records = RecordsRequest::fromRequest($this->app, $request, true);

        $manipulator = $this->getBasketManipulator();
        $manipulator->addRecords($basket, $records);
        $manipulator->saveBasket($basket);

        if ($request->getRequestFormat() === 'json') {
            $data = [
                'success' => true,
                'message' => $this->app->trans('Basket created'),
                'basket'  => [
                    'id' => $basket->getId(),
                ]
            ];

            return $this->app->json($data);
        }

        return $this->app->redirectPath('prod_baskets_basket', ['basket' => $basket->getId()]);
    }

    /**
     * @return BasketManipulator
     */
    private function getBasketManipulator()
    {
        return $this->app['manipulator.basket'];
    }

    public function deleteBasket(Request $request, Basket $basket)
    {
        $this->getBasketManipulator()->removeBasket($basket);

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
        $this->getBasketManipulator()->removeElements($basket, [$basketElement]);

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
        $records = RecordsRequest::fromRequest($this->app, $request, true);

        $elements = $this->getBasketManipulator()->addRecords($basket, $records);

        $data = [
            'success' => true,
            'message' => $this->app->trans('%quantity% records added', ['%quantity%' => count($elements)]),
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

            $oldBasket = $basket_element->getBasket();

            $oldBasket->removeElement($basket_element);
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
     * @return TokenRepository
     */
    private function getTokenRepository()
    {
        return $this->app['repo.tokens'];
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }
}
