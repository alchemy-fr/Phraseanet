<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use ACL;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\BasketParticipantVoteEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Record\RecordReference;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
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
        $manager = $this->getEntityManager();
        $manager->beginTransaction();
        $date = new \DateTime();

        $message = [
            'message_type'  => MessagePublisher::SHARE_BASKET_TYPE,
            'payload'       => $payload
        ];

        try {
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setWork(MessagePublisher::SHARE_BASKET_TYPE)
                ->setPayload($message)
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
            ;

            $manager->persist($workerRunningJob);

            $manager->flush();

            $manager->commit();
        } catch (\Exception $e) {
            $manager->rollback();
        }

        $isFeedback = $payload['isFeedback'];
        $participants = $payload['participants'];
        $feedbackAction = $payload['feedbackAction'];
        $shareExpiresDate = $payload['shareExpires'];
        $voteExpiresDate = $payload['voteExpires'];
        $notSendReminder = empty($payload['send_reminder']) ? true : false ;
        $expireOn = null;

        $n_participants = 0;
        // file_put_contents("./tmp/phraseanet-log.txt", sprintf("CWD = %s\n\n%s; %d participants in payload\n", getcwd(), $_t0 = time(), count($participants)), FILE_APPEND);

        if (!empty($shareExpiresDate )) {
            $shareExpiresDate = new DateTime($shareExpiresDate);     // d: "Y-m-d"
            $expireOn = $payload['shareExpires'] . " 23:59:59";
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



        // used to check participant to be removed
        $remainingParticipantsUserId = [];
        if ($feedbackAction == 'adduser') {
            $remainingParticipantsUserId = $basket->getListParticipantsUserId();
        }

        // build an array of basket elements now to avoid longer calls on participants loop
        $basketElements = [];
        foreach ($basket->getElements() as $basketElement) {
            $basketElements[] = [
                'element' => $basketElement,
                'ref'     => RecordReference::createFromDataboxIdAndRecordId(
                    $basketElement->getSbasId(),
                    $basketElement->getRecordId()
                ),
 //               'record'  => $basketElement->getRecord($this->app)
            ];
        }
        // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; %d records in basket\n", time(), count($basketElements)), FILE_APPEND);

        $basketUserId = $basket->getUser()->getId();
        try {
            foreach ($participants as $key => $participant) {

                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("\n%s; participant n = %d\n", time(), $n_participants++), FILE_APPEND);

                if (!$isFeedback && $participant['usr_id'] == $basketUserId) {
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
                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; searching participant\n", time()), FILE_APPEND);
                try {
                    $basketParticipant = $basket->getParticipant($participantUser);
                    $basketParticipant->setCanAgree($participant['agree']);
                    $basketParticipant->setCanModify($participant['modify']);
                    $basketParticipant->setCanSeeOthers($participant['see_others']);

                    if ($notSendReminder) {
                        // column reminded to be not null
                        $basketParticipant->setReminded(new DateTime());
                    }

                    $manager->persist($basketParticipant);
                    $manager->flush();

                    $acl = $this->getAclForUser($participantUser);

                    // update right on records_rights
                    foreach ($basketElements as $be) {
                        if ($participant['HD']) {
                            $acl->grant_hd_on(
                            // $basketElementReference,
                                $be['ref'],
                                $authenticatedUser,
                                ACL::GRANT_ACTION_VALIDATE,
                                $expireOn
                            );
                        } else {
                            $acl->grant_preview_on(
                            // $basketElementReference,
                                $be['ref'],
                                $authenticatedUser,
                                ACL::GRANT_ACTION_VALIDATE
                            );
                        }
                    }

                    // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; participant already exists -> next...\n", time()), FILE_APPEND);

                    continue; // !!!
                }
                catch (Exception $e) {
                    // no-op
                }
                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; participant not found\n", time()), FILE_APPEND);

                // here the participant did not exist, create
                $basketParticipant = $basket->addParticipant($participantUser);
                // set rights (nb: hd right is managed by acl on record for the user)
                $basketParticipant
                    ->setCanAgree($participant['agree'])
                    ->setCanModify($participant['modify'])
                    ->setCanSeeOthers($participant['see_others']);

                if ($notSendReminder) {
                    // column reminded to be not null
                    $basketParticipant->setReminded(new DateTime());
                }

                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; participant created\n", time()), FILE_APPEND);

                $manager->persist($basketParticipant);

                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; participant persisted\n", time()), FILE_APPEND);

                $acl = $this->getAclForUser($participantUser);

                $nVotes = 0;
                foreach ($basketElements as $be) {
//                    /** @var BasketElement $basketElement * /
//                    $basketElement = &$be['element'];
//                    /** @var record_adapter $basketElementRecord * /
//                    $basketElementRecord = &$be['record'];
//                    / ** @var RecordReference $basketElementReference * /
//                    $basketElementReference = &$be['ref'];

                    // this is slow... why ?
//                    $basketParticipantVote = $basketElement->createVote($basketParticipant);
                    //

                    if ($participant['HD']) {
                        $acl->grant_hd_on(
                            // $basketElementReference,
                            $be['ref'],
                            $authenticatedUser,
                            ACL::GRANT_ACTION_VALIDATE,
                            $expireOn
                        );
                    }
                    else {
                        $acl->grant_preview_on(
                            // $basketElementReference,
                            $be['ref'],
                            $authenticatedUser,
                            ACL::GRANT_ACTION_VALIDATE
                        );
                    }

          //          $manager->merge($basketElement);
          //          $manager->persist($basketParticipantVote);

//                $this->getDataboxLogger($basketElementRecord->getDatabox())->log(
//                    $basketElementRecord,
//                    Session_Logger::EVENT_VALIDATE,
//                    $participantUser->getId(),
//                    ''
//                );

                    $nVotes++;
                }

//                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; %d votes created\n", time(), $nVotes), FILE_APPEND);
/*
                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; %d acl set\n", time(), $nVotes), FILE_APPEND);
*/
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

                // send only mail if notify is needed

                // if basket is a vote and the user can vote -> "vote request email"
                // else -> "shared with you" email
                //     done during email build, from event data
                if ($payload['notify'] == 1) {

                    if (!$this->getConf()->get(['registry', 'actions', 'enable-push-authentication']) || !$payload['force_authentication']) {
                        if ($participantUser->getId() === $authenticatedUser->getId()) {
                            // the initiator of the validation gets a no-expire token (so he can see result after validation expiration)
                            $arguments['LOG'] = $this->getTokenManipulator()->createBasketValidationToken($basket, $participantUser, null)->getValue();
                        }
                        else {
                            // a "normal" participant/user gets an expiring token, expirationdate CAN be null
                            $arguments['LOG'] = $this->getTokenManipulator()->createBasketValidationToken($basket, $participantUser, $voteExpiresDate)->getValue();
                        }

                        // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; token generated\n", time()), FILE_APPEND);
                    }

                    $url = $this->app->url('lightbox_validation', $arguments);
                    $receipt = !empty($payload['recept']) ? $authenticatedUser->getEmail() : '';

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

                    // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; user notified\n", time()), FILE_APPEND);

                }

                unset($basketParticipant, $participantUser, $participant);
                gc_collect_cycles();

                // file_put_contents("./tmp/phraseanet-log.txt", sprintf("%s; gc_collect_cycles done\n", time()), FILE_APPEND);

            }

//   !!!!!!!!!!!!!!!!!!!!!         if ($feedbackAction == 'adduser') {

            // file_put_contents("./tmp/phraseanet-log.txt", sprintf("\n%s; %d participants to remove\n", time(), count($remainingParticipantsUserId)), FILE_APPEND);

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

//            $manager->commit();
        }
        catch (Exception $e) {
            // file_put_contents("./tmp/phraseanet-log.txt", sprintf("\n%s; *** %s\n", time(), $e->getMessage()), FILE_APPEND);

//            $manager->rollback();
        }

        // file_put_contents("./tmp/phraseanet-log.txt", sprintf("\n%s; end of participants loop\n", time()), FILE_APPEND);

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

        if ($workerRunningJob != null) {
            $workerRunningJob
                ->setStatus(WorkerRunningJob::FINISHED)
                ->setFinished(new \DateTime('now'))
            ;

            $manager->persist($workerRunningJob);

            $manager->flush();
        }

        // file_put_contents("./tmp/phraseanet-log.txt", sprintf("\n%s; ==== END (N = %d ; dT = %d ==> %0.2f / sec) ====\n\n", time(), $n_participants, time()-$_t0, $n_participants/(max(time()-$_t0, 0.001))), FILE_APPEND);

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
