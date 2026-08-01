<?php

declare(strict_types=1);

namespace Platform\Model\ErrorLog;

use Application\Model\AppModel;

/** Log lỗi hệ thống để tra cứu sự cố. Không ghi dữ liệu nhạy cảm vào model này. */
class ErrorLogModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $requestId = null;
    protected ?string $source = null;
    protected ?string $level = null;
    protected ?string $dispatchError = null;
    protected ?string $exceptionClass = null;
    protected ?string $errorCode = null;
    protected ?string $message = null;
    protected ?string $filePath = null;
    protected ?int $lineNumber = null;
    protected ?string $stackTrace = null;
    protected ?string $contextJson = null;
    protected ?int $userId = null;
    protected ?string $ip = null;
    protected ?string $userAgent = null;
    protected ?string $httpMethod = null;
    protected ?string $url = null;
    protected ?int $statusCode = null;
    protected ?string $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getDispatchError(): ?string
    {
        return $this->dispatchError;
    }

    public function setDispatchError(?string $dispatchError): self
    {
        $this->dispatchError = $dispatchError;
        return $this;
    }

    public function getExceptionClass(): ?string
    {
        return $this->exceptionClass;
    }

    public function setExceptionClass(?string $exceptionClass): self
    {
        $this->exceptionClass = $exceptionClass;
        return $this;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    public function setLineNumber(?int $lineNumber): self
    {
        $this->lineNumber = $lineNumber;
        return $this;
    }

    public function getStackTrace(): ?string
    {
        return $this->stackTrace;
    }

    public function setStackTrace(?string $stackTrace): self
    {
        $this->stackTrace = $stackTrace;
        return $this;
    }

    public function getContextJson(): ?string
    {
        return $this->contextJson;
    }

    public function setContextJson(mixed $contextJson): self
    {
        $this->contextJson = is_string($contextJson) && $contextJson !== '' ? $contextJson : null;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(?string $httpMethod): self
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
