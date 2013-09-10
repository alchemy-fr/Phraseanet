<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Symfony\Component\HttpKernel\Client;
use Alchemy\Phrasea\Application;

class ControllerCategoryTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$DI['app'] = new Application('test');
    }

    public function testCategoryCreate()
    {
        self::$DI['client']->request('POST', '/prod/category/create/', array(), array(), array(), json_encode(array(
            'title'    => 'titre test',
            'subtitle' => 'description test',
            'translation_title' => array('locale' => 'fr',
                                         'value' => 'titre test'),
            'translation_subtitle' => array('locale' => 'fr',
                                         'value' => 'description test')
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $actual = json_decode($response->getContent())->category;
        $this->assertNotNull($actual);
        $expected = self::$DI['app']['EM']->getRepository('Entities\Category')->find($actual->id);
        $this->assertEquals($expected->getTitle(), $actual->title);
        $this->assertEquals($expected->getSubtitle(), $actual->subtitle);
        foreach ($expected->getTranslations() as $expectedTranslation) {
            foreach ($actual->translations as $actualTranslation) {
                if ($expectedTranslation->getField() === $actualTranslation->field) {
                    $this->assertEquals($expectedTranslation->getLocale(), $actualTranslation->locale);
                    $this->assertEquals($expectedTranslation->getContent(), $actualTranslation->content);
                }
            }
        }
    }

    public function testCategoryCreateWithoutTitle()
    {
        self::$DI['client']->request('POST', '/prod/category/create/', array(), array(), array(), json_encode(array()));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCategoryCreateWithExistingTitle()
    {
        $this->insertOneCategory();

        self::$DI['client']->request('POST', '/prod/category/create/', array(), array(), array(), json_encode(array(
            'title'    => 'title test'
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCategoryCreateWrongParent()
    {
        self::$DI['client']->request('POST', '/prod/category/create/', array(), array(), array(), json_encode(array(
            'title'    => 'title test',
            'parent_id'=> 42
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCategoryCreateTree()
    {
        $food = $this->insertOneCategory('food');
        $vegetables = $this->insertOneCategory('vegetables', $food);
        self::$DI['client']->request('POST', '/prod/category/create/', array(), array(), array(), json_encode(array(
            'title'    => 'carrots',
            'parent_id'=> $vegetables->getId()
        )));
        self::$DI['client']->request('POST', '/prod/category/create/', array(), array(), array(), json_encode(array(
            'title'    => 'cabbage',
            'parent_id'=> $vegetables->getId()
        )));

        $repo = self::$DI['app']['EM']->getRepository('Entities\Category');

        $this->assertEquals(3, $repo->childCount($food)); // all the children
        $this->assertEquals(1, $repo->childCount($food, true)); // first level children

        $children = $repo->children($food, false); // all the children

        $found = false;
        foreach($children as $child) {
            if ('carrots' === $child->getTitle()){
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $this->assertEquals(3, count($children));

        $children = $repo->children($food, true); // first level children

        $found = false;
        foreach($children as $child) {
            if ('vegetables' === $child->getTitle()){
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $this->assertEquals(1, count($children));
    }

    public function testCategoryUpdateTitleAndSubtitle()
    {
        $category = $this->insertOneCategory();

        $title = $category->getTitle();
        $subtitle = $category->getSubtitle();

        self::$DI['client']->request('POST', '/prod/category/'.$category->getId().'/update/', array(), array(), array(), json_encode(array(
            'title'    => 'new title',
            'subtitle' => 'new subtitle'
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $actual = json_decode($response->getContent())->category;
        $this->assertNotNull($actual);
        $this->assertNotEquals($title, $actual->title);
        $this->assertNotEquals($subtitle, $actual->subtitle);
    }

    public function testCategoryUpdateChangeTranslation()
    {
        $category = $this->insertOneCategory();
        $this->insertOneCategoryTranslation($category, 'subtitle', 'en');

        $contentTitle = $category->getTranslation('en', 'title')->getContent();
        $contentSubtitle = $category->getTranslation('en', 'subtitle')->getContent();

        self::$DI['client']->request('POST', '/prod/category/'.$category->getId().'/update/', array(), array(), array(), json_encode(array(
            'translation_title' => array('locale' => 'en',
                                         'value' => 'new content'),
            'translation_subtitle' => array('locale' => 'en',
                                         'value' => 'new content 2'),
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $translationTitle = $category->getTranslation('en', 'title');
        $translationSubtitle = $category->getTranslation('en', 'subtitle');
        $this->assertNotEquals($contentTitle, $translationTitle);
        $this->assertNotEquals($contentSubtitle, $translationSubtitle);
    }

    public function testCategoryUpdateAddTranslation()
    {
        $category = $this->insertOneCategory();

        self::$DI['client']->request('POST', '/prod/category/'.$category->getId().'/update/', array(), array(), array(), json_encode(array(
            'translation_title' => array('locale' => 'fr',
                                         'value' => 'new content')
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $translation = $category->getTranslation('fr', 'title');
        $this->assertNotNull($translation);
        $this->assertEquals('new content', $translation->getContent());
    }

    public function testCategoryUpdateWrongParent()
    {
        $category = $this->insertOneCategory();

        self::$DI['client']->request('POST', '/prod/category/'.$category->getId().'/update/', array(), array(), array(), json_encode(array(
            'parent_id'=> 42
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCategoryUpdateUnexistingCategory()
    {
        self::$DI['client']->request('POST', '/prod/category/999999/update/', array(), array(), array(), json_encode(array()));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testCategoryUpdateChangeParentUp()
    {
        $food = $this->insertOneCategory('food');
        $vegetables = $this->insertOneCategory('vegetables', $food);
        $carrots = $this->insertOneCategory('carrots', $vegetables);
        $this->insertOneCategory('cabbage', $vegetables);

        self::$DI['client']->request('POST', '/prod/category/'.$carrots->getId().'/update/', array(), array(), array(), json_encode(array(
            'parent_id'=> $food->getId()
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $repo = self::$DI['app']['EM']->getRepository('Entities\Category');

        $this->assertEquals(3, $repo->childCount($food));
        $this->assertEquals(2, $repo->childCount($food, true));
        $children = $repo->children($food, true);

        $found = false;
        foreach($children as $child) {
            if ('carrots' === $child->getTitle()){
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

        public function testCategoryUpdateChangeParentDown()
    {
        $food = $this->insertOneCategory('food');
        $vegetables = $this->insertOneCategory('vegetables', $food);
        $carrots = $this->insertOneCategory('carrots', $vegetables);
        $cabbage = $this->insertOneCategory('cabbage', $vegetables);

        self::$DI['client']->request('POST', '/prod/category/'.$carrots->getId().'/update/', array(), array(), array(), json_encode(array(
            'parent_id'=> $cabbage->getId()
        )));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $repo = self::$DI['app']['EM']->getRepository('Entities\Category');

        $this->assertEquals(3, $repo->childCount($food));
        $this->assertEquals(1, $repo->childCount($cabbage, true));
        $children = $repo->children($cabbage, true);

        $found = false;
        foreach($children as $child) {
            if ('carrots' === $child->getTitle()){
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testCategoryDelete()
    {
        $category = $this->insertOneCategory();

        $id = $category->getId();

        self::$DI['client']->request('POST', '/prod/category/'.$id.'/delete/', array(), array(), array(), json_encode(array()));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $category = self::$DI['app']['EM']->getRepository('Entities\Category')->find($id);
        $this->assertNull($category);
    }

    public function testCategoryDeleteUnexistingCategory()
    {
        self::$DI['client']->request('POST', '/prod/category/999999/delete/', array(), array(), array(), json_encode(array()));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }
}
