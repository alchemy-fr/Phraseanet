<?php

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;

/**
 * This class is used to provide CRSF token in PHPUNIT test suite.
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
