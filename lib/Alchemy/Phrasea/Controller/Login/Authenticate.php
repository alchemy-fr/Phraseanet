<?php

namespace Alchemy\Phrasea\Controller\Login;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Authenticate implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/', __CLASS__ . '::authenticate')
            ->before(function() use ($app) {
                    return $app['phraseanet.core']['Firewall']->requireNotAuthenticated($app);
                });

        return $controllers;
    }

    public function authenticate(Application $app, Request $request)
    {
        /* @var $Core \Alchemy\Phrasea\Core */
        $Core = $app['phraseanet.core'];

        $appbox = \appbox::get_instance($Core);
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        if ($registry->get('GV_captchas')
            && trim($registry->get('GV_captcha_private_key')) !== ''
            && trim($registry->get('GV_captcha_public_key')) !== '')
            include($registry->get('GV_RootPath') . 'lib/vendor/recaptcha/recaptchalib.php');

        $is_guest = false;

        if (null !== $request->get('nolog') && \phrasea::guest_allowed()) {
            $is_guest = true;
        }

        if ((null !== $request->get('login') && null !== $request->get('pwd')) || $is_guest) {

            /**
             * @todo dispatch an event that can be used to tweak the authentication
             * (LDAP....)
             */
            // $app['dispatcher']->dispatch();

            try {
                if ($is_guest) {
                    $auth = new \Session_Authentication_Guest($appbox);
                } else {
                    $captcha = false;

                    if ($registry->get('GV_captchas')
                        && trim($registry->get('GV_captcha_private_key')) !== ''
                        && trim($registry->get('GV_captcha_public_key')) !== ''
                        && ! is_null($request->get("recaptcha_challenge_field")
                            && ! is_null($request->get("recaptcha_response_field")))) {
                        $checkCaptcha = recaptcha_check_answer($registry->get('GV_captcha_private_key'), $_SERVER["REMOTE_ADDR"], $request->get("recaptcha_challenge_field"), $request->get("recaptcha_response_field"));

                        if ($checkCaptcha->is_valid) {
                            $captcha = true;
                        }
                    }

                    $auth = new \Session_Authentication_Native($appbox, $request->get('login'), $request->get('pwd'));
                    $auth->set_captcha_challenge($captcha);
                }
                $session->authenticate($auth);
            } catch (\Exception_Session_StorageClosed $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=session");
            } catch (\Exception_Session_RequireCaptcha $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=captcha");
            } catch (\Exception_Unauthorized $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=auth");
            } catch (\Exception_Session_MailLocked $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=mail-not-confirmed&usr=" . $e->get_usr_id());
            } catch (\Exception_Session_WrongToken $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=token");
            } catch (\Exception_InternalServerError $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=session");
            } catch (\Exception_ServiceUnavailable $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=maintenance");
            } catch (\Exception_Session_BadSalinity $e) {
                $date = new \DateTime('5 minutes');
                $usr_id = \User_Adapter::get_usr_id_from_login($request->get('login'));
                $url = \random::getUrlToken(\random::TYPE_PASSWORD, $usr_id, $date);

                $url = '/account/forgot-password/?token=' . $url . '&salt=1';

                return $app->redirect($url);
            } catch (\Exception $e) {
                return $app->redirect("/login/?redirect=" . $request->get('redirect') . "&error=" . _('An error occured'));
            }

            if ($app['browser']->isMobile()) {
                return $app->redirect("/lightbox/");
            } elseif ($request->get('redirect')) {
                return $app->redirect($request->get('redirect'));
            } elseif (true !== $app['browser']->isNewGeneration()) {
                return $app->redirect('/client/');
            } else {
                return $app->redirect('/prod/');
            }
        } else {
            return $app->redirect("/login/");
        }
    }
}
