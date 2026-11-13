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

class PhraseaCLIExceptionHandler
{
    /**
     * Registers the exception handler as the PHP exception handler.
     *
     * @return static
     */
    public static function register()
    {
        $handler = new static();

        set_exception_handler([$handler, 'handle']);

        return $handler;
    }

    public function handle(\Throwable $exception)
    {
        throw $exception;
    }
}
