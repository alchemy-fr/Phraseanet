<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Elasticsearch\Client;
use igorw;
use Psr\Log\LoggerInterface;

class BulkOperation
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $stack = array();

    /**
     * @var string[]
     */
    private $operationIdentifiers = [];

    /**
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $flushLimit = 1000;

    /**
     * @var callable[]
     */
    private $flushCallbacks = [];

    /**
     * @param Client $client
     * @param LoggerInterface $logger
     */
    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param string $index
     */
    public function setDefaultIndex($index)
    {
        $this->index = (string) $index;
    }

    /**
     * @param string $type
     */
    public function setDefaultType($type)
    {
        if (!$this->index) {
            throw new \RuntimeException('You must provide a default index first');
        }

        $this->type = (string) $type;
    }

    public function setAutoFlushLimit($limit)
    {
        $this->flushLimit = (int) $limit;
    }

    public function onFlush(\Closure $callback)
    {
        $this->flushCallbacks[] = $callback;
    }

    public function index(array $params, $operationIdentifier)
    {
        $header = $this->buildHeader('index', $params);
        $body = $params['body'];

        $this->push($header, $body, $operationIdentifier);
    }

    public function delete(array $params, $operationIdentifier)
    {
        $this->push($this->buildHeader('delete', $params), null, $operationIdentifier);
    }

    private function push($header, $body, $operationIdentifier)
    {
        $this->stack[] = $header;

        if ($body) {
            $this->stack[] = $body;
        }

        $this->operationIdentifiers[] = $operationIdentifier;

        if (count($this->operationIdentifiers) === $this->flushLimit) {
            $this->flush();
        }
    }

    public function flush()
    {
        // Do not try to flush an empty stack
        if (count($this->stack) === 0) {
            return;
        }

        $params = array();

        if ($this->index) {
            $params['index'] = $this->index;

            if ($this->type) {
                $params['type'] = $this->type;
            }
        }

        $params['body'] = $this->stack;

        $this->logger->debug("ES Bulk query about to be performed\n", ['opCount' => count($this->operationIdentifiers)]);

        $response = $this->client->bulk($params);
        $this->stack = array();

        $callbackData = [];     // key: operationIdentifier passed when command was pushed on this bulk
                                // value: json result from es for the command
        // nb: results (items) are returned IN THE SAME ORDER as commands were pushed in the stack
        // so the items[X] match the operationIdentifiers[X]
        foreach ($response['items'] as $key => $item) {
            foreach ($item as $command=>$result) {   // command may be "index" or "delete"
                if ($response['errors'] && $result['status'] >= 400) { // 4xx or 5xx
                    $err = array_key_exists('error', $result) ? var_export($result['error'], true) : ($command . " error " . $result['status']);
                    throw new Exception(sprintf('%d: %s', $key, $err));
                }
            }

            $operationIdentifier = $this->operationIdentifiers[$key];

            if (is_string($operationIdentifier) || is_int($operationIdentifier)) {   // dont include null keys
                $callbackData[$operationIdentifier] = $response['items'][$key];
            }
        }

        foreach($this->flushCallbacks as $iCallBack=>$flushCallback) {
            $flushCallback($callbackData);
        }

        $this->operationIdentifiers = [];
    }

    private function buildHeader($key, array $params)
    {
        $header = [];

        $header['_id']    = $params['id'];
        $header['_type']  = $params['type'];

        if (isset($params['index']) && $index = $params['index']) {
            $header['_index'] = $index;
        }

        return [$key => $header];
    }

}
