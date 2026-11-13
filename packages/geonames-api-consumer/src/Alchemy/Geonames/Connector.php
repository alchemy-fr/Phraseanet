<?php

/*
 * This file is part of Geonames API Connector.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Geonames;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Alchemy\Geonames\Exception\TransportException;
use Alchemy\Geonames\Exception\NotFoundException;

class Connector
{
    private $client;
    private $serverUri;

    public function __construct(ClientInterface $client, $serverUri)
    {
        $this->client = $client;
        $this->serverUri = '/' === substr((string) $serverUri, -1) ? $serverUri : $serverUri . '/';
    }

    /**
     * Search for a place by its name.
     *
     * @param string  $place
     * @param integer $limit
     * @param string  $clientIp
     *
     * @return array
     *
     * @throws Exception
     */
    public function search($place, $limit = 40, $clientIp = null)
    {
        $parameters = array(
            'sort'  => 'closeness',
            'name'  => $place,
            'limit' => $limit,
        );

        if ($clientIp) {
            $parameters['client-ip'] = $clientIp;
        }

        $result = $this->get('city', $parameters);

        return array_map(function ($geoname) {
            return new Geoname($geoname);
        }, $result);
    }

    /**
     * Search for a place by its IP address.
     *
     * @param string $ip
     *
     * @return Geoname
     *
     * @throws Exception
     */
    public function ip($ip)
    {
        return new Geoname($this->get('ip', array('ip' => $ip)));
    }

    /**
     * Gets a geoname given a geoname id.
     *
     * @param Integer $geonameId
     *
     * @return Geoname
     *
     * @throws Exception
     */
    public function geoname($geonameId)
    {
        return new Geoname($this->get('city/'.(int) $geonameId));
    }

    /**
     * Creates a Connector.
     *
     * @param type $serverUri
     *
     * @return Connector
     */
    public static function create($serverUri)
    {
        return new static(new Client(), $serverUri);
    }

    /**
     * Executes a GET HTTP query.
     *
     * @param string $endPoint
     * @param array  $queryParameters
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws TransportException
     */
    private function get($endPoint, array $queryParameters = array())
    {
        try {
            $response = $this->client->request('GET', $this->serverUri . $endPoint, array(
                'headers' => array('Accept' => 'application/json'),
                'query'   => $queryParameters,
            ));
            $result = json_decode((string) $response->getBody(), true);
        } catch (ClientException $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                throw new NotFoundException('Resource not found', $e->getCode(), $e);
            }

            throw new TransportException('Failed to execute query', $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new TransportException('Failed to execute query', $e->getCode(), $e);
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new TransportException('Unable to parse result');
        }

        return $result;
    }
}
