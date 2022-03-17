<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Connection;

class eventsmanager_broker
{
    protected $notifications = [];
    protected $pool_classes = [];

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
        $iterators_pool = [
            'event' => [
                'eventsmanager_event_test'
            ],
            'notify' => [
                'eventsmanager_notify_autoregister',
                'eventsmanager_notify_downloadmailfail',
                'eventsmanager_notify_feed',
                'eventsmanager_notify_order',
                'eventsmanager_notify_orderdeliver',
                'eventsmanager_notify_ordernotdelivered',
                'eventsmanager_notify_push',
                'eventsmanager_notify_basketwip',
                'eventsmanager_notify_register',
                'eventsmanager_notify_uploadquarantine',
                'eventsmanager_notify_validate',
                'eventsmanager_notify_validationdone',
                'eventsmanager_notify_validationreminder',
            ]
        ];

        foreach ($iterators_pool as $type => $iterators) {
            foreach ($iterators as $fileinfo) {
                $classname = $fileinfo;

                if ( ! class_exists($classname, true)) {
                    continue;
                }
                $this->pool_classes[$classname] = new $classname($this->app);

                if ($type === 'notify' && $this->pool_classes[$classname])
                    $this->notifications[] = $classname;
            }
        }

        return;
    }

    public function notify($usr_id, $event_type, $datas, $mailed = false)
    {
        try {
            $event_type = str_replace('eventsmanager_', '', $event_type);

            $sql = 'INSERT INTO notifications (id, usr_id, type, unread, mailed, datas, created_on)
              VALUES
              (null, :usr_id, :event_type, 1, :mailed, :datas, NOW())';

            $params = [
                ':usr_id'     => $usr_id
                , ':event_type' => $event_type
                , ':mailed'     => ($mailed ? 1 : 0)
                , ':datas'      => $datas
            ];

            $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /*
    public function get_notifications_as_array($page = 0)
    {
        $r = $this->get_notifications($page * 10, 10);


        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // !!!!!!!!!!!!!!!!!!!!!!!!! FAKE USER FOR TESTING !!!!!!!!!!!!!!!!!!!!!!!!

        // $usr_id = $this->app->getAuthenticatedUser()->getId();

        $usr_id = 15826;

        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

        $total = 0;

        $sql = 'SELECT count(id) as total, sum(unread) as unread
            FROM notifications WHERE usr_id = :usr_id';

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $usr_id]);
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

        $data = ['notifications' => [], 'next' => ''];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $usr_id]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $type = 'eventsmanager_' . $row['type'];
            $json = @json_decode($row['datas'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            /** @var eventsmanager_notifyAbstract $obj * /
            $obj = $this->pool_classes[$type];
            $content = $obj->datas($json, $row['unread']);

            if ( ! isset($this->pool_classes[$type]) || count($content) === 0) {
                $sql = 'DELETE FROM notifications WHERE id = :id';
                $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
                $stmt->execute([':id' => $row['id']]);
                $stmt->closeCursor();
                continue;
            }

            $date_key = str_replace('-', '_', substr($row['created_on'], 0, 10));
            $display_date = $this->app['date-formatter']->getDate(new DateTime($row['created_on']));

            if ( ! isset($data['notifications'][$date_key])) {
                $data['notifications'][$date_key] = [
                    'display'       => $display_date
                    , 'notifications' => []
                ];
            }

            $data['notifications'][$date_key]['notifications'][$row['id']] = [
                'classname' => $content['class']
                , 'time'      => $this->app['date-formatter']->getTime(new DateTime($row['created_on']))
                , 'icon'      => '<img src="' . $this->pool_classes[$type]->icon_url() . '" style="vertical-align:middle;width:16px;margin:2px;" />'
                , 'id'        => $row['id']
                , 'text'      => $content['text']
            ];
        }

        if (((int) $page + 1) * $n < $total) {
            $data['next'] = '<a href="#" class="notification__print-action" data-page="' . ((int) $page + 1) . '">' . $this->app->trans('charger d\'avantages de notifications') . '</a>';
        }

        return $data;
    }
    */
/*
    public function get_unread_notifications_number()
    {
        $total = 0;

        $sql = 'SELECT count(id) as total
            FROM notifications
            WHERE usr_id = :usr_id AND unread="1"';
        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->app->getAuthenticatedUser()->getId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $total = $row['total'];
        }

        return $total;
    }
*/
    const READ = 1;
    const UNREAD = 2;

    public function get_notifications(int $offset=0, int $limit=10, $readFilter = self::READ | self::UNREAD, \Alchemy\Phrasea\Utilities\Stopwatch $stopwatch = null)
    {
        if(!$this->app->getAuthenticatedUser()) {
            return;
        }

        if($stopwatch) $stopwatch->lap("broker start");

        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // !!!!!!!!!!!!!!!!!!!!!!!!! FAKE USER FOR TESTING !!!!!!!!!!!!!!!!!!!!!!!!

        $usr_id = $this->app->getAuthenticatedUser()->getId();

        // $usr_id = 29882;

        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!




        // delete old already read notifs (nb: we do this for everybody - not only the current user -)
        // todo: for now we use "created_on" since there is no timestamp set when reading.
        //

        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // do restore : for testing we do not yet delete

        $sql = "DELETE FROM `notifications` WHERE `unread`=0 AND TIMESTAMPDIFF(HOUR, `created_on`, NOW()) > 10";
        // $this->app->getApplicationBox()->get_connection()->exec($sql);

        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

        // get count of unread notifications (to be displayed on navbar)
        //
        $total = 0;
        $unread = 0;
        $sql = 'SELECT COUNT(`id`) AS `total`, SUM(`unread`) AS `unread` FROM `notifications` WHERE `usr_id` = :usr_id';
        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $usr_id]);
        if( ($row = $stmt->fetch(PDO::FETCH_ASSOC)) ) {
            $total  = (int)$row['total'];
            $unread = (int)$row['unread'];
        }
        $stmt->closeCursor();

        if($stopwatch) $stopwatch->lap("sql count unread");

        $notifications = [];

        // fetch notifications
        //
        $sql = "SELECT * FROM `notifications` WHERE `usr_id` = :usr_id";
        switch ($readFilter) {
            case self::READ:
                $sql .= " AND `unread`=0";
                $total -= $unread;          // fix total to match the filter
                break;
            case self::UNREAD:
                $sql .= " AND `unread`=1";
                $total = $unread;           // fix total to match the filter
                break;
            default:
                // any other case : fetch both ; no need to fix total
                break;
        }
        $sql .= " ORDER BY created_on DESC LIMIT " . $offset . ", " . $limit;

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $usr_id]); // , ':offset' => $offset, ':limit' => $limit]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if($stopwatch) $stopwatch->lap("sql 2");

        $bad_ids = [];
        // nb : we asked for a "page" of notifs (limit), but since some notifications may be ignored (bad type, bad json, ...)
        //      the result array may contain less than expected (but this should not happen).
        foreach ($rs as $row) {
            $type = 'eventsmanager_' . $row['type'];
            if ( ! isset($this->pool_classes[$type])) {
                $bad_ids[] = $row['id'];
                continue;
            }

            $data = @json_decode($row['datas'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $bad_ids[] = $row['id'];
                continue;
            }

            /** @var eventsmanager_notifyAbstract $obj */
            $obj = $this->pool_classes[$type];
            $datas = $obj->datas($data, $row['unread']);

            if (count($datas) === 0) {
                $bad_ids[] = $row['id'];
                continue;
            }

            // add infos to the notification and add to list
            //
            $created_on = new DateTime($row['created_on']);
            $notifications[] = array_merge(
                $datas,
                [
                    'id'                => $row['id'],
                    'created_on_day'    => $created_on->format('Ymd'),
                    'created_on'        => $this->app['date-formatter']->getPrettyString($created_on),
                    'time'              => $this->app['date-formatter']->getTime($created_on),
                    'icon'              => $this->pool_classes[$type]->icon_url(),
                    'unread'            => $row['unread'],
                ]
            );
        }
        $stmt->closeCursor();

        if($stopwatch) $stopwatch->lap("fetch");

        if(!empty($bad_ids)) {
            // delete broken notifs
            $sql = 'DELETE FROM `notifications` WHERE `id` IN (' . join(',', $bad_ids) . ')';
            $stmt = $this->app->getApplicationBox()->get_connection()->exec($sql);
        }

        $next_offset = $offset+$limit;

        return [
            'unread_count' => $unread,
            'offset' => $offset,
            'limit' => $limit,
            // 'prev_offset' => $offset === 0 ? null : max(0, $offset-$limit),
            'next_offset' => $next_offset < $total ? $next_offset : null,
            'notifications' => $notifications
        ];

    }

    /**
     * mark a notification as read
     * todo : add a "read_on" datetime so we can delete read notifs after N days. For now we use "created_on"
     *
     * @param array $notifications
     * @param $usr_id
     * @return $this|false
     */
    public function read(Array $notifications, $usr_id)
    {
        if (count($notifications) == 0) {
            return false;
        }

        /** @var Connection $connection */
        $connection = $this->app->getApplicationBox()->get_connection();
        $builder = $connection->createQueryBuilder();
        $builder
            ->update('notifications')
            ->set('unread', '0')
            ->where(
                $builder->expr()->eq('usr_id', ':usr_id'),
                $builder->expr()->in('id', [':notifications'])
            )
            ->setParameters(
                [
                    'usr_id' => $usr_id,
                    'notifications' => $notifications,
                ],
                [
                    'usr_id' => PDO::PARAM_INT,
                    'notifications' => Connection::PARAM_INT_ARRAY,
                ]
            )
            ->execute()
        ;

        return $this;
    }

    /**
     * mark all user notification as read
     * @param $usr_id
     * @return $this
     */
    public function readAll($usr_id)
    {
        /** @var Connection $connection */
        $connection = $this->app->getApplicationBox()->get_connection();
        $builder = $connection->createQueryBuilder();
        $builder
            ->update('notifications')
            ->set('unread', '0')
            ->where(
                $builder->expr()->eq('usr_id', ':usr_id')
            )
            ->setParameters(
                [
                    'usr_id' => $usr_id,
                ],
                [
                    'usr_id' => PDO::PARAM_INT,
                ]
            )
            ->execute()
        ;

        return $this;
    }

    public function list_notifications_available(User $user)
    {
        $personal_notifications = [];

        foreach ($this->notifications as $notification) {
            if (!$this->pool_classes[$notification]->is_available($user)) {
                continue;
            }
            $group = $this->pool_classes[$notification]->get_group();
            $group = $group === null ? $this->app->trans('Notifications globales') : $group;

            $personal_notifications[$group][] = [
                'name'             => $this->pool_classes[$notification]->get_name(),
                'id'               => $notification,
                'description'      => $this->pool_classes[$notification]->get_description(),
                'subscribe_emails' => true,
            ];
        }

        return $personal_notifications;
    }
}
