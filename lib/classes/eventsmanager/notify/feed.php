<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Model\Entities\User;

class eventsmanager_notify_feed extends eventsmanager_notifyAbstract
{
    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/assets/common/images/icons/rss16.png';
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas(array $data, $unread)
    {
        $entry = $this->app['repo.feed-entries']->find($data['entry_id']);

        if (null === $entry) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans('%user% has published %title%', ['%user%' => htmlentities($entry->getAuthorName()), '%title%' => '<a href="/lightbox/feeds/entry/' . $entry->getId() . '/" target="_blank">' . htmlentities($entry->getTitle()) . '</a>'])
            , 'class' => ($unread == 1 ? 'reload_baskets' : '')
        ];

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('Feeds');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Receive notification when a publication is available');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        return true;
    }
}
