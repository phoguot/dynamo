<?php

declare(strict_types=1);

namespace Platform\Model\Attachment;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Platform\Model\PlatformConst;

class AttachmentMapper extends AppMapper
{
    public const string TABLE_NAME = 'pfm_attachments';

    public function searchAttachments(AttachmentModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['a' => AttachmentMapper::TABLE_NAME]);
        foreach ([
            'a.ownerType = ?' => $criteria->getOwnerType(),
            'a.ownerId = ?'   => $criteria->getOwnerId(),
            'a.kind = ?'      => $criteria->getKind(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }
        $this->applySort($select, PlatformConst::ATTACHMENT_SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, PlatformConst::ATTACHMENT_SORT_DEFAULT);

        return $this->preparePaginator($select, $paging, new AttachmentModel());
    }

    public function getAttachment(int $id): ?AttachmentModel
    {
        if ($id <= 0) {
            return null;
        }

        $select = $this->getDbSql()->select(['a' => AttachmentMapper::TABLE_NAME]);
        $select->where(['a.id = ?' => $id]);
        $select->limit(1);
        $row = $this->getDbSql()->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new AttachmentModel())->exchangeArray((array)$row) : null;
    }

    public function insertAttachment(AttachmentModel $item): AttachmentModel
    {
        $dbSql = $this->getDbSql();
        $insert = $dbSql->insert(AttachmentMapper::TABLE_NAME);
        $insert->values([
            'ownerType'    => $item->getOwnerType(),
            'ownerId'      => $item->getOwnerId(),
            'kind'         => $item->getKind(),
            'originalName' => $item->getOriginalName(),
            'storagePath'  => $item->getStoragePath(),
            'mimeType'     => $item->getMimeType(),
            'sizeBytes'    => $item->getSizeBytes(),
            'checksum'     => $item->getChecksum(),
            'version'      => $item->getVersion() ?? 1,
            'createdAt'    => $item->getCreatedAt() ?? DateModel::getUtcNow(),
            'createdBy'    => $item->getCreatedBy(),
        ]);
        $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
        $item->setId((int)$result->getGeneratedValue());

        return $item;
    }
}
