<?php

declare(strict_types=1);

namespace Platform\Event;

use Platform\Model\OutboxEvent\OutboxEventModel;

interface OutboxEventHandlerInterface
{
    public function handle(OutboxEventModel $event): void;
}
