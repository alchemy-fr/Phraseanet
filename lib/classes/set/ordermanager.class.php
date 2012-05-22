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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class set_ordermanager extends set_abstract
{
    /**
     *
     * @var int
     */
    protected $page;

    /**
     *
     * @var int
     */
    protected $total;

    const PER_PAGE = 10;

    /**
     *
     * @return set_ordermanager
     */
    public function __construct($sort = false, $page = 1)
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $conn = $appbox->get_connection();

        $page = (int) $page;

        $debut = ($page - 1) * self::PER_PAGE;

        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
        $base_ids = array_keys($user->ACL()->get_granted_base(array('order_master')));
        $sql = 'SELECT distinct o.id, o.usr_id, created_on, deadline, `usage`
              FROM (`order_elements` e, `order` o)
              WHERE e.base_id IN (' . implode(', ', $base_ids) . ')
              AND e.order_id = o.id
              GROUP BY o.id
              ORDER BY o.id DESC
              LIMIT ' . (int) $debut . ',' . self::PER_PAGE;

        $elements = array();

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $elements[] = new set_order($row['id']);
        }

        if ($sort) {
            if ($sort == 'created_on')
                uasort($elements, array('ordermanager', 'date_orders_sort'));
            elseif ($sort == 'user')
                uasort($elements, array('ordermanager', 'user_orders_sort'));
            elseif ($sort == 'usage')
                uasort($elements, array('ordermanager', 'usage_orders_sort'));
        }

        $sql = 'SELECT distinct o.id
              FROM (`order_elements` e, `order` o)
              WHERE e.base_id IN (' . implode(', ', $base_ids) . ')
              AND e.order_id = o.id
              GROUP BY o.id
              ORDER BY o.id DESC';

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':usr_id' => $session->get_usr_id()));
        $total = $stmt->rowCount();
        $stmt->closeCursor();

        $this->elements = $elements;
        $this->page = $page;
        $this->total = $total;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_page()
    {
        return $this->page;
    }

    /**
     *
     * @return int
     */
    public function get_previous_page()
    {
        $p_page = $this->page < 2 ? false : ($this->page - 1);

        return $p_page;
    }

    /**
     *
     * @return int
     */
    public function get_next_page()
    {
        $t_page = ceil($this->total / self::PER_PAGE);
        $n_page = $this->page >= $t_page ? false : $this->page + 1;

        return $n_page;
    }

    /**
     *
     * @param  string $a
     * @param  string $b
     * @return int
     */
    protected static function usage_orders_sort($a, $b)
    {
        $comp = strcasecmp($a['usage'], $b['usage']);

        if ($comp == 0) {
            return 0;
        }

        return $comp < 0 ? -1 : 1;
    }

    /**
     *
     * @param  string $a
     * @param  string $b
     * @return int
     */
    protected static function user_orders_sort($a, $b)
    {
        $comp = strcasecmp($a['usr_display'], $b['usr_display']);

        if ($comp == 0) {
            return 0;
        }

        return $comp < 0 ? -1 : 1;
    }

    /**
     *
     * @param  DateTime $a
     * @param  DateTime $b
     * @return int
     */
    protected static function date_orders_sort(DateTime $a, DateTime $b)
    {
        $comp = $b->format('U') - $a->format('U');

        if ($comp == 0) {
            return 0;
        }

        return $comp < 0 ? -1 : 1;
    }
}
