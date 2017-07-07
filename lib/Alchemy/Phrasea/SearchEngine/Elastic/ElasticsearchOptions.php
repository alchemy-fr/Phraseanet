<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\SearchEngine\Elastic;

class ElasticsearchOptions
{
    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var string */
    private $indexName;
    /** @var int */
    private $shards;
    /** @var int */
    private $replicas;
    /** @var int */
    private $minScore;
    /** @var  bool */
    private $highlight;
    /** @var int */
    private $maxResultWindow;
    /** @var string */
    private $populateOrder;
    /** @var string */
    private $populateDirection;
    /** @var string */
    private $populateLimitType;
    /** @var int */
    private $populateLimitDuration;


    const POPULATE_ORDER_RID  = "RECORD_ID";
    const POPULATE_ORDER_MODDATE = "UPDATED_ON";
    const POPULATE_DIRECTION_ASC  = "ASC";
    const POPULATE_DIRECTION_DESC = "DESC";
    const POPULATE_LIMIT_TYPE_DAY = 'DAY';
    const POPULATE_LIMIT_TYPE_HOUR = 'HOUR';
    const POPULATE_LIMIT_TYPE_MINUTE = 'MINUTE';
    const POPULATE_LIMIT_DURATION = 1;

    /**
     * Factory method to hydrate an instance from serialized options
     *
     * @param array $options
     * @return self
     */
    public static function fromArray(array $options)
    {
        $options = array_replace([
            'host' => '127.0.0.1',
            'port' => 9200,
            'index' => '',
            'shards' => 3,
            'replicas' => 0,
            'minScore' => 4,
            'highlight' => true,
            'max_result_window' => 500000,
            'populate_order' => self::POPULATE_ORDER_RID,
            'populate_direction' => self::POPULATE_DIRECTION_DESC,
            'populate_limit_type' => self::POPULATE_LIMIT_TYPE_DAY,
            'populate_limit_duration' => self::POPULATE_LIMIT_DURATION,
        ], $options);

        $self = new self();
        $self->setHost($options['host']);
        $self->setPort($options['port']);
        $self->setIndexName($options['index']);
        $self->setShards($options['shards']);
        $self->setReplicas($options['replicas']);
        $self->setMinScore($options['minScore']);
        $self->setHighlight($options['highlight']);
        $self->setMaxResultWindow($options['max_result_window']);
        $self->setPopulateOrder($options['populate_order']);
        $self->setPopulateDirection($options['populate_direction']);
        $self->setPopulateLimitType($options['populate_limit_type']);
        $self->setPopulateLimitDuration($options['populate_limit_duration']);

        return $self;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'index' => $this->indexName,
            'shards' => $this->shards,
            'replicas' => $this->replicas,
            'minScore' => $this->minScore,
            'highlight' => $this->highlight,
            'maxResultWindow' => $this->maxResultWindow,
            'populate_order' => $this->populateOrder,
            'populate_direction' => $this->populateDirection,
            'populate_limit_type' => $this->populateLimitType,
            'populate_limit_duration' => $this->populateLimitDuration,
        ];
    }

    /**
     * @return string
     */
    public function getPopulateLimitType()
    {
        return $this->populateLimitType;
    }


    /**
     * @param $populateLimit
     * @return bool
     */
    public function setPopulateLimitType($populateLimit)
    {
        $limitType = strtoupper($populateLimit);
        if(in_array($limitType, [self::POPULATE_LIMIT_TYPE_DAY,self::POPULATE_LIMIT_TYPE_HOUR,self::POPULATE_LIMIT_TYPE_MINUTE])) {
            $this->populateLimitType = $limitType;
            return true;
        }

        return false;

    }

    /**
     * @return int
     */
    public function getPopulateLimitDuration()
    {
        return $this->populateLimitDuration;
    }

    /**
     * @param $populateLimitDuration
     */
    public function setPopulateLimitDuration($populateLimitDuration)
    {
        $this->populateLimitDuration = $populateLimitDuration;
    }

    /**
     * @param string $order
     * @return bool returns false if order is invalid
     */
    public function setPopulateOrder($order)
    {
        $order = strtoupper($order);
        if(in_array($order, [self::POPULATE_ORDER_RID, self::POPULATE_ORDER_MODDATE])) {
            $this->populateOrder = $order;

            return true;
        }

        return false;
    }

    /**
     * @param string $direction
     * @return bool returns false if direction is invalid
     */
    public function setPopulateDirection($direction)
    {
        $direction = strtoupper($direction);
        if(in_array($direction, [self::POPULATE_DIRECTION_DESC, self::POPULATE_DIRECTION_ASC])) {
            $this->populateDirection = $direction;

            return true;
        }

        return false;
    }

    public function getPopulateLimitAsSQL()
    {
        if($this->populateLimitType){
            $where = "WHERE `updated_on` BETWEEN DATE_SUB(NOW(), INTERVAL ".$this->populateLimitDuration." ".strtoupper($this->populateLimitType).") AND NOW() ";

            return  $where;
        }

        return '';
    }

    /**
     * @return string
     */
    public function getPopulateOrderAsSQL()
    {
        static $orderAsColumn = [
            self::POPULATE_ORDER_RID     => "`record_id`",
            self::POPULATE_ORDER_MODDATE => "`moddate`",
        ];
        // populateOrder IS one of the keys (ensured by setPopulateOrder)
        return $orderAsColumn[$this->populateOrder];
    }

    /**
     * @return string
     */
    public function getPopulateDirectionAsSQL()
    {
        // already a SQL word
        return $this->populateDirection;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $minScore
     */
    public function setMinScore($minScore)
    {
        $this->minScore = (int)$minScore;
    }

    /**
     * @return int
     */
    public function getMinScore()
    {
        return $this->minScore;
    }

    /**
     * @param string $indexName
     */
    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @param int $shards
     */
    public function setShards($shards)
    {
        $this->shards = (int)$shards;
    }

    /**
     * @return int
     */
    public function getShards()
    {
        return $this->shards;
    }

    /**
     * @param int $replicas
     */
    public function setReplicas($replicas)
    {
        $this->replicas = (int)$replicas;
    }

    /**
     * @return int
     */
    public function getReplicas()
    {
        return $this->replicas;
    }

    /**
     * @return bool
     */
    public function getHighlight()
    {
        return $this->highlight;
    }

    /**
     * @param bool $highlight
     */
    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }

    /**
     * @param int $maxResultWindow
     */
    public function setMaxResultWindow($maxResultWindow)
    {
        $this->maxResultWindow = (int)$maxResultWindow;
    }

    /**
     * @return int
     */
    public function getMaxResultWindow()
    {
        return $this->maxResultWindow;
    }
}
