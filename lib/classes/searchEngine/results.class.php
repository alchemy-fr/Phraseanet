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
 * @package     searchEngine
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class searchEngine_results
{
    /**
     *
     * @var set
     */
    protected $result;

    /**
     *
     * @var searchEngine_adapter_interface
     */
    protected $engine;

    /**
     *
     * @param  set                            $result
     * @param  searchEngine_adapter_interface $engine
     * @return searchEngine_results
     */
    public function __construct(set_abstract $result, searchEngine_adapter_interface $engine)
    {
        $this->engine = $engine;
        $this->result = $result;

        return $this;
    }

    /**
     *
     * @return set
     */
    public function get_datas()
    {
        return $this->result;
    }

    /**
     *
     * @return float
     */
    public function get_query_time()
    {
        return $this->engine->get_time();
    }

    /**
     *
     * @return int
     */
    public function get_total_pages()
    {
        return $this->engine->get_total_pages();
    }

    /**
     *
     * @return int
     */
    public function get_current_page()
    {
        return (int) $this->engine->get_current_page();
    }

    /**
     *
     * @return int
     */
    public function get_count_available_results()
    {
        return (int) $this->engine->get_available_results();
    }

    /**
     *
     * @return int
     */
    public function get_count_total_results()
    {
        return (int) $this->engine->get_total_results();
    }

    /**
     *
     * @return string
     */
    public function get_error()
    {
        return $this->engine->get_error();
    }

    /**
     *
     * @return string
     */
    public function get_warning()
    {
        return $this->engine->get_warning();
    }

    /**
     *
     * @return array
     */
    public function get_suggestions()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();

        return $this->engine->get_suggestions($session);
    }

    /**
     *
     * @return string
     */
    public function get_propositions()
    {
        return $this->engine->get_propositions();
    }

    /**
     *
     * @return string
     */
    public function get_search_indexes()
    {
        return $this->engine->get_current_indexes();
    }
}
