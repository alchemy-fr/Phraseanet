<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Form;

use Pimple\Container;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

class SilexFormExtension implements FormExtensionInterface
{
    private $app;
    private $types;
    private $typeExtensions;
    private $guessers;
    private $guesserLoaded = false;
    private $guesser;

    public function __construct(Container $app, array $types, array $typeExtensions, array $guessers)
    {
        $this->app = $app;
        $this->setTypes($types);
        $this->setTypeExtensions($typeExtensions);
        $this->setGuessers($guessers);
    }

    public function getType(string $name): FormTypeInterface
    {
        if (!isset($this->types[$name])) {
            throw new InvalidArgumentException(sprintf('The type "%s" is not the name of a registered form type.', $name));
        }
        if (!is_object($this->types[$name])) {
            $this->types[$name] = $this->app[$this->types[$name]];
        }

        return $this->types[$name];
    }

    public function hasType(string $name): bool
    {
        return isset($this->types[$name]);
    }

    public function getTypeExtensions(string $name): array
    {
        return isset($this->typeExtensions[$name]) ? $this->typeExtensions[$name] : [];
    }

    public function hasTypeExtensions(string $name): bool
    {
        return isset($this->typeExtensions[$name]);
    }

    public function getTypeGuesser(): ?FormTypeGuesserInterface
    {
        if (!$this->guesserLoaded) {
            $this->guesserLoaded = true;

            if ($this->guessers) {
                $guessers = [];
                foreach ($this->guessers as $guesser) {
                    if (!is_object($guesser)) {
                        $guesser = $this->app[$guesser];
                    }
                    $guessers[] = $guesser;
                }
                $this->guesser = new FormTypeGuesserChain($guessers);
            }
        }

        return $this->guesser;
    }

    private function setTypes(array $types)
    {
        $this->types = [];
        foreach ($types as $type) {
            if (!is_object($type)) {
                if (!isset($this->app[$type])) {
                    throw new InvalidArgumentException(sprintf('Invalid form type. The silex service "%s" does not exist.', $type));
                }
                $this->types[$type] = $type;
            } else {
                $this->types[get_class($type)] = $type;
            }
        }
    }

    private function setTypeExtensions(array $typeExtensions)
    {
        $this->typeExtensions = [];
        foreach ($typeExtensions as $extension) {
            if (!is_object($extension)) {
                if (!isset($this->app[$extension])) {
                    throw new InvalidArgumentException(sprintf('Invalid form type extension. The silex service "%s" does not exist.', $extension));
                }
                $extension = $this->app[$extension];
            }
            foreach ($extension::getExtendedTypes() as $extendedType) {
                $this->typeExtensions[$extendedType][] = $extension;
            }
        }
    }

    private function setGuessers(array $guessers)
    {
        $this->guessers = [];
        foreach ($guessers as $guesser) {
            if (!is_object($guesser) && !isset($this->app[$guesser])) {
                throw new InvalidArgumentException(sprintf('Invalid form type guesser. The silex service "%s" does not exist.', $guesser));
            }
            $this->guessers[] = $guesser;
        }
    }
}
