<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class is used to provided CRSF token in PHPUNIT test suite.
 */
class CsrfTestProvider implements CsrfProviderInterface
{
    public function generateCsrfToken($intention)
    {
        return mt_rand();
    }

    public function isCsrfTokenValid($intention, $token)
    {
        return true;
    }
}