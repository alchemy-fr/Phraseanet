<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PsSettings
 *
 * @ORM\Table(name="PS_Settings", indexes={@ORM\Index(name="parent_id", columns={"parent_id"}), @ORM\Index(name="role", columns={"role"}), @ORM\Index(name="name", columns={"name"}), @ORM\Index(name="value_int", columns={"value_int"}), @ORM\Index(name="value_char", columns={"value_varchar"})})
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
     * @ORM\Column(name="value_varchar", type="string", length=255, nullable=true)
     */
    private $valueVarchar = null;

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
     * @ORM\OneToMany(targetEntity="PsSettings", mappedBy="parent")
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
     * @ORM\OneToMany(targetEntity="PsSettingKeys", mappedBy="setting")
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
     * @return PsSettings
     */
    public function setValueVarchar($valueVarchar)
    {
        $this->valueVarchar = $valueVarchar;

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
                case 'valueVarchar':
                    $this->setValueVarchar($v);
                    break;
            }
        }
    }

    public function addKey(string $keyName, array $values = [])
    {
        $ke = new PsSettingKeys();
        $ke->setKeyName($keyName)->setSetting($this)->setValues($values);
        $this->keys->add($ke);

        return $ke;
    }

    public function asArray()
    {
        $r = [
            'role' => $this->getRole(),
            'name' => $this->getName(),
        ];
        if(!is_null($v = $this->getValueText())) {
            $r['value_text'] = $v;
        }
        if(!is_null($v = $this->getValueVarchar())) {
            $r['value_string'] = $v;
        }
        if(!is_null($v = $this->getValueInt())) {
            $r['value_int'] = $v;
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
