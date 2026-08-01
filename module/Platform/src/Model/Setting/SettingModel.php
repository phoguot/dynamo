<?php

declare(strict_types=1);

namespace Platform\Model\Setting;

use Application\Model\AppFormat;
use Application\Model\AppModel;
use Platform\Model\PlatformConst;

class SettingModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $configKey = null;
    protected ?string $configValue = null;
    protected ?string $valueType = null;
    protected ?string $description = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getConfigKey(): ?string { return $this->configKey; }
    public function setConfigKey(?string $configKey): self { $this->configKey = $configKey; return $this; }
    public function getConfigValue(): ?string { return $this->configValue; }
    public function setConfigValue(?string $configValue): self { $this->configValue = $configValue; return $this; }
    public function getValueType(): ?string { return $this->valueType; }
    public function setValueType(?string $valueType): self { $this->valueType = $valueType; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getTypedValue(): mixed
    {
        return match ($this->valueType) {
            PlatformConst::SETTING_TYPE_INT => (int)$this->configValue,
            PlatformConst::SETTING_TYPE_BOOL => in_array(strtolower((string)$this->configValue), ['1', 'true', 'yes'], true),
            PlatformConst::SETTING_TYPE_JSON => PlatformConst::decodeJson($this->configValue),
            default => $this->configValue,
        };
    }

    public function getRespSetting(): array
    {
        return [
            'id'          => AppFormat::castIntOrNull($this->id),
            'configKey'   => AppFormat::castStringOrNull($this->configKey),
            'configValue' => $this->getTypedValue(),
            'valueType'   => AppFormat::castStringOrNull($this->valueType),
            'description' => AppFormat::castStringOrNull($this->description),
            'updatedAt'   => AppFormat::castStringOrNull($this->updatedAt),
        ];
    }
}
