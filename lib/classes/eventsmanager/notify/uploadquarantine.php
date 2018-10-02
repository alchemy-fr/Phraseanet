<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\Translation\TranslatorInterface;

class eventsmanager_notify_uploadquarantine extends eventsmanager_notifyAbstract
{
    /**
     * @return string
     */
    public function icon_url()
    {
        return '/assets/common/images/icons/quarantine.png';
    }

    /**
     * @param array $data
     * @param bool $unread
     * @return array
     */
    public function datas(array $data, $unread)
    {
        /** @var Manager $manager */
        $manager = $this->app['border-manager'];
        /** @var TranslatorInterface $translator */
        $translator = $this->app['translator'];

        $reasons = array_map(function ($checkerFQCN) use ($manager, $translator) {
            return $manager->getCheckerFromFQCN($checkerFQCN)->getMessage($translator);
        }, $data['reasons']);

        $filename = $data['filename'];

        $text = $this->app->trans('The document %name% has been quarantined', ['%name%' => $filename]);

        if ($reasons) {
            $text .= ' ' . $this->app->trans('for the following reasons : %reasons%', ['%reasons%' => implode(', ', $reasons)]);
        }

        $ret = ['text'  => $text, 'class' => ''];

        return $ret;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('Quarantine notificaton');
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('be notified when a document is placed in quarantine');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        return $this->app->getAclForUser($user)->has_right(\ACL::CANADDRECORD);
    }
}
