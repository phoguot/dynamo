<?php

declare(strict_types=1);

namespace Application\Paginator;

final class PaginatorUtil
{
    /**
     * Map từng item của trang hiện tại qua callback.
     *
     * @template T
     * @param callable(T):array $fn
     * @return array<int, array<string, mixed>>
     */
    public static function mapPaginator(Paginator $paginator, callable $fn): array
    {
        $result = [];
        foreach ((array)$paginator->getCurrentItems() as $item) {
            $result[] = $fn($item);
        }
        return $result;
    }

    /**
     * Lấy tham số phân trang từ dữ liệu đã qua Filter.
     * Giá trị sai hoặc vượt ngưỡng bị đưa về mặc định — không tin dữ liệu client.
     *
     * @return array{page: int, pageSize: int}
     */
    public static function fromFormData(array $formData): array
    {
        $page = (int)($formData[PaginatorConst::KEY_PAGE] ?? 0);
        if ($page <= 0) {
            $page = PaginatorConst::DEFAULT_PAGE;
        }

        $pageSize = (int)($formData[PaginatorConst::KEY_PAGE_SIZE] ?? 0);
        if ($pageSize <= 0 || $pageSize > PaginatorConst::MAX_PAGE_SIZE) {
            $pageSize = PaginatorConst::DEFAULT_PAGE_SIZE;
        }

        return [
            PaginatorConst::KEY_PAGE      => $page,
            PaginatorConst::KEY_PAGE_SIZE => $pageSize,
        ];
    }
}
