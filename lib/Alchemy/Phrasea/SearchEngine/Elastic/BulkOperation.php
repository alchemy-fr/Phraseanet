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

use Elasticsearch\Client;
use igorw;

class BulkOperation
{
    private $client;

    private $stack = array();
    private $index;
    private $type;
    private $flushLimit = 1000;

    public function __construct(Client $client)
    {
        $this->client = $client;
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
        $header = array();
        $header['_id']    = igorw\get_in($params, ['id']);
        $header['_index'] = igorw\get_in($params, ['index']);
        $header['_type']  = igorw\get_in($params, ['type']);
        $this->stack[] = ['index' => $header];
        $this->stack[] = igorw\get_in($params, ['body']);

        if ($this->flushLimit === count($this->stack) / 2) {
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
        printf("ES Bulk query with %d items\n", count($this->stack) / 2);
        $this->client->bulk($params);
        $this->stack = array();
    }
}
