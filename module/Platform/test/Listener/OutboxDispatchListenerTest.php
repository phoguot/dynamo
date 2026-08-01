<?php

declare(strict_types=1);

namespace PlatformTest\Listener;

use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;
use Platform\Listener\OutboxDispatchListener;
use Platform\Service\OutboxDispatcherService;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Dự án không có cron/worker: outbox chỉ được phát bởi listener này, ở cuối
 * request đã ghi event. Test giữ ba điều kiện đó không bị sửa nhầm.
 */
class OutboxDispatchListenerTest extends TestCase
{
    public function test_phat_outbox_o_cuoi_request_post(): void
    {
        $dispatcher = $this->spyDispatcher();

        $this->listener($dispatcher)->onFinish($this->event('POST'));

        self::assertSame([OutboxDispatchListener::DEFAULT_LIMIT], $dispatcher->calls);
    }

    public function test_khong_quet_outbox_o_request_get(): void
    {
        $dispatcher = $this->spyDispatcher();

        $this->listener($dispatcher)->onFinish($this->event('GET'));

        self::assertSame([], $dispatcher->calls);
    }

    public function test_loi_handler_khong_lam_hong_request_da_thanh_cong(): void
    {
        $dispatcher = new class extends OutboxDispatcherService {
            public function dispatch(int $limit = 50): array
            {
                throw new RuntimeException('handler chet');
            }
        };

        $this->listener($dispatcher)->onFinish($this->event('POST'));

        self::assertTrue(true, 'onFinish nuot loi thay vi nem nguoc len Application::run().');
    }

    public function test_chay_sau_khi_response_da_gui(): void
    {
        // SendResponseListener cua Laminas dung -10000; nho hon nghia la chay sau no.
        self::assertLessThan(-10000, OutboxDispatchListener::PRIORITY);
    }

    private function spyDispatcher(): OutboxDispatcherService
    {
        return new class extends OutboxDispatcherService {
            public array $calls = [];

            public function dispatch(int $limit = 50): array
            {
                $this->calls[] = $limit;

                return ['fetched' => 0, 'published' => 0, 'failed' => 0];
            }
        };
    }

    private function listener(OutboxDispatcherService $dispatcher): OutboxDispatchListener
    {
        return new OutboxDispatchListener(new class($dispatcher) implements ContainerInterface {
            public function __construct(private OutboxDispatcherService $dispatcher)
            {
            }

            public function get(string $id)
            {
                return $id === OutboxDispatcherService::class ? $this->dispatcher : [];
            }

            public function has(string $id): bool
            {
                return true;
            }
        });
    }

    private function event(string $method): MvcEvent
    {
        $request = new Request();
        $request->setMethod($method);

        $event = new MvcEvent();
        $event->setRequest($request);

        return $event;
    }
}
