<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Doctrine\Server\ORM\CRUD\TestAsset;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Doctrine\Server\Event\DoctrineResourceEvent;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Override;

use function sprintf;

class FailureAggregateListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * @param string $eventName
     */
    public function __construct(private $eventName)
    {
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach($this->eventName, [$this, 'failure']);
    }

    public function failure(DoctrineResourceEvent $event): ApiProblem
    {
        $event->stopPropagation();
        return new ApiProblem(400, sprintf('LaminasTestFailureAggregateListener: %s', $event->getName()));
    }
}
