<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Response;

/**
 * Used as a temporary fix for https://github.com/fabpot/Silex/issues/438
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class API_V1_Response extends Response
{
    private $originalStatusCode;

    public function setOriginalStatusCode($code)
    {
        $this->originalStatusCode = $code;
    }

    public function getOriginalStatusCode()
    {
        return $this->originalStatusCode;
    }
}