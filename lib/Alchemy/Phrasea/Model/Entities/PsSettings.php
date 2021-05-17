<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PsSettings
 *
 * @ORM\Table(
 *     name="PS_Settings",
 *     indexes={
 *          @ORM\Index(name="parent_id", columns={"parent_id"}),
 *          @ORM\Index(name="role", columns={"role"}),
 *          @ORM\Index(name="name", columns={"name"}),
 *          @ORM\Index(name="value_int", columns={"value_int"}),
 *          @ORM\Index(name="value_string", columns={"value_string"})
 *      }
 *     )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\PsSettingsRepository")
 */
class PsSettings
{
    public function __construct()
    {
        $this->keys     = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

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
     * @ORM\Column(name="role", type="string", length=32, nullable=false)
     */
    private $role;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value_text", type="text", length=65535, nullable=true)
     */
    private $valueText = null;

    /**
     * @var integer
     *
     * @ORM\Column(name="value_int", type="integer", nullable=true)
     */
    private $valueInt = null;

    /**
     * @var string
     *
     * @ORM\Column(name="value_string", type="string", length=255, nullable=true)
     */
    private $valueString = null;

    /**
     * @var PsSettings
     *
     * @ORM\ManyToOne(targetEntity="PsSettings", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parent;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PsSettings", mappedBy="parent", cascade={"persist"})
     *
     */
    private $children;

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PsSettingKeys", mappedBy="setting", cascade={"persist"})
     *
     */
    private $keys;

    /**
     * @return ArrayCollection
     */
    public function getKeys()
    {
        return $this->keys;
    }

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
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set role
     *
     * @param string $role
     *
     * @return PsSettings
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
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
     * @return PsSettings
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return PsSettings
     */
    public function setValueText($valueText)
    {
        $this->valueText = $valueText;

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
     * @return PsSettings
     */
    public function setValueInt($valueInt)
    {
        $this->valueInt = $valueInt;

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
     * @return PsSettings
     */
    public function setValueString($valueString)
    {
        $this->valueString = $valueString;

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
     * @param PsSettings $parent
     *
     * @return PsSettings
     */
    public function setParent(PsSettings $parent = null)
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

    public function setKey(string $keyName, array $values = [])
    {
        /** @var PsSettingKeys $key */
        foreach($this->getKeys() as $key) {
            if($key->getKeyName() === $keyName) {
                $key->setValues($values);
                return $key;
            }
        }
        $key = new PsSettingKeys();
        $key->setKeyName($keyName)->setSetting($this)->setValues($values);
        $this->keys->add($key);

        return $key;
    }

    public function asArray()
    {
        $r = [];

        if(!is_null($v = $this->getRole())) {
            $r['role'] = $v;
        }
        if(!is_null($v = $this->getName())) {
            $r['name'] = $v;
        }
        if(!is_null($v = $this->getValueText())) {
            $r['valueText'] = $v;
        }
        if(!is_null($v = $this->getValueString())) {
            $r['valueString'] = $v;
        }
        if(!is_null($v = $this->getValueInt())) {
            $r['valueInt'] = $v;
        }

        foreach($this->getKeys() as $key) {
            /** @var PsSettingKeys $key */
            if(!array_key_exists('keys', $r)) {
                $r['keys'] = [];
            }
            $r['keys'][] = $key->asArray();
        }

        foreach($this->getChildren() as $child) {
            /** @var PsSettings $child */
            if(!array_key_exists('children', $r)) {
               $r['children'] = [];
            }
            $r['children'][] = $child->asArray();
        }

        return $r;
    }

}
