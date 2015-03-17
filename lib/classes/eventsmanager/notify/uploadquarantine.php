<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Border\Checker\CheckerInterface;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\Translation\TranslatorInterface;

class eventsmanager_notify_uploadquarantine extends eventsmanager_notifyAbstract
{
    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '';
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas(array $data, $unread)
    {
        /** @var CheckerInterface[] $checkers */
        $checkers = $this->app['border-manager']->getCheckers();
        /** @var TranslatorInterface $translator */
        $translator = $this->app['translator'];

        $reasons = array_map(function ($checkerFQCN) use ($checkers, $translator) {
            foreach ($checkers as $actualChecker) {
                if (get_class($actualChecker) === $checkerFQCN) {
                    return $actualChecker->getMessage($translator);
                }
            }

            throw new RuntimeException('Could not find checker');
        }, $data['reasons']);

        $filename = $data['filename'];

        $text = $this->app->trans('The document %name% has been quarantined', ['%name%' => $filename]);

        if ( ! ! count($reasons)) {
            $text .= ' ' . $this->app->trans('for the following reasons : %reasons%', ['%reasons%' => implode(', ', $reasons)]);
        }

        $ret = ['text'  => $text, 'class' => ''];

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('Quarantine notificaton');
    }

    /**
     *
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
        return $this->app['acl']->get($user)->has_right('addrecord');
    }
}
