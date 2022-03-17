<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use ACL;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\BasketParticipantVoteEvent;
use Alchemy\Phrasea\Core\Event\PushEvent;
use Alchemy\Phrasea\Core\Event\ShareEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Record\RecordReference;
use DateTime;
use eventsmanager_broker;
use Exception;
use record_adapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasketSubscriber extends AbstractNotificationSubscriber
{
    public function onPush(PushEvent $event)
    {
        $params = [
            'from'    => $event->getBasket()->getPusher()->getId(),
            'to'      => $event->getBasket()->getUser()->getId(),
            'message' => $event->getMessage(),
            'ssel_id' => $event->getBasket()->getId(),
        ];

        $datas = json_encode($params);

        $mailed = false;

        if ($this->shouldSendNotificationFor($event->getBasket()->getUser(), 'eventsmanager_notify_push')) {
            $basket = $event->getBasket();

            $user_from = $event->getBasket()->getPusher();
            $user_to = $event->getBasket()->getUser();

            try {
                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $mail = MailInfoPushReceived::create($this->app, $receiver, $emitter, $params['message'], $event->getUrl());
                $mail->setBasket($basket);
                $mail->setPusher($user_from);

                if (($locale = $user_to->getLocale()) != null) {
                    $mail->setLocale($locale);
                }
                elseif (($locale1 = $user_from->getLocale()) != null) {
                    $mail->setLocale($locale1);
                }

                $this->deliver($mail, $event->hasReceipt());

                $mailed = true;
            }
            catch (Exception $e) {
                // ignore bad emails
            }
        }

        return $this->getEventsManager()->notify($params['to'], 'eventsmanager_notify_push', $datas, $mailed);
    }

    public function onShare(ShareEvent $event)
    {
        $request = $event->getRequest();
        $isFeedback = $request->request->get('isFeedback') == "1";
        $participants = $request->request->get('participants');
        $feedbackAction = $request->request->get('feedbackAction');
        if(!empty($shareExpiresDate = $request->request->get('shareExpires'))) {
            $shareExpiresDate = new DateTime($shareExpiresDate);     // d: "Y-m-d"
        }
        else {
            $shareExpiresDate = null;
        }
        if(!empty($voteExpiresDate = $request->request->get('voteExpires'))) {
            $voteExpiresDate = new DateTime($voteExpiresDate);     // d: "Y-m-d"
        }
        else {
            $voteExpiresDate = null;
        }

        $authenticatedUser = $event->getAuthenticatedUser();
        $manager = $event->getEntityManager();

        $basket = $event->getBasket();

        $basket->setWip(new DateTime());
        $manager->persist($basket);
        $manager->flush();

        $this->getEventsManager()->notify(
            $authenticatedUser->getId(),
            'eventsmanager_notify_basketwip',
            // 'eventsmanager_notify_push',
            json_encode([
                'message' => $this->app->trans('Sharing basket "%name%"...', ['%name%' => htmlentities($basket->getName())]),
            ]),
            false
        );


        $manager->beginTransaction();

        // used to check participant to be removed
        $remainingParticipantsUserId = [];
        if ($feedbackAction == 'adduser') {
            $remainingParticipantsUserId = $basket->getListParticipantsUserId();
        }

        try {
            foreach ($participants as $key => $participant) {
                
                file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export(null, true)), FILE_APPEND);

                if (!$isFeedback && $participant['usr_id'] == $basket->getUser()->getId()) {
                    // For simple "share" basket, the owner does not have to be in participants.
                    // The front may prevent this, but we fiter here anyway

                    // ignored here, so user will still be present in remainingParticipantsUserId, and removed
                    continue;
                }

                // sanity check
//                foreach (['see_others', 'usr_id', 'agree', 'modify', 'HD'] as $mandatoryParam) {
//                    if (!array_key_exists($mandatoryParam, $participant)) {
//                        throw new Exception(
//                            $this->app->trans('Missing mandatory parameter %parameter%', ['%parameter%' => $mandatoryParam])
//                        );
//                    }
//                }

                try {
                    /** @var User $participantUser */
                    $participantUser = $this->getUserRepository()->find($participant['usr_id']);
                    if ($feedbackAction == 'adduser') {
                        $remainingParticipantsUserId = array_diff($remainingParticipantsUserId, [$participant['usr_id']]);
                    }

                }
                catch (Exception $e) {
                    throw new Exception(
                        $this->app->trans('Unknown user %usr_id%', ['%usr_id%' => $participant['usr_id']])
                    );
                }
                // end of sanity check

                // if participant already exists, just update right AND CONTINUE WITH NEXT USER
                try {
                    $basketParticipant = $basket->getParticipant($participantUser);
                    $basketParticipant->setCanAgree($participant['agree']);
                    $basketParticipant->setCanModify($participant['modify']);
                    $basketParticipant->setCanSeeOthers($participant['see_others']);
                    $manager->persist($basketParticipant);
                    $manager->flush();

                    continue; // !!!
                }
                catch (Exception $e) {
                    // no-op
                }

                // here the participant did not exist, create
                $basketParticipant = $basket->addParticipant($participantUser);
                // set rights (nb: hd right is managed by acl on record for the user)
                $basketParticipant
                    ->setCanAgree($participant['agree'])
                    ->setCanModify($participant['modify'])
                    ->setCanSeeOthers($participant['see_others']);

                $manager->persist($basketParticipant);

                $acl = $this->getAclForUser($participantUser);

                // build an array of basket elements now to avoid longer calls on participants loop
                $basketElements = [];
                foreach ($basket->getElements() as $basketElement) {
                    $basketElements[] = [
                        'element' => $basketElement,
                        'ref'     => RecordReference::createFromDataboxIdAndRecordId(
                            $basketElement->getSbasId(),
                            $basketElement->getRecordId()
                        ),
                        'record'  => $basketElement->getRecord($this->app)
                    ];
                }

                foreach ($basketElements as $be) {
                    /** @var BasketElement $basketElement */
                    $basketElement = &$be['element'];
                    /** @var record_adapter $basketElementRecord */
                    $basketElementRecord = &$be['record'];
                    /** @var RecordReference $basketElementReference */
                    $basketElementReference = &$be['ref'];

                    // this is slow... why ?
                    $basketParticipantVote = $basketElement->createVote($basketParticipant);
                    //

                    if ($participant['HD']) {
                        $acl->grant_hd_on(
                        // $basketElement->getRecord($this->app),
                            $basketElementReference,
                            $authenticatedUser,
                            ACL::GRANT_ACTION_VALIDATE
                        );
                    }
                    else {
                        $acl->grant_preview_on(
                        // $basketElement->getRecord($this->app),
                            $basketElementReference,
                            $authenticatedUser,
                            ACL::GRANT_ACTION_VALIDATE
                        );
                    }

                    $manager->merge($basketElement);
                    $manager->persist($basketParticipantVote);

//                $this->getDataboxLogger($basketElementRecord->getDatabox())->log(
//                    $basketElementRecord,
//                    Session_Logger::EVENT_VALIDATE,
//                    $participantUser->getId(),
//                    ''
//                );
                }

                /** @var BasketParticipant $basketParticipant */
                $basketParticipant = $manager->merge($basketParticipant);

                $manager->flush();

                $arguments = [
                    'basket' => $basket->getId(),
                ];

                // here we email to each participant
                //
                // if we don't request the user to auth (=type his login/pwd),
                //  we generate a !!!! 'validate' !!!! token to be included as 'LOG' parameter in url
                //
                // - the 'validate' token has same expiration as validation-session (except for initiator)
                //

                if (!$this->getConf()->get(['registry', 'actions', 'enable-push-authentication']) || !$request->get('force_authentication')) {
                    if ($participantUser->getId() === $authenticatedUser->getId()) {
                        // the initiator of the validation gets a no-expire token (so he can see result after validation expiration)
                        $arguments['LOG'] = $this->getTokenManipulator()->createBasketValidationToken($basket, $participantUser, null)->getValue();
                    }
                    else {
                        // a "normal" participant/user gets an expiring token, expirationdate CAN be null
                        $arguments['LOG'] = $this->getTokenManipulator()->createBasketValidationToken($basket, $participantUser, $voteExpiresDate)->getValue();
                    }
                }

                $url = $this->app->url('lightbox_validation', $arguments);


                $receipt = $request->request->get('recept') ? $authenticatedUser->getEmail() : '';


                // send only mail if notify is needed

                // if basket is a vote and the user can vote -> "vote request email"
                // else -> "shared with you" email
                //     done during email build, from event data
                if ($request->request->get('notify') == 1) {

                    $this->getDispatcher()->dispatch(
                        PhraseaEvents::VALIDATION_CREATE,
                        new BasketParticipantVoteEvent(
                            $basketParticipant,
                            $url,
                            $request->request->get('message'),
                            $receipt,
                            (int)$request->request->get('duration'),
                            $basket->isVoteBasket(),
                            $shareExpiresDate,
                            $voteExpiresDate
                        )
                    );
                }
            }

//   !!!!!!!!!!!!!!!!!!!!!         if ($feedbackAction == 'adduser') {
            foreach ($remainingParticipantsUserId as $userIdToRemove) {
                try {
                    /** @var  User $participantUser */
                    $participantUser = $this->getUserRepository()->find($userIdToRemove);
                }
                catch (Exception $e) {
                    throw new Exception(
                        $this->app->trans('Unknown user %usr_id%', ['%usr_id%' => $userIdToRemove])
                    );
                }
                try {
                    // nb: for a vote, the owner IS participant and can't be removed
                    $basketParticipant = $basket->getParticipant($participantUser);
                    $basket->removeParticipant($basketParticipant);
                    $manager->remove($basketParticipant);
                }
                catch (Exception $e) {
                    // no-op
                }
            }
//            }
            $manager->merge($basket);
            $manager->flush();

            $manager->commit();
        }
        catch (Exception $e) {
            $manager->rollback();
        }

        $basket->setWip(NULL);
        $manager->persist($basket);
        $manager->flush();

        $this->getEventsManager()->notify(
            $authenticatedUser->getId(),
            'eventsmanager_notify_basketwip',
            // 'eventsmanager_notify_push',
            json_encode([
                'message' => $this->app->trans('Basket %name% is shared', ['%name%' => htmlentities($basket->getName())]),
            ]),
            false
        );

    }

    public static function getSubscribedEvents()
    {
        return [
            /** @uses onPush */
            PhraseaEvents::BASKET_PUSH => 'onPush',
            /** @uses onShare */
            PhraseaEvents::BASKET_SHARE => 'onShare',
        ];
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @param User $user
     * @return ACL
     */
    public function getAclForUser(User $user)
    {
        $aclProvider = $this->getAclProvider();
        return $aclProvider->get($user);
    }

    /**
     * @return ACLProvider
     */
    public function getAclProvider()
    {
        return $this->app['acl'];
    }

    /**
     * @return PropertyAccess
     */
    protected function getConf()
    {
        return $this->app['conf'];
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->app['dispatcher'];
    }

    /**
     * @return eventsmanager_broker
     */
    private function getEventsManager()
    {
        return $this->app['events-manager'];
    }
}
