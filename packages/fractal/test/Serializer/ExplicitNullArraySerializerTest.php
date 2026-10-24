<?php

namespace League\Fractal\Test\Serializer;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\Scope;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Serializer\ExplicitNullArraySerializer;
use League\Fractal\TransformerAbstract;
use PHPUnit\Framework\TestCase;

class ExplicitNullArraySerializerTest extends TestCase
{
    public function testNullResourceIsSerializedAsNull()
    {
        $manager = new Manager();
        $manager->setSerializer(new ExplicitNullArraySerializer());

        $this->assertNull((new Scope($manager, new NullResource()))->toArray());
    }

    public function testItemTransformedAsNullIsSerializedAsNullResource()
    {
        $manager = new Manager();
        $manager->setSerializer(new ExplicitNullArraySerializer());

        $resource = new Item(['title' => 'Foo'], function () {
            return null;
        });

        $this->assertNull((new Scope($manager, $resource))->toArray());
        $this->assertSame('null', (new Scope($manager, $resource))->toJson());
    }

    public function testItemTransformedAsNullWithDefaultSerializers()
    {
        $resource = new Item(['title' => 'Foo'], function () {
            return null;
        });

        $manager = new Manager();
        $manager->setSerializer(new ArraySerializer());
        $this->assertSame([], (new Scope($manager, $resource))->toArray());

        $manager->setSerializer(new DataArraySerializer());
        $this->assertSame(['data' => []], (new Scope($manager, $resource))->toArray());
    }

    public function testResourceKeyIsPassedToNullSerialization()
    {
        $serializer = new class () extends ArraySerializer {
            public $resourceKeys = [];

            public function null(?string $resourceKey = null): ?array
            {
                $this->resourceKeys[] = $resourceKey;

                return null;
            }
        };

        $manager = new Manager();
        $manager->setSerializer($serializer);

        (new Scope($manager, new Item(null, function () {
            return null;
        }, 'book')))->toArray();
        (new Scope($manager, new NullResource(null, null, 'author')))->toArray();
        (new Scope($manager, new NullResource()))->toArray();

        $this->assertSame(['book', 'author', null], $serializer->resourceKeys);
    }

    public function testNullTransformedIncludesAreSerializedAsNull()
    {
        $manager = new Manager();
        $manager->setSerializer(new ExplicitNullArraySerializer());
        $manager->parseIncludes('author');

        $transformer = new class () extends TransformerAbstract {
            protected array $availableIncludes = ['author'];

            public function transform(array $book)
            {
                return ['title' => $book['title']];
            }

            public function includeAuthor(array $book)
            {
                return $this->item($book['author'], new class () extends TransformerAbstract {
                    protected array $availableIncludes = ['publisher'];

                    public function transform(?array $author)
                    {
                        return null === $author ? null : ['name' => $author['name']];
                    }

                    public function includePublisher()
                    {
                        throw new \LogicException('Includes of a null transformed item must not be processed.');
                    }
                });
            }
        };

        $resource = new Collection([
            ['title' => 'Foo', 'author' => ['name' => 'Dave']],
            ['title' => 'Bar', 'author' => null],
        ], $transformer, 'books');

        $this->assertSame([
            'books' => [
                ['title' => 'Foo', 'author' => ['name' => 'Dave']],
                ['title' => 'Bar', 'author' => null],
            ],
        ], (new Scope($manager, $resource))->toArray());
    }

    public function testCollectionMayContainNullTransformedItems()
    {
        $manager = new Manager();
        $manager->setSerializer(new ExplicitNullArraySerializer());

        $resource = new Collection([1, 2, 3], function ($value) {
            return 2 === $value ? null : ['value' => $value];
        });

        $this->assertSame(
            ['data' => [['value' => 1], null, ['value' => 3]]],
            (new Scope($manager, $resource))->toArray()
        );
    }
}
