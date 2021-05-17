<?php

namespace Alchemy\Phrasea\Model\Proxies\__CG__\Alchemy\Phrasea\Model\Entities;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class PsSettingKeys extends \Alchemy\Phrasea\Model\Entities\PsSettingKeys implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = [];



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'id', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'name', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'valueString', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'valueInt', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'valueText', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'parent'];
        }

        return ['__isInitialized__', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'id', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'name', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'valueString', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'valueInt', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'valueText', '' . "\0" . 'Alchemy\\Phrasea\\Model\\Entities\\PsSettingKeys' . "\0" . 'parent'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (PsSettingKeys $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', []);
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', []);
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', []);

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', []);

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', [$name]);

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueString()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getValueString', []);

        return parent::getValueString();
    }

    /**
     * {@inheritDoc}
     */
    public function setValueString($valueString)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setValueString', [$valueString]);

        return parent::setValueString($valueString);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueInt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getValueInt', []);

        return parent::getValueInt();
    }

    /**
     * {@inheritDoc}
     */
    public function setValueInt($valueInt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setValueInt', [$valueInt]);

        return parent::setValueInt($valueInt);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueText()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getValueText', []);

        return parent::getValueText();
    }

    /**
     * {@inheritDoc}
     */
    public function setValueText($valueText)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setValueText', [$valueText]);

        return parent::setValueText($valueText);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getParent', []);

        return parent::getParent();
    }

    /**
     * {@inheritDoc}
     */
    public function setSetting(\Alchemy\Phrasea\Model\Entities\PsSettings $parent = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSetting', [$parent]);

        return parent::setSetting($parent);
    }

    /**
     * {@inheritDoc}
     */
    public function setValues(array $values)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setValues', [$values]);

        return parent::setValues($values);
    }

    /**
     * {@inheritDoc}
     */
    public function asArray()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'asArray', []);

        return parent::asArray();
    }

}
