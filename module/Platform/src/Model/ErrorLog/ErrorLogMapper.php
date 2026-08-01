<?php

declare(strict_types=1);

namespace Platform\Model\ErrorLog;

use Application\Model\AppMapper;
use Application\Model\DateModel;

/** Ghi bảng pfm_error_logs. Bảng này append-only để còn tra sự cố sau khi lỗi xảy ra. */
class ErrorLogMapper extends AppMapper
{
    public const string TABLE_NAME = 'pfm_error_logs';

    public function insertLog(ErrorLogModel $item): ErrorLogModel
    {
        $dbSql = $this->getDbSql();

        $insert = $dbSql->insert(ErrorLogMapper::TABLE_NAME);
        $insert->values([
            'requestId'      => $item->getRequestId(),
            'source'         => $item->getSource() ?? 'web',
            'level'          => $item->getLevel() ?? 'error',
            'dispatchError'  => $item->getDispatchError(),
            'exceptionClass' => $item->getExceptionClass(),
            'errorCode'      => $item->getErrorCode(),
            'message'        => $item->getMessage() ?? '',
            'filePath'       => $item->getFilePath(),
            'lineNumber'     => $item->getLineNumber(),
            'stackTrace'     => $item->getStackTrace(),
            'contextJson'    => $item->getContextJson(),
            'userId'         => $item->getUserId(),
            'ip'             => $item->getIp(),
            'userAgent'      => $item->getUserAgent(),
            'httpMethod'     => $item->getHttpMethod(),
            'url'            => $item->getUrl(),
            'statusCode'     => $item->getStatusCode(),
            'createdAt'      => $item->getCreatedAt() ?? DateModel::getUtcNow(),
        ]);

        $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
        $item->setId((int)$result->getGeneratedValue());

        return $item;
    }
}
