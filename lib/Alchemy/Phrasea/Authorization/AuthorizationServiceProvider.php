<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Authorization;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Model\Entities\User;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

class AuthorizationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.security_token'] = $app->share(function (PhraseaApplication $app) {
            $user = $app['authentication']->getUser();

            if ($user instanceof User) {
                return new PreAuthenticatedToken((string)$user->getId(), null, 'fake', ['ROLE_USER']);
            }

            return new AnonymousToken('fake', 'anon.', []);
        });

        $app['phraseanet.access_manager'] = $app->share(function (PhraseaApplication $app) {
            return new AccessDecisionManager($app['phraseanet.voters']);
        });
        $app['phraseanet.voters'] = $app->share(function () {
            return [new MockedAuthenticatedVoter()];
        });

        $app['phraseanet.authorization_checker'] = $app->share(function (PhraseaApplication $app) {
            return new AuthorizationChecker(
                $app['phraseanet.access_manager'],
                $app['phraseanet.security_token']
            );
        });
    }

    public function boot(Application $app)
    {
        // Nothing to do
    }
}
