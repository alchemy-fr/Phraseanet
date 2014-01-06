<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected $generator;
    protected $session;

    /**
     * @param UrlGenerator $generator
     *
     * @return ProviderInterface
     */
    public function setUrlGenerator(UrlGenerator $generator)
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * @return UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * @param SessionInterface $session
     *
     * @return ProviderInterface
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplates(Identity $identity)
    {
        return array();
    }

    /**
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    protected function createState()
    {
        return md5(uniqid(microtime(true), true));
    }
}
