<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Exception;

use Alchemy\Phrasea\Exception\RuntimeException;

class JsonValidationException extends RuntimeException
{
    protected $errors;

    public function __construct($message, $errors = [])
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
