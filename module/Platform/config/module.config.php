<?php

declare(strict_types=1);

return [
    // eventName => service class or list of service classes implementing Platform\Event\OutboxEventHandlerInterface.
    // Business modules add mappings when they subscribe to an outbox event.
    'platform_event_handlers' => [],

    // Max outbox events flushed per request by Platform\Listener\OutboxDispatchListener.
    // No cron, no background worker (ADR-0005): events are published at the end of the
    // POST request that recorded them. Backlog beyond this drains over later requests.
    'platform_outbox_dispatch_limit' => 50,
];
