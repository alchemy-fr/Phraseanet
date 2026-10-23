<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordTokenValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PasswordToken) {
            throw new UnexpectedTypeException($constraint, PasswordToken::class);
        }

        if (!$constraint->isValid($value)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
