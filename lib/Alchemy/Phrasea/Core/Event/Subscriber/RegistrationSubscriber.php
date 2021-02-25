<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\RegistrationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered;

class RegistrationSubscriber extends AbstractNotificationSubscriber
{
    public function onRegistration(RegistrationEvent $event)
    {
        $baseIds = array_map(function (\collection $coll) { return $coll->get_base_id(); }, $event->getCollections());

        if (count($baseIds) === 0) {
            return;
        }

        $params = [
            'usr_id'        => $event->getUser()->getId(),
            'base_ids'      => $baseIds,
        ];

        try {
            $rs = $this->app['orm.em.native-query']->getAdminsOfBases(array_keys($baseIds));
            $adminUsers = array_map(function ($row) { return $row[0]; }, $rs);
        } catch (\Exception $e) {
            return;
        }

        $datas = json_encode($params);

        $registeredUser = $event->getUser();

        foreach ($adminUsers as $adminUser) {
            $mailed = false;

            if ($this->shouldSendNotificationFor($adminUser, 'eventsmanager_notify_register')) {
                try {
                    $receiver = Receiver::fromUser($adminUser);
                } catch (\Exception $e) {
                    continue;
                }

                $mail = MailInfoUserRegistered::create($this->app, $receiver);
                $mail->setRegisteredUser($registeredUser);

                if (($locale = $adminUser->getLocale()) != null) {
                    $mail->setLocale($locale);
                }

                $this->deliver($mail);

                $mailed = true;
            }

            $this->app['events-manager']->notify($adminUser->getId(), 'eventsmanager_notify_register', $datas, $mailed);
        }
    }

    public function onAutoRegistration(RegistrationEvent $event)
    {
        if (count($event->getCollections()) === 0) {
            return;
        }

        $baseIds = array_map(function (\collection $coll) {
            return $coll->get_base_id();
        }, $event->getCollections());

        $params = [
            'usr_id'   => $event->getUser()->getId() ,
            'base_ids' => $baseIds,
        ];

        try {
            $rs = $this->app['orm.em.native-query']->getAdminsOfBases(array_keys($baseIds));
            $adminUsers = array_map(function ($row) { return $row[0]; }, $rs);
        } catch (\Exception $e) {
            return;
        }

        $datas = json_encode($params);

        $registered_user = $event->getUser();

        foreach ($adminUsers as $adminUser) {
            $mailed = false;

            if ($this->shouldSendNotificationFor($adminUser, 'eventsmanager_notify_autoregister')) {
                $mailed = $this->autoregisterEMail($adminUser, $registered_user);
            }

            $this->app['events-manager']->notify($adminUser->getId(), 'eventsmanager_notify_autoregister', $datas, $mailed);
        }
    }

    private function autoregisterEMail(User $to, User $registeredUser)
    {
        $locale = ($to->getLocale() != null)? $to->getLocale() : null;

        $body = '';
        $body .= sprintf("Login : %s\n", $registeredUser->getLogin());
        $body .= sprintf("%s : %s\n", $this->app->trans('admin::compte-utilisateur nom', [], 'messages', $locale), $registeredUser->getFirstName());
        $body .= sprintf("%s : %s\n", $this->app->trans('admin::compte-utilisateur prenom', [], 'messages', $locale), $registeredUser->getLastName());
        $body .= sprintf("%s : %s\n", $this->app->trans('admin::compte-utilisateur email', [], 'messages', $locale), $registeredUser->getEmail());
        $body .= sprintf("%s/%s\n", $registeredUser->getJob(), $registeredUser->getCompany());

        $readyToSend = false;
        try {
            $receiver = Receiver::fromUser($to);
            $readyToSend = true;
        } catch (\Exception $e) {

        }

        if ($readyToSend) {
            $mail = MailInfoSomebodyAutoregistered::create($this->app, $receiver, null, $body);

            if ($locale != null) {
                $mail->setLocale($locale);
            }

            $this->deliver($mail);
        }

        return true;
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::REGISTRATION_AUTOREGISTER => 'onAutoRegistration',
            PhraseaEvents::REGISTRATION_CREATE => 'onRegistration',
        ];
    }
}
