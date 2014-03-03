<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

class PhraseaCLIExceptionHandler extends SymfonyExceptionHandler
{
    public function handle(\Exception $exception)
    {
        throw $exception;
    }
}
