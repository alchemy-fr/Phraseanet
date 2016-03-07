<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Record;

use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Assert\Assertion;

class RecordReference implements RecordReferenceInterface
{
    /**
     * @var int
     */
    private $databoxId;
    /**
     * @var int
     */
    private $recordId;

    /**
     * @param int $databoxId
     * @param int $recordId
     */
    private function __construct($databoxId, $recordId)
    {
        $this->databoxId = (int)$databoxId;
        $this->recordId = (int)$recordId;
    }

    /**
     * @param int $databoxId
     * @param int $recordId
     * @return RecordReference
     */
    public static function createFromDataboxIdAndRecordId($databoxId, $recordId)
    {
        return new self($databoxId, $recordId);
    }

    /**
     * @param string $reference
     * @return RecordReference
     */
    public static function createFromRecordReference($reference)
    {
        $array = explode('_', $reference);

        Assertion::count($array, 2);

        list($databoxId, $recordId) = $array;

        return new self($databoxId, $recordId);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return sprintf('%d_%d', $this->databoxId, $this->recordId);
    }

    /**
     * @return int
     */
    public function getDataboxId()
    {
        return $this->databoxId;
    }

    /**
     * @return int
     */
    public function getRecordId()
    {
        return $this->recordId;
    }
}
