<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractEditor implements EditorInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function updateXMLWithRequest(Request $request)
    {
        $dom = $this->createBlankDom();

        if (false === @$dom->loadXML($request->request->get('xml'))) {
            throw new BadRequestHttpException('Invalid XML data.');
        }

        foreach ($this->getFormProperties() as $name => $type) {
            $value = $request->request->get($name);
            if (null !== $node = $dom->getElementsByTagName($name)->item(0)) {
                // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                while ($child = $node->firstChild) {
                    $node->removeChild($child);
                }
            } else {
                // le champ n'existait pas dans le xml, on le cree
                $node = $dom->documentElement->appendChild($dom->createElement($name));
            }
            // on fixe sa valeur
            switch ($type) {
                case static::FORM_TYPE_STRING:
                default:
                    $node->appendChild($dom->createTextNode($value));
                    break;
                case static::FORM_TYPE_BOOLEAN:
                    $node->appendChild($dom->createTextNode($value ? '1' : '0'));
                    break;
                case static::FORM_TYPE_INTEGER:
                    $node->appendChild($dom->createTextNode(null !== $value ? (int) $value : ''));
                    break;
            }
        }

        return new Response($dom->saveXML(), 200, ['Content-type' => 'text/xml']);
    }

    /**
     * {@inheritdoc}
     */
    public function facility(Application $app, Request $request)
    {
        throw new NotFoundHttpException('Route not found.');
    }

    /**
     * Returns a new blank DOM document.
     *
     * @return \DOMDocument
     */
    protected function createBlankDom()
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        return $dom;
    }

    /**
     * @return array
     */
    abstract protected function getFormProperties();
}
