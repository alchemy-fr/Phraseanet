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

use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Symfony\Component\HttpFoundation\Request;

interface PasswordAuthenticationInterface
{
    /**
     * Validates credentials for a web based authentication
     *
     * @param string  $username
     * @param string  $password
     * @param Request $request
     *
     * @return integer|null
     *
     * @throws AccountLockedException
     * @throws RequireCaptchaException
     */
    public function getUsrId($username, $password, Request $request);
}
