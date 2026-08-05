<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Doctrine\Server\ORM\CRUD;

use DateTime;
use Doctrine\Instantiator\InstantiatorInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\SchemaTool;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\ApiTools\Doctrine\DoctrineResource;
use Laminas\ApiTools\Doctrine\Server\Event\DoctrineResourceEvent;
use Laminas\ApiTools\Rest\ResourceEvent;
use Laminas\Filter\FilterChain;
use Laminas\Http\Request;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\ApiTools\Doctrine\TestCase;
use LaminasTestApiToolsDb\Entity\Album;
use LaminasTestApiToolsDb\Entity\Artist;
use LaminasTestApiToolsDb\Entity\Product;
use LaminasTestApiToolsDbApi\V1\Rest\Artist\ArtistResource;
use LaminasTestApiToolsGeneral\Listener\EventCatcher;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

use function in_array;
use function json_decode;
use function json_encode;
use function print_r;
use function sprintf;
use function strrev;

use const JSON_THROW_ON_ERROR;

class CRUDTest extends TestCase
{
    protected EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setApplicationConfig(
            include __DIR__ . '/../../../../config/ORM/application.config.php'
        );

        // Services are now defined statically in the test module's committed config,
        // so only the schema needs building here. Previously this lived in
        // buildORMApi(), alongside the Admin service generation that has been removed.
        /** @var EntityManager $em */
        $em = $this->getApplication()->getServiceManager()->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        $this->em = $em;

    }


    public function testCreate(): void
    {
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch(
            '/test/rest/artist',
            Request::METHOD_POST,
            [
                'name'      => 'ArtistOne',
                'createdAt' => '2016-08-09 22:30:42',
            ]
        );
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('ArtistOne', $body['name']);
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_CREATE_PRE,
            DoctrineResourceEvent::EVENT_CREATE_POST,
        ]);
    }

    public function testCreateWithRelation(): void
    {
        $artist = $this->createArtist();
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch(
            '/test/rest/album',
            Request::METHOD_POST,
            [
                'name'      => 'Album One',
                'createdAt' => '2016-08-21 22:32:38',
                'artist'    => $artist->getId(),
            ]
        );
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(201);
        $this->assertEquals('Album One', $body['name']);
        $this->assertEquals($artist->getId(), $body['_embedded']['artist']['id']);
    }

    #[DataProvider('listener')]
    public function testCreateWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $this->$method(DoctrineResourceEvent::EVENT_CREATE_PRE);
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch(
            '/test/rest/artist',
            Request::METHOD_POST,
            [
                'name'      => 'ArtistEleven',
                'createdAt' => '2016-08-21 22:33:17',
            ]
        );
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_CREATE_PRE),
            $body['detail']
        );
    }

    public function testFetchByCustomIdField(): void
    {
        $product = $this->createProduct();

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/test/rest/product/' . $product->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals($product->getId(), $body['id']);
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_FETCH_PRE,
            DoctrineResourceEvent::EVENT_FETCH_POST,
        ]);
    }

    /**
     * @see https://github.com/zfcampus/zf-apigility-doctrine/pull/316
     */
    public function testFetchByCustomIdFieldIncludesApiToolsResourceEventInDoctrineResourceEvent(): void
    {
        $product = $this->createProduct();

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $spy          = (object) ['caught' => false];
        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            DoctrineResource::class,
            DoctrineResourceEvent::EVENT_FETCH_PRE,
            function (DoctrineResourceEvent $e) use ($spy): void {
                Assert::assertInstanceOf(ResourceEvent::class, $e->getResourceEvent());
                $spy->caught = true;
            }
        );

        $this->dispatch('/test/rest/product/' . $product->getId());

        $this->assertResponseStatusCode(200);
        $this->assertTrue($spy->caught, 'EVENT_FETCH_PRE listener was not triggered');
    }

    public function testFetchEntityWithVersionFieldWithVersionParamInPath(): void
    {
        $product = $this->createProduct();

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/v1/test/rest/product/' . $product->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals($product->getId(), $body['id']);
        $this->assertNull($body['version']);
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_FETCH_PRE,
            DoctrineResourceEvent::EVENT_FETCH_POST,
        ]);
    }

    public function testFetchByCustomIdFieldWithInvalidIdValue(): void
    {
        $product = $this->createProduct();

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/test/rest/product/' . strrev($product->getId()));

        $this->assertResponseStatusCode(404);
        $this->validateTriggeredEvents([DoctrineResourceEvent::EVENT_FETCH_PRE]);
    }

    public function testCreateByExplicitlySettingEntityFactoryInConstructor(): void
    {
        /** @var MockObject $entityFactoryMock */
        $entityFactoryMock = $this->getMockBuilder(InstantiatorInterface::class)->getMock();
        $entityFactoryMock->expects(self::once())
            ->method('instantiate')
            ->with(Artist::class)
            ->willReturnCallback(fn($class): object => new $class());

        /** @var ServiceManager $sm */
        $sm = $this->getApplication()->getServiceManager();

        $config                           = $sm->get('config');
        $resourceName                     = 'LaminasTestApiToolsDbApi\V1\Rest\Artist\ArtistResource';
        $resourceConfig                   = $config['api-tools']['doctrine-connected'][$resourceName];
        $resourceConfig['entity_factory'] = 'ResourceInstantiator';
        $config['api-tools']['doctrine-connected'][$resourceName] = $resourceConfig;

        $sm->setAllowOverride(true);
        $sm->setService('config', $config);
        $sm->setAllowOverride(false);

        $sm->setService(
            'ResourceInstantiator',
            $entityFactoryMock
        );

        // dispatch a request to create a meta document (similar to testCreate())
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch(
            '/test/rest/artist',
            Request::METHOD_POST,
            [
                'name'      => 'ArtistTwelve',
                'createdAt' => '2017-03-01 08:56:32',
            ]
        );

        $this->assertResponseStatusCode(201);
    }

    public function testFetch(): void
    {
        $artist = $this->createArtist('Artist Name');
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals('Artist Name', $body['name']);
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_FETCH_PRE,
            DoctrineResourceEvent::EVENT_FETCH_POST,
        ]);
    }

    public function testFetchWithNonPrimaryKeyIdentifier(): void
    {
        $artist = $this->createArtist('ArtistTwo');
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/test/rest/artist-by-name/' . $artist->getName());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals('ArtistTwo', $body['name']);
    }

    public function testFetchWithRelation(): void
    {
        $artist = $this->createArtist('NewArtist');
        $album  = $this->createAlbum('NewAlbum', $artist);
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/test/rest/artist/' . $artist->getId() . '/album/' . $album->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals('NewAlbum', $body['name']);
    }

    #[DataProvider('listener')]
    public function testFetchWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $artist = $this->createArtist('Artist Fetch ApiProblem');
        $this->$method(DoctrineResourceEvent::EVENT_FETCH_PRE);
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_FETCH_PRE),
            $body['detail']
        );
    }

    public function testFetchAll(): void
    {
        $artist1 = $this->createArtist('Artist 1');
        $artist2 = $this->createArtist('Artist 2');
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals(2, $body['total_items']);
        $this->assertCount(2, $body['_embedded']['artist']);
        $this->assertEquals($artist1->getId(), $body['_embedded']['artist'][0]['id']);
        $this->assertEquals($artist2->getId(), $body['_embedded']['artist'][1]['id']);
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_FETCH_ALL_PRE,
            DoctrineResourceEvent::EVENT_FETCH_ALL_POST,
        ]);
    }

    public function testFetchAllEmptyCollection(): void
    {
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_GET);

        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals(0, $body['total_items']);
        $this->assertCount(0, $body['_embedded']['artist']);
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_FETCH_ALL_PRE,
            DoctrineResourceEvent::EVENT_FETCH_ALL_POST,
        ]);
    }

    #[DataProvider('listener')]
    public function testFetchAllWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $this->createArtist('Artist FetchAll ApiProblem');
        $this->$method(DoctrineResourceEvent::EVENT_FETCH_ALL_PRE);
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_FETCH_ALL_PRE),
            $body['detail']
        );
    }

    public function testPatch(): void
    {
        $artist = $this->createArtist('Artist Patch');
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent(json_encode(['name' => 'Artist Patch Edit']));

        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals('Artist Patch Edit', $body['name']);
        $this->assertEquals($artist->getId(), $body['id']);
        $foundEntity = $this->em->getRepository(Artist::class)->find($artist->getId());
        $this->assertEquals('Artist Patch Edit', $foundEntity->getName());
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_PATCH_PRE,
            DoctrineResourceEvent::EVENT_PATCH_POST,
        ]);
    }

    #[DataProvider('listener')]
    public function testPatchWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $artist = $this->createArtist('Artist Patch ApiProblem');
        $this->$method(DoctrineResourceEvent::EVENT_PATCH_PRE);
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent(json_encode(['name' => 'ArtistTenPatchEdit']));

        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_PATCH_PRE),
            $body['detail']
        );
    }

    public function testPatchList(): void
    {
        $artist1 = $this->createArtist('Artist Patch List 1');
        $artist2 = $this->createArtist('Artist Patch List 2');
        $artist3 = $this->createArtist('Artist Patch List 3');

        $patchList = [
            [
                'id'   => $artist1->getId(),
                'name' => 'oneNewName',
            ],
            [
                'id'   => $artist2->getId(),
                'name' => 'twoNewName',
            ],
            [
                'id'   => $artist3->getId(),
                'name' => 'threeNewName',
            ],
        ];

        $this->em->clear();

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent(json_encode($patchList));

        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals('oneNewName', $body['_embedded']['artist'][0]['name']);
        $this->assertEquals('twoNewName', $body['_embedded']['artist'][1]['name']);
        $this->assertEquals('threeNewName', $body['_embedded']['artist'][2]['name']);
        $this->validateTriggeredEventsContains([
            DoctrineResourceEvent::EVENT_PATCH_LIST_PRE,
            DoctrineResourceEvent::EVENT_PATCH_LIST_POST,
        ]);
    }

    #[DataProvider('listener')]
    public function testPatchListWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $artist = $this->createArtist('Artist Patch List ApiProblem');
        $this->$method(DoctrineResourceEvent::EVENT_PATCH_LIST_PRE);
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent(json_encode([
            [
                'id'   => $artist->getId(),
                'name' => 'Artist Edit',
            ],
        ]));

        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_PATCH_LIST_PRE),
            $body['detail']
        );
    }

    public function testPut(): void
    {
        $artist = $this->createArtist('Artist Put');
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PUT);
        $this->getRequest()->setContent(json_encode([
            'name'      => 'Artist Put Edit',
            'createdAt' => '2016-08-21 22:10:11',
        ]));

        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(200);
        $this->assertEquals('Artist Put Edit', $body['name']);
        $foundEntity = $this->em->getRepository(Artist::class)->find($artist->getId());
        $this->assertEquals('Artist Put Edit', $foundEntity->getName());
        $this->assertEquals('2016-08-21 22:10:11', $foundEntity->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_UPDATE_PRE,
            DoctrineResourceEvent::EVENT_UPDATE_POST,
        ]);
    }

    #[DataProvider('listener')]
    public function testPutWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $artist = $this->createArtist('Artist Put ApiProblem');
        $this->$method(DoctrineResourceEvent::EVENT_UPDATE_PRE);
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PUT);
        $this->getRequest()->setContent(json_encode([
            'name'      => 'Artist Put Edit',
            'createdAt' => '2016-08-21 22:10:19',
        ]));

        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_UPDATE_PRE),
            $body['detail']
        );
    }

    public function testDelete(): void
    {
        $artist = $this->createArtist('Artist Delete');
        $id     = $artist->getId();
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_DELETE);

        $this->dispatch('/test/rest/artist/' . $id);

        $this->assertResponseStatusCode(204);
        $this->assertNull($this->em->getRepository(Artist::class)->find($id));
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_DELETE_PRE,
            DoctrineResourceEvent::EVENT_DELETE_POST,
        ]);
    }

    #[DataProvider('listener')]
    public function testDeleteWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $artist = $this->createArtist('Artist Delete ApiProblem');
        $this->$method(DoctrineResourceEvent::EVENT_DELETE_PRE);
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_DELETE);

        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_DELETE_PRE),
            $body['detail']
        );
        $foundEntity = $this->em->getRepository(Artist::class)->find($artist->getId());
        $this->assertEquals($artist->getId(), $foundEntity->getId());
    }

    public function testDeleteEntityNotFound(): void
    {
        $artist = $this->createArtist();
        $id     = $artist->getId() + 1;
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_DELETE);

        $this->dispatch('/test/rest/artist/' . $id);

        $this->assertResponseStatusCode(404);
        $this->validateTriggeredEvents([]);
        $this->assertNull($this->em->getRepository(Artist::class)->find($id));
    }

    public function testDeleteEntityDeleted(): void
    {
        $artist = $this->createArtist();
        $id     = $artist->getId();
        $this->em->remove($artist);
        $this->em->flush();
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->getRequest()->setMethod(Request::METHOD_DELETE);

        $this->dispatch('/test/rest/artist/' . $id);

        $this->assertResponseStatusCode(404);
        $this->validateTriggeredEvents([]);
        $this->assertNull($this->em->getRepository(Artist::class)->find($id));
    }

    public function testDeleteList(): void
    {
        $artist1 = $this->createArtist('Artist Delete 1');
        $artist2 = $this->createArtist('Artist Delete 2');
        $artist3 = $this->createArtist('Artist Delete 3');

        $deleteList = [
            ['id' => $artist1->getId()],
            ['id' => $artist2->getId()],
            ['id' => $artist3->getId()],
        ];

        $this->em->clear();

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->getRequest()->setContent(json_encode($deleteList));

        $this->dispatch('/test/rest/artist');

        $this->assertResponseStatusCode(204);
        $this->assertNull($this->em->getRepository(Artist::class)->find($artist1->getId()));
        $this->assertNull($this->em->getRepository(Artist::class)->find($artist2->getId()));
        $this->assertNull($this->em->getRepository(Artist::class)->find($artist3->getId()));
        $this->validateTriggeredEventsContains([
            DoctrineResourceEvent::EVENT_DELETE_LIST_PRE,
            DoctrineResourceEvent::EVENT_DELETE_LIST_POST,
        ]);
    }

    #[DataProvider('listener')]
    public function testDeleteListWithListenerThatReturnsApiProblem(string $method, string $message): void
    {
        $this->$method(DoctrineResourceEvent::EVENT_DELETE_LIST_PRE);

        $artist1 = $this->createArtist('Artist Delete 1');
        $artist2 = $this->createArtist('Artist Delete 2');
        $artist3 = $this->createArtist('Artist Delete 3');

        $deleteList = [
            ['id' => $artist1->getId()],
            ['id' => $artist2->getId()],
            ['id' => $artist3->getId()],
        ];

        $this->em->clear();

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->getRequest()->setContent(json_encode($deleteList));

        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseStatusCode(400);
        $this->assertInstanceOf(ApiProblemResponse::class, $this->getResponse());
        $this->assertEquals(
            sprintf('%s: %s', $message, DoctrineResourceEvent::EVENT_DELETE_LIST_PRE),
            $body['detail']
        );
        $foundEntity1 = $this->em->getRepository(Artist::class)->find($artist1->getId());
        $foundEntity2 = $this->em->getRepository(Artist::class)->find($artist2->getId());
        $foundEntity3 = $this->em->getRepository(Artist::class)->find($artist3->getId());
        $this->assertEquals($artist1->getId(), $foundEntity1->getId());
        $this->assertEquals($artist2->getId(), $foundEntity2->getId());
        $this->assertEquals($artist3->getId(), $foundEntity3->getId());
    }

    /**
     * @psalm-return never
     */
    public function testGetRpcNoParams(): void
    {
        $this->markTestIncomplete('Doctrine RPC Services are not fully implemented.');

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch('/test/artist/album');
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        print_r($body);
    }

    /**
     * @psalm-return never
     */
    public function testGetRpcWithParams(): void
    {
        $this->markTestIncomplete('Doctrine RPC Services are not fully implemented.');

        $artist = $this->createArtist('Artist RPC');
        $album  = $this->createAlbum('Album RPC', $artist);

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        $this->dispatch(sprintf('/test/artist/%d/album/%d', $artist->getId(), $album->getId()));
        $body = json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);

        print_r($body);
    }

    protected function validateTriggeredEvents(array $expectedEvents): void
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $eventCatcher   = $serviceManager->get(EventCatcher::class);

        $this->assertEquals($expectedEvents, $eventCatcher->getCaughtEvents());
    }

    protected function validateTriggeredEventsContains(array $expectedEvents): void
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $eventCatcher   = $serviceManager->get(EventCatcher::class);

        foreach ($expectedEvents as $event) {
            $this->assertTrue(
                in_array($event, $eventCatcher->getCaughtEvents()),
                sprintf(
                    'Did not identify event "%s" in caught events',
                    $event
                )
            );
        }
    }

    /**
     * @param null|string $name
     */
    protected function createArtist($name = null): Artist
    {
        $artist = new Artist();
        $artist->setName($name ?: 'Artist name');
        $artist->setCreatedAt(new DateTime());
        $this->em->persist($artist);
        $this->em->flush();

        return $artist;
    }

    /**
     * @param null|string $name
     */
    protected function createAlbum($name = null, ?Artist $artist = null): Album
    {
        $album = new Album();
        $album->setName($name ?: 'Album name');
        $album->setArtist($artist ?: $this->createArtist());
        $album->setCreatedAt(new DateTime());
        $this->em->persist($album);
        $this->em->flush();

        return $album;
    }

    protected function createProduct(): Product
    {
        $product = new Product();
        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /** @psalm-return array<string, array{0: string, 1: string}> */
    public static function listener(): array
    {
        return [
            //          $methodToAttachListener,     $detailMessage
            'shared' => ['attachSharedListener',     'LaminasTestSharedListenerFailure'],
            'config' => ['attachAggregatedListener', 'LaminasTestFailureAggregateListener'],
        ];
    }

    /**
     * @param string $eventName
     * @return void
     */
    protected function attachSharedListener($eventName)
    {
        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            DoctrineResource::class,
            $eventName,
            function (DoctrineResourceEvent $e) use ($eventName): ApiProblem {
                $e->stopPropagation();
                return new ApiProblem(400, sprintf('LaminasTestSharedListenerFailure: %s', $eventName));
            }
        );
    }

    /**
     * @param string $eventName
     * @return void
     */
    protected function attachAggregatedListener($eventName)
    {
        $sm = $this->getApplication()->getServiceManager();
        $sm->setAllowOverride(true);
        $config = $sm->get('config');
        $config['api-tools']['doctrine-connected'][ArtistResource::class]['listeners'][]
            = 'LaminasTestFailureAggregateListener';
        $sm->setService('config', $config);
        $sm->setAllowOverride(false);

        $listener = new TestAsset\FailureAggregateListener($eventName);
        $sm->setService('LaminasTestFailureAggregateListener', $listener);
    }
}
