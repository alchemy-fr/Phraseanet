<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Border\File;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class SubdefsTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     * cestlafete.jpg holds an IPTC:CodedCharacterSet tag for which the exiftool
     * query yields no value
     */
    public function testMetadataRendersWhenATagQueryHasNoValue()
    {
        $app = $this->getApplication();
        $file = new File($app, $app['mediavorus']->guess(__DIR__ . '/../../../../../files/cestlafete.jpg'), $this->getCollection());
        $record = \record_adapter::createFromFile($file, $app);

        try {
            self::$DI['client']->request('GET', sprintf('/prod/subdefs/%d/%d/metadatas/document/', $record->getDataboxId(), $record->getRecordId()));
            $response = self::$DI['client']->getResponse();

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('CodedCharacterSet', $response->getContent());
        } finally {
            $record->delete();
        }
    }
}
