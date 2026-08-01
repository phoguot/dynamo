<?php

declare(strict_types=1);

namespace Application\Factory;

use Application\Service\AuthContextService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Lớp nền cho Service và Mapper.
 *
 * Mọi Service/Mapper đăng ký qua AppInvokableFactory: container được tiêm vào bằng
 * setContainer(), sau đó lấy phụ thuộc bằng getContainerEntry(). Không nhận dependency
 * qua constructor để một class có thể vừa là service vừa là factory của chính nó.
 */
class AppServiceFactory implements FactoryInterface
{
    protected ?ContainerInterface $container = null;

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container): static
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Lấy đối tượng từ container theo tên hoặc class.
     *
     * @template T
     * @param class-string<T>|string $entryName
     * @return T|null
     */
    public function getContainerEntry(string $entryName)
    {
        try {
            return $this->getContainer()?->get($entryName);
        } catch (Throwable) {
            return null;
        }
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $this->setContainer($container);
        return $this->initService();
    }

    /**
     * Hook cho subclass chuẩn bị trạng thái sau khi có container.
     */
    protected function initService(): static
    {
        return $this;
    }

    /**
     * Ngữ cảnh người đăng nhập của request hiện tại (id, vai trò).
     * Service dùng để kiểm phạm vi dữ liệu — xem .claude/rules/security.md.
     */
    protected function getAuthContext(): ?AuthContextService
    {
        return $this->getContainerEntry(AuthContextService::class);
    }

    protected function currentUserId(): ?int
    {
        return $this->getAuthContext()?->getUserId();
    }

    protected function currentRole(): ?string
    {
        return $this->getAuthContext()?->getRole();
    }
}
