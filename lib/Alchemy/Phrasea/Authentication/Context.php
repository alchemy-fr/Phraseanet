<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class Context
{
    const CONTEXT_OAUTH2_TOKEN = 0;
    const CONTEXT_NATIVE = 1;
    const CONTEXT_GUEST = 2;
    const CONTEXT_OAUTH2_NATIVE = 3;

    private $context;

    public function __construct($context)
    {
        $this->setContext($context);
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        if (false === in_array($context, [static::CONTEXT_OAUTH2_NATIVE, static::CONTEXT_OAUTH2_TOKEN, static::CONTEXT_NATIVE, static::CONTEXT_GUEST], true)) {
            throw new InvalidArgumentException(sprintf('`%s` is not a valid context', $context));
        }

        $this->context = $context;

        return $this;
    }
}
