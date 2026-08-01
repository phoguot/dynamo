<?php

declare(strict_types=1);

namespace Fleet\Model\Generator;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Truy vấn bảng flt_generators. Chỉ M02 fleet được đọc/ghi bảng này.
 */
class GeneratorMapper extends AppMapper
{
    public const string TABLE_NAME = 'flt_generators';

    /**
     * Danh sách máy có phân trang.
     *
     * @param array{page?:int, pageSize?:int, sort?:string, dir?:string} $paging
     */
    public function searchGenerators(GeneratorModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);

        $this->applySort(
            $select,
            GeneratorConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            GeneratorConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new GeneratorModel());
    }

    private function buildSearchSelect(GeneratorModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['g' => GeneratorMapper::TABLE_NAME]);
        if ($criteria->getStatus() !== null) {
            $select->where(['g.status = ?' => $criteria->getStatus()]);
        }

        if ($criteria->getFuelType() !== null) {
            $select->where(['g.fuelType = ?' => $criteria->getFuelType()]);
        }

        if ($criteria->getCapacityFrom() !== null) {
            $select->where->greaterThanOrEqualTo('g.capacityKva', $criteria->getCapacityFrom());
        }

        if ($criteria->getCapacityTo() !== null) {
            $select->where->lessThanOrEqualTo('g.capacityKva', $criteria->getCapacityTo());
        }

        // Tìm theo tiền tố để còn dùng được index — xem .claude/rules/database.md mục Hiệu năng.
        $keyword = trim((string)$criteria->getKeyword());
        if ($keyword !== '') {
            $prefix = $this->escapeLike($keyword) . '%';
            $select->where->nest()
                ->like('g.code', $prefix)
                ->or->like('g.name', $prefix)
                ->or->like('g.serialNumber', $prefix)
                ->unnest();
        }

        return $select;
    }

    public function getGenerator(int $id): ?GeneratorModel
    {
        if ($id <= 0) {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['g' => GeneratorMapper::TABLE_NAME]);
        $select->where(['g.id = ?' => $id]);
        $select->limit(1);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $row = $rows->current();
        if (!$row) {
            return null;
        }

        return $this->hydrate((array)$row);
    }

    /** Tìm theo mã máy — dùng để chặn trùng mã trước khi lưu. */
    public function getGeneratorByCode(string $code, ?int $exceptId = null): ?GeneratorModel
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['g' => GeneratorMapper::TABLE_NAME]);
        $select->where(['g.code = ?' => $code]);
        if ($exceptId !== null) {
            $select->where->notEqualTo('g.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? $this->hydrate((array)$row) : null;
    }

    /** Tìm theo serial — serial là UNIQUE ở tầng DB, đây chỉ để báo lỗi đẹp. */
    public function getGeneratorBySerial(string $serialNumber, ?int $exceptId = null): ?GeneratorModel
    {
        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['g' => GeneratorMapper::TABLE_NAME]);
        $select->where(['g.serialNumber = ?' => $serialNumber]);
        if ($exceptId !== null) {
            $select->where->notEqualTo('g.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? $this->hydrate((array)$row) : null;
    }

    /**
     * Thêm mới hoặc cập nhật. Trả về model kèm id sau khi lưu.
     */
    public function saveGenerator(GeneratorModel $item): GeneratorModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'code'             => $item->getCode(),
            'name'             => $item->getName(),
            'serialNumber'     => $item->getSerialNumber(),
            'manufacturer'     => $item->getManufacturer(),
            'model'            => $item->getModel(),
            'capacityKva'      => $item->getCapacityKva(),
            'fuelType'         => $item->getFuelType(),
            'status'           => $item->getStatus(),
            'hourMeter'        => $item->getHourMeter(),
            'currentLocation'  => $item->getCurrentLocation(),
            'latitude'         => $item->getLatitude(),
            'longitude'        => $item->getLongitude(),
            'note'             => $item->getNote(),
            'extraContent'     => json_encode($item->getExtraFieldsArray(), JSON_UNESCAPED_UNICODE) ?: null,
            'updatedAt'        => $now,
            'updatedBy'        => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(GeneratorMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(GeneratorMapper::TABLE_NAME)
            ->set($data)
            ->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    /**
     * Cập nhật MỘT VÀI cột của máy (trạng thái, giờ máy…).
     *
     * Dùng hàm này thay vì `saveGenerator()` khi chỉ đổi một hai trường: `saveGenerator()`
     * ghi đè toàn bộ cột, model không set đủ sẽ làm mất dữ liệu.
     * Tên cột do CODE truyền vào, không bao giờ lấy từ client.
     *
     * @param array<string, mixed> $data cột DB => giá trị
     */
    public function updateAttrsGenerator(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(GeneratorMapper::TABLE_NAME)->set($data)->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    /**
     * @return array{totalGenerators:int, availableCount:int, heldCount:int, transitCount:int, rentedCount:int, maintenanceCount:int, repairCount:int, retiredCount:int}
     */
    public function summarizeStatusCounts(): array
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['g' => GeneratorMapper::TABLE_NAME]);
        $select->columns([
            'totalGenerators' => new Expression('COUNT(*)'),
            'availableCount' => new Expression("COALESCE(SUM(CASE WHEN status = 'san_sang' THEN 1 ELSE 0 END), 0)"),
            'heldCount' => new Expression("COALESCE(SUM(CASE WHEN status = 'dang_giu_cho' THEN 1 ELSE 0 END), 0)"),
            'transitCount' => new Expression("COALESCE(SUM(CASE WHEN status = 'dang_van_chuyen' THEN 1 ELSE 0 END), 0)"),
            'rentedCount' => new Expression("COALESCE(SUM(CASE WHEN status = 'dang_thue' THEN 1 ELSE 0 END), 0)"),
            'maintenanceCount' => new Expression("COALESCE(SUM(CASE WHEN status = 'dang_bao_tri' THEN 1 ELSE 0 END), 0)"),
            'repairCount' => new Expression("COALESCE(SUM(CASE WHEN status = 'dang_sua_chua' THEN 1 ELSE 0 END), 0)"),
            'retiredCount' => new Expression("COALESCE(SUM(CASE WHEN status = 'ngung_khai_thac' THEN 1 ELSE 0 END), 0)"),
        ]);

        $row = (array)($dbSql->prepareStatementForSqlObject($select)->execute()->current() ?: []);

        return [
            'totalGenerators' => (int)($row['totalGenerators'] ?? 0),
            'availableCount' => (int)($row['availableCount'] ?? 0),
            'heldCount' => (int)($row['heldCount'] ?? 0),
            'transitCount' => (int)($row['transitCount'] ?? 0),
            'rentedCount' => (int)($row['rentedCount'] ?? 0),
            'maintenanceCount' => (int)($row['maintenanceCount'] ?? 0),
            'repairCount' => (int)($row['repairCount'] ?? 0),
            'retiredCount' => (int)($row['retiredCount'] ?? 0),
        ];
    }

    private function hydrate(array $row): GeneratorModel
    {
        return (new GeneratorModel())->exchangeArray($row);
    }

    /** Thoát ký tự đại diện của LIKE để người dùng gõ `%` không quét cả bảng. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
