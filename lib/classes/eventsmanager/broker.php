<?php

use Alchemy\Phrasea\Application;

class eventsmanager_broker
{
    protected $events = array();
    protected $notifications = array();
    protected $pool_classes = array();

    /**
     *
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        return $this;
    }

    public function start()
    {
        $iterators_pool = array(
            'event' => array(
                'eventsmanager_event_test'
            ),
            'notify' => array(
                'eventsmanager_notify_autoregister',
                'eventsmanager_notify_bridgeuploadfail',
                'eventsmanager_notify_downloadmailfail',
                'eventsmanager_notify_feed',
                'eventsmanager_notify_order',
                'eventsmanager_notify_orderdeliver',
                'eventsmanager_notify_ordernotdelivered',
                'eventsmanager_notify_push',
                'eventsmanager_notify_register',
                'eventsmanager_notify_uploadquarantine',
                'eventsmanager_notify_validate',
                'eventsmanager_notify_validationdone',
                'eventsmanager_notify_validationreminder',
            )
        );

        foreach ($iterators_pool as $type => $iterators) {
            foreach ($iterators as $fileinfo) {
                $classname = $fileinfo;

                if ( ! class_exists($classname, true)) {
                    continue;
                }
                $this->pool_classes[$classname] = new $classname($this->app, $this);

                foreach ($this->pool_classes[$classname]->get_events() as $event)
                    $this->bind($event, $classname);

                if ($type === 'notify' && $this->pool_classes[$classname]->is_available())
                    $this->notifications[] = $classname;
            }
        }

        return;
    }

    public function trigger($event, $array_params = array(), &$object = false)
    {
        if (array_key_exists($event, $this->events)) {
            foreach ($this->events[$event] as $classname) {
                $this->pool_classes[$classname]->fire($event, $array_params, $object);
            }
        }

        return;
    }

    public function bind($event, $object_name)
    {

        if ( ! array_key_exists($event, $this->events))
            $this->events[$event] = array();

        $this->events[$event][] = $object_name;
    }

    public function notify($usr_id, $event_type, $datas, $mailed = false)
    {
        try {
            $event_type = str_replace('eventsmanager_', '', $event_type);

            $sql = 'INSERT INTO notifications (id, usr_id, type, unread, mailed, datas, created_on)
              VALUES
              (null, :usr_id, :event_type, 1, :mailed, :datas, NOW())';

            $params = array(
                ':usr_id'     => $usr_id
                , ':event_type' => $event_type
                , ':mailed'     => ($mailed ? 1 : 0)
                , ':datas'      => $datas
            );

            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function get_notifications_as_array($page = 0)
    {
        $total = 0;

        $sql = 'SELECT count(id) as total, sum(unread) as unread
            FROM notifications WHERE usr_id = :usr_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->app['authentication']->getUser()->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $total = $row['total'];
        }

        $n = 10;

        $sql = 'SELECT * FROM notifications
            WHERE usr_id = :usr_id
            ORDER BY created_on DESC
            LIMIT ' . ((int) $page * $n) . ', ' . $n;

        $data = array('notifications' => array(), 'next' => '');

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->app['authentication']->getUser()->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $type = 'eventsmanager_' . $row['type'];
            $content = $this->pool_classes[$type]->datas($row['datas'], $row['unread']);

            if ( ! isset($this->pool_classes[$type]) || count($content) === 0) {
                $sql = 'DELETE FROM notifications WHERE id = :id';
                $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
                $stmt->execute(array(':id' => $row['id']));
                $stmt->closeCursor();
                continue;
            }

            $date_key = str_replace('-', '_', substr($row['created_on'], 0, 10));
            $display_date = $this->app['date-formatter']->getDate(new DateTime($row['created_on']));

            if ( ! isset($data['notifications'][$date_key])) {
                $data['notifications'][$date_key] = array(
                    'display'       => $display_date
                    , 'notifications' => array()
                );
            }

            $data['notifications'][$date_key]['notifications'][$row['id']] = array(
                'classname' => $content['class']
                , 'time'      => $this->app['date-formatter']->getTime(new DateTime($row['created_on']))
                , 'icon'      => '<img src="' . $this->pool_classes[$type]->icon_url() . '" style="vertical-align:middle;width:16px;margin:2px;" />'
                , 'id'        => $row['id']
                , 'text'      => $content['text']
            );
        }

        if (((int) $page + 1) * $n < $total) {
            $data['next'] = '<a href="#" onclick="print_notifications(' . ((int) $page + 1) . ');return false;">' . _('charger d\'avantages de notifications') . '</a>';
        }

        return $data;
    }

    public function get_unread_notifications_number()
    {
        $total = 0;

        $sql = 'SELECT count(id) as total
            FROM notifications
            WHERE usr_id = :usr_id AND unread="1"';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->app['authentication']->getUser()->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $total = $row['total'];
        }

        return $total;
    }

    public function get_notifications()
    {
        $unread = 0;

        $sql = 'SELECT count(id) as total, sum(unread) as unread
            FROM notifications WHERE usr_id = :usr_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->app['authentication']->getUser()->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $unread = $row['unread'];
        }

        if ($unread < 3) {
            $sql = 'SELECT * FROM notifications
              WHERE usr_id = :usr_id ORDER BY created_on DESC LIMIT 0,4';
        } else {
            $sql = 'SELECT * FROM notifications
              WHERE usr_id = :usr_id AND unread="1" ORDER BY created_on DESC';
        }

        $ret = array();
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->app['authentication']->getUser()->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $type = 'eventsmanager_' . $row['type'];
            if ( ! isset($this->pool_classes[$type])) {
                continue;
            }
            $datas = $this->pool_classes[$type]->datas($row['datas'], $row['unread']);

            if ( ! isset($this->pool_classes[$type]) || count($datas) === 0) {
                $sql = 'DELETE FROM notifications WHERE id = :id';
                $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
                $stmt->execute(array(':id' => $row['id']));
                $stmt->closeCursor();
                continue;
            }

            $ret[] = array_merge(
                $datas
                , array(
                'created_on' => $this->app['date-formatter']->getPrettyString(new DateTime($row['created_on']))
                , 'icon'       => $this->pool_classes[$type]->icon_url()
                , 'id'         => $row['id']
                , 'unread'     => $row['unread']
                )
            );
        }

        return $ret;
    }

    public function read(Array $notifications, $usr_id)
    {
        if (count($notifications) == 0) {
            return false;
        }

        $sql = 'UPDATE notifications SET unread="0"
            WHERE usr_id = :usr_id
              AND (id="' . implode('" OR id="', $notifications) . '")';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $usr_id));
        $stmt->closeCursor();

        return $this;
    }

    public function list_notifications_available()
    {
        $personnal_notifications = array();

        foreach ($this->notifications as $notification) {
            $group = $this->pool_classes[$notification]->get_group();
            $group = $group === null ? _('Notifications globales') : $group;

            $personnal_notifications[$group][] = array(
                'name'             => $this->pool_classes[$notification]->get_name()
                , 'id'               => $notification
                , 'description'      => $this->pool_classes[$notification]->get_description()
                , 'subscribe_emails' => true
            );
        }

        return $personnal_notifications;
    }
}
