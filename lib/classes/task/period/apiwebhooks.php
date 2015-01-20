<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Guzzle\Http\Client as GuzzleClient;

class task_period_apiwebhooks extends task_appboxAbstract
{
    public static function getName()
    {
        return _('Api Webhook');
    }

    public static function help()
    {
        return _('Notify Phraseanet Oauth2 client applications using webhooks.');
    }

    protected function retrieveContent(appbox $appbox)
    {
        $stmt = $appbox->get_connection()->prepare('SELECT id, `type`, `data` FROM api_webhooks');
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $rs;
    }

    protected function processOneContent(appbox $appbox, array $row)
    {
        $data = null;
        switch ($row['type']) {
            case \API_Webhook::NEW_FEED_ENTRY:
                $data = $this->processNewFeedEntry($row);
        }

        if (null === $data) {
            return;
        }
        $urls = $this->getApplicationHookUrls($appbox);
        $this->sendData($urls, $data);
    }

    protected function postProcessOneContent(appbox $appbox, array $row)
    {
        $w = new API_Webhook($appbox, $row['id']);
        $w->delete();
    }

    protected function getApplicationHookUrls(appbox $appbox)
    {
        $stmt = $appbox->get_connection()->prepare('
            SELECT webhook_url
            FROM api_applications
            WHERE webhook_url IS NOT NULL
        ');
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return array_map(function ($row) {
            return $row['webhook_url'];
        }, $rows);
    }

    protected function sendData(array $urls, array $data)
    {
        if (count($urls) === 0) {
            return;
        }
        $client = new GuzzleClient();
        $body = json_encode($data);
        $requests = array();
        foreach ($urls as $url) {
            $requests[] = $client->createRequest('POST', $url, array(
                'Content-Type' => 'application/vnd.phraseanet.event+json'
            ), $body);
        }
        $client->send($requests);
    }

    protected function processNewFeedEntry(array $row)
    {
        $data = json_decode($row['data']);
        if (!isset($data->{"feed_id"}) || !isset($data->{"entry_id"})) {
            return;
        }
        $feed = new Feed_Adapter($this->dependencyContainer, $data->{"feed_id"});
        $entry = new \Feed_Entry_Adapter($this->dependencyContainer, $feed, $data->{"entry_id"});
        $query = new \User_Query($this->dependencyContainer);

        $query->include_phantoms(true)
            ->include_invite(false)
            ->include_templates(false)
            ->email_not_null(true);

        if ($entry->get_feed()->get_collection()) {
            $query->on_base_ids(array($entry->get_feed()->get_collection()->get_base_id()));
        }

        $start = 0;
        $perLoop = 100;
        $users = array();

        do {
            $results = $query->limit($start, $perLoop)->execute()->get_results();
            foreach ($results as $user) {
                $users[] = array(
                    'email' => $user->get_email(),
                    'firstname' => $user->get_firstname() ?: null,
                    'lastname' => $user->get_lastname() ?: null,
                );
            }
            $start += $perLoop;
        } while (count($results) > 0);

        return array(
            'event' => $row['type'],
            'users_were_notified' => !!$data->{"notify_email"},
            'feed' => array(
                'id' => $feed->get_id(),
                'title' => $feed->get_title(),
                'description' => $feed->get_subtitle() ?: null,
            ),
            'entry' => array(
                'id' => $entry->get_id(),
                'author' => array(
                    'name' => $entry->get_author_name(),
                    'email' => $entry->get_author_email()
                ),
                'title' => $entry->get_title(),
                'description' => $entry->get_subtitle() ?: null,
            ),
            'users' => $users
        );
    }
}
