<?php

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecoveryService
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var Deliverer
     */
    private $mailer;

    /**
     * @var TokenManipulator
     */
    private $tokenManipulator;

    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param Application $application
     * @param Deliverer $mailer
     * @param TokenManipulator $tokenManipulator
     * @param TokenRepository $tokenRepository
     * @param UserManipulator $userManipulator
     * @param UserRepository $userRepository
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        Application $application,
        Deliverer $mailer,
        TokenManipulator $tokenManipulator,
        TokenRepository $tokenRepository,
        UserManipulator $userManipulator,
        UserRepository $userRepository,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->application = $application;
        $this->mailer = $mailer;
        $this->tokenManipulator = $tokenManipulator;
        $this->tokenRepository = $tokenRepository;
        $this->userManipulator = $userManipulator;
        $this->userRepository = $userRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param $email
     * @param bool $notifyUser
     * @return string
     * @throws InvalidArgumentException
     */
    public function requestPasswordResetToken($email, $notifyUser = true)
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            throw new InvalidArgumentException('phraseanet::erreur: Le compte n\'a pas ete trouve');
        }

        return $this->requestPasswordResetTokenByUser($user, $notifyUser);
    }

    /**
     * @param $login
     * @param bool $notifyUser
     * @return string
     * @throws InvalidArgumentException
     */
    public function requestPasswordResetTokenByLogin($login, $notifyUser = true)
    {
        $user = $this->userRepository->findByLogin($login);

        if (! $user) {
            throw new InvalidArgumentException('phraseanet::erreur: Le compte n\'a pas ete trouve');
        }

        return $this->requestPasswordResetTokenByUser($user, $notifyUser);
    }

    /**
     * @param User $user
     * @param bool $notifyUser
     * @return string
     */
    private function requestPasswordResetTokenByUser(User $user, $notifyUser = true)
    {
        $receiver = Receiver::fromUser($user);
        $token = $this->tokenManipulator->createResetPasswordToken($user);

        if ($notifyUser) {
            $url = $this->urlGenerator->generate('login_renew_password', [ 'token' => $token->getValue() ], true);

            $mail = MailRequestPasswordUpdate::create($this->application, $receiver);
            $mail->setLogin($user->getLogin());
            $mail->setButtonUrl($url);
            $mail->setExpiration($token->getExpiration());

            if (($locale = $user->getLocale()) != null) {
                $mail->setLocale($locale);
            }

            $this->mailer->deliver($mail);
        }

        return $token->getValue();
    }

    public function resetPassword($resetToken, $newPassword)
    {
        $token = $this->tokenRepository->findValidToken($resetToken);

        if ($token === null || $token->getType() != TokenManipulator::TYPE_PASSWORD) {
            $this->application->abort(401, 'A token is required');
        }

        $this->userManipulator->setPassword($token->getUser(), $newPassword);
        $this->tokenManipulator->delete($token);
    }
}
