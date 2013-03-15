<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;

interface ProviderInterface
{
    /**
     * Returns a unique identifier for the provider.
     *
     * Allowed characters are a-z and - (minus).
     * Examples : twitter => Twitter
     *            google-plus => GooglePlus
     *
     * @return string
     */
    public function getId();

    /**
     * Returns an UTF-8 name for the provider.
     *
     * @return string
     */
    public function getName();

    /**
     * Redirects to the actual authentication provider
     *
     * @return RedirectResponse
     */
    public function authenticate();


    /**
     * This method is called on provider callback, whenever the auth was
     * successful or failure.
     *
     * @param Application $app
     * @param Request $request
     */
    public function onCallback(Request $request);

    /**
     * Returns the identity
     *
     * @return Identity
     */
    public function getIdentity();

    /**
     * Returns a Token
     *
     * @return Token
     */
    public function getToken();

    /**
     * Get an URI representing the provider
     *
     * @return string
     */
    public function getIconURI();

    /**
     * Creates a provider
     *
     * @param UrlGenerator $generator
     * @param SessionInterface $session
     * @param array $options
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options);
}
