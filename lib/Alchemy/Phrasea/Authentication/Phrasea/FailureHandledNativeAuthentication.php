<?php

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;

class FailureHandledNativeAuthentication implements PasswordAuthenticationInterface
{
    private $auth;
    private $failure;

    public function __construct(PasswordAuthenticationInterface $auth, FailureManager $failure)
    {
        $this->auth = $auth;
        $this->failure = $failure;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsrId($username, $password, Request $request)
    {
        // check failures and throws a RequireCaptchaExeption is needed
        $this->failure->checkFailures($username, $request);

        $usr_id = $this->auth->getUsrId($username, $password, $request);

        if (null === $usr_id) {
            $this->failure->saveFailure($username, $request);
            // check failures
            $this->failure->checkFailures($username, $request);
        }

        return $usr_id;
    }

    /**
     * {@inheritdoc}
     *
     * @return FailureHandledNativeAuthentication
     */
    public static function create(Application $app)
    {
        return new static(NativeAuthentication::create($app));
    }
}
