<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use ACL;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\BasketParticipantVoteEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Record\RecordReference;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use record_adapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShareBasketWorker implements WorkerInterface
{
    private $app;

    public function __construct(PhraseaApplication $app)
    {
        $this->app = $app;
    }

    public function process(array $payload)
    {
        $isFeedback = $payload['isFeedback'];
        $participants = $payload['participants'];
        $feedbackAction = $payload['feedbackAction'];
        $shareExpiresDate = $payload['shareExpires'];
        $voteExpiresDate = $payload['voteExpires'];

        if (!empty($shareExpiresDate )) {
            $shareExpiresDate = new DateTime($shareExpiresDate);     // d: "Y-m-d"
        } else {
            $shareExpiresDate = null;
        }

        if (!empty($voteExpiresDate)) {
            $voteExpiresDate = new DateTime($voteExpiresDate);     // d: "Y-m-d"
        } else {
            $voteExpiresDate = null;
        }

        $authenticatedUser = $this->getUserRepository()->find($payload['authenticatedUserId']);

        $manager = $this->getEntityManager();

        /** @var Basket $basket */
        $basket = $this->getBasketRepository()->find($payload['basketId']);

        $basket->setWip(new DateTime());
        $manager->persist($basket);
        $manager->flush();

        $this->getEventsManager()->notify(
            $authenticatedUser->getId(),
            'eventsmanager_notify_basketwip',
            // 'eventsmanager_notify_push',
            json_encode([
                'message' => $this->app->trans('notification:: Sharing basket "%name%"...', ['%name%' => htmlentities($basket->getName())]),
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

                if (!$this->getConf()->get(['registry', 'actions', 'enable-push-authentication']) || !$payload['force_authentication']) {
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


                $receipt = !empty($payload['recept']) ? $authenticatedUser->getEmail() : '';


                // send only mail if notify is needed

                // if basket is a vote and the user can vote -> "vote request email"
                // else -> "shared with you" email
                //     done during email build, from event data
                if ($payload['notify'] == 1) {

                    $this->getDispatcher()->dispatch(
                        PhraseaEvents::VALIDATION_CREATE,
                        new BasketParticipantVoteEvent(
                            $basketParticipant,
                            $url,
                            !empty($payload['message']) ? $payload['message'] : '',
                            $receipt,
                            !empty($payload['duration']) ? (int)$payload['duration'] : 0,
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
                } catch (Exception $e) {
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
                'message' => $this->app->trans('notification:: Basket %name% is shared', ['%name%' => htmlentities($basket->getName())]),
            ]),
            false
        );

        $this->getLogger()->info("Basket with Id " . $basket->getId() . " successfully shared !");
    }

    /**
     * @return \eventsmanager_broker
     */
    private function getEventsManager()
    {
        return $this->app['events-manager'];
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @return PropertyAccess
     */
    protected function getConf()
    {
        return $this->app['conf'];
    }

    /**
     * @return BasketRepository
     */
    private function getBasketRepository()
    {
        return $this->app['repo.baskets'];
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->app['orm.em'];
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
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getDispatcher()
    {
        return $this->app['dispatcher'];
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return $this->app['alchemy_worker.logger'];
    }
}
