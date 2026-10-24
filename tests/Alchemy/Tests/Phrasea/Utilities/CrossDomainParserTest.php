<?php

namespace Alchemy\Tests\Phrasea\Utilities;

use Alchemy\Phrasea\Utilities\CrossDomainParser;

class CrossDomainParserTest extends \PHPUnit\Framework\TestCase
{
    public function testParser()
    {
        $parser = new CrossDomainParser();
        $this->assertEquals($parser->parse(__DIR__.'/fixture.crossdomain.xml'), array(
            'site-control' => 'master-only',
            'allow-access-from' => array(
                array(
                    'domain'=> '*.example.com',
                    'secure'=> 'false'
                ),
                array(
                    'domain'=> 'www.example.com',
                    'secure'=>'true',
                    'to-ports'=>'507,516-523'
                )
            ),
            'allow-access-from-identity' => array(
                array(
                    'fingerprint-algorithm'=> 'sha-1',
                    'fingerprint'=> '01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67'
                ),
                array(
                    'fingerprint-algorithm'=> 'sha256',
                    'fingerprint' => '01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67'
                )
            ),
            'allow-http-request-headers-from' => array(
                array(
                    'domain'=> '*.bar.com',
                    'secure'=> 'true',
                    'headers'=> 'SOAPAction, X-Foo*'
                ),
                array(
                    'domain'=> 'foo.example.com',
                    'secure'=> 'false',
                    'headers'=> 'Authorization,X-Foo*'
                )
            )
        ));
    }
}
