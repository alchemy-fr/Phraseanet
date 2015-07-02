<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Phrasea;

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
        // check failures and throws a RequireCaptchaException is needed
        $this->failure->checkFailures($username, $request);

        $usr_id = $this->auth->getUsrId($username, $password, $request);

        if (null === $usr_id) {
            $this->failure->saveFailure($username, $request);
            // check failures
            $this->failure->checkFailures($username, $request);
        }

        return $usr_id;
    }
}
