SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `usr_users` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`         VARCHAR(30)     NOT NULL,
    `fullName`         VARCHAR(120)    NOT NULL,
    `email`            VARCHAR(190)         NULL,
    `phone`            VARCHAR(20)          NULL,
    `passwordHash`     VARCHAR(255)    NOT NULL,
    `role`             VARCHAR(32)     NOT NULL,
    `status`           VARCHAR(32)     NOT NULL DEFAULT 'hoat_dong',
    `lastLoginAt`      DATETIME(3)          NULL,
    `failedLoginCount` INT UNSIGNED    NOT NULL DEFAULT 0,
    `lockedUntil`      DATETIME(3)          NULL,
    `note`             VARCHAR(255)         NULL,
    `createdAt`        DATETIME(3)     NOT NULL,
    `updatedAt`        DATETIME(3)     NOT NULL,
    `createdBy`        BIGINT UNSIGNED      NULL,
    `updatedBy`        BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usr_users_username` (`username`),
    UNIQUE KEY `uq_usr_users_email` (`email`),
    KEY `idx_usr_users_role_status` (`role`, `status`),
    KEY `idx_usr_users_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `usr_user_permissions` (
    `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId`    BIGINT UNSIGNED NOT NULL,
    `resource`  VARCHAR(64)     NOT NULL,
    `privilege` VARCHAR(64)     NOT NULL,
    `effect`    VARCHAR(8)      NOT NULL,
    `createdAt` DATETIME(3)     NOT NULL,
    `createdBy` BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usr_user_permissions` (`userId`, `resource`, `privilege`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `usr_sessions` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sessionId`      VARCHAR(128)    NOT NULL,
    `userId`         BIGINT UNSIGNED NOT NULL,
    `deviceLabel`    VARCHAR(120)         NULL,
    `userAgent`      VARCHAR(255)         NULL,
    `ip`             VARCHAR(45)          NULL,
    `isFieldApp`     TINYINT(1)      NOT NULL DEFAULT 0,
    `lastActivityAt` DATETIME(3)     NOT NULL,
    `expiresAt`      DATETIME(3)     NOT NULL,
    `revokedAt`      DATETIME(3)          NULL,
    `createdAt`      DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usr_sessions_session` (`sessionId`),
    KEY `idx_usr_sessions_user` (`userId`, `revokedAt`),
    KEY `idx_usr_sessions_expires` (`expiresAt`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `usr_audit_logs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId`     BIGINT UNSIGNED      NULL,
    `action`     VARCHAR(32)     NOT NULL,
    `objectType` VARCHAR(64)          NULL,
    `objectId`   BIGINT UNSIGNED      NULL,
    `beforeJson` JSON                 NULL,
    `afterJson`  JSON                 NULL,
    `ip`         VARCHAR(45)          NULL,
    `userAgent`  VARCHAR(255)         NULL,
    `createdAt`  DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_usr_audit_logs_object` (`objectType`, `objectId`, `id`),
    KEY `idx_usr_audit_logs_user` (`userId`, `id`),
    KEY `idx_usr_audit_logs_action` (`action`, `id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `pfm_attachments` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ownerType`    VARCHAR(64)     NOT NULL,
    `ownerId`      BIGINT UNSIGNED NOT NULL,
    `kind`         VARCHAR(32)     NOT NULL,
    `originalName` VARCHAR(255)    NOT NULL,
    `storagePath`  VARCHAR(500)    NOT NULL,
    `mimeType`     VARCHAR(120)    NOT NULL,
    `sizeBytes`    BIGINT UNSIGNED NOT NULL,
    `checksum`     VARCHAR(64)          NULL,
    `version`      INT UNSIGNED    NOT NULL DEFAULT 1,
    `createdAt`    DATETIME(3)     NOT NULL,
    `createdBy`    BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    KEY `idx_pfm_attachments_owner` (`ownerType`, `ownerId`, `id`),
    KEY `idx_pfm_attachments_kind` (`kind`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `pfm_notifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId`     BIGINT UNSIGNED NOT NULL,
    `channel`    VARCHAR(16)     NOT NULL DEFAULT 'in_app',
    `title`      VARCHAR(200)    NOT NULL,
    `body`       VARCHAR(1000)        NULL,
    `linkUrl`    VARCHAR(500)         NULL,
    `objectType` VARCHAR(64)          NULL,
    `objectId`   BIGINT UNSIGNED      NULL,
    `readAt`     DATETIME(3)          NULL,
    `createdAt`  DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_pfm_notifications_inbox` (`userId`, `readAt`, `id`),
    KEY `idx_pfm_notifications_object` (`objectType`, `objectId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `pfm_error_logs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `requestId`      VARCHAR(64)          NULL,
    `source`         VARCHAR(32)     NOT NULL DEFAULT 'web',
    `level`          VARCHAR(16)     NOT NULL DEFAULT 'error',
    `dispatchError`  VARCHAR(64)          NULL,
    `exceptionClass` VARCHAR(255)         NULL,
    `errorCode`      VARCHAR(64)          NULL,
    `message`        VARCHAR(1000)   NOT NULL,
    `filePath`       VARCHAR(500)         NULL,
    `lineNumber`     INT UNSIGNED         NULL,
    `stackTrace`     MEDIUMTEXT           NULL,
    `contextJson`    JSON                 NULL,
    `userId`         BIGINT UNSIGNED      NULL,
    `ip`             VARCHAR(45)          NULL,
    `userAgent`      VARCHAR(255)         NULL,
    `httpMethod`     VARCHAR(10)          NULL,
    `url`            VARCHAR(500)         NULL,
    `statusCode`     SMALLINT UNSIGNED    NULL,
    `createdAt`      DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_pfm_error_logs_created` (`createdAt`),
    KEY `idx_pfm_error_logs_level` (`level`, `createdAt`),
    KEY `idx_pfm_error_logs_request` (`requestId`, `id`),
    KEY `idx_pfm_error_logs_user` (`userId`, `createdAt`),
    KEY `idx_pfm_error_logs_exception` (`exceptionClass`, `createdAt`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `pfm_settings` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `configKey`   VARCHAR(120)    NOT NULL,
    `configValue` TEXT                 NULL,
    `valueType`   VARCHAR(16)     NOT NULL DEFAULT 'string',
    `description` VARCHAR(255)         NULL,
    `createdAt`   DATETIME(3)     NOT NULL,
    `updatedAt`   DATETIME(3)     NOT NULL,
    `createdBy`   BIGINT UNSIGNED      NULL,
    `updatedBy`   BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pfm_settings_key` (`configKey`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `pfm_outbox_events` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `eventName`   VARCHAR(120)    NOT NULL,
    `aggregateId` BIGINT UNSIGNED      NULL,
    `payloadJson` JSON            NOT NULL,
    `status`      VARCHAR(16)     NOT NULL DEFAULT 'cho_phat',
    `attempts`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `lastError`   VARCHAR(1000)        NULL,
    `publishedAt` DATETIME(3)          NULL,
    `createdAt`   DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_pfm_outbox_pending` (`status`, `id`),
    KEY `idx_pfm_outbox_event` (`eventName`, `aggregateId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `flt_generators` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`            VARCHAR(30)     NOT NULL,
    `name`            VARCHAR(120)    NOT NULL,
    `serialNumber`    VARCHAR(120)         NULL,
    `manufacturer`    VARCHAR(120)         NULL,
    `model`           VARCHAR(120)         NULL,
    `manufactureYear` SMALLINT UNSIGNED    NULL,
    `capacityKva`     INT             NOT NULL,
    `fuelType`        VARCHAR(32)     NOT NULL,
    `status`          VARCHAR(32)     NOT NULL DEFAULT 'san_sang',
    `hourMeter`       DECIMAL(10,1)   NOT NULL DEFAULT 0.0,
    `warehouseCode`   VARCHAR(32)          NULL,
    `currentLocation` VARCHAR(120)         NULL,
    `latitude`        DECIMAL(9,6)         NULL,
    `longitude`       DECIMAL(9,6)         NULL,
    `note`            VARCHAR(255)         NULL,
    `extraContent`    JSON                 NULL,
    `createdAt`       DATETIME(3)     NOT NULL,
    `updatedAt`       DATETIME(3)     NOT NULL,
    `createdBy`       BIGINT UNSIGNED      NULL,
    `updatedBy`       BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_flt_generators_code` (`code`),
    UNIQUE KEY `uq_flt_generators_serial` (`serialNumber`),
    KEY `idx_flt_generators_status` (`status`),
    KEY `idx_flt_generators_capacity` (`capacityKva`),
    KEY `idx_flt_generators_fuel_status` (`fuelType`, `status`),
    KEY `idx_flt_generators_warehouse` (`warehouseCode`, `status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `flt_hour_meter_readings` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `generatorId`   BIGINT UNSIGNED NOT NULL,
    `hourMeter`     DECIMAL(10,1)   NOT NULL,
    `previousValue` DECIMAL(10,1)        NULL,
    `source`        VARCHAR(32)     NOT NULL,
    `contextType`   VARCHAR(64)          NULL,
    `contextId`     BIGINT UNSIGNED      NULL,
    `photoId`       BIGINT UNSIGNED      NULL,
    `isDecrease`    TINYINT(1)      NOT NULL DEFAULT 0,
    `decreaseReason` VARCHAR(255)        NULL,
    `approvedBy`    BIGINT UNSIGNED      NULL,
    `recordedAt`    DATETIME(3)     NOT NULL,
    `createdAt`     DATETIME(3)     NOT NULL,
    `createdBy`     BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    KEY `idx_flt_hour_readings_generator` (`generatorId`, `recordedAt`),
    KEY `idx_flt_hour_readings_context` (`contextType`, `contextId`),
    KEY `idx_flt_hour_readings_decrease` (`isDecrease`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `crm_customers` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`            VARCHAR(30)     NOT NULL,
    `name`            VARCHAR(200)    NOT NULL,
    `customerType`    VARCHAR(16)     NOT NULL DEFAULT 'doanh_nghiep',
    `taxCode`         VARCHAR(20)          NULL,
    `idNumber`        VARCHAR(20)          NULL,
    `address`         VARCHAR(255)         NULL,
    `phone`           VARCHAR(20)          NULL,
    `email`           VARCHAR(190)         NULL,
    `bankAccount`     VARCHAR(40)          NULL,
    `salesOwnerId`    BIGINT UNSIGNED      NULL,
    `creditWarning`   TINYINT(1)      NOT NULL DEFAULT 0,
    `status`          VARCHAR(32)     NOT NULL DEFAULT 'hoat_dong',
    `note`            TEXT                 NULL,
    `createdAt`       DATETIME(3)     NOT NULL,
    `updatedAt`       DATETIME(3)     NOT NULL,
    `createdBy`       BIGINT UNSIGNED      NULL,
    `updatedBy`       BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_crm_customers_code` (`code`),
    KEY `idx_crm_customers_name` (`name`),
    KEY `idx_crm_customers_tax` (`taxCode`),
    KEY `idx_crm_customers_owner` (`salesOwnerId`, `status`),
    KEY `idx_crm_customers_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `crm_sites` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customerId`         BIGINT UNSIGNED NOT NULL,
    `code`               VARCHAR(30)          NULL,
    `name`               VARCHAR(200)    NOT NULL,
    `address`            VARCHAR(255)         NULL,
    `latitude`           DECIMAL(9,6)         NULL,
    `longitude`          DECIMAL(9,6)         NULL,
    `contactName`        VARCHAR(120)         NULL,
    `contactPhone`       VARCHAR(20)          NULL,
    `installConditions`  TEXT                 NULL,
    `accessNote`         VARCHAR(500)         NULL,
    `status`             VARCHAR(32)     NOT NULL DEFAULT 'hoat_dong',
    `createdAt`          DATETIME(3)     NOT NULL,
    `updatedAt`          DATETIME(3)     NOT NULL,
    `createdBy`          BIGINT UNSIGNED      NULL,
    `updatedBy`          BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_crm_sites_code` (`customerId`, `code`),
    KEY `idx_crm_sites_customer` (`customerId`, `status`),
    KEY `idx_crm_sites_name` (`name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `crm_contacts` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customerId` BIGINT UNSIGNED NOT NULL,
    `siteId`     BIGINT UNSIGNED      NULL,
    `fullName`   VARCHAR(120)    NOT NULL,
    `position`   VARCHAR(120)         NULL,
    `phone`      VARCHAR(20)          NULL,
    `email`      VARCHAR(190)         NULL,
    `isPrimary`  TINYINT(1)      NOT NULL DEFAULT 0,
    `note`       VARCHAR(255)         NULL,
    `createdAt`  DATETIME(3)     NOT NULL,
    `updatedAt`  DATETIME(3)     NOT NULL,
    `createdBy`  BIGINT UNSIGNED      NULL,
    `updatedBy`  BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    KEY `idx_crm_contacts_customer` (`customerId`, `isPrimary`),
    KEY `idx_crm_contacts_site` (`siteId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `sal_price_lists` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(30)     NOT NULL,
    `name`       VARCHAR(200)    NOT NULL,
    `validFrom`  DATE            NOT NULL,
    `validTo`    DATE                 NULL,
    `isActive`   TINYINT(1)      NOT NULL DEFAULT 1,
    `note`       VARCHAR(255)         NULL,
    `createdAt`  DATETIME(3)     NOT NULL,
    `updatedAt`  DATETIME(3)     NOT NULL,
    `createdBy`  BIGINT UNSIGNED      NULL,
    `updatedBy`  BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sal_price_lists_code` (`code`),
    KEY `idx_sal_price_lists_active` (`isActive`, `validFrom`, `validTo`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `sal_price_list_items` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `priceListId`    BIGINT UNSIGNED NOT NULL,
    `capacityFrom`   INT             NOT NULL,
    `capacityTo`     INT             NOT NULL,
    `durationTier`   VARCHAR(16)     NOT NULL,
    `minDays`        INT UNSIGNED    NOT NULL DEFAULT 1,
    `unitPrice`      BIGINT          NOT NULL,
    `dailyRate`      BIGINT               NULL,
    `deliveryFee`    BIGINT          NOT NULL DEFAULT 0,
    `installFee`     BIGINT          NOT NULL DEFAULT 0,
    `depositAmount`  BIGINT          NOT NULL DEFAULT 0,
    `createdAt`      DATETIME(3)     NOT NULL,
    `updatedAt`      DATETIME(3)     NOT NULL,
    `createdBy`      BIGINT UNSIGNED      NULL,
    `updatedBy`      BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sal_price_items` (`priceListId`, `capacityFrom`, `capacityTo`, `durationTier`, `minDays`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `sal_quotes` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `quoteNo`        VARCHAR(30)     NOT NULL,
    `customerId`     BIGINT UNSIGNED NOT NULL,
    `siteId`         BIGINT UNSIGNED      NULL,
    `priceListId`    BIGINT UNSIGNED      NULL,
    `rentFrom`       DATE            NOT NULL,
    `rentTo`         DATE            NOT NULL,
    `status`         VARCHAR(32)     NOT NULL DEFAULT 'nhap',
    `validUntil`     DATE                 NULL,
    `rentAmount`     BIGINT          NOT NULL DEFAULT 0,
    `deliveryFee`    BIGINT          NOT NULL DEFAULT 0,
    `installFee`     BIGINT          NOT NULL DEFAULT 0,
    `otherFee`       BIGINT          NOT NULL DEFAULT 0,
    `discountAmount` BIGINT          NOT NULL DEFAULT 0,
    `vatRate`        INT             NOT NULL DEFAULT 0,
    `vatAmount`      BIGINT          NOT NULL DEFAULT 0,
    `totalAmount`    BIGINT          NOT NULL DEFAULT 0,
    `depositAmount`  BIGINT          NOT NULL DEFAULT 0,
    `submittedAt`    DATETIME(3)          NULL,
    `approvedBy`     BIGINT UNSIGNED      NULL,
    `approvedAt`     DATETIME(3)          NULL,
    `rejectReason`   VARCHAR(500)         NULL,
    `terms`          TEXT                 NULL,
    `createdAt`      DATETIME(3)     NOT NULL,
    `updatedAt`      DATETIME(3)     NOT NULL,
    `createdBy`      BIGINT UNSIGNED      NULL,
    `updatedBy`      BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sal_quotes_no` (`quoteNo`),
    KEY `idx_sal_quotes_customer` (`customerId`, `status`),
    KEY `idx_sal_quotes_status` (`status`, `id`),
    KEY `idx_sal_quotes_period` (`rentFrom`, `rentTo`),
    KEY `idx_sal_quotes_valid` (`status`, `validUntil`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `sal_quote_lines` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `quoteId`      BIGINT UNSIGNED NOT NULL,
    `generatorId`  BIGINT UNSIGNED      NULL,
    `capacityKva`  INT             NOT NULL,
    `quantity`     INT UNSIGNED    NOT NULL DEFAULT 1,
    `rentFrom`     DATE            NOT NULL,
    `rentTo`       DATE            NOT NULL,
    `durationTier` VARCHAR(16)     NOT NULL,
    `durationQty`  DECIMAL(10,2)   NOT NULL,
    `unitPrice`    BIGINT          NOT NULL,
    `oddDays`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `oddDayRate`   BIGINT          NOT NULL DEFAULT 0,
    `lineAmount`   BIGINT          NOT NULL DEFAULT 0,
    `suggestReason` VARCHAR(255)        NULL,
    `note`         VARCHAR(255)         NULL,
    `createdAt`    DATETIME(3)     NOT NULL,
    `updatedAt`    DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_sal_quote_lines_quote` (`quoteId`),
    KEY `idx_sal_quote_lines_generator` (`generatorId`, `rentFrom`, `rentTo`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `sal_contracts` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contractNo`     VARCHAR(30)     NOT NULL,
    `quoteId`        BIGINT UNSIGNED      NULL,
    `customerId`     BIGINT UNSIGNED NOT NULL,
    `siteId`         BIGINT UNSIGNED      NULL,
    `signedDate`     DATE                 NULL,
    `effectiveFrom`  DATE            NOT NULL,
    `effectiveTo`    DATE            NOT NULL,
    `status`         VARCHAR(32)     NOT NULL DEFAULT 'nhap',
    `totalAmount`    BIGINT          NOT NULL DEFAULT 0,
    `depositAmount`  BIGINT          NOT NULL DEFAULT 0,
    `paymentTermDays` INT UNSIGNED   NOT NULL DEFAULT 0,
    `billingCycle`   VARCHAR(16)     NOT NULL DEFAULT 'thang',
    `creditOverrideBy` BIGINT UNSIGNED    NULL,
    `creditOverrideReason` VARCHAR(500) NULL,
    `terms`          TEXT                 NULL,
    `cancelledAt`    DATETIME(3)          NULL,
    `cancelledBy`    BIGINT UNSIGNED      NULL,
    `cancelReason`   VARCHAR(500)         NULL,
    `createdAt`      DATETIME(3)     NOT NULL,
    `updatedAt`      DATETIME(3)     NOT NULL,
    `createdBy`      BIGINT UNSIGNED      NULL,
    `updatedBy`      BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sal_contracts_no` (`contractNo`),
    KEY `idx_sal_contracts_customer` (`customerId`, `status`),
    KEY `idx_sal_contracts_status` (`status`, `id`),
    KEY `idx_sal_contracts_period` (`effectiveFrom`, `effectiveTo`),
    KEY `idx_sal_contracts_quote` (`quoteId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `rnt_rental_orders` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `orderNo`          VARCHAR(30)     NOT NULL,
    `contractId`       BIGINT UNSIGNED      NULL,
    `customerId`       BIGINT UNSIGNED NOT NULL,
    `siteId`           BIGINT UNSIGNED      NULL,
    `generatorId`      BIGINT UNSIGNED NOT NULL,
    `startDate`        DATE            NOT NULL,
    `expectedEndDate`  DATE            NOT NULL,
    `actualEndDate`    DATE                 NULL,
    `status`           VARCHAR(32)     NOT NULL DEFAULT 'moi_tao',
    `startHourMeter`   DECIMAL(10,1)        NULL,
    `endHourMeter`     DECIMAL(10,1)        NULL,
    `handoverAt`       DATETIME(3)          NULL,
    `recoveredAt`      DATETIME(3)          NULL,
    `unitPrice`        BIGINT          NOT NULL DEFAULT 0,
    `durationTier`     VARCHAR(16)     NOT NULL DEFAULT 'thang',
    `withOperator`     TINYINT(1)      NOT NULL DEFAULT 0,
    `extendedTimes`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `settledAt`        DATETIME(3)          NULL,
    `cancelledAt`      DATETIME(3)          NULL,
    `cancelledBy`      BIGINT UNSIGNED      NULL,
    `cancelReason`     VARCHAR(500)         NULL,
    `note`             VARCHAR(500)         NULL,
    `createdAt`        DATETIME(3)     NOT NULL,
    `updatedAt`        DATETIME(3)     NOT NULL,
    `createdBy`        BIGINT UNSIGNED      NULL,
    `updatedBy`        BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rnt_orders_no` (`orderNo`),
    KEY `idx_rnt_orders_generator` (`generatorId`, `status`),
    KEY `idx_rnt_orders_customer` (`customerId`, `status`),
    KEY `idx_rnt_orders_contract` (`contractId`),
    KEY `idx_rnt_orders_status` (`status`, `id`),
    KEY `idx_rnt_orders_period` (`startDate`, `expectedEndDate`),
    KEY `idx_rnt_orders_overdue` (`status`, `expectedEndDate`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `rnt_generator_occupancy` (
    `generatorId`   BIGINT UNSIGNED NOT NULL,
    `occupiedDate`  DATE            NOT NULL,
    `rentalOrderId` BIGINT UNSIGNED NOT NULL,
    `holdType`      VARCHAR(16)     NOT NULL,
    `sourceType`    VARCHAR(32)          NULL,
    `sourceId`      BIGINT UNSIGNED      NULL,
    `expiresAt`     DATETIME(3)          NULL,
    `createdAt`     DATETIME(3)     NOT NULL,
    `createdBy`     BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`generatorId`, `occupiedDate`),
    KEY `idx_rnt_occupancy_order` (`rentalOrderId`),
    KEY `idx_rnt_occupancy_date` (`occupiedDate`),
    KEY `idx_rnt_occupancy_hold` (`holdType`, `expiresAt`),
    KEY `idx_rnt_occupancy_source` (`sourceType`, `sourceId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `rnt_rental_logs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rentalOrderId` BIGINT UNSIGNED NOT NULL,
    `generatorId`   BIGINT UNSIGNED NOT NULL,
    `logType`       VARCHAR(32)     NOT NULL,
    `fuelLiters`    DECIMAL(10,2)        NULL,
    `incidentLevel` VARCHAR(16)          NULL,
    `content`       VARCHAR(1000)        NULL,
    `recordedAt`    DATETIME(3)     NOT NULL,
    `createdAt`     DATETIME(3)     NOT NULL,
    `createdBy`     BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    KEY `idx_rnt_logs_order` (`rentalOrderId`, `recordedAt`),
    KEY `idx_rnt_logs_generator` (`generatorId`, `recordedAt`),
    KEY `idx_rnt_logs_type` (`logType`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `dsp_vehicles` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`         VARCHAR(30)     NOT NULL,
    `plateNumber`  VARCHAR(20)     NOT NULL,
    `vehicleType`  VARCHAR(32)     NOT NULL,
    `capacityKg`   INT UNSIGNED         NULL,
    `driverId`     BIGINT UNSIGNED      NULL,
    `status`       VARCHAR(32)     NOT NULL DEFAULT 'san_sang',
    `note`         VARCHAR(255)         NULL,
    `createdAt`    DATETIME(3)     NOT NULL,
    `updatedAt`    DATETIME(3)     NOT NULL,
    `createdBy`    BIGINT UNSIGNED      NULL,
    `updatedBy`    BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dsp_vehicles_code` (`code`),
    UNIQUE KEY `uq_dsp_vehicles_plate` (`plateNumber`),
    KEY `idx_dsp_vehicles_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `dsp_jobs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jobNo`          VARCHAR(30)     NOT NULL,
    `jobType`        VARCHAR(16)     NOT NULL,
    `rentalOrderId`  BIGINT UNSIGNED NOT NULL,
    `generatorId`    BIGINT UNSIGNED NOT NULL,
    `newGeneratorId` BIGINT UNSIGNED      NULL,
    `siteId`         BIGINT UNSIGNED      NULL,
    `vehicleId`      BIGINT UNSIGNED      NULL,
    `scheduledAt`    DATETIME(3)          NULL,
    `departedAt`     DATETIME(3)          NULL,
    `arrivedAt`      DATETIME(3)          NULL,
    `completedAt`    DATETIME(3)          NULL,
    `checklistJson`        JSON        NULL,
    `checklistCompletedAt` DATETIME(3) NULL,
    `status`         VARCHAR(32)     NOT NULL DEFAULT 'moi_tao',
    `failReason`     VARCHAR(500)         NULL,
    `feeBearer`      VARCHAR(16)          NULL,
    `priority`       VARCHAR(16)     NOT NULL DEFAULT 'binh_thuong',
    `note`           VARCHAR(500)         NULL,
    `createdAt`      DATETIME(3)     NOT NULL,
    `updatedAt`      DATETIME(3)     NOT NULL,
    `createdBy`      BIGINT UNSIGNED      NULL,
    `updatedBy`      BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dsp_jobs_no` (`jobNo`),
    KEY `idx_dsp_jobs_schedule` (`scheduledAt`, `status`),
    KEY `idx_dsp_jobs_order` (`rentalOrderId`),
    KEY `idx_dsp_jobs_generator` (`generatorId`),
    KEY `idx_dsp_jobs_vehicle` (`vehicleId`, `scheduledAt`),
    KEY `idx_dsp_jobs_status` (`status`, `id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `dsp_assignments` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jobId`      BIGINT UNSIGNED NOT NULL,
    `userId`     BIGINT UNSIGNED NOT NULL,
    `roleInJob`  VARCHAR(16)     NOT NULL DEFAULT 'ky_thuat',
    `isLead`     TINYINT(1)      NOT NULL DEFAULT 0,
    `acceptedAt` DATETIME(3)          NULL,
    `createdAt`  DATETIME(3)     NOT NULL,
    `createdBy`  BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dsp_assignments` (`jobId`, `userId`, `roleInJob`),
    KEY `idx_dsp_assignments_job` (`jobId`),
    KEY `idx_dsp_assignments_user` (`userId`, `jobId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `dsp_handover_records` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recordNo`        VARCHAR(30)     NOT NULL,
    `jobId`           BIGINT UNSIGNED NOT NULL,
    `rentalOrderId`   BIGINT UNSIGNED NOT NULL,
    `generatorId`     BIGINT UNSIGNED NOT NULL,
    `recordType`      VARCHAR(16)     NOT NULL,
    `hourMeter`       DECIMAL(10,1)   NOT NULL,
    `fuelLevel`       VARCHAR(16)          NULL,
    `conditionNote`   VARCHAR(1000)        NULL,
    `customerSignerName` VARCHAR(120)      NULL,
    `signatureFileId` BIGINT UNSIGNED      NULL,
    `idempotencyKey`  VARCHAR(120)         NULL,
    `syncStatus`      VARCHAR(16)     NOT NULL DEFAULT 'da_dong_bo',
    `recordedAt`      DATETIME(3)     NOT NULL,
    `createdAt`       DATETIME(3)     NOT NULL,
    `createdBy`       BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dsp_handover_no` (`recordNo`),
    UNIQUE KEY `uq_dsp_handover_idempotency` (`idempotencyKey`),
    KEY `idx_dsp_handover_job` (`jobId`),
    KEY `idx_dsp_handover_order` (`rentalOrderId`, `recordType`),
    KEY `idx_dsp_handover_generator` (`generatorId`, `recordedAt`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `bil_credit_limits` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customerId`    BIGINT UNSIGNED NOT NULL,
    `creditLimit`   BIGINT          NOT NULL DEFAULT 0,
    `currentDebt`   BIGINT          NOT NULL DEFAULT 0,
    `overdueAmount` BIGINT          NOT NULL DEFAULT 0,
    `isBlocked`     TINYINT(1)      NOT NULL DEFAULT 0,
    `lastCheckedAt` DATETIME(3)          NULL,
    `createdAt`     DATETIME(3)     NOT NULL,
    `updatedAt`     DATETIME(3)     NOT NULL,
    `createdBy`     BIGINT UNSIGNED      NULL,
    `updatedBy`     BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bil_credit_customer` (`customerId`),
    KEY `idx_bil_credit_blocked` (`isBlocked`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `bil_invoices` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoiceNo`     VARCHAR(30)     NOT NULL,
    `customerId`    BIGINT UNSIGNED NOT NULL,
    `contractId`    BIGINT UNSIGNED      NULL,
    `rentalOrderId` BIGINT UNSIGNED      NULL,
    `periodFrom`    DATE            NOT NULL,
    `periodTo`      DATE            NOT NULL,
    `issueDate`     DATE                 NULL,
    `dueDate`       DATE                 NULL,
    `status`        VARCHAR(32)     NOT NULL DEFAULT 'nhap',
    `rentAmount`    BIGINT          NOT NULL DEFAULT 0,
    `surchargeAmount` BIGINT        NOT NULL DEFAULT 0,
    `discountAmount` BIGINT         NOT NULL DEFAULT 0,
    `vatRate`       INT             NOT NULL DEFAULT 0,
    `vatAmount`     BIGINT          NOT NULL DEFAULT 0,
    `totalAmount`   BIGINT          NOT NULL DEFAULT 0,
    `paidAmount`    BIGINT          NOT NULL DEFAULT 0,
    `remainAmount`  BIGINT          NOT NULL DEFAULT 0,
    `voidedAt`      DATETIME(3)          NULL,
    `voidedBy`      BIGINT UNSIGNED      NULL,
    `voidReason`    VARCHAR(500)         NULL,
    `note`          VARCHAR(500)         NULL,
    `createdAt`     DATETIME(3)     NOT NULL,
    `updatedAt`     DATETIME(3)     NOT NULL,
    `createdBy`     BIGINT UNSIGNED      NULL,
    `updatedBy`     BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bil_invoices_no` (`invoiceNo`),
    KEY `idx_bil_invoices_customer` (`customerId`, `status`),
    KEY `idx_bil_invoices_contract` (`contractId`),
    KEY `idx_bil_invoices_order` (`rentalOrderId`),
    KEY `idx_bil_invoices_status` (`status`, `id`),
    KEY `idx_bil_invoices_due` (`status`, `dueDate`),
    KEY `idx_bil_invoices_period` (`periodFrom`, `periodTo`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `bil_invoice_lines` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoiceId`   BIGINT UNSIGNED NOT NULL,
    `lineType`    VARCHAR(32)     NOT NULL,
    `generatorId` BIGINT UNSIGNED      NULL,
    `description` VARCHAR(255)    NOT NULL,
    `quantity`    DECIMAL(10,2)   NOT NULL DEFAULT 1.00,
    `unit`        VARCHAR(16)          NULL,
    `unitPrice`   BIGINT          NOT NULL DEFAULT 0,
    `lineAmount`  BIGINT          NOT NULL DEFAULT 0,
    `isVatable`   TINYINT(1)      NOT NULL DEFAULT 1,
    `createdAt`   DATETIME(3)     NOT NULL,
    `updatedAt`   DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_bil_invoice_lines_invoice` (`invoiceId`),
    KEY `idx_bil_invoice_lines_type` (`lineType`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `bil_payments` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `paymentNo`      VARCHAR(30)     NOT NULL,
    `invoiceId`      BIGINT UNSIGNED      NULL,
    `customerId`     BIGINT UNSIGNED NOT NULL,
    `amount`         BIGINT          NOT NULL,
    `paymentDate`    DATE            NOT NULL,
    `method`         VARCHAR(32)     NOT NULL,
    `referenceNo`    VARCHAR(60)          NULL,
    `attachmentId`   BIGINT UNSIGNED      NULL,
    `status`         VARCHAR(16)     NOT NULL DEFAULT 'da_ghi_nhan',
    `cancelledAt`    DATETIME(3)          NULL,
    `cancelledBy`    BIGINT UNSIGNED      NULL,
    `cancelReason`   VARCHAR(500)         NULL,
    `note`           VARCHAR(500)         NULL,
    `createdAt`      DATETIME(3)     NOT NULL,
    `updatedAt`      DATETIME(3)     NOT NULL,
    `createdBy`      BIGINT UNSIGNED      NULL,
    `updatedBy`      BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bil_payments_no` (`paymentNo`),
    KEY `idx_bil_payments_invoice` (`invoiceId`, `status`),
    KEY `idx_bil_payments_customer` (`customerId`, `paymentDate`),
    KEY `idx_bil_payments_date` (`paymentDate`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `bil_deposits` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `depositNo`      VARCHAR(30)     NOT NULL,
    `customerId`     BIGINT UNSIGNED NOT NULL,
    `contractId`     BIGINT UNSIGNED      NULL,
    `rentalOrderId`  BIGINT UNSIGNED      NULL,
    `amount`         BIGINT          NOT NULL,
    `receivedDate`   DATE            NOT NULL,
    `deductedAmount` BIGINT          NOT NULL DEFAULT 0,
    `deductReason`   VARCHAR(500)         NULL,
    `refundedAmount` BIGINT          NOT NULL DEFAULT 0,
    `refundedDate`   DATE                 NULL,
    `status`         VARCHAR(32)     NOT NULL DEFAULT 'dang_giu',
    `note`           VARCHAR(500)         NULL,
    `createdAt`      DATETIME(3)     NOT NULL,
    `updatedAt`      DATETIME(3)     NOT NULL,
    `createdBy`      BIGINT UNSIGNED      NULL,
    `updatedBy`      BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bil_deposits_no` (`depositNo`),
    KEY `idx_bil_deposits_customer` (`customerId`, `status`),
    KEY `idx_bil_deposits_order` (`rentalOrderId`),
    KEY `idx_bil_deposits_contract` (`contractId`),
    KEY `idx_bil_deposits_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `mnt_schedules` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `generatorId`     BIGINT UNSIGNED NOT NULL,
    `scheduleType`    VARCHAR(16)     NOT NULL,
    `intervalHours`   DECIMAL(10,1)        NULL,
    `intervalDays`    INT UNSIGNED         NULL,
    `lastServiceHour` DECIMAL(10,1)        NULL,
    `lastServiceDate` DATE                 NULL,
    `nextDueHour`     DECIMAL(10,1)        NULL,
    `nextDueDate`     DATE                 NULL,
    `isActive`        TINYINT(1)      NOT NULL DEFAULT 1,
    `createdAt`       DATETIME(3)     NOT NULL,
    `updatedAt`       DATETIME(3)     NOT NULL,
    `createdBy`       BIGINT UNSIGNED      NULL,
    `updatedBy`       BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mnt_schedules` (`generatorId`, `scheduleType`),
    KEY `idx_mnt_schedules_generator` (`generatorId`, `isActive`),
    KEY `idx_mnt_schedules_due_hour` (`isActive`, `nextDueHour`),
    KEY `idx_mnt_schedules_due_date` (`isActive`, `nextDueDate`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `mnt_jobs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jobNo`         VARCHAR(30)     NOT NULL,
    `generatorId`   BIGINT UNSIGNED NOT NULL,
    `scheduleId`    BIGINT UNSIGNED      NULL,
    `jobType`       VARCHAR(16)     NOT NULL,
    `priority`      VARCHAR(16)     NOT NULL DEFAULT 'binh_thuong',
    `status`        VARCHAR(32)     NOT NULL DEFAULT 'cho_lich',
    `triggerReason` VARCHAR(255)         NULL,
    `triggerHourMeter` DECIMAL(10,1)     NULL,
    `idempotencyKey` VARCHAR(120)        NULL,
    `scheduledDate` DATE                 NULL,
    `startedAt`     DATETIME(3)          NULL,
    `completedAt`   DATETIME(3)          NULL,
    `assigneeId`    BIGINT UNSIGNED      NULL,
    `laborCost`     BIGINT          NOT NULL DEFAULT 0,
    `partsCost`     BIGINT          NOT NULL DEFAULT 0,
    `totalCost`     BIGINT          NOT NULL DEFAULT 0,
    `findings`      TEXT                 NULL,
    `cancelledAt`   DATETIME(3)          NULL,
    `cancelReason`  VARCHAR(500)         NULL,
    `createdAt`     DATETIME(3)     NOT NULL,
    `updatedAt`     DATETIME(3)     NOT NULL,
    `createdBy`     BIGINT UNSIGNED      NULL,
    `updatedBy`     BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mnt_jobs_no` (`jobNo`),
    KEY `idx_mnt_jobs_generator` (`generatorId`, `status`),
    KEY `idx_mnt_jobs_status` (`status`, `scheduledDate`),
    KEY `idx_mnt_jobs_assignee` (`assigneeId`, `status`),
    UNIQUE KEY `uq_mnt_jobs_idempotency` (`idempotencyKey`),
    KEY `idx_mnt_jobs_schedule` (`scheduleId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `mnt_parts_used` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jobId`      BIGINT UNSIGNED NOT NULL,
    `partCode`   VARCHAR(60)          NULL,
    `partName`   VARCHAR(200)    NOT NULL,
    `quantity`   DECIMAL(10,2)   NOT NULL DEFAULT 1.00,
    `unit`       VARCHAR(16)          NULL,
    `unitPrice`  BIGINT          NOT NULL DEFAULT 0,
    `lineAmount` BIGINT          NOT NULL DEFAULT 0,
    `supplier`   VARCHAR(200)         NULL,
    `createdAt`  DATETIME(3)     NOT NULL,
    `updatedAt`  DATETIME(3)     NOT NULL,
    `createdBy`  BIGINT UNSIGNED      NULL,

    PRIMARY KEY (`id`),
    KEY `idx_mnt_parts_job` (`jobId`),
    KEY `idx_mnt_parts_code` (`partCode`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `rpt_fleet_utilization_daily` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reportDate`       DATE            NOT NULL,
    `warehouseCode`    VARCHAR(32)          NULL,
    `totalGenerators`  INT UNSIGNED    NOT NULL DEFAULT 0,
    `activeGenerators` INT UNSIGNED    NOT NULL DEFAULT 0,
    `rentedCount`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `availableCount`   INT UNSIGNED    NOT NULL DEFAULT 0,
    `heldCount`        INT UNSIGNED    NOT NULL DEFAULT 0,
    `transitCount`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `maintenanceCount` INT UNSIGNED    NOT NULL DEFAULT 0,
    `repairCount`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `retiredCount`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `utilizationRate`  DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `computedAt`       DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rpt_util_date` (`reportDate`, `warehouseCode`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `rpt_revenue_monthly` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `periodYear`      SMALLINT UNSIGNED NOT NULL,
    `periodMonth`     TINYINT UNSIGNED  NOT NULL,
    `customerId`      BIGINT UNSIGNED      NULL,
    `invoicedAmount`  BIGINT          NOT NULL DEFAULT 0,
    `collectedAmount` BIGINT          NOT NULL DEFAULT 0,
    `outstandingAmount` BIGINT        NOT NULL DEFAULT 0,
    `overdueAmount`   BIGINT        NOT NULL DEFAULT 0,
    `orderCount`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `computedAt`      DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rpt_revenue_period` (`periodYear`, `periodMonth`, `customerId`),
    KEY `idx_rpt_revenue_customer` (`customerId`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `rpt_receivables_snapshot` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `snapshotDate`   DATE            NOT NULL,
    `customerId`     BIGINT UNSIGNED NOT NULL,
    `bucket0To30`    BIGINT          NOT NULL DEFAULT 0,
    `bucket31To60`   BIGINT          NOT NULL DEFAULT 0,
    `bucket61To90`   BIGINT          NOT NULL DEFAULT 0,
    `bucketOver90`   BIGINT          NOT NULL DEFAULT 0,
    `totalDebt`      BIGINT          NOT NULL DEFAULT 0,
    `dsoDays`        DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
    `computedAt`     DATETIME(3)     NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rpt_receivables_date` (`snapshotDate`, `customerId`),
    KEY `idx_rpt_receivables_customer` (`customerId`, `snapshotDate`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO usr_users (
    username,
    fullName,
    email,
    phone,
    passwordHash,
    role,
    status,
    lastLoginAt,
    failedLoginCount,
    lockedUntil,
    note,
    createdAt,
    createdBy,
    updatedAt,
    updatedBy
) VALUES (
    'admin',
    'Admin Test',
    'admin.test@dynamo.local',
    '0900000000',
    '$2y$12$6ZzKGEjKGlyg8VKMy8mLxezBorrYohd.0l1vd.hdikJlB2gkjtL2e',
    'admin',
    'hoat_dong',
    NULL,
    0,
    NULL,
    'User test local.',
    UTC_TIMESTAMP(3),
    NULL,
    UTC_TIMESTAMP(3),
    NULL
);
