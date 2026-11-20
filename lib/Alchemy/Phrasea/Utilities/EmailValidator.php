<?php

namespace Alchemy\Phrasea\Utilities;

use Egulias\EmailValidator\EmailValidator as RfcEmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * Validates email addresses against RFC 5322.
 */
class EmailValidator
{
    public static function isValid($email)
    {
        if (!is_string($email) || '' === $email) {
            return false;
        }

        return (new RfcEmailValidator())->isValid($email, new RFCValidation());
    }
}
