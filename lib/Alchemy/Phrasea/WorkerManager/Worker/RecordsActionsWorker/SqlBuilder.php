<?php

namespace Alchemy\Phrasea\WorkerManager\Worker\RecordsActionsWorker;

use databox;
use Exception;

class SqlBuilder
{
    /**
     * @var databox
     */
    private $databox;

    private $ijoin = 0;

    private $selectClauses = [];
    private $fromClauses = [];
    private $whereClauses = [];
    private $negWhereClauses = [];
    private $references = [];

    public function __construct(databox $databox)
    {
        $this->databox = $databox;
    }

    public function addReference($key, $value)
    {
        $this->references[$key] = $value;
    }

    /**
     * @param $key
     * @return string|null
     */
    public function getReference($key)
    {
        return $this->references[$key] ?: null;
    }

    public function incIjoin(): int
    {
        $this->ijoin++;
        return $this->ijoin;
    }

    public function addSelect(string $s): self
    {
        $this->selectClauses[] = $s;

        return $this;
    }

    public function addWhere(string $clause): self
    {
        $this->whereClauses[] = $clause;
        return $this;
    }

    public function addNegWhere(string $clause): self
    {
        $this->negWhereClauses[] = $clause;
        return $this;
    }

    public function addFrom(string $table): self
    {
        $this->fromClauses[] = $table;

        return $this;
    }

    public function getWhereSql()
    {
        $w = $this->whereClauses;

        if(!empty($this->negWhereClauses)) {
            if(count($this->negWhereClauses) == 1) {
                $neg = $this->negWhereClauses[0];
            }
            else {
                $neg = "(" . join(") AND (", $this->negWhereClauses) . ")";
            }
            $w[] = "NOT(" . $neg . ")";
        }

        if(empty($w)) {
            return "";
        }
        if(count($w) === 1) {
            return $w[0];
        }
        return "(" . join(") AND (", $w) . ")";
    }

    public function getSql(): string
    {
        $sql = "";

        if(!empty($this->selectClauses)) {
            $sql .= $sql ? ' ' : '';
            $sql .= sprintf("SELECT %s",
                join(', ', $this->selectClauses)
            );
        }

        if(!empty($this->fromClauses)) {
            $sql .= $sql ? ' ' : '';
            $sql .= sprintf("FROM %s",
                join(' ', $this->fromClauses)
            );
        }

        if(!empty($this->whereClauses)) {
            $sql .= $sql ? ' ' : '';
            $sql .= sprintf("WHERE %s", $this->getWhereSql());
        }

        return $sql;
    }
}
