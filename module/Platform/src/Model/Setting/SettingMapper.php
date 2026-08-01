<?php

declare(strict_types=1);

namespace Platform\Model\Setting;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Platform\Model\PlatformConst;

class SettingMapper extends AppMapper
{
    public const string TABLE_NAME = 'pfm_settings';

    public function searchSettings(SettingModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['s' => SettingMapper::TABLE_NAME]);
        if ($criteria->getValueType() !== null) {
            $select->where(['s.valueType = ?' => $criteria->getValueType()]);
        }
        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('s.configKey', $this->escapeLike($keyword) . '%');
        }
        $this->applySort($select, PlatformConst::SETTING_SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, PlatformConst::SETTING_SORT_DEFAULT);

        return $this->preparePaginator($select, $paging, new SettingModel());
    }

    public function getSettingByKey(string $configKey): ?SettingModel
    {
        $configKey = trim($configKey);
        if ($configKey === '') {
            return null;
        }

        $select = $this->getDbSql()->select(['s' => SettingMapper::TABLE_NAME]);
        $select->where(['s.configKey = ?' => $configKey]);
        $select->limit(1);
        $row = $this->getDbSql()->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new SettingModel())->exchangeArray((array)$row) : null;
    }

    public function saveSetting(SettingModel $item): SettingModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();
        $data = [
            'configKey'   => $item->getConfigKey(),
            'configValue' => $item->getConfigValue(),
            'valueType'   => $item->getValueType(),
            'description' => $item->getDescription(),
            'updatedAt'   => $now,
            'updatedBy'   => $item->getUpdatedBy(),
        ];
        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();
            $insert = $dbSql->insert(SettingMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }
        $update = $dbSql->update(SettingMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
