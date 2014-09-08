<?php

namespace Alchemy\Phrasea\Utilities;

use Alchemy\Phrasea\Exception\RuntimeException;

/** Parse crossdomain.xml file */
class CrossDomainParser
{
    public function parse($file)
    {
        if (!file_exists($file)) {
            throw new RuntimeException(sprintf('File "%s" does not exist.', $file));
        }

        $xml = simplexml_load_file($file);

        if (!$xml) {
            throw new RuntimeException(sprintf('File "%s" could not be parsed.', $file));
        }

        $conf = array();

        if (isset($xml->{"site-control"})) {
            $sc = $xml->{"site-control"};
            foreach ($sc->attributes() as $k => $v) {
                if ($k === 'permitted-cross-domain-policies') {
                    $conf['site-control'] = (string) $v;
                }
            }
        }
        if (isset($xml->{"allow-access-from"})) {
            if (count($xml->{"allow-access-from"}) > 0) {
                $conf['allow-access-from'] = array();
                foreach ($xml->{"allow-access-from"} as $el) {
                    $opt = array();
                    foreach ($el->attributes() as $k => $v) {
                        $opt[$k] = (string) $v;
                    }
                    $conf['allow-access-from'][] = $opt;
                }
            }
        }
        if (isset($xml->{"signatory"})) {
            if (count($xml->{"signatory"}) > 0) {
                $conf['allow-access-from-identity'] = array();
                foreach ($xml->{"signatory"} as $el) {
                    if (isset($el->{"certificate"})) {
                        $c = array();
                        foreach ($el->{"certificate"}->attributes() as $k => $v) {
                            $c[$k] = (string) $v;
                        }
                        $conf['allow-access-from-identity'][] = $c;
                    }
                }
            }
        }
        if (isset($xml->{"allow-http-request-headers-from"})) {
            if (count($xml->{"allow-http-request-headers-from"}) > 0) {
                $conf['allow-http-request-headers-from'] = array();
                foreach ($xml->{"allow-http-request-headers-from"} as $el) {
                    $opt = array();
                    foreach ($el->attributes() as $k => $v) {
                        $opt[$k] = (string) $v;
                    }
                    $conf['allow-http-request-headers-from'][] = $opt;
                }
            }
        }

        return $conf;
    }
}
