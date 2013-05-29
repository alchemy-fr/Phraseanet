<?php

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Symfony\Component\HttpFoundation\Request;

interface PasswordAuthenticationInterface
{
    /**
     * Validates credentials for a web based authentication
     *
     * @param string $username
     * @param string $password
     * @param Request $request
     *
     * @return integer|null
     *
     * @throws AccountLockedException
     * @throws RequireCaptchaException
     */
    public function getUsrId($username, $password, Request $request);

    /**
     * Factory for the class
     *
     * @param Application $app
     */
    public static function create(Application $app);
}
