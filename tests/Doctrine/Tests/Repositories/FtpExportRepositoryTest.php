<?php

namespace Doctrine\Tests\Repositories;

use Entities\FtpExport;
use Entities\FtpExportElement;
use Gedmo\Timestampable\TimestampableListener;

class FtpExportRepositoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFindCrashedExportsWithoutDate()
    {
        $failure1 = new FtpExport();
        $failure1
                ->setAddr('')
                ->setUser(self::$DI['user'])
                ->setCrash(2)
                ->setNbretry(2);

        $failure2 = new FtpExport();
        $failure2
                ->setAddr('')
                ->setUser(self::$DI['user'])
                ->setCrash(7)
                ->setNbretry(5);

        $good1 = new FtpExport();
        $good1
                ->setAddr('')
                ->setUser(self::$DI['user'])
                ->setCrash(2)
                ->setNbretry(3);

        $good2 = new FtpExport();
        $good2
                ->setAddr('')
                ->setUser(self::$DI['user'])
                ->setCrash(12)
                ->setNbretry(23);

        self::$DI['app']['EM']->persist($failure1);
        self::$DI['app']['EM']->persist($failure2);
        self::$DI['app']['EM']->persist($good1);
        self::$DI['app']['EM']->persist($good2);
        self::$DI['app']['EM']->flush();

        $crashed = self::$DI['app']['EM']
                ->getRepository('Entities\FtpExport')
                ->findCrashedExports();

        $this->assertCount(2, $crashed);
        $this->assertContains($failure1, $crashed);
        $this->assertContains($failure2, $crashed);
    }

    public function testFindCrashedExportsWithDate()
    {
        self::$DI['app']['EM']->getEventManager()->removeEventSubscriber(new TimestampableListener());

        $failure1 = new FtpExport();
        $failure1
                ->setAddr('Failure 1')
                ->setUser(self::$DI['user'])
                ->setCrash(2)
                ->setNbretry(2)
                ->setCreated(new \DateTime('-6 days'));

        $failure2 = new FtpExport();
        $failure2
                ->setAddr('Failure 2')
                ->setUser(self::$DI['user'])
                ->setCrash(2)
                ->setNbretry(2)
                ->setCreated(new \DateTime('-7 days'));

        $good1 = new FtpExport();
        $good1
                ->setAddr('Good 1')
                ->setUser(self::$DI['user'])
                ->setCrash(7)
                ->setNbretry(5)
                ->setCreated(new \DateTime('-5 days'));

        $good2 = new FtpExport();
        $good2
                ->setAddr('Good 2')
                ->setUser(self::$DI['user'])
                ->setCrash(2)
                ->setNbretry(3)
                ->setCreated(new \DateTime('-9 days'));

        $good3 = new FtpExport();
        $good3
                ->setAddr('Good 3')
                ->setUser(self::$DI['user'])
                ->setCrash(12)
                ->setNbretry(23)
                ->setCreated(new \DateTime('-6 days'));

        self::$DI['app']['EM']->persist($failure1);
        self::$DI['app']['EM']->persist($failure2);
        self::$DI['app']['EM']->persist($good1);
        self::$DI['app']['EM']->persist($good2);
        self::$DI['app']['EM']->persist($good3);
        self::$DI['app']['EM']->flush();

        $crashed = self::$DI['app']['EM']
                ->getRepository('Entities\FtpExport')
                ->findCrashedExports(new \DateTime('-6 days'));

        $this->assertCount(2, $crashed);
        $this->assertContains($failure1, $crashed);
        $this->assertContains($failure2, $crashed);
    }

    public function testFindDoableExports()
    {
        $notDoable1 = new FtpExport();
        $notDoable1
                ->setAddr('Not Doable 1')
                ->setUser(self::$DI['user']);

        $elem1 = new FtpExportElement();
        $elem1
                ->setSubdef('subdef')
                ->setFilename('name')
                ->setBaseId(self::$DI['record_1']->get_base_id())
                ->setRecordId(self::$DI['record_1']->get_record_id())
                ->setDone(true);

        $elem1->setExport($notDoable1);
        $notDoable1->addElement($elem1);

        $notDoable2 = new FtpExport();
        $notDoable2
                ->setAddr('Not Doable 2')
                ->setUser(self::$DI['user']);

        $doable1 = new FtpExport();
        $doable1
                ->setAddr('Doable 1')
                ->setUser(self::$DI['user']);

        $elem2 = new FtpExportElement();
        $elem2
                ->setSubdef('subdef')
                ->setFilename('name')
                ->setBaseId(self::$DI['record_1']->get_base_id())
                ->setRecordId(self::$DI['record_1']->get_record_id())
                ->setDone(true);

        $elem2->setExport($doable1);
        $doable1->addElement($elem2);

        $elem3 = new FtpExportElement();
        $elem3
                ->setSubdef('subdef')
                ->setFilename('name')
                ->setBaseId(self::$DI['record_2']->get_base_id())
                ->setRecordId(self::$DI['record_2']->get_record_id())
                ->setDone(false);

        $elem3->setExport($doable1);
        $doable1->addElement($elem3);

        $doable2 = new FtpExport();
        $doable2
                ->setAddr('Doable 2')
                ->setUser(self::$DI['user']);

        $elem4 = new FtpExportElement();
        $elem4
                ->setSubdef('subdef')
                ->setFilename('name')
                ->setBaseId(self::$DI['record_1']->get_base_id())
                ->setRecordId(self::$DI['record_1']->get_record_id())
                ->setDone(false);

        $elem4->setExport($doable2);
        $doable2->addElement($elem4);

        self::$DI['app']['EM']->persist($notDoable1);
        self::$DI['app']['EM']->persist($notDoable2);
        self::$DI['app']['EM']->persist($doable1);
        self::$DI['app']['EM']->persist($doable2);

        self::$DI['app']['EM']->flush();

        $doables = self::$DI['app']['EM']
                ->getRepository('Entities\FtpExport')
                ->findDoableExports();

        $this->assertCount(2, $doables);
        $this->assertContains($doable1, $doables);
        $this->assertContains($doable2, $doables);
    }

    public function testFindByUser()
    {
        $match1 = new FtpExport();
        $match1
                ->setAddr('Match 1')
                ->setUser(self::$DI['user']);

        $match2 = new FtpExport();
        $match2
                ->setAddr('Match 2')
                ->setUser(self::$DI['user']);

        $noMatch1 = new FtpExport();
        $noMatch1
                ->setAddr('No match 1')
                ->setUser(self::$DI['user_alt1']);

        self::$DI['app']['EM']->persist($match1);
        self::$DI['app']['EM']->persist($match2);
        self::$DI['app']['EM']->persist($noMatch1);

        self::$DI['app']['EM']->flush();

        $exports = self::$DI['app']['EM']
                ->getRepository('Entities\FtpExport')
                ->findByUser(self::$DI['user']);

        $this->assertCount(2, $exports);
        $this->assertContains($match1, $exports);
        $this->assertContains($match2, $exports);
    }
}
