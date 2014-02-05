<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Model\Entities\FeedEntry;

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
     * @return string The default sort
     */
    public function isStemmingEnabled();

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
     * @param  FeedEntry             $entry
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function addFeedEntry(FeedEntry $entry);

    /**
     * Remove an entry to index
     *
     * @param  FeedEntry             $entry
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function removeFeedEntry(FeedEntry $entry);

    /**
     * Update an entry in the index
     *
     * @param  FeedEntry             $entry
     * @return SearchEngineInterface
     * @throws RuntimeException
     */
    public function updateFeedEntry(FeedEntry $entry);

    /**
     *
     * @param string              $query
     * @param integer             $offset
     * @param integer             $perPage
     * @param SearchEngineOptions $options
     *
     * @return SearchEngineResult
     */
    public function query($query, $offset, $perPage, SearchEngineOptions $options = null);

    /**
     * Return an array of suggestions corresponding to the last word of the
     * query
     *
     * @param string $query
     *
     * @return ArrayCollection A collection of SearchEngineSuggestion
     */
    public function autocomplete($query, SearchEngineOptions $options);

    /**
     * Highlight the fields of a record
     *
     * @param type            $query
     * @param type            $fields
     * @param \record_adapter $record
     *
     * @return array The array of highlighted fields
     */
    public function excerpt($query, $fields, \record_adapter $record, SearchEngineOptions $options = null);

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
     * Returns a subscriber
     *
     * @return EventSubscriberInterface
     */
    public static function createSubscriber(Application $app);

    /**
     * Creates the adapter.
     *
     * @param Application $app
     * @param array       $options
     */
    public static function create(Application $app, array $options = []);
}
