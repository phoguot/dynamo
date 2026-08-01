<?php

declare(strict_types=1);

namespace PlatformTest\Model;

use Application\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Platform\Event\OutboxEventHandlerInterface;
use Platform\Model\Notification\NotificationModel;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Platform\Model\PlatformConst;
use Platform\Model\Setting\SettingModel;
use Platform\Service\AttachmentService;
use Platform\Service\NotificationService;
use Platform\Service\OutboxDispatcherService;
use Platform\Service\OutboxEventService;
use Platform\Service\SettingService;
use Psr\Container\ContainerInterface;

class PlatformReadModelTest extends TestCase
{
    public function test_json_payload_chuan_hoa_va_doc_lai_duoc(): void
    {
        $payload = ['rentalOrderId' => 10, 'status' => 'dang_thue'];
        $json = PlatformConst::normalizeJson($payload);

        self::assertSame($payload, PlatformConst::decodeJson($json));
        self::assertSame('', PlatformConst::normalizeJson('{broken json'));
        self::assertSame('', PlatformConst::normalizeJson('123'));
    }

    public function test_setting_tra_ve_dung_kieu_du_lieu(): void
    {
        self::assertSame(12, (new SettingModel())->setValueType(PlatformConst::SETTING_TYPE_INT)->setConfigValue('12')->getTypedValue());
        self::assertTrue((new SettingModel())->setValueType(PlatformConst::SETTING_TYPE_BOOL)->setConfigValue('1')->getTypedValue());
        self::assertSame(['a' => 1], (new SettingModel())->setValueType(PlatformConst::SETTING_TYPE_JSON)->setConfigValue('{"a":1}')->getTypedValue());
    }

    public function test_outbox_doc_payload_tu_json(): void
    {
        $event = (new OutboxEventModel())->setPayloadJson('{"event":"invoice"}');

        self::assertSame(['event' => 'invoice'], $event->getPayload());
    }

    public function test_notification_doc_trang_thai_read(): void
    {
        self::assertFalse((new NotificationModel())->isRead());
        self::assertTrue((new NotificationModel())->setReadAt('2026-07-24 01:02:03.000')->isRead());
    }

    public function test_whitelist_sort_va_enum_platform(): void
    {
        self::assertArrayHasKey(PlatformConst::OUTBOX_SORT_DEFAULT, PlatformConst::OUTBOX_SORT_MAP);
        self::assertContains(PlatformConst::OUTBOX_STATUS_PENDING, PlatformConst::OUTBOX_STATUSES);
        self::assertArrayNotHasKey('e.id; DROP TABLE pfm_outbox_events', PlatformConst::OUTBOX_SORT_MAP);
    }

    public function test_service_khong_luu_secret_vao_setting_db(): void
    {
        $this->expectException(ValidationException::class);

        (new SettingService())->saveSetting('mail.smtpPassword', 'secret');
    }

    public function test_service_validate_attachment_notification_va_outbox(): void
    {
        foreach ([
            fn () => (new AttachmentService())->registerAttachment([]),
            fn () => (new NotificationService())->createNotification(['userId' => 1]),
            fn () => (new OutboxEventService())->recordEvent('', null, []),
        ] as $callback) {
            try {
                $callback();
                self::fail('Expected validation error.');
            } catch (ValidationException $exception) {
                self::assertNotSame([], $exception->getErrors());
            }
        }
    }

    public function test_outbox_dispatcher_goi_handler_va_danh_dau_da_phat(): void
    {
        $outbox = new class extends OutboxEventService {
            public array $published = [];

            public function fetchPendingEvents(int $limit = 50): array
            {
                return [(new OutboxEventModel())->setId(11)->setEventName('fleet.generator.status_changed')->setPayloadJson('{"generatorId":1}')];
            }

            public function markPublished(int $id): bool
            {
                $this->published[] = $id;
                return true;
            }
        };
        $handler = new class implements OutboxEventHandlerInterface {
            public array $payloads = [];

            public function handle(OutboxEventModel $event): void
            {
                $this->payloads[] = $event->getPayload();
            }
        };
        $dispatcher = new OutboxDispatcherService();
        $dispatcher->setContainer(new class($outbox, $handler) implements ContainerInterface {
            public function __construct(private OutboxEventService $outbox, private OutboxEventHandlerInterface $handler) {}

            public function get(string $id)
            {
                return match ($id) {
                    OutboxEventService::class => $this->outbox,
                    'handler.event' => $this->handler,
                    'config' => ['platform_event_handlers' => ['fleet.generator.status_changed' => ['handler.event']]],
                };
            }

            public function has(string $id): bool
            {
                return true;
            }
        });

        self::assertSame(['fetched' => 1, 'published' => 1, 'failed' => 0], $dispatcher->dispatch());
        self::assertSame([11], $outbox->published);
        self::assertSame([['generatorId' => 1]], $handler->payloads);
    }

    public function test_outbox_dispatcher_danh_dau_that_bai_khi_chua_co_handler(): void
    {
        $outbox = new class extends OutboxEventService {
            public array $failed = [];

            public function fetchPendingEvents(int $limit = 50): array
            {
                return [(new OutboxEventModel())->setId(12)->setEventName('missing.event')];
            }

            public function markFailed(int $id, string $lastError): bool
            {
                $this->failed[$id] = $lastError;
                return true;
            }
        };
        $dispatcher = new OutboxDispatcherService();
        $dispatcher->setContainer(new class($outbox) implements ContainerInterface {
            public function __construct(private OutboxEventService $outbox) {}

            public function get(string $id)
            {
                return $id === OutboxEventService::class
                    ? $this->outbox
                    : ['platform_event_handlers' => []];
            }

            public function has(string $id): bool
            {
                return true;
            }
        });

        self::assertSame(['fetched' => 1, 'published' => 0, 'failed' => 1], $dispatcher->dispatch());
        self::assertStringContainsString('missing.event', $outbox->failed[12]);
    }
}
