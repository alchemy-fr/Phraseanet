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

use JMS\TranslationBundle\Exception\RuntimeException;
use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Annotation\Meaning;
use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use Doctrine\Common\Annotations\DocParser;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class HelpMessageExtractor implements FileVisitorInterface, \PHPParser_NodeVisitor
{
    private $docParser;
    private $traverser;
    private $file;
    private $catalogue;
    private $logger;
    private $defaultDomain;
    private $defaultDomainMessages;

    public function __construct(DocParser $docParser)
    {
        $this->docParser = $docParser;

        $this->traverser = new \PHPParser_NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    public function enterNode(\PHPParser_Node $node)
    {
        if ($node instanceof \PHPParser_Node_Stmt_Class) {
            $this->defaultDomain = null;
            $this->defaultDomainMessages = [];
        }

         if ($node instanceof \PHPParser_Node_Expr_Array) {
            // first check if a translation_domain is set for this field
            $domain = null;
            foreach ($node->items as $item) {
                if (!$item->key instanceof \PHPParser_Node_Scalar_String) {
                    continue;
                }

                if ('translation_domain' === $item->key->value) {
                    if (!$item->value instanceof \PHPParser_Node_Scalar_String) {
                        continue;
                    }

                    $domain = $item->value->value;
                }
            }

            // look for options containing a message
            foreach ($node->items as $item) {
                if (!$item->key instanceof \PHPParser_Node_Scalar_String) {
                    continue;
                }

                if ('help_message' !== $item->key->value) {
                    continue;
                }

                $this->parseItem($item, $domain);
            }
        }
    }

    private function parseItem($item, $domain = null)
    {
        // get doc comment
        $ignore = false;
        $desc = $meaning = null;
        $docComment = $item->key->getDocComment();
        $docComment = $docComment ? $docComment : $item->value->getDocComment();
        if ($docComment) {
            foreach ($this->docParser->parse($docComment, 'file '.$this->file.' near line '.$item->value->getLine()) as $annot) {
                if ($annot instanceof Ignore) {
                    $ignore = true;
                } elseif ($annot instanceof Desc) {
                    $desc = $annot->text;
                } elseif ($annot instanceof Meaning) {
                    $meaning = $annot->text;
                }
            }
        }

        if (!$item->value instanceof \PHPParser_Node_Scalar_String) {
            if ($ignore) {
                return;
            }

            $message = sprintf('Unable to extract translation id for form label from non-string values, but got "%s" in %s on line %d. Please refactor your code to pass a string, or add "/** @Ignore */".', get_class($item->value), $this->file, $item->value->getLine());
            if ($this->logger) {
                $this->logger->err($message);

                return;
            }

            throw new RuntimeException($message);
        }

        $source = new FileSource((string) $this->file, $item->value->getLine());
        $id = $item->value->value;

        if (null === $domain) {
            $this->defaultDomainMessages[] = [
                'id' => $id,
                'source' => $source,
                'desc' => $desc,
                'meaning' => $meaning
            ];
        } else {
            $this->addToCatalogue($id, $source, $domain, $desc, $meaning);
        }
    }

    private function addToCatalogue($id, $source, $domain = null, $desc = null, $meaning = null)
    {
        if (null === $domain) {
            $message = new Message($id);
        } else {
            $message = new Message($id, $domain);
        }

        $message->addSource($source);

        if ($desc) {
            $message->setDesc($desc);
        }

        if ($meaning) {
            $message->setMeaning($meaning);
        }

        $this->catalogue->add($message);
    }

    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast)
    {
        $this->file = $file;
        $this->catalogue = $catalogue;
        $this->traverser->traverse($ast);

        if ($this->defaultDomainMessages) {
            foreach ($this->defaultDomainMessages as $message) {
                $this->addToCatalogue($message['id'], $message['source'], $this->defaultDomain, $message['desc'], $message['meaning']);
            }
        }
    }

    public function leaveNode(\PHPParser_Node $node) { }

    public function beforeTraverse(array $nodes) { }
    public function afterTraverse(array $nodes) { }
    public function visitFile(\SplFileInfo $file, MessageCatalogue $catalogue) { }
    public function visitTwigFile(\SplFileInfo $file, MessageCatalogue $catalogue, \Twig_Node $ast) { }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
