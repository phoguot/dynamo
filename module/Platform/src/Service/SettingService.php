<?php

declare(strict_types=1);

namespace Platform\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Platform\Model\PlatformConst;
use Platform\Model\Setting\SettingMapper;
use Platform\Model\Setting\SettingModel;

class SettingService extends AppServiceFactory
{
    private const array SECRET_KEY_PARTS = ['password', 'secret', 'token', 'credential', 'privateKey'];

    private function mapper(): SettingMapper
    {
        return $this->getContainerEntry(SettingMapper::class);
    }

    public function searchSettings(array $criteria = []): Paginator
    {
        $item = new SettingModel();
        $item->addOption('keyword', $this->nullIfBlank($criteria['keyword'] ?? null));
        $item->setValueType($this->nullIfBlank($criteria['valueType'] ?? null));
        $paging = PaginatorUtil::fromFormData($criteria);
        $paging['sort'] = $criteria['sort'] ?? PlatformConst::SETTING_SORT_DEFAULT;
        $paging['dir'] = $criteria['dir'] ?? 'asc';

        return $this->mapper()->searchSettings($item, $paging);
    }

    public function getSetting(string $configKey): SettingModel
    {
        $item = $this->mapper()->getSettingByKey($configKey);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function getValue(string $configKey, mixed $default = null): mixed
    {
        $item = $this->mapper()->getSettingByKey($configKey);

        return $item?->getTypedValue() ?? $default;
    }

    public function saveSetting(string $configKey, mixed $value, string $valueType = PlatformConst::SETTING_TYPE_STRING, ?string $description = null): SettingModel
    {
        $configKey = trim($configKey);
        $valueType = trim($valueType);
        $description = $this->nullIfBlank($description);

        $errors = [];
        if ($configKey === '' || strlen($configKey) > 120) {
            $errors['configKey'] = 'Khóa cấu hình không hợp lệ.';
        }
        if ($this->looksLikeSecretKey($configKey)) {
            $errors['configKey'] = 'Không lưu secret trong bảng cấu hình; hãy dùng biến môi trường.';
        }
        if (!in_array($valueType, PlatformConst::SETTING_TYPES, true)) {
            $errors['valueType'] = 'Kiểu cấu hình không hợp lệ.';
        }

        $configValue = $this->normalizeValue($value, $valueType, $errors);
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $item = $this->mapper()->getSettingByKey($configKey) ?? new SettingModel();
        $item->setConfigKey($configKey)
            ->setConfigValue($configValue)
            ->setValueType($valueType)
            ->setDescription($description)
            ->setUpdatedBy($this->currentUserId());
        if (!$item->getId()) {
            $item->setCreatedBy($this->currentUserId());
        }

        return $this->mapper()->saveSetting($item);
    }

    private function normalizeValue(mixed $value, string $valueType, array &$errors): ?string
    {
        return match ($valueType) {
            PlatformConst::SETTING_TYPE_INT => (string)(int)$value,
            PlatformConst::SETTING_TYPE_BOOL => $value ? '1' : '0',
            PlatformConst::SETTING_TYPE_JSON => $this->normalizeJsonValue($value, $errors),
            default => $this->nullIfBlank($value),
        };
    }

    private function normalizeJsonValue(mixed $value, array &$errors): ?string
    {
        $json = PlatformConst::normalizeJson($value);
        if ($json === '') {
            $errors['configValue'] = 'Giá trị JSON không hợp lệ.';
            return null;
        }

        return $json;
    }

    private function looksLikeSecretKey(string $configKey): bool
    {
        $lowerKey = strtolower($configKey);
        foreach (self::SECRET_KEY_PARTS as $part) {
            if (str_contains($lowerKey, strtolower($part))) {
                return true;
            }
        }

        return false;
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }
}
