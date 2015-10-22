<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Core\Event\Listener;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Core\Event\ApiOAuth2EndEvent;
use Alchemy\Phrasea\Core\Event\ApiOAuth2StartEvent;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthListener
{
    /** @var array */
    private $verifyOptions = [
        'scope'            => null,
        'exit_not_present' => true,
        'exit_invalid'     => true,
        'exit_expired'     => true,
        'exit_scope'       => true,
        'realm'            => null,
    ];

    public function __construct(array $options = [])
    {
        if ($options) {
            $this->setVerifyOptions($options);
        }
    }

    /**
     * oAuth token verification process
     * - Check if oauth_token exists && is valid
     * - Check if request comes from phraseanet Navigator && phraseanet Navigator
     *  is enable on current instance
     * - restore user session
     *
     * @ throws \API_V1_exception_unauthorized
     * @ throws \API_V1_exception_forbidden
     * @param Request     $request
     * @param Application $app
     * @throws \API_V1_exception_forbidden
     * @throws \Exception
     */
    public function __invoke(Request $request, Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];

        $context = new Context(Context::CONTEXT_OAUTH2_TOKEN);
        $dispatcher->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));
        $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_START, new ApiOAuth2StartEvent());

        /** @var \API_OAuth2_Adapter $oauth2 */
        $oauth2 = $app['oauth2_server'];


        if (false === $this->verifyAccessToken($oauth2)) {
            $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());

            return;
        }

        $token = $app['token'];

        if (!$token instanceof \API_OAuth2_Token) {
            throw new RuntimeException('Token could not be found');
        }

        $oAuth2App = $token->get_account()->get_application();

        if ($oAuth2App->get_client_id() == \API_OAuth2_Application_Navigator::CLIENT_ID
            && !$app['phraseanet.registry']->get('GV_client_navigator')) {
            throw new \API_V1_exception_forbidden(_('The use of phraseanet Navigator is not allowed'));
        }

        if ($oAuth2App->get_client_id() == \API_OAuth2_Application_OfficePlugin::CLIENT_ID
            && ! $app['phraseanet.registry']->get('GV_client_officeplugin')) {
            throw new \API_V1_exception_forbidden('The use of Office Plugin is not allowed.');
        }

        /** @var Authenticator $authentication */
        $authentication = $app['authentication'];

        if ($authentication->isAuthenticated()) {
            $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());
            $this->registerClosingAccountCallback($dispatcher, $authentication);

            return;
        }

        $user = \User_Adapter::getInstance($oauth2->get_usr_id(), $app);

        $authentication->openAccount($user);
        $oauth2->remember_this_ses_id($app['session']->get('session_id'));

        $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());
        $this->registerClosingAccountCallback($dispatcher, $authentication);
    }

    /**
     * @param \OAuth2 $oauth2
     * @return bool
     */
    private function verifyAccessToken(\OAuth2 $oauth2)
    {
        return $oauth2->verifyAccessToken(
            $this->verifyOptions['scope'],
            $this->verifyOptions['exit_not_present'],
            $this->verifyOptions['exit_invalid'],
            $this->verifyOptions['exit_expired'],
            $this->verifyOptions['exit_scope'],
            $this->verifyOptions['realm']
        );
    }

    public function setVerifyOptions(array $options)
    {
        $this->verifyOptions = array_merge($this->verifyOptions, array_intersect_key($options, $this->verifyOptions));
    }

    public function getVerifyOptions()
    {
        return $this->verifyOptions;
    }

    private function registerClosingAccountCallback(EventDispatcherInterface $dispatcher, Authenticator $authenticator)
    {
        $dispatcher->addListener(KernelEvents::RESPONSE, function () use ($authenticator) {
            return $authenticator->closeAccount();
        }, -20);
    }
}
