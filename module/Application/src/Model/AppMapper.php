<?php

declare(strict_types=1);

namespace Application\Model;

use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorConst;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Paginator\Adapter\DbSelect;

/**
 * Lớp nền cho mọi Mapper.
 *
 * Mapper là nơi DUY NHẤT được viết SQL. Quy tắc bắt buộc:
 * - Chỉ truy vấn bảng thuộc module của mình (tiền tố bảng riêng) — xem .claude/rules/module-boundaries.md.
 * - Luôn dùng prepared statement qua Laminas\Db\Sql. Cấm nối chuỗi SQL, kể cả tên cột sắp xếp.
 * - Không chứa quy tắc nghiệp vụ; quy tắc nằm ở Service.
 */
class AppMapper extends AppServiceFactory
{
    /** Trần an toàn cho các truy vấn fetch-all (danh mục, export nội bộ). */
    public const int MAX_RECORD_FETCH_ALL = 2000;

    private ?Select $select = null;

    public function getDbSql(): Sql
    {
        return $this->getContainerEntry('dbSql');
    }

    public function getDbAdapter(): Adapter
    {
        return $this->getContainerEntry('dbAdapter');
    }

    /**
     * Dựng paginator phân trang phía server cho một Select.
     *
     * @param array{page?:int, pageSize?:int} $options
     * @param AppModel|null                   $objectPrototype Kiểu đối tượng của từng dòng kết quả
     */
    public function preparePaginator(Select $select, array $options = [], ?AppModel $objectPrototype = null): Paginator
    {
        $resultSetPrototype = null;
        if ($objectPrototype !== null) {
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->buffer();
            $resultSetPrototype->setArrayObjectPrototype($objectPrototype);
        }

        $paginatorAdapter = new DbSelect($select, $this->getDbAdapter(), $resultSetPrototype);
        $paginator = new Paginator($paginatorAdapter);

        $paginator->setItemCountPerPage(
            (int)($options[PaginatorConst::KEY_PAGE_SIZE] ?? PaginatorConst::DEFAULT_PAGE_SIZE)
        );
        $paginator->setCurrentPageNumber(
            (int)($options[PaginatorConst::KEY_PAGE] ?? PaginatorConst::DEFAULT_PAGE)
        );

        return $paginator;
    }

    /**
     * Áp sắp xếp cho Select theo whitelist.
     * Tên cột lấy từ tham số người dùng KHÔNG BAO GIỜ được nội suy thẳng — xem .claude/rules/security.md.
     *
     * @param array<string, string> $sortMap  key client gửi lên => tên cột thật trong SQL
     */
    protected function applySort(Select $select, array $sortMap, ?string $sort, ?string $dir, string $defaultSort): void
    {
        $column = $sortMap[$sort] ?? $sortMap[$defaultSort] ?? null;
        if ($column === null) {
            return;
        }
        $direction = strtolower((string)$dir) === 'asc' ? 'ASC' : 'DESC';
        $select->order($column . ' ' . $direction);
    }

    /**
     * Chạy một khối lệnh trong transaction. Rollback và ném lại nếu lỗi.
     *
     * Bắt buộc dùng khi một nghiệp vụ chạm nhiều bảng (tạo đơn thuê + chiếm dụng lịch,
     * ghi hóa đơn + thanh toán) — xem .claude/rules/database.md.
     *
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public function transactional(callable $fn): mixed
    {
        $connection = $this->getDbAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $result = $fn();
            $connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $connection->rollback();
            throw $e;
        }
    }

    public function setSelect(Select $select): void
    {
        $this->select = $select;
    }

    /**
     * Trả về Select "sạch" — reset mọi phần trước khi dùng lại.
     */
    public function getSelect(): Select
    {
        if ($this->select === null) {
            $this->setSelect(new Select());
        }

        if (!$this->select->isTableReadOnly()) {
            foreach ([
                Select::TABLE, Select::QUANTIFIER, Select::COLUMNS, Select::JOINS,
                Select::WHERE, Select::GROUP, Select::HAVING, Select::LIMIT,
                Select::OFFSET, Select::ORDER, Select::COMBINE,
            ] as $part) {
                $this->select->reset($part);
            }
        }

        return $this->select;
    }
}
