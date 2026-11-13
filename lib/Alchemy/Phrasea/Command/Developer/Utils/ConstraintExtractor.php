<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer\Utils;

use Alchemy\Phrasea\Application;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Symfony\Component\Validator\Constraint;

/**
 * Extracts translations validation constraints for Phraseanet definition.
 */
class ConstraintExtractor implements FileVisitorInterface, NodeVisitor
{
    private $messageProperties = ['message', 'minMessage', 'maxMessage', 'multipleMessage',
                                       'extractFieldsMessage', 'missingFieldsMessage', 'notFoundMessage',
                                       'notReadableMessage', 'maxSizeMessage', 'mimeTypesMessage',
                                       'uplaodIniSizeErrorMessage', 'uploadFormSizeErrorMessage',
                                       'uploadErrorMessage', 'mimeTypesMessage', 'sizeNotDetectedMessage',
                                       'maxWidthMessage', 'maxWidthMessage', 'minWidthMessage', 'maxHeightMessage',
                                       'minHeightMessage', 'invalidMessage',];

    private $app;
    private $traverser;
    private $file;
    private $catalogue;
    private $namespace = '';

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->namespace = null === $node->name ? '' : $node->name->toString();

            return;
        }

        if (!$node instanceof Class_ || null === $node->name) {
            return;
        }

        $name = '' === $this->namespace ? $node->name->toString() : $this->namespace.'\\'.$node->name->toString();

        if (!class_exists($name)) {
            return;
        }

        if (!is_a($name, 'Symfony\Component\Validator\Constraint', true)) {
            return;
        }

        $constraint = $name::create($this->app);
        $ref = new \ReflectionClass($name);

        foreach ($this->messageProperties as $prop) {
            if ($ref->hasProperty($prop)) {
                $message = new Message($constraint->$prop, 'validators');
                $this->catalogue->add($message);
            }
        }
    }

    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast)
    {
        $this->file = $file;
        $this->namespace = '';
        $this->catalogue = $catalogue;
        $this->traverser->traverse($ast);
    }

    public function beforeTraverse(array $nodes) { }
    public function leaveNode(Node $node) { }
    public function afterTraverse(array $nodes) { }
    public function visitFile(\SplFileInfo $file, MessageCatalogue $catalogue) { }
    public function visitTwigFile(\SplFileInfo $file, MessageCatalogue $catalogue, \Twig\Node\Node $ast) { }
}
