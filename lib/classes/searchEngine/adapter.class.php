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
class searchEngine_adapter
{
    /**
     *
     * @var searchEngine_adapter_interface
     */
    protected $search_engine;

    /**
     *
     * @var searchEngine_adapter_options
     */
    protected $search_options;

    /**
     *
     * @var boolean
     */
    protected $first_page = false;

    /**
     *
     * @param registryInterface $registry
     * @return searchEngine_adapter
     */
    public function __construct(registryInterface $registry)
    {
        if ($registry->get('GV_sphinx')) {
            $this->search_engine = new searchEngine_adapter_sphinx_engine();
        } elseif (function_exists('phrasea_query2')) {
            $this->search_engine = new searchEngine_adapter_phrasea_engine();
        } else {
            throw new Exception('No search engine available, try phrasea2 or sphinx');
        }

        return $this;
    }

    /**
     *
     * @param searchEngine_options $options
     * @return searchEngine_adapter
     */
    public function set_options(searchEngine_options $options)
    {
        $this->search_options = $options;
        $this->search_engine->set_options($options);

        return $this;
    }

    /**
     *
     * @param boolean $boolean
     * @return searchEngine_adapter
     */
    public function set_is_first_page($boolean)
    {
        $this->first_page = ! ! $boolean;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function is_first_page()
    {
        return $this->first_page;
    }

    /**
     *
     * @return string
     */
    public function get_query()
    {
        return $this->search_engine->get_parsed_query();
    }

    /**
     *
     * @return searchEngine_adapter
     */
    public function reset_cache()
    {
        $this->search_engine->reset_cache();

        return $this;
    }

    /**
     *
     * @param string $query
     * @param int $page
     * @param int $perPage
     * @return searchEngine_results
     */
    public function query_per_page($query, $page, $perPage)
    {
        assert(is_int($page));
        assert($page > 0);
        assert(is_int($perPage));
        assert($perPage > 0);
        $offset = ($page - 1) * $perPage;

        return $this->search_engine->results($query, $offset, $perPage);
    }

    /**
     *
     * @param string $query
     * @param int $offset
     * @param int $perPage
     * @return searchEngine_results
     */
    public function query_per_offset($query, $offset, $perPage)
    {
        assert(is_int($offset));
        assert($offset >= 0);
        assert(is_int($perPage));
        assert($perPage > 0);

        return $this->search_engine->results($query, $offset, $perPage);
    }

    /**
     *
     * @return array
     */
    public function get_status()
    {
        return $this->search_engine->get_status();
    }

    public function build_excerpt($query, array $fields, record_adapter $record)
    {
        return $this->search_engine->build_excerpt($query, $fields, $record);
    }
}
