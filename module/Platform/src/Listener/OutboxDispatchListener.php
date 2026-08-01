<?php

declare(strict_types=1);

namespace Platform\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Platform\Service\OutboxDispatcherService;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Phát các outbox event còn `cho_phat` ngay trong request, sau khi transaction
 * nghiệp vụ đã commit và response đã gửi cho trình duyệt.
 *
 * Dự án không có cron và không có worker nền (ADR-0005): đây là chỗ duy nhất
 * gọi `OutboxDispatcherService::dispatch()`. Priority -20000 để chạy sau
 * `Laminas\Mvc\SendResponseListener` (-10000) — người dùng không phải chờ
 * handler của module nghe event chạy xong.
 *
 * Lỗi ở đây không được làm hỏng request: event thất bại đã được dispatcher
 * đánh dấu `that_bai` kèm lý do, lần request sau sẽ không lấy lại nó.
 */
class OutboxDispatchListener extends AbstractListenerAggregate
{
    /** Chạy sau SendResponseListener (-10000). */
    public const int PRIORITY = -20000;

    /** Trần số event xử lý mỗi request — không để một request gánh cả tồn đọng. */
    public const int DEFAULT_LIMIT = 50;

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function attach(EventManagerInterface $events, $priority = self::PRIORITY): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], $priority);
    }

    public function onFinish(MvcEvent $event): void
    {
        if (!$this->shouldDispatch($event)) {
            return;
        }

        // Đóng kết nối tới trình duyệt trước khi chạy handler: dưới PHP-FPM,
        // response mà SendResponseListener (-10000) vừa echo chỉ thực sự flush khi
        // gọi hàm này. Không có nó, người dùng phải chờ handler chạy xong dù listener
        // đã ở priority sau. Chỉ tồn tại trên FPM — môi trường khác bỏ qua an toàn.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        try {
            $dispatcher = $this->container->get(OutboxDispatcherService::class);
            if ($dispatcher instanceof OutboxDispatcherService) {
                $dispatcher->dispatch($this->limit());
            }
        } catch (Throwable) {
            // Response đã gửi xong — không còn cách nào báo lỗi ra UI, và cũng
            // không được ném ngược lên làm hỏng request đã thành công. Event lỗi đã
            // được dispatcher đánh dấu `that_bai`; request POST sau sẽ không lấy lại nó.
        }
    }

    /**
     * Chỉ phát ở request thực sự đổi dữ liệu. Request GET không sinh event mới,
     * quét bảng outbox ở đó chỉ tốn một truy vấn thừa cho mỗi lần tải trang.
     */
    private function shouldDispatch(MvcEvent $event): bool
    {
        $request = $event->getRequest();
        if (!method_exists($request, 'getMethod')) {
            return false;
        }

        return strtoupper((string)$request->getMethod()) === 'POST';
    }

    private function limit(): int
    {
        try {
            $config = $this->container->get('config');
        } catch (Throwable) {
            return self::DEFAULT_LIMIT;
        }

        $limit = is_array($config) ? (int)($config['platform_outbox_dispatch_limit'] ?? 0) : 0;

        return $limit > 0 ? min($limit, 500) : self::DEFAULT_LIMIT;
    }
}
