<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     User
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface User_QueryInterface
{

    public function __construct(Application $app);

    public function get_results();

    public function who_have_right(Array $rights);

    public function who_have_not_right(Array $rights);

    public function execute();

    public function get_total();

    public function get_page();

    public function on_bases_where_i_am(ACL $ACL, Array $rights);

    public function on_sbas_where_i_am(ACL $ACL, Array $rights);

    public function limit($offset_start, $results_quantity);

    public function like($like_field, $like_value);

    public function like_match($like_match);

    public function on_sbas_ids(Array $sbas_ids);

    public function on_base_ids(Array $base_ids);

    public function sort_by($sort, $ord = 'asc');

    public function get_inactives($boolean = true);
}
