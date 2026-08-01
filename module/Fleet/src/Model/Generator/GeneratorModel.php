<?php

declare(strict_types=1);

namespace Fleet\Model\Generator;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Máy phát điện — entity chính của M02 fleet.
 *
 * POPO: chỉ getter/setter và hàm dựng dữ liệu hiển thị. Không SQL, không quy tắc nghiệp vụ.
 * Tên thuộc tính và cột DB đều dùng camelCase để model nạp dữ liệu trực tiếp.
 */
class GeneratorModel extends AppModel
{
    // --- Cột trong bảng flt_generators ---
    protected ?int $id = null;
    protected ?string $code = null;            // Mã máy nội bộ, duy nhất
    protected ?string $name = null;
    protected ?string $serialNumber = null;    // Số serial nhà sản xuất, duy nhất
    protected ?string $manufacturer = null;
    protected ?string $model = null;
    protected ?int $capacityKva = null;        // Công suất (kVA)
    protected ?string $fuelType = null;
    protected ?string $status = null;
    protected ?float $hourMeter = null;        // Giờ máy tích lũy
    protected ?string $currentLocation = null; // Mô tả vị trí hiện tại (kho / công trình)
    protected ?float $latitude = null;
    protected ?float $longitude = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    // --- Trường phục vụ tìm kiếm, không lưu DB ---
    protected ?string $keyword = null;
    protected ?int $capacityFrom = null;
    protected ?int $capacityTo = null;

    protected function getConstClass(): ?string
    {
        return GeneratorConst::class;
    }

    // -------------------------------------------------------------------------
    // Cột DB

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code !== null ? strtoupper(trim($code)) : null;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): self
    {
        $this->manufacturer = $manufacturer;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getCapacityKva(): ?int
    {
        return $this->capacityKva;
    }

    public function setCapacityKva(?int $capacityKva): self
    {
        $this->capacityKva = $capacityKva;
        return $this;
    }

    public function getFuelType(): ?string
    {
        return $this->fuelType;
    }

    public function setFuelType(?string $fuelType): self
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getHourMeter(): ?float
    {
        return $this->hourMeter;
    }

    public function setHourMeter(?float $hourMeter): self
    {
        $this->hourMeter = $hourMeter;
        return $this;
    }

    public function getCurrentLocation(): ?string
    {
        return $this->currentLocation;
    }

    public function setCurrentLocation(?string $currentLocation): self
    {
        $this->currentLocation = $currentLocation;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
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

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Trường tìm kiếm

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(?string $keyword): self
    {
        $this->keyword = $keyword;
        return $this;
    }

    public function getCapacityFrom(): ?int
    {
        return $this->capacityFrom;
    }

    public function setCapacityFrom(?int $capacityFrom): self
    {
        $this->capacityFrom = $capacityFrom;
        return $this;
    }

    public function getCapacityTo(): ?int
    {
        return $this->capacityTo;
    }

    public function setCapacityTo(?int $capacityTo): self
    {
        $this->capacityTo = $capacityTo;
        return $this;
    }

    // -------------------------------------------------------------------------

    public function getStatusLabel(): string
    {
        return GeneratorConst::statusLabel($this->status);
    }

    public function getFuelLabel(): string
    {
        return GeneratorConst::fuelLabel($this->fuelType);
    }

    /**
     * Dữ liệu đã chuẩn hóa để hiển thị — gom một chỗ để View và báo cáo không tự
     * định dạng lại từng trường mỗi nơi một kiểu.
     *
     * @return array<string, mixed>
     */
    public function getRespGenerator(): array
    {
        return [
            'id'           => AppFormat::castIntOrNull($this->id),
            'code'         => AppFormat::castStringOrNull($this->code),
            'name'         => AppFormat::castStringOrNull($this->name),
            'serialNumber' => AppFormat::castStringOrNull($this->serialNumber),
            'manufacturer' => AppFormat::castStringOrNull($this->manufacturer),
            'model'        => AppFormat::castStringOrNull($this->model),
            'capacityKva'  => AppFormat::castIntOrNull($this->capacityKva),
            'fuel'         => [
                'id'   => AppFormat::castStringOrNull($this->fuelType),
                'name' => $this->getFuelLabel(),
            ],
            'status'       => [
                'id'   => AppFormat::castStringOrNull($this->status),
                'name' => $this->getStatusLabel(),
            ],
            'hourMeter'    => AppFormat::castDoubleOrNull($this->hourMeter),
            'location'     => AppFormat::castStringOrNull($this->currentLocation),
            'specs'        => $this->getExtraFieldsArray(),
            'createdAt'    => AppFormat::castStringOrNull($this->createdAt),
        ];
    }
}
