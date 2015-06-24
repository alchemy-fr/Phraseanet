<?php

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\RecoveryException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecoveryService
{

    /**
     * @var Application
     */
    private $app;

    /**
     * @var \random
     */
    private $tokenGenerator;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param Application $app
     * @param \random $tokenGenerator
     * @param UrlGeneratorInterface $urlGenerator
     * @param Deliverer $mailer
     */
    public function __construct(Application $app,
                                \random $tokenGenerator,
                                UrlGeneratorInterface $urlGenerator,
                                Deliverer $mailer)
    {
        $this->app = $app;
        $this->tokenGenerator = $tokenGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->mailer = $mailer;
    }

    /**
     * @param $email
     * @param bool $notifyUser
     * @return bool
     * @throws RecoveryException
     * @throws InvalidArgumentException
     */
    public function requestPasswordResetToken($email, $notifyUser = true)
    {
        try {
            $user = \User_Adapter::getInstance(\User_Adapter::get_usr_id_from_email($this->app, $email), $this->app);
            $expirationDate = new \DateTime('+1 day');
            $token = $this->tokenGenerator->getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), $expirationDate);
        } catch (\Exception_InvalidArgument $e) {
            throw new RecoveryException('Unable to generate a token.');
        } catch (\Exception $e) {
            throw new InvalidArgumentException('phraseanet::erreur: Le compte n\'a pas ete trouve', 0, $e);
        }

        if (!$token) {
            throw new RecoveryException('Unable to generate a token.');
        }

        if ($notifyUser) {
            $this->notifyPasswordRequestProcessed($user, $expirationDate, $token);
        }

        return $token;
    }

    private function notifyPasswordRequestProcessed(\User_Adapter $user, \DateTime $expirationDate, $token)
    {
        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException('Invalid email address', 0, $e);
        }

        $url = $this->urlGenerator->generate(
            'login_renew_password',
            array('token' => $token),
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        /** @var MailRequestPasswordUpdate $mail */
        $mail = MailRequestPasswordUpdate::create($this->app, $receiver);
        $mail->setLogin($user->get_login());
        $mail->setButtonUrl($url);
        $mail->setExpiration($expirationDate);

        $this->mailer->deliver($mail);
        $this->app->addFlash('info', _('phraseanet:: Un email vient de vous etre envoye'));
    }
}