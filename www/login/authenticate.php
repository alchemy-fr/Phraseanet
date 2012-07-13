<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();
$registry = $appbox->get_registry();

if ($registry->get('GV_captchas')
    && trim($registry->get('GV_captcha_private_key')) !== ''
    && trim($registry->get('GV_captcha_public_key')) !== '')
    include($registry->get('GV_RootPath') . 'lib/vendor/recaptcha/recaptchalib.php');

$request = http_request::getInstance();
$parm = $request->get_parms('redirect', 'login', 'pwd', 'nolog', 'recaptcha_response_field', 'remember', 'recaptcha_challenge_field');

$is_guest = false;

if ( ! is_null($parm['nolog']) && phrasea::guest_allowed()) {
    $is_guest = true;
}

if (( ! is_null($parm['login']) && ! is_null($parm['pwd'])) || $is_guest) {
    if (file_exists($registry->get('GV_RootPath') . 'config/personnalisation/prelog.class.php')) {
        include($registry->get('GV_RootPath') . 'config/personnalisation/prelog.class.php');
        $prelog = new prelog($parm['login'], $parm['pwd']);
    }

    try {

        if ($is_guest) {
            $auth = new Session_Authentication_Guest($appbox);
        } else {
            $captcha = false;

            if ($registry->get('GV_captchas')
                && trim($registry->get('GV_captcha_private_key')) !== ''
                && trim($registry->get('GV_captcha_public_key')) !== ''
                && ! is_null($parm["recaptcha_challenge_field"])
                && ! is_null($parm["recaptcha_response_field"])) {
                $checkCaptcha = recaptcha_check_answer($registry->get('GV_captcha_private_key'), $_SERVER["REMOTE_ADDR"], $parm["recaptcha_challenge_field"], $parm["recaptcha_response_field"]);

                if ($checkCaptcha->is_valid) {
                    $captcha = true;
                }
            }

            $auth = new Session_Authentication_Native($appbox, $parm['login'], $parm['pwd']);
            $auth->set_captcha_challenge($captcha);
        }
        $session->authenticate($auth);
    } catch (Exception_Session_StorageClosed $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=session");
    } catch (Exception_Session_RequireCaptcha $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=captcha");
    } catch (Exception_Unauthorized $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=auth");
    } catch (Exception_Session_MailLocked $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=mailNotConfirm&usr=" . $e->get_usr_id());
    } catch (Exception_Session_WrongToken $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=token");
    } catch (Exception_InternalServerError $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=session");
    } catch (Exception_ServiceUnavailable $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=maintenance");
    } catch (Exception_Session_BadSalinity $e) {
        $date = new DateTime('5 minutes');
        $usr_id = User_Adapter::get_usr_id_from_login($parm['login']);
        $url = random::getUrlToken(\random::TYPE_PASSWORD, $usr_id, $date);

        $url = '/account/forgot-password/?token=' . $url . '&salt=1';

        return phrasea::redirect($url);
    } catch (\Exception $e) {
        return phrasea::redirect("/login/?redirect=" . $parm['redirect'] . "&error=" . $e->getMessage() . $e->getFile() . $e->getLine());
    }

    $browser = Browser::getInstance();

    if ( ! $browser->isNewGeneration())
        $app = 'client';


    if ($browser->isMobile()) {
        return phrasea::redirect("/lightbox/");
    } elseif ($parm['redirect']) {
        return phrasea::redirect($parm['redirect']);
    } else {
        return phrasea::redirect('/prod');
    }
} else {
    return phrasea::redirect("/login/");
}
