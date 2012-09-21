<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     searchEngine
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface searchEngine_adapter_interface
{

    public function __construct(Application $app);

    public function set_options(searchEngine_options $options);

    public function reset_cache();

    public function get_time();

    public function get_total_pages();

    public function get_offset_start();

    public function get_current_page();

    public function get_per_page();

    public function get_total_results();

    public function get_available_results();

    public function get_propositions();

    public function get_parsed_query();

    public function get_suggestions($I18n);

    public function get_error();

    public function get_warning();

    public function get_current_indexes();

    public function get_status();

    public function results($query, $page, $perPage);

    public function build_excerpt($query, array $fields, record_adapter $record);
}
