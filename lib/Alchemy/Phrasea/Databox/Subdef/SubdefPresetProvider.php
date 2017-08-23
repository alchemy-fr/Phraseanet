<?php

namespace Alchemy\Phrasea\Databox\Subdef;

class SubdefPresetProvider
{

    private $presets = [];

    /**
     * @param string $type Type of media for which to get presets
     * @return SubdefPreset[]
     */
    public function getPresets($type)
    {
        if (! isset($this->presets[$type])) {
            throw new \InvalidArgumentException('Invalid type');
        }

        return $this->presets[$type];
    }
}
