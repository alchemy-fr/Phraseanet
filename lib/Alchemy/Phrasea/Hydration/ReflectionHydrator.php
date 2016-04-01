<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Hydration;

use Assert\Assertion;

class ReflectionHydrator implements Hydrator
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string[]
     */
    private $properties;

    /**
     * @var null|\ReflectionClass
     */
    private $reflectionClass;
    /**
     * @var null|\ReflectionProperty[]
     */
    private $reflectionProperties;

    /**
     * @param string $className
     * @param string[] $properties
     */
    public function __construct($className, array $properties)
    {
        $this->className = $className;
        $this->properties = $properties;
    }

    /**
     * @param array $data
     * @param object $instance
     * @throws \Assert\AssertionFailedException
     */
    public function hydrate($instance, array $data)
    {
        Assertion::isInstanceOf($instance, $this->className);

        foreach ($data as $key => $value) {
            $this->getReflectionProperty($key)->setValue($instance, $value);
        }
    }

    /**
     * @param object $instance
     * @return array
     * @throws \Assert\AssertionFailedException
     */
    public function extract($instance)
    {
        Assertion::isInstanceOf($instance, $this->className);

        $data = [];

        foreach ($this->getReflectionProperties() as $name => $property) {
            $data[$name] = $property->getValue($instance);
        }

        return $data;
    }

    /**
     * @return \ReflectionClass
     */
    private function getReflectionClass()
    {
        if (null === $this->reflectionClass) {
            $this->reflectionClass = new \ReflectionClass($this->className);
        }

        return $this->reflectionClass;
    }

    /**
     * @param string $name
     * @return \ReflectionProperty
     * @throws \RuntimeException
     */
    private function getReflectionProperty($name)
    {
        $this->loadReflectionProperties();

        return $this->reflectionProperties[$name];
    }

    /**
     * @return \ReflectionProperty[]
     */
    private function getReflectionProperties()
    {
        $this->loadReflectionProperties();

        return $this->reflectionProperties;
    }

    private function loadReflectionProperties()
    {
        if (null !== $this->reflectionProperties) {
            return;
        }

        $class = $this->getReflectionClass();
        $properties = [];

        foreach ($this->properties as $name) {
            $property = $class->getProperty($name);
            $property->setAccessible(true);

            $properties[$name] = $property;
        }

        $this->reflectionProperties = $properties;
    }
}
