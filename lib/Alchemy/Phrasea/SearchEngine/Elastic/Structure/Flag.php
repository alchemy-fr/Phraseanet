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
        $labelOn = self::getLabelOn($status['bit'], $status['labelon']);
        return new self(self::normalizeName($labelOn));
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

    public static function getLabelOn($bit, $labelOn)
    {
        if(!$labelOn) {
            $labelOn = 'sb_' . $bit;
        }

        return $labelOn;
    }

    /*
     * TODO: Rewrite to have all data injected at construct time in createFromLegacyStatus()
     */
    public function getBitPositionInDatabox(\databox $databox)
    {
        foreach ($databox->getStatusStructure() as $bit => $status) {
            $labelOn = self::getLabelOn($bit, $status['labelon']);
            $candidate_name = self::normalizeName($labelOn);
            if ($candidate_name === $this->name) {
                return (int) $status['bit'];
            }
        }

        return null;
    }
}
