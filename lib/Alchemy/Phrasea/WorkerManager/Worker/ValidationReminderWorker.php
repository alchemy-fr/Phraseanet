<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\BasketParticipantRepository;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ValidationReminderWorker implements WorkerInterface
{
    use NotifierAware;

    private $app;
    private $logger;

    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    /** @var  WorkerRunningJobRepository $repoWorkerJob */
    private $repoWorkerJob;

    public function __construct(PhraseaApplication $app)
    {
        $this->app              = $app;
        $this->messagePublisher = $this->app['alchemy_worker.message.publisher'];
        $this->logger           = $this->app['alchemy_worker.logger'];
    }

    public function process(array $payload)
    {
        $this->setDelivererLocator(new LazyLocator($this->app, 'notification.deliverer'));

        $timeLeftPercent = (int)$this->getConf()->get(['registry', 'actions', 'validation-reminder-time-left-percent']);

        if ($timeLeftPercent == null) {
            $this->logger->error('validation-reminder-time-left-percent is not set in the configuration!');

            return 0;
        }

        foreach ($this->getBasketParticipantRepository()->findNotConfirmedAndNotRemindedParticipantsByTimeLeftPercent($timeLeftPercent, new DateTime()) as $participant) {

            $basket = $participant->getBasket();

            $expiresDate = $basket->getVoteExpires();
            $diffInterval = $expiresDate->diff(new DateTime());

            if ($diffInterval->days) {
                $timeLeft = $diffInterval->format(' %d days %Hh%I ');
            } else {
                $timeLeft = $diffInterval->format(' %Hh%I ');
            }

            $canSend = true;

            $user = $participant->getUser();        // always ok !
            try {
                $str_email = $user->getEmail();     // force to hydrate
            } catch (\Exception $e) {
                $this->logger->error('user not found!');
                $canSend = false;
            }

            $emails[] =

            // find the token if exists
            // nb : a validation may have not generated tokens if forcing auth was required upon creation
            $token = null;
            try {
                $token = $this->getTokenRepository()->findValidationToken($basket, $user);
            }
            catch (\Exception $e) {
                // not unique token ? should not happen
                $canSend = false;
            }

            if(!$canSend) {
                continue;
            }

            if(!is_null($token)) {
                $url = $this->app->url('lightbox_validation', ['basket' => $basket->getId(), 'LOG' => $token->getValue()]);
            } else {
                $url = $this->app->url('lightbox_validation', ['basket' => $basket->getId()]);
            }

            $this->doRemind($participant, $basket, $url, $timeLeft);
        }

        $this->getEntityManager()->flush();
    }

    private function doRemind(BasketParticipant $participant, Basket $basket, $url, $timeLeft)
    {
        $params = [
            'from'    => $basket->getVoteInitiator()->getId(),
            'to'      => $participant->getUser()->getId(),
            'ssel_id' => $basket->getId(),
            'url'     => $url,
            'time_left'=> $timeLeft
        ];

        $datas = json_encode($params);

        $mailed = false;

        $userFrom = $basket->getVoteInitiator();
        $userTo = $participant->getUser();

        if ($this->shouldSendNotificationFor($participant->getUser(), 'eventsmanager_notify_validationreminder')) {
            $readyToSend = false;
            $title = $receiver = $emitter = null;
            try {
                $title = $basket->getName();

                $receiver = Receiver::fromUser($userTo);
                $emitter = Emitter::fromUser($userFrom);

                $readyToSend = true;
            }
            catch (\Exception $e) {
                // no-op
            }

            if ($readyToSend) {
                $this->logger->info(sprintf('    -> remind "%s" from "%s" to "%s"', $title, $emitter->getEmail(), $receiver->getEmail()));

                $mail = MailInfoValidationReminder::create($this->app, $receiver, $emitter);
                $mail->setTimeLeft($timeLeft);
                $mail->setButtonUrl($params['url']);
                $mail->setTitle($title);

                if (($locale = $userTo->getLocale()) != null) {
                    $mail->setLocale($locale);
                } elseif (($locale1 = $userFrom->getLocale()) != null) {
                    $mail->setLocale($locale1);
                }

                $this->deliver($mail);
                $mailed = true;

                $participant->setReminded(new DateTime('now'));
                $this->getEntityManager()->persist($participant);
            }
        }

        return $this->app['events-manager']->notify($params['to'], 'eventsmanager_notify_validationreminder', $datas, $mailed);
    }

    /**
     * @param User $user
     * @param $type
     * @return mixed
     */
    private function shouldSendNotificationFor(User $user, $type)
    {
        return $this->app['settings']->getUserNotificationSetting($user, $type);
    }

    /**
     * @return PropertyAccess
     */
    private function getConf()
    {
        return $this->app['conf'];
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->app['orm.em'];
    }

    /**
     * @return BasketParticipantRepository
     */
    private function getBasketParticipantRepository()
    {
        return $this->app['repo.basket-participants'];
    }

    /**
     * @return TokenRepository
     */
    private function getTokenRepository()
    {
        return $this->app['repo.tokens'];
    }
}
