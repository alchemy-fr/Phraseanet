<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder;
use Alchemy\Phrasea\Notification\Receiver;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendValidationRemindersCommand extends Command
{
    use NotifierAware;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /** @var bool */
    private $dry;


    public function __construct( /** @noinspection PhpUnusedParameterInspection */ $name = null)
    {
        parent::__construct('validation:remind');

        $this->setDescription('Send validation reminders.');
        $this->addOption('dry',null, InputOption::VALUE_NONE,'dry run, list but don\'t act');
    }


    /**
     * sanity check the cmd line options
     *
     * @return bool
     */
    protected function sanitizeArgs()
    {
        $this->dry  = $this->input->getOption('dry') ? true : false;

        return true;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->setDelivererLocator(new LazyLocator($this->container, 'notification.deliverer'));

        if(!$this->sanitizeArgs()) {
            return -1;
        }

        try {
            $date = new DateTime('+' . (int)$this->getConf()->get(['registry', 'actions', 'validation-reminder-days']) . ' days');
        }
        catch (Exception $e) {
            $output->writeln('<error>Bad or unset setting "registry.actions.validation-reminder-days"</error>');
            return 1;
        }

        if($this->dry) {
            $this->output->writeln('<info>dry mode : emails will NOT be sent</info>');
        }


        foreach ($this->getValidationParticipantRepository()->findNotConfirmedAndNotRemindedParticipantsByExpireDate($date) as $participant) {
            $validationSession = $participant->getSession();
            $basket = $validationSession->getBasket();
            $user = $participant->getUser();

            // fu..ing doctrine : we can get user id if user does not exists ! we must try to hydrate to get an exception !
            try {
                $user->getEmail();
            }
            catch (Exception $e) {
                $output->writeln(sprintf('<error>user id %s for participant id %s not found</error>', $user->getId(), $participant->getId()));
                continue;
            }

            $token = null;
            try {
                $token = $this->getTokenRepository()->findValidationToken($basket, $user);
            }
            catch (Exception $e) {
                $output->writeln(sprintf('<error>error finding token for user "%s" (id=%s) on basket id %s</error>', $user->getEmail(), $user->getId(), $basket->getId()));
            }

            if ($token === null) {
                $output->writeln(sprintf('<error>token not found for user "%s" (id=%s) on basket id %s</error>', $user->getEmail(), $user->getId(), $basket->getId()));
                continue;
            }

            $url = $this->container->url('lightbox_validation', ['basket' => $basket->getId(), 'LOG' => $token->getValue()]);

            // $this->dispatch(PhraseaEvents::VALIDATION_REMINDER, new ValidationEvent($participant, $basket, $url));
            $this->doRemind($participant, $basket, $url);
        }

        $this->getEntityManager()->flush();

        return 0;
    }

    private function doRemind(ValidationParticipant $participant, Basket $basket, $url)
    {
        $params = [
            'from'    => $basket->getValidation()->getInitiator()->getId(),
            'to'      => $participant->getUser()->getId(),
            'ssel_id' => $basket->getId(),
            'url'     => $url,
        ];

        $datas = json_encode($params);

        $mailed = false;

        $user_from = $basket->getValidation()->getInitiator();
        $user_to = $participant->getUser();

        if ($this->shouldSendNotificationFor($participant->getUser(), 'eventsmanager_notify_validationreminder')) {
            $readyToSend = false;
            $title = $receiver = $emitter = null;
            try {
                $title = $basket->getName();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            }
            catch (Exception $e) {
                // no-op
            }

            if ($readyToSend) {
                $this->output->writeln(sprintf('sending "%s" from "%s" to "%s"', $title, $receiver->getEmail(), $emitter->getEmail()));
                if(!$this->dry) {
                    // for real
                    $mail = MailInfoValidationReminder::create($this->container, $receiver, $emitter);
                    $mail->setButtonUrl($params['url']);
                    $mail->setTitle($title);

                    $this->deliver($mail);
                    $mailed = true;

                    $participant->setReminded(new DateTime('now'));
                    $this->getEntityManager()->persist($participant);
                }
            }
        }

        return $this->container['events-manager']->notify($params['to'], 'eventsmanager_notify_validationreminder', $datas, $mailed);
    }



    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->container['orm.em'];
    }

    /**
     * @return PropertyAccess
     */
    protected function getConf()
    {
        return $this->container['conf'];
    }

    /**
     * @return ValidationParticipantRepository
     */
    private function getValidationParticipantRepository()
    {
        return $this->container['repo.validation-participants'];
    }

    /**
     * @return TokenRepository
     */
    private function getTokenRepository()
    {
        return $this->container['repo.tokens'];
    }

    /**
     * @param User $user
     * @param $type
     * @return mixed
     */
    protected function shouldSendNotificationFor(User $user, $type)
    {
        return $this->container['settings']->getUserNotificationSetting($user, $type);
    }
}