<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered;
use Alchemy\Phrasea\Model\Entities\User;

class eventsmanager_notify_autoregister extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = ['__REGISTER_AUTOREGISTER__'];

    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/skins/icons/user.png';
    }

    /**
     *
     * @param  string        $event
     * @param  Array         $params
     * @param  mixed content $object
     * @return Void
     */
    public function fire($event, $params, &$object)
    {
        $default = ['usr_id' => '' , 'autoregister' => []];
        $params = array_merge($default, $params);
        $base_ids = array_keys($params['autoregister']);

        if (count($base_ids) == 0) {
            return;
        }

        $mailColl = [];

        try {
            $rs = $this->app['EM.native-query']->getAdminsOfBases(array_keys($base_ids));

            foreach ($rs as $row) {
                $user = $row[0];

                if (!isset($mailColl[$user->getId()])) {
                    $mailColl[$user->getId()] = [];
                }

                $mailColl[$user->getId()][] = $row['base_id'];
            }
        } catch (\Exception $e) {
        }

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $usr_id = $dom_xml->createElement('usr_id');
        $base_ids = $dom_xml->createElement('base_ids');

        $usr_id->appendChild($dom_xml->createTextNode($params['usr_id']));

        foreach ($params['autoregister'] as $base_id => $collection) {
            $base_id_node = $dom_xml->createElement('base_id');
            $base_id_node->appendChild($dom_xml->createTextNode($base_id));
            $base_ids->appendChild($base_id_node);
        }

        $root->appendChild($usr_id);
        $root->appendChild($base_ids);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        if (null === $registered_user = $this->app['manipulator.user']->getRepository()->find($params['usr_id'])) {
            return;
        }

        foreach ($mailColl as $usr_id => $base_ids) {

            $mailed = false;

            if ($this->shouldSendNotificationFor($usr_id)) {
                if (null === $admin_user = $this->app['manipulator.user']->getRepository()->find($usr_id)) {
                    continue;
                }

                if (self::mail($admin_user, $registered_user))
                    $mailed = true;
            }

            $this->broker->notify($usr_id, __CLASS__, $datas, $mailed);
        }

        return;
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $usr_id = (string) $sx->usr_id;

        if (null === $user = $this->app['manipulator.user']->getRepository()->find($usr_id)) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans('%user% s\'est enregistre sur une ou plusieurs %before_link% scollections %after_link%', ['%user%' => $user->getDisplayName(), '%before_link%' => '<a href="/admin/?section=users" target="_blank">', '%after_link%' => '</a>'])
            , 'class' => ''
        ];

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('AutoRegister information');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Recevoir des notifications lorsqu\'un utilisateur s\'inscrit sur une collection');
    }

    /**
     *
     * @param User $to
     * @param User $registeredUser
     *
     * @return boolean
     */
    public function mail(User $to, User $registeredUser)
    {
        $body = '';
        $body .= sprintf("Login : %s\n", $registeredUser->getLogin());
        $body .= sprintf("%s : %s\n", _('admin::compte-utilisateur nom'), $registeredUser->getFirstName());
        $body .= sprintf("%s : %s\n", _('admin::compte-utilisateur prenom'), $registeredUser->getLastName());
        $body .= sprintf("%s : %s\n", _('admin::compte-utilisateur email'), $registeredUser->getEmail());
        $body .= sprintf("%s/%s\n", $registeredUser->get_job(), $registeredUser->getCompany());

        $readyToSend = false;
        try {
            $receiver = Receiver::fromUser($to);
            $readyToSend = true;
        } catch (\Exception $e) {

        }

        if ($readyToSend) {
            $mail = MailInfoSomebodyAutoregistered::create($this->app, $receiver, null, $body);
            $this->app['notification.deliverer']->deliver($mail);
        }

        return true;
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available($usr_id)
    {
        if (!$this->app['registration.enabled']) {
            return false;
        }

        if (null === $user = $this->app['manipulator.user']->getRepository()->find($usr_id)) {
            return false;
        }

        return $this->app['acl']->get($user)->has_right('manageusers');
    }
}
