<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Serializer;

use Symfony\Component\Yaml\Dumper as YamlDumper;

class CaptionSerializer
{
    const SERIALIZE_XML = 'xml';
    const SERIALIZE_YAML = 'yaml';
    const SERIALIZE_JSON = 'json';

    public function serialize(\caption_record $caption, $format, $includeBusinessFields = false)
    {
        switch ($format) {
            case self::SERIALIZE_XML:
                return $this->serializeXML($caption, (Boolean) $includeBusinessFields);
                break;
            case self::SERIALIZE_YAML:
                return $this->serializeYAML($caption, (Boolean) $includeBusinessFields);
                break;
            case self::SERIALIZE_JSON:
                return $this->serializeJSON($caption, (Boolean) $includeBusinessFields);
                break;
            default:
                throw new \Exception(sprintf('Unknown format %s', $format));
                break;
        }
    }

    private function serializeYAML(\caption_record $caption, $includeBusinessFields)
    {
        return (new YamlDumper())->dump($this->toArray($caption, $includeBusinessFields), 3);
    }

    private function serializeJSON(\caption_record $caption, $includeBusinessFields)
    {
        return \p4string::jsonencode($this->toArray($caption, $includeBusinessFields));
    }

    private function toArray(\caption_record $caption, $includeBusinessFields)
    {
        $buffer = [];

        foreach ($caption->get_fields([], $includeBusinessFields) as $field) {
            $vi = $field->get_values();

            if ($field->is_multi()) {
                $buffer[$field->get_name()] = [];
                foreach ($vi as $value) {
                    $val = $value->getValue();
                    $buffer[$field->get_name()][] = ctype_digit($val) ? (int) $val : $this->sanitizeSerializedValue($val);
                }
            } else {
                $value = array_pop($vi);
                $val = $value->getValue();
                $buffer[$field->get_name()] = ctype_digit($val) ? (int) $val : $this->sanitizeSerializedValue($val);
            }
        }

        return ['record' => ['description' => $buffer]];
    }

    private function serializeXML(\caption_record $caption, $includeBusinessFields)
    {
        $dom_doc = new \DOMDocument('1.0', 'UTF-8');
        $dom_doc->formatOutput = true;
        $dom_doc->standalone = true;

        $record = $dom_doc->createElement('record');
        $record->setAttribute('record_id', $caption->get_record()->get_record_id());
        $dom_doc->appendChild($record);
        $description = $dom_doc->createElement('description');
        $record->appendChild($description);

        foreach ($caption->get_fields([], $includeBusinessFields) as $field) {
            $values = $field->get_values();

            foreach ($values as $value) {
                $elem = $dom_doc->createElement($field->get_name());
                $elem->appendChild($dom_doc->createTextNode($this->sanitizeSerializedValue($value->getValue())));
                $elem->setAttribute('meta_id', $value->getId());
                $elem->setAttribute('meta_struct_id', $field->get_meta_struct_id());
                $description->appendChild($elem);
            }
        }

        $doc = $dom_doc->createElement('doc');

        $tc_datas = $caption->get_record()->get_technical_infos();

        foreach ($tc_datas as $key => $data) {
            $doc->setAttribute($key, $data);
        }

        $record->appendChild($doc);

        return $dom_doc->saveXML();
    }

    protected function sanitizeSerializedValue($value)
    {
        return str_replace([
            "\x00", //null
            "\x01", //start heading
            "\x02", //start text
            "\x03", //end of text
            "\x04", //end of transmission
            "\x05", //enquiry
            "\x06", //acknowledge
            "\x07", //bell
            "\x08", //backspace
            "\x0C", //new page
            "\x0E", //shift out
            "\x0F", //shift in
            "\x10", //data link escape
            "\x11", //dc 1
            "\x12", //dc 2
            "\x13", //dc 3
            "\x14", //dc 4
            "\x15", //negative ack
            "\x16", //synchronous idle
            "\x17", //end of trans block
            "\x18", //cancel
            "\x19", //end of medium
            "\x1A", //substitute
            "\x1B", //escape
            "\x1C", //file separator
            "\x1D", //group sep
            "\x1E", //record sep
            "\x1F", //unit sep
        ], '', $value);
    }
}
