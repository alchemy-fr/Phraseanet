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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

abstract class AbstractProvider implements ProviderInterface
{
    protected $generator;
    protected $session;

    /**
     * @var bool
     */
    private $display = true;
    /**
     * @var string
     */
    private $title;

    protected function __construct(UrlGenerator $generator, SessionInterface $session)
    {
        $this->generator = $generator;
        $this->session = $session;
    }

    public function getDisplay(): bool
    {
        return $this->display;
    }

    /**
     * @param bool $display
     */
    public function setDisplay(bool $display)
    {
        $this->display = $display;
    }

    /**
     * @deprecated  replaced by getTitle()
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTitle();
    }

    /**
     * more clear that getName because the key in conf is "title"
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

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
        return [];
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
