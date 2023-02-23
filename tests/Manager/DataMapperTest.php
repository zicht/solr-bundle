<?php declare(strict_types=1);
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace AppTest {
    use Zicht\Bundle\SolrBundle\Manager\AbstractDataMapper;

    class TestEntity
    {
        private int $id;

        public function __construct(int $id)
        {
            $this->id = $id;
        }

        public function getId(): int
        {
            return $this->id;
        }
    }

    class TestMapper extends AbstractDataMapper
    {
        protected $classNames = [
            TestEntity::class,
        ];

        protected function getBoost($entity)
        {
            return 10.0;
        }

        protected function mapDocument($entity)
        {
            return [
                'type' => get_class($entity),
                'title' => 'This is a test',
            ];
        }
    }
}

namespace Zicht\Bundle\SolrBundle\Tests\Manager {
    use AppTest\TestEntity;
    use AppTest\TestMapper;
    use PHPUnit\Framework\TestCase;
    use Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Update;

    class DataMapperTest extends TestCase
    {
        public function testUpdate()
        {
            $testMapper = new TestMapper();
            $entityId = 123;

            $update = $this->createMock(Update::class);
            $update->expects($this->once())
                ->method('add')
                ->with(
                    $this->equalTo([
                        'id' => sha1(TestEntity::class . ':' . $entityId),
                        'type' => TestEntity::class,
                        'title' => 'This is a test',
                    ]),
                    $this->equalTo([
                        'boost' => 10.0,
                    ])
                );

            $testMapper->update($update, new TestEntity($entityId));
        }

        public function testDelete()
        {
            $testMapper = new TestMapper();
            $entityId = 123;

            $update = $this->createMock(Update::class);
            $update->expects($this->once())
                ->method('deleteOne')
                ->with(
                    $this->equalTo(sha1(TestEntity::class . ':' . $entityId))
                );

            $testMapper->delete($update, new TestEntity($entityId));
        }

        public function testSupports()
        {
            $testMapper = new TestMapper();

            $this->assertTrue($testMapper->supports(new TestEntity(123)));
        }

        public function testSettingAndGettingClassNames()
        {
            $classNames = [\Foo\Bar::class, \Foo\Baz::class];

            $testMapper = new TestMapper();
            $testMapper->setClassNames($classNames);

            $this->assertSame($testMapper->getClassNames(), $classNames);
        }
    }
}
