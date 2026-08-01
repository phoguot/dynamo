<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthContextService;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Model\ViewModel;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Lớp nền chung cho mọi Controller: truy cập container và đọc tham số request.
 *
 * KHÔNG kế thừa trực tiếp class này ở module nghiệp vụ — kế thừa `BaseController`.
 * Hệ thống chỉ có trang HTML render phía server, không có tầng API (ADR-0002).
 */
class AppController extends AbstractActionController implements FactoryInterface
{
    protected ?ViewModel $viewModel = null;

    protected ?ContainerInterface $container = null;

    public function getViewModel(): ViewModel
    {
        return $this->viewModel ??= new ViewModel();
    }

    public function getRequest(): Request
    {
        return parent::getRequest();
    }

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
    }

    protected function getAuthContext(): AuthContextService
    {
        return $this->getContainerEntry(AuthContextService::class);
    }

    protected function currentUserId(): ?int
    {
        return $this->getAuthContext()->getUserId();
    }

    protected function currentRole(): ?string
    {
        return $this->getAuthContext()->getRole();
    }

    /**
     * Toàn bộ tham số POST (kèm file upload). Đưa THẲNG cho Service, không xử lý ở Controller.
     */
    protected function getAllPostParams(): array
    {
        return array_merge_recursive(
            $this->getRequest()->getPost()->toArray(),
            $this->getRequest()->getFiles()->toArray()
        );
    }

    protected function getAllQueryParams(): array
    {
        return $this->getRequest()->getQuery()->toArray();
    }

    protected function getParam(string $name, mixed $default = null): mixed
    {
        $value = $this->getRequest()->getPost($name, $this->getRequest()->getQuery($name));
        return ($value === null || $value === '') ? $default : $value;
    }
}
