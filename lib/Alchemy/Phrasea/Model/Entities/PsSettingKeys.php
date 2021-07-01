<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PsSettingKeys
 *
 * @ORM\Table(
 *     name="PS_Setting_Keys",
 *     indexes={
 *          @ORM\Index(name="name", columns={"name"}),
 *          @ORM\Index(name="value_int", columns={"value_int"}),
 *          @ORM\Index(name="value_string", columns={"value_string"}),
 *          @ORM\Index(name="parent_id", columns={"parent_id"})
 *      }
 *    )
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
     * @ORM\Column(name="name", type="string", length=32, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value_string", type="string", length=255, nullable=true)
     */
    private $valueString = null;

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
     * @var PsSettings
     *
     * @ORM\ManyToOne(targetEntity="PsSettings", inversedBy="keys")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parent;


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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return PsSettingKeys
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get valueString
     *
     * @return string
     */
    public function getValueString()
    {
        return $this->valueString;
    }

    /**
     * Set valueString
     *
     * @param string $valueString
     *
     * @return PsSettingKeys
     */
    public function setValueString($valueString)
    {
        $this->valueString = $valueString;

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
     * Get parent
     *
     * @return PsSettings
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set parent
     *
     * @param PsSettings|null $parent
     * @return PsSettingKeys
     */
    public function setSetting(PsSettings $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    public function setValues(array $values)
    {
        foreach ($values as $k => $v) {
            switch ($k) {
                case 'valueText':
                    $this->setValueText($v);
                    break;
                case 'valueInt':
                    $this->setValueInt($v);
                    break;
                case 'valueString':
                    $this->setValueString($v);
                    break;
            }
        }

        return $this;
    }

    public function asArray()
    {
        $r = [];
        if (!is_null($v = $this->getName())) {
            $r['name'] = $v;
        }
        if (!is_null($v = $this->getValueText())) {
            $r['valueText'] = $v;
        }
        if (!is_null($v = $this->getValueString())) {
            $r['valueString'] = $v;
        }
        if (!is_null($v = $this->getValueInt())) {
            $r['valueInt'] = $v;
        }

        return $r;
    }

    public static function fromArray(array $a)
    {
        $e = new self();
        if (array_key_exists('name', $a) && is_scalar(($v = $a['name']))) {
            $e->setName($v);
        }
        if (array_key_exists('valueText', $a) && is_scalar(($v = $a['valueText']))) {
            $e->setValueText($v);
        }
        if (array_key_exists('valueString', $a) && is_scalar(($v = $a['valueString']))) {
            $e->setValueString($v);
        }
        if (array_key_exists('valueInt', $a) && is_scalar(($v = $a['valueInt']))) {
            $e->setValueInt($v);
        }

        return $e;
    }
}