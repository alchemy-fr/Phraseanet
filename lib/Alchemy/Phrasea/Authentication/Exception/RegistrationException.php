<?php

namespace Alchemy\Phrasea\Authentication\Exception;

class RegistrationException extends \RuntimeException
{
    const ACCOUNT_ALREADY_UNLOCKED = 1;
}
