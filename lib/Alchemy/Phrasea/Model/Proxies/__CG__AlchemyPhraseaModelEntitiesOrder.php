<?php

namespace Alchemy\Phrasea\Model\Proxies\__CG__\Alchemy\Phrasea\Model\Entities;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Order extends \Alchemy\Phrasea\Model\Entities\Order implements \Doctrine\ORM\Proxy\Proxy
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
    public static $lazyPropertiesDefaults = array();



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
            return array('__isInitialized__', 'id', 'usrId', 'orderUsage', 'todo', 'deadline', 'createdOn', 'elements', 'basket');
        }

        return array('__isInitialized__', 'id', 'usrId', 'orderUsage', 'todo', 'deadline', 'createdOn', 'elements', 'basket');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Order $proxy) {
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
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
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


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setUsrId($usrId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUsrId', array($usrId));

        return parent::setUsrId($usrId);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsrId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUsrId', array());

        return parent::getUsrId();
    }

    /**
     * {@inheritDoc}
     */
    public function setDeadline($deadline)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDeadline', array($deadline));

        return parent::setDeadline($deadline);
    }

    /**
     * {@inheritDoc}
     */
    public function getDeadline()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDeadline', array());

        return parent::getDeadline();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreatedOn($createdOn)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreatedOn', array($createdOn));

        return parent::setCreatedOn($createdOn);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedOn()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCreatedOn', array());

        return parent::getCreatedOn();
    }

    /**
     * {@inheritDoc}
     */
    public function addElement(\Alchemy\Phrasea\Model\Entities\OrderElement $elements)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addElement', array($elements));

        return parent::addElement($elements);
    }

    /**
     * {@inheritDoc}
     */
    public function removeElement(\Alchemy\Phrasea\Model\Entities\OrderElement $elements)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeElement', array($elements));

        return parent::removeElement($elements);
    }

    /**
     * {@inheritDoc}
     */
    public function getElements()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getElements', array());

        return parent::getElements();
    }

    /**
     * {@inheritDoc}
     */
    public function getUser(\Alchemy\Phrasea\Application $app)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUser', array($app));

        return parent::getUser($app);
    }

    /**
     * {@inheritDoc}
     */
    public function setTodo($todo)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setTodo', array($todo));

        return parent::setTodo($todo);
    }

    /**
     * {@inheritDoc}
     */
    public function getTodo()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTodo', array());

        return parent::getTodo();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotal()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTotal', array());

        return parent::getTotal();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrderUsage($orderUsage)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOrderUsage', array($orderUsage));

        return parent::setOrderUsage($orderUsage);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderUsage()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrderUsage', array());

        return parent::getOrderUsage();
    }

    /**
     * {@inheritDoc}
     */
    public function setBasket(\Alchemy\Phrasea\Model\Entities\Basket $basket = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBasket', array($basket));

        return parent::setBasket($basket);
    }

    /**
     * {@inheritDoc}
     */
    public function getBasket()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBasket', array());

        return parent::getBasket();
    }

}
