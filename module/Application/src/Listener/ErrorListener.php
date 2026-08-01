<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Chuẩn hóa mã trạng thái HTTP cho lỗi dispatch/render, rồi để Laminas render
 * trang lỗi (error/404 · error/index) như bình thường.
 *
 * Không ghi dữ liệu cá nhân, token hay mật khẩu vào log (.claude/rules/security.md).
 */
class ErrorListener extends AbstractListenerAggregate
{
    public function __construct(private readonly ?ContainerInterface $container = null)
    {
    }

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'onError'], $priority);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'onError'], $priority);
    }

    public function onError(MvcEvent $e): void
    {
        $error = $e->getError();
        if (!$error) {
            return;
        }

        $statusCode = match ($error) {
            'error-router-no-match',
            'error-controller-not-found',
            'error-controller-invalid' => Response::STATUS_CODE_404,
            'error-unauthorized',
            'error-unauthorized-controller',
            'error-unauthorized-route'  => Response::STATUS_CODE_403,
            default                     => Response::STATUS_CODE_500,
        };

        $exception = $e->getParam('exception');
        if ($exception instanceof Throwable) {
            $this->logException($exception, $error, $e, $statusCode);
        }

        $e->getResponse()?->setStatusCode($statusCode);
    }

    private function logException(Throwable $exception, string $dispatchError, MvcEvent $event, int $statusCode): void
    {
        if ($this->logExceptionToDb($exception, $dispatchError, $event, $statusCode)) {
            return;
        }

        $this->logExceptionToFile($exception, $dispatchError);
    }

    private function logExceptionToDb(Throwable $exception, string $dispatchError, MvcEvent $event, int $statusCode): bool
    {
        if ($this->container === null || !$this->container->has('Platform\Service\ErrorLogService')) {
            return false;
        }

        try {
            $service = $this->container->get('Platform\Service\ErrorLogService');
            if (!method_exists($service, 'logThrowable')) {
                return false;
            }

            $service->logThrowable($exception, $this->buildContext($event, $dispatchError, $statusCode));

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(MvcEvent $event, string $dispatchError, int $statusCode): array
    {
        $request = $event->getRequest();
        $routeMatch = $event->getRouteMatch();
        $headers = method_exists($request, 'getHeaders') ? $request->getHeaders() : null;
        $server = method_exists($request, 'getServer') ? $request->getServer() : null;

        $requestId = null;
        if ($headers !== null && $headers->has('X-Request-Id')) {
            $requestId = $headers->get('X-Request-Id')->getFieldValue();
        }
        if ($requestId === null || $requestId === '') {
            $requestId = bin2hex(random_bytes(16));
        }

        return [
            'requestId'     => $requestId,
            'source'        => PHP_SAPI === 'cli' ? 'cli' : 'web',
            'level'         => $statusCode >= 500 ? 'error' : 'warning',
            'dispatchError' => $dispatchError,
            'userId'        => $this->currentUserId(),
            'ip'            => $server?->get('REMOTE_ADDR'),
            'userAgent'     => $headers !== null && $headers->has('User-Agent')
                ? $headers->get('User-Agent')->getFieldValue()
                : null,
            'httpMethod'    => method_exists($request, 'getMethod') ? $request->getMethod() : null,
            'url'           => method_exists($request, 'getUri') ? $request->getUri()->getPath() : null,
            'statusCode'    => $statusCode,
            'route'         => $routeMatch?->getMatchedRouteName(),
            'routeParams'   => $routeMatch?->getParams() ?? [],
        ];
    }

    private function currentUserId(): ?int
    {
        if ($this->container === null || !$this->container->has(\Application\Service\AuthContextService::class)) {
            return null;
        }

        try {
            return $this->container->get(\Application\Service\AuthContextService::class)?->getUserId();
        } catch (Throwable) {
            return null;
        }
    }

    private function logExceptionToFile(Throwable $exception, string $dispatchError): void
    {
        if (!defined('LOG_PATH')) {
            return;
        }
        $line = sprintf(
            "[%s] %s | %s | %s:%d | %s\n",
            date('Y-m-d H:i:s'),
            $dispatchError,
            $exception::class,
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage(),
        );
        @file_put_contents(LOG_PATH . '/error.log', $line, FILE_APPEND);
    }
}
