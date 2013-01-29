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

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ConfigurationPanelInterface
{
    /**
     * Handles the GET request to the configuration panel
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function get(Application $app, Request $request);

    /**
     * Handles the POST request to the configuration panel
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function post(Application $app, Request $request);

    /**
     * Return the associated search engine name
     *
     * @return string The name
     */
    public function getName();

    /**
     * Returns the configuration of the search engine
     *
     * @return array The configuration
     */
    public function getConfiguration();

    /**
     * Saves the search engine configuration
     *
     * @param  array                       $configuration
     * @return ConfigurationPanelInterface
     */
    public function saveConfiguration(array $configuration);

    /**
     * Return the names of the date fields
     *
     * @param  array $databoxes
     * @return array An array of date fields names
     */
    public function getAvailableDateFields(array $databoxes);
}
