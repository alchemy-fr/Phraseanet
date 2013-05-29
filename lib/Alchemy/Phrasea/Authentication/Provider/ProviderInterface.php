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
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * Logout from the provider, removes the token if possible
     *
     * @throws RuntimeException In case logout fails.
     */
    public function logout();

    /**
     * This method is called on provider callback, whenever the auth was
     * successful or failure.
     *
     * @param Application $app
     * @param Request $request
     *
     * @throws NotAuthenticatedException In case the authentication failed.
     */
    public function onCallback(Request $request);

    /**
     * Returns the identity
     *
     * @return Identity
     *
     * @throws NotAuthenticatedException In case the provider is not connected
     */
    public function getIdentity();

    /**
     * Returns a Token
     *
     * @return Token
     *
     * @throws NotAuthenticatedException In case the provider is not connected
     */
    public function getToken();

    /**
     * Get an URI representing the provider
     *
     * @return string
     */
    public function getIconURI();

    /**
     * Returns an array of templates related to the provided Identity
     *
     * @param Identity $identity
     *
     * @return array
     */
    public function getTemplates(Identity $identity);

    /**
     * Creates a provider
     *
     * @param UrlGenerator $generator
     * @param SessionInterface $session
     * @param array $options
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options);
}
