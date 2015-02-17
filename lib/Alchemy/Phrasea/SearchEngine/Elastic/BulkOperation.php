<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Elasticsearch\Client;
use igorw;

class BulkOperation
{
    private $client;

    private $stack = array();
    private $opCount = 0;
    private $index;
    private $type;
    private $flushLimit = 1000;
    private $throwOnError;

    public function __construct(Client $client, $throwOnError = false)
    {
        $this->client = $client;
        $this->throwOnError = $throwOnError;
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

    public function index(array $params)
    {
        $header = $this->buildHeader('index', $params);
        $body = igorw\get_in($params, ['body']);
        $this->push($header, $body);
    }

    public function delete(array $params)
    {
        $this->push($this->buildHeader('delete', $params));
    }

    private function push($header, $body = null)
    {
        $this->stack[] = $header;
        if ($body) {
            $this->stack[] = $body;
        }
        $this->opCount++;

        if ($this->flushLimit === $this->opCount) {
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

        if (php_sapi_name() === 'cli') {
            printf("ES Bulk query with %d items\n", $this->opCount);
        }

        $response = $this->client->bulk($params);
        $this->stack = array();
        $this->opCount = 0;

        if ($this->throwOnError && igorw\get_in($response, ['errors'], true)) {
            foreach ($response['items'] as $key => $item) {
                if ($item['index']['status'] >= 400) { // 4xx or 5xx error
                    throw new Exception(sprintf('%d: %s', $key, $item['index']['error']));
                }
            }
        }
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
