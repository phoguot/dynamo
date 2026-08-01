<?php

declare(strict_types=1);

namespace Platform\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Platform\Model\Attachment\AttachmentMapper;
use Platform\Model\Attachment\AttachmentModel;
use Platform\Model\PlatformConst;

class AttachmentService extends AppServiceFactory
{
    private const int MAX_STORAGE_PATH_LENGTH = 500;

    private function mapper(): AttachmentMapper
    {
        return $this->getContainerEntry(AttachmentMapper::class);
    }

    public function searchAttachments(array $criteria = []): Paginator
    {
        $item = new AttachmentModel();
        $item->setOwnerType($this->nullIfBlank($criteria['ownerType'] ?? null));
        $item->setOwnerId($this->positiveIntOrNull($criteria['ownerId'] ?? null));
        $item->setKind($this->nullIfBlank($criteria['kind'] ?? null));
        $paging = PaginatorUtil::fromFormData($criteria);
        $paging['sort'] = $criteria['sort'] ?? PlatformConst::ATTACHMENT_SORT_DEFAULT;
        $paging['dir'] = $criteria['dir'] ?? 'desc';

        return $this->mapper()->searchAttachments($item, $paging);
    }

    public function getAttachment(int $id): AttachmentModel
    {
        $item = $this->mapper()->getAttachment($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /**
     * Ghi metadata file đã upload vào storage private. Service này không tự nhận binary upload.
     *
     * @param array<string, mixed> $data
     */
    public function registerAttachment(array $data = []): AttachmentModel
    {
        $errors = [];
        $ownerType = $this->boundedString($data['ownerType'] ?? null, 64);
        $ownerId = $this->positiveIntOrNull($data['ownerId'] ?? null);
        $kind = $this->boundedString($data['kind'] ?? PlatformConst::ATTACHMENT_KIND_OTHER, 32);
        $originalName = $this->boundedString($data['originalName'] ?? null, 255);
        $storagePath = $this->boundedString($data['storagePath'] ?? null, self::MAX_STORAGE_PATH_LENGTH);
        $mimeType = $this->boundedString($data['mimeType'] ?? null, 120);
        $sizeBytes = $this->positiveIntOrNull($data['sizeBytes'] ?? null);
        $checksum = $this->boundedString($data['checksum'] ?? null, 64);
        $version = $this->positiveIntOrNull($data['version'] ?? 1) ?? 1;

        if ($ownerType === null) { $errors['ownerType'] = 'Thiếu loại đối tượng sở hữu file.'; }
        if ($ownerId === null) { $errors['ownerId'] = 'Thiếu id đối tượng sở hữu file.'; }
        if (!in_array($kind, PlatformConst::ATTACHMENT_KINDS, true)) { $errors['kind'] = 'Loại file đính kèm không hợp lệ.'; }
        if ($originalName === null) { $errors['originalName'] = 'Thiếu tên file hiển thị.'; }
        if ($storagePath === null) { $errors['storagePath'] = 'Thiếu đường dẫn storage private.'; }
        if ($mimeType === null) { $errors['mimeType'] = 'Thiếu MIME đã kiểm tra.'; }
        if ($sizeBytes === null) { $errors['sizeBytes'] = 'Dung lượng file phải lớn hơn 0.'; }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $item = (new AttachmentModel())
            ->setOwnerType($ownerType)
            ->setOwnerId($ownerId)
            ->setKind($kind)
            ->setOriginalName($originalName)
            ->setStoragePath($storagePath)
            ->setMimeType($mimeType)
            ->setSizeBytes($sizeBytes)
            ->setChecksum($checksum)
            ->setVersion($version)
            ->setCreatedBy($this->currentUserId());

        return $this->mapper()->insertAttachment($item);
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function boundedString(mixed $value, int $maxLength): ?string
    {
        $value = $this->nullIfBlank($value);
        return $value !== null ? mb_substr($value, 0, $maxLength) : null;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}
