<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;

/**
 * Gắn layout chung cho mọi trang. Hệ thống chỉ render HTML phía server (ADR-0002),
 * không có nhánh JSON nào cần bỏ qua layout.
 */
class LayoutListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 100): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'applyLayout'], $priority);
    }

    public function applyLayout(MvcEvent $event): void
    {
        $event->getViewModel()->setTemplate('layout/layout');
    }
}
