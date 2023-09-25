<?php

declare(strict_types=1);

namespace LaminasTestApiToolsDb\EventListener;

use Laminas\ApiTools\Doctrine\Server\Event\DoctrineResourceEvent;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;

class ArtistAggregateListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            DoctrineResourceEvent::EVENT_CREATE_POST,
            [$this, 'createPost']
        );
    }

    public function createPost(DoctrineResourceEvent $event): void
    {
        $event->getObjectManager();

        $event->getEntity();
        $event->getData();
        $event->getResourceEvent();
        $event->getEntityClassName();
        $event->getEntityId();
    }
}
