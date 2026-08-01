<?php

declare(strict_types=1);

namespace Platform\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Platform\Model\ErrorLog\ErrorLogMapper;
use Platform\Model\ErrorLog\ErrorLogModel;
use Throwable;

/** Ghi log lỗi hệ thống. Mọi dữ liệu đưa vào đây phải được scrub trước khi lưu DB. */
class ErrorLogService extends AppServiceFactory
{
    private const int MAX_MESSAGE_LENGTH = 1000;
    private const int MAX_URL_LENGTH = 500;
    private const int MAX_USER_AGENT_LENGTH = 255;
    private const int MAX_TRACE_LENGTH = 60000;
    private const string MASK = '[filtered]';

    private const array SENSITIVE_KEYS = [
        'password',
        'passwordConfirm',
        'passwordHash',
        'token',
        'csrfToken',
        'csrf',
        'secret',
        'authorization',
        'cookie',
        'set-cookie',
        'idempotencyKey',
    ];

    private function mapper(): ErrorLogMapper
    {
        return $this->getContainerEntry(ErrorLogMapper::class);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function logThrowable(Throwable $exception, array $context = []): ?ErrorLogModel
    {
        $item = new ErrorLogModel();
        $item->setRequestId($this->stringOrNull($context['requestId'] ?? null, 64));
        $item->setSource($this->stringOrNull($context['source'] ?? 'web', 32));
        $item->setLevel($this->stringOrNull($context['level'] ?? 'error', 16));
        $item->setDispatchError($this->stringOrNull($context['dispatchError'] ?? null, 64));
        $item->setExceptionClass($exception::class);
        $item->setErrorCode($this->stringOrNull($context['errorCode'] ?? null, 64));
        $item->setMessage($this->truncate($this->scrubString($exception->getMessage()), self::MAX_MESSAGE_LENGTH));
        $item->setFilePath($this->stringOrNull($exception->getFile(), 500));
        $item->setLineNumber($exception->getLine());
        $item->setStackTrace($this->truncate($this->scrubString($exception->getTraceAsString()), self::MAX_TRACE_LENGTH));
        $item->setContextJson($this->encodeContext($context));
        $item->setUserId($this->intOrNull($context['userId'] ?? null));
        $item->setIp($this->stringOrNull($context['ip'] ?? null, 45));
        $item->setUserAgent($this->stringOrNull($context['userAgent'] ?? null, self::MAX_USER_AGENT_LENGTH));
        $item->setHttpMethod($this->stringOrNull($context['httpMethod'] ?? null, 10));
        $item->setUrl($this->stringOrNull($context['url'] ?? null, self::MAX_URL_LENGTH));
        $item->setStatusCode($this->intOrNull($context['statusCode'] ?? null));
        $item->setCreatedAt(DateModel::getUtcNow());

        return $this->mapper()->insertLog($item);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function encodeContext(array $context): ?string
    {
        unset(
            $context['ip'],
            $context['userAgent'],
            $context['httpMethod'],
            $context['url'],
            $context['statusCode'],
        );

        $context = $this->scrubValue($context);
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) && $json !== '' ? $json : null;
    }

    private function scrubValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && $this->isSensitiveKey($key)) {
                    $result[$key] = self::MASK;
                    continue;
                }
                $result[$key] = $this->scrubValue($item);
            }
            return $result;
        }

        if (is_string($value)) {
            return $this->scrubString($value);
        }

        return $value;
    }

    private function scrubString(string $value): string
    {
        $value = preg_replace('/(Bearer\s+)[A-Za-z0-9._~+\-\/]+=*/i', '$1' . self::MASK, $value) ?? $value;
        $value = preg_replace('/([?&](?:token|csrfToken|password|secret|idempotencyKey)=)[^&\s]+/i', '$1' . self::MASK, $value) ?? $value;

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === strtolower($sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int)$value;
    }

    private function stringOrNull(mixed $value, int $maxLength): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->truncate((string)$value, $maxLength);
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength);
    }
}
