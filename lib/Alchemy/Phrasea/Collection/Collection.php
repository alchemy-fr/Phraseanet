<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection;

use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use PHPExiftool\Exception\LogicException;

class Collection
{

    /**
     * @var int
     */
    private $databoxId;

    /**
     * @var int
     */
    private $collectionId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $labels = [];

    /**
     * @var string|int[] Binary representation of logo
     */
    private $logo;

    /**
     * @var \DateTimeInterface
     */
    private $logoUpdatedAt;

    /**
     * @var string
     */
    private $publicWatermark;

    /**
     * @var string
     */
    private $preferences;

    /**
     * @var CollectionReference
     */
    private $collectionReference;

    public function __construct($databoxId, $collectionId, $name)
    {
        $this->databoxId = (int) $databoxId;
        $this->collectionId = (int) $collectionId;
        $this->name = (string) $name;
        $this->preferences = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs>
    <status>0</status>
    <sugestedValues></sugestedValues>
</baseprefs>
EOT;
        $this->logo = '';
        $this->labels = array(
            'en' => '',
            'fr' => '',
            'de' => '',
            'nl' => ''
        );
        $this->publicWatermark = '';
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
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * @param int $collectionId
     */
    public function setCollectionId($collectionId)
    {
        if ($this->collectionId > 0) {
            throw new LogicException('Cannot change the ID of an existing collection.');
        }

        $this->collectionId = (int) $collectionId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $name = trim(strip_tags($name));

        if ($name === '') {
            throw new \InvalidArgumentException();
        }

        $this->name = $name;
    }

    /**
     * @return string[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param string[] $labels
     */
    public function setLabels($labels)
    {
        $this->labels = $labels;
    }

    /**
     * @param string $lang
     * @param bool $substitute
     * @return string
     */
    public function getLabel($lang, $substitute = true)
    {
        if (!array_key_exists($lang, $this->labels)) {
            throw new \InvalidArgumentException(sprintf('Code %s is not defined', $lang));
        }

        if ($substitute) {
            return isset($this->labels[$lang]) ? $this->labels[$lang] : $this->name;
        }

        return $this->labels[$lang];
    }

    /**
     * @param string $lang
     * @param string $label
     */
    public function setLabel($lang, $label)
    {
        if (!array_key_exists($lang, $this->labels)) {
            throw new \InvalidArgumentException(sprintf("Language '%s' is not defined.", $lang));
        }

        $this->labels[$lang] = $label;
    }

    /**
     * @return int[]|string|null
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param int[]|string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLogoUpdatedAt()
    {
        return $this->logoUpdatedAt;
    }

    /**
     * @return string
     */
    public function getPublicWatermark()
    {
        return $this->publicWatermark;
    }

    /**
     * @param string $publicWatermark
     */
    public function setPublicWatermark($publicWatermark)
    {
        if (! in_array($publicWatermark, ['none', 'wm', 'stamp'])) {
            return;
        }

        $this->publicWatermark = $publicWatermark;
    }

    /**
     * @return string
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @param string $preferences
     */
    public function setPreferences($preferences)
    {
        $this->preferences = $preferences;
    }

    /**
     * @return CollectionReference
     */
    public function getCollectionReference()
    {
        return $this->collectionReference;
    }

    /**
     * @param CollectionReference $collectionReference
     */
    public function setCollectionReference($collectionReference)
    {
        $this->collectionReference = $collectionReference;
    }
}
