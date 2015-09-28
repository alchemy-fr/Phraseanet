<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\StringUtils;
use Assert\Assertion;
use InvalidArgumentException;

class Flag
{
    private $name;

    public static function createFromLegacyStatus(array $status)
    {
        if (!isset($status['labelon'])) {
            throw new InvalidArgumentException('Status array must contain the "labelon" key.');
        }
        return new self(self::normalizeName($status['labelon']));
    }

    public function __construct($name)
    {
        Assertion::string($name);
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIndexField()
    {
        return sprintf('flags.%s', $this->name);
    }

    public static function normalizeName($key)
    {
        return StringUtils::slugify($key, '_');
    }

    /*
     * TODO: Rewrite to have all data injected at construct time in createFromLegacyStatus()
     */
    public function getBitPositionInDatabox(\databox $databox)
    {
        foreach ($databox->getStatusStructure() as $bit => $status) {
            $candidate_name = self::normalizeName($status['labelon']);
            if ($candidate_name === $this->name) {
                return (int) $status['bit'];
            }
        }
    }
}
