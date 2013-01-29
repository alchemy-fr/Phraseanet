<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;

interface SearchEngineInterface
{
    const GEM_TYPE_RECORD = 'record';
    const GEM_TYPE_STORY = 'story';
    const GEM_TYPE_ENTRY = 'entry';

    /**
     * Returns the name of the search engine
     *
     * @return string The name of the search-engine
     */
    public function getName();

    /**
     * Check the status of the search engine
     *
     * @return array            An array of key/value parameters
     * @throws RuntimeException if something is wrong
     */
    public function getStatus();

    /**
     * @return ConfigurationPanelInterface
     */
    public function getConfigurationPanel();

    /**
     * @return array an array of field names
     */
    public function getAvailableDateFields();

    /**
     * @return array an array containing criteria values as key and criteria names as value
     */
    public function getAvailableSort();

    /**
     * @return string The default sort
     */
    public function getDefaultSort();

    /**
     * @return array an array containing sort order values as key and sort order names as value
     */
    public function getAvailableOrder();

    /**
     * @return Boolean return true if the search engine supports stemmed search
     */
    public function hasStemming();

    /**
     *
     * @return an array of self::GEM_TYPE_* indexed types
     */
    public function getAvailableTypes();

    /**
     * Add a record to index
     *
     * @param  \record_adapter       $record
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function addRecord(\record_adapter $record);

    /**
     * Remove a record from index
     *
     * @param  \record_adapter       $record
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function removeRecord(\record_adapter $record);

    /**
     * Update a record in index
     *
     * @param  \record_adapter       $record
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function updateRecord(\record_adapter $record);

    /**
     * Add a story to index
     *
     * @param  \record_adapter       $story
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function addStory(\record_adapter $story);

    /**
     * Remove a story from index
     *
     * @param  \record_adapter       $story
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function removeStory(\record_adapter $story);

    /**
     * Update a story in index
     *
     * @param  \record_adapter       $story
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function updateStory(\record_adapter $story);

    /**
     * Add an entry to index
     *
     * @param  \Feed_Entry_Adapter   $entry
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function addFeedEntry(\Feed_Entry_Adapter $entry);

    /**
     * Remove an entry to index
     *
     * @param  \Feed_Entry_Adapter   $entry
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function removeFeedEntry(\Feed_Entry_Adapter $entry);

    /**
     * Update an entry in the index
     *
     * @param  \Feed_Entry_Adapter   $entry
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function updateFeedEntry(\Feed_Entry_Adapter $entry);

    /**
     * Set options to search-engine
     *
     * @param  SearchEngineOptions   $options
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function setOptions(SearchEngineOptions $options);

    /**
     * Reset search-engine options
     *
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function resetOptions();

    /**
     *
     * @param string  $query
     * @param integer $offset
     * @param integer $perPage
     *
     * @return SearchEngineResult
     */
    public function query($query, $offset, $perPage);

    /**
     * Return an array of suggestions corresponding to the last word of the
     * query
     *
     * @param string $query
     *
     * @return ArrayCollection A collection of SearchEngineSuggestion
     */
    public function autocomplete($query);

    /**
     * Highlight the fields of a record
     *
     * @param type            $query
     * @param type            $fields
     * @param \record_adapter $record
     *
     * @return array The array of highlighted fields
     */
    public function excerpt($query, $fields, \record_adapter $record);

    /**
     * Reset the cache of the SE (if applicable)
     *
     * @return SearchEngineInterface
     */
    public function resetCache();

    /**
     * Clear the cache of the SE for the current user (if applicable)
     *
     * @return SearchEngineInterface
     */
    public function clearCache();

    /**
     * Clear all cache prior to the given date (if applicable)
     *
     * If no date provided, clear all entries
     *
     * @return SearchEngineInterface
     */
    public function clearAllCache(\DateTime $date = null);
}
