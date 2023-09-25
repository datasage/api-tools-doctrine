<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Doctrine\Admin\Controller;

use Laminas\ApiTools\Doctrine\Admin\Controller\DoctrineAutodiscoveryController;
use Laminas\ApiTools\Doctrine\Admin\Controller\DoctrineAutodiscoveryControllerFactory;
use Laminas\ApiTools\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;
use LaminasTest\ApiTools\Doctrine\DeprecatedAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;

class DoctrineAutodiscoveryControllerFactoryTest extends TestCase
{
    use DeprecatedAssertionsTrait;
    use ProphecyTrait;

    /** @var ObjectProphecy<ContainerInterface> */
    private ObjectProphecy $container;
    private DoctrineAutodiscoveryModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model     = $this->prophesize(DoctrineAutodiscoveryModel::class)->reveal();
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get(DoctrineAutodiscoveryModel::class)->willReturn($this->model);
    }

    public function testInvokableFactoryReturnsDoctrineAutodiscoveryController(): void
    {
        $factory    = new DoctrineAutodiscoveryControllerFactory();
        $controller = $factory($this->container->reveal(), DoctrineAutodiscoveryController::class);

        $this->assertInstanceOf(DoctrineAutodiscoveryController::class, $controller);
        $this->assertAttributeSame($this->model, 'model', $controller);
    }
}
