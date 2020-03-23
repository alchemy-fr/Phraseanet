<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
    protected $id;
    protected $display;
    protected $title;
    protected $options;

    protected function __construct(UrlGenerator $generator, SessionInterface $session, $id, $display, $title, array $options)
    {
        $this->generator = $generator;
        $this->session = $session;
        $this->id = $id;
        $this->display = $display;
        $this->title = $title;
        $this->options = $options;
    }

    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->title;
    }

    /**
     * Used only for unit-testing
     *
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
     * Used only for unit-testing
     *
     * @return UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * Used only for unit-testing
     *
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
        return [];
    }

    /**
     * Used only for unit-testing
     *
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
