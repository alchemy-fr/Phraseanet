<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PsSettingKeys
 *
 * @ORM\Table(name="PS_Setting_Keys", indexes={@ORM\Index(name="value_int", columns={"value_int"}), @ORM\Index(name="value_varchar", columns={"value_varchar"}), @ORM\Index(name="setting_id", columns={"setting_id"}), @ORM\Index(name="key", columns={"key"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\PsSettingKeysRepository")
 */
class PsSettingKeys
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="key_name", type="string", length=32, nullable=false)
     */
    private $keyName;

    /**
     * @var string
     *
     * @ORM\Column(name="value_varchar", type="string", length=255, nullable=true)
     */
    private $valueVarchar = null;

    /**
     * @var integer
     *
     * @ORM\Column(name="value_int", type="integer", nullable=true)
     */
    private $valueInt = null;

    /**
     * @var string
     *
     * @ORM\Column(name="value_text", type="text", length=65535, nullable=true)
     */
    private $valueText = null;

    /**
     * @var \Alchemy\Phrasea\Model\Entities\PsSettings
     *
     * @ORM\ManyToOne(targetEntity="Alchemy\Phrasea\Model\Entities\PsSettings")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="setting_id", referencedColumnName="id")
     * })
     */
    private $setting;



    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get keyName
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName;
    }

    /**
     * Set key
     *
     * @param string $keyName
     *
     * @return PsSettingKeys
     */
    public function setKeyName($keyName)
    {
        $this->keyName = $keyName;

        return $this;
    }

    /**
     * Get valueVarchar
     *
     * @return string
     */
    public function getValueVarchar()
    {
        return $this->valueVarchar;
    }

    /**
     * Set valueVarchar
     *
     * @param string $valueVarchar
     *
     * @return PsSettingKeys
     */
    public function setValueVarchar($valueVarchar)
    {
        $this->valueVarchar = $valueVarchar;

        return $this;
    }

    /**
     * Get valueInt
     *
     * @return integer
     */
    public function getValueInt()
    {
        return $this->valueInt;
    }

    /**
     * Set valueInt
     *
     * @param integer $valueInt
     *
     * @return PsSettingKeys
     */
    public function setValueInt($valueInt)
    {
        $this->valueInt = $valueInt;

        return $this;
    }

    /**
     * Get valueText
     *
     * @return string
     */
    public function getValueText()
    {
        return $this->valueText;
    }

    /**
     * Set valueText
     *
     * @param string $valueText
     *
     * @return PsSettingKeys
     */
    public function setValueText($valueText)
    {
        $this->valueText = $valueText;

        return $this;
    }

    /**
     * Get setting
     *
     * @return \Alchemy\Phrasea\Model\Entities\PsSettings
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * Set setting
     *
     * @param \Alchemy\Phrasea\Model\Entities\PsSettings $setting
     *
     * @return PsSettingKeys
     */
    public function setSetting(\Alchemy\Phrasea\Model\Entities\PsSettings $setting = null)
    {
        $this->setting = $setting;

        return $this;
    }
}
