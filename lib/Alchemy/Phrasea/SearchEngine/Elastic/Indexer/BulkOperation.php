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
    private $client;
    /** @var LoggerInterface */
    private $logger;

    private $stack = array();
    private $opData = [];
    private $index;
    private $type;
    private $flushLimit = 1000;
    private $flushCallbacks = [];

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function setDefaultIndex($index)
    {
        $this->index = (string) $index;
    }

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

    public function index(array $params, $_data)
    {
        $header = $this->buildHeader('index', $params);
        $body = igorw\get_in($params, ['body']);
        $this->push($header, $body, $_data);
    }

    public function delete(array $params, $_data)
    {
        $this->push($this->buildHeader('delete', $params), null, $_data);
    }

    private function push($header, $body, $_data)
    {
        $this->stack[] = $header;
        if ($body) {
            $this->stack[] = $body;
        }
        $this->opData[] = $_data;

        if (count($this->opData) === $this->flushLimit) {
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

        $this->logger->debug("ES Bulk query about to be performed\n", ['opCount' => count($this->opData)]);

        $response = $this->client->bulk($params);
        $this->stack = array();

        if (igorw\get_in($response, ['errors'], true)) {
            foreach ($response['items'] as $key => $item) {
                if ($item['index']['status'] >= 400) { // 4xx or 5xx error
                    throw new Exception(sprintf('%d: %s', $key, $item['index']['error']));
                }
            }
        }
        foreach($this->flushCallbacks as $flushCallback) {
            $flushCallback($this->opData);
        }
        $this->opData = [];
    }

    private function buildHeader($key, array $params)
    {
        $header = [];
        $header['_id']    = igorw\get_in($params, ['id']);
        $header['_type']  = igorw\get_in($params, ['type']);
        if ($index = igorw\get_in($params, ['index'])) {
            $header['_index'] = $index;
        }

        return [$key => $header];
    }

}
