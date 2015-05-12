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
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class SubdefsEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/subdefs.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPeriod()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings(PropertyAccess $config = null)
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <embedded>1</embedded>
  <sbas/>
  <type_image>1</type_image>
  <type_video>1</type_video>
  <type_audio>1</type_audio>
  <type_document>1</type_document>
  <type_flash>1</type_flash>
  <type_unknown>1</type_unknown>
  <flush>5</flush>
  <maxrecs>20</maxrecs>
  <maxmegs>256</maxmegs>
</tasksettings>
EOF;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return [
            'sbas' => static::FORM_TYPE_INTEGER,
            'type_image' => static::FORM_TYPE_BOOLEAN,
            'type_video' => static::FORM_TYPE_BOOLEAN,
            'type_audio' => static::FORM_TYPE_BOOLEAN,
            'type_document' => static::FORM_TYPE_BOOLEAN,
            'type_flash' => static::FORM_TYPE_BOOLEAN,
            'type_unknown' => static::FORM_TYPE_BOOLEAN,
            'flush' => static::FORM_TYPE_INTEGER,
            'maxrecs' => static::FORM_TYPE_INTEGER,
            'maxmegs' => static::FORM_TYPE_INTEGER,
            'embedded' => static::FORM_TYPE_BOOLEAN,
        ];
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

            $values = $request->request->get($name);
            if($values === null) {
                $values = array();
            } elseif(!is_array($values)) {
                $values = array($values);
            }

            // erase the former setting but keep the node in place.
            // in case on multi-valued, keep only the first node (except if no value at all: erase all)
            foreach($dom->getElementsByTagName($name) as $i=>$node) {
                if($i > 0 || count($values)==0) {
                    $node->parentNode->removeChild($node);
                } else {
                    while ($child = $node->firstChild) {
                        $node->removeChild($child);
                    }
                }
            }
            // if no setting to write, no reason to create a node
            if(count($values) == 0) {
                continue;
            }

            // in case the node did not exist at all, create one
            if ( ($node = $dom->getElementsByTagName($name)->item(0)) === null) {
                $node = $dom->documentElement->appendChild($dom->createElement($name));
            }

            // because dom::insertBefore is used, reverse allows to respect order while serializing.
            $values = array_reverse($values);

            // write
            foreach($values as $i=>$value) {
                if($i>0) {
                    // multi-valued ? add an entry
                    $node = $dom->documentElement->insertBefore($dom->createElement($name), $node);
                }
                $node->appendChild($dom->createTextNode($this->toXMLValue($type, $value)));
            }
        }

        return new Response($dom->saveXML(), 200, ['Content-type' => 'text/xml']);
    }

    private function toXMLValue($type, $value)
    {
        switch ($type) {
            case static::FORM_TYPE_BOOLEAN:
                $value = (!$value ? '0' : '1');
                break;
            case static::FORM_TYPE_INTEGER:
                $value = ($value !== null ? (string)((int) $value) : '');
                break;
        }
        return $value;
    }

}
