SET NAMES utf8mb4;

START TRANSACTION;

SET @now = UTC_TIMESTAMP(3);
SET @today = UTC_DATE();
SET @passwordHash = '$2y$12$6ZzKGEjKGlyg8VKMy8mLxezBorrYohd.0l1vd.hdikJlB2gkjtL2e';

INSERT INTO usr_users (
    username, fullName, email, phone, passwordHash, role, status,
    note, createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('admin', 'Admin Test', 'admin.test@dynamo.local', '0900000000', @passwordHash, 'admin', 'hoat_dong', 'Demo password: Admin@123456', @now, @now, NULL, NULL),
    ('manager.demo', 'Quan ly Demo', 'manager.demo@dynamo.local', '0901000001', @passwordHash, 'quan_ly', 'hoat_dong', 'Demo password: Admin@123456', @now, @now, 1, 1),
    ('sales.demo', 'Kinh doanh Demo', 'sales.demo@dynamo.local', '0901000002', @passwordHash, 'kinh_doanh', 'hoat_dong', 'Demo password: Admin@123456', @now, @now, 1, 1),
    ('dispatch.demo', 'Dieu phoi Demo', 'dispatch.demo@dynamo.local', '0901000003', @passwordHash, 'dieu_phoi', 'hoat_dong', 'Demo password: Admin@123456', @now, @now, 1, 1),
    ('tech.demo', 'Ky thuat Demo', 'tech.demo@dynamo.local', '0901000004', @passwordHash, 'ky_thuat_vien', 'hoat_dong', 'Demo password: Admin@123456', @now, @now, 1, 1),
    ('accounting.demo', 'Ke toan Demo', 'accounting.demo@dynamo.local', '0901000005', @passwordHash, 'ke_toan', 'hoat_dong', 'Demo password: Admin@123456', @now, @now, 1, 1),
    ('driver.demo', 'Tai xe Demo', 'driver.demo@dynamo.local', '0901000006', @passwordHash, 'dieu_phoi', 'hoat_dong', 'Demo password: Admin@123456', @now, @now, 1, 1)
ON DUPLICATE KEY UPDATE
    fullName = VALUES(fullName),
    email = VALUES(email),
    phone = VALUES(phone),
    passwordHash = VALUES(passwordHash),
    role = VALUES(role),
    status = VALUES(status),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = 1;

SET @adminId = (SELECT id FROM usr_users WHERE username = 'admin');
SET @managerId = (SELECT id FROM usr_users WHERE username = 'manager.demo');
SET @salesId = (SELECT id FROM usr_users WHERE username = 'sales.demo');
SET @dispatcherId = (SELECT id FROM usr_users WHERE username = 'dispatch.demo');
SET @techId = (SELECT id FROM usr_users WHERE username = 'tech.demo');
SET @accountantId = (SELECT id FROM usr_users WHERE username = 'accounting.demo');
SET @driverId = (SELECT id FROM usr_users WHERE username = 'driver.demo');

INSERT INTO crm_customers (
    code, name, customerType, taxCode, address, phone, email, bankAccount,
    salesOwnerId, creditWarning, status, note, createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('CUS-DEMO-001', 'Cong ty Xay dung Minh An', 'doanh_nghiep', '0312345678', '12 Nguyen Huu Canh, Binh Thanh, TP HCM', '02873000001', 'ops@minhan.example', '9704000012345678', @salesId, 0, 'hoat_dong', 'Khach demo co hop dong dang hieu luc.', @now, @now, @salesId, @salesId),
    ('CUS-DEMO-002', 'Nha may Bao Tin', 'doanh_nghiep', '3709876543', 'KCN VSIP II, Binh Duong', '02743000002', 'purchase@baotin.example', '9704000098765432', @salesId, 1, 'hoat_dong', 'Khach demo co canh bao cong no.', @now, @now, @salesId, @salesId),
    ('CUS-DEMO-003', 'Cua hang An Phu', 'doanh_nghiep', '0311112222', '88 Quoc lo 13, Thu Duc, TP HCM', '02873000003', 'owner@anphu.example', NULL, @salesId, 0, 'hoat_dong', 'Khach demo moi bao gia.', @now, @now, @salesId, @salesId)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    taxCode = VALUES(taxCode),
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    bankAccount = VALUES(bankAccount),
    salesOwnerId = VALUES(salesOwnerId),
    creditWarning = VALUES(creditWarning),
    status = VALUES(status),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @salesId;

SET @cust1 = (SELECT id FROM crm_customers WHERE code = 'CUS-DEMO-001');
SET @cust2 = (SELECT id FROM crm_customers WHERE code = 'CUS-DEMO-002');
SET @cust3 = (SELECT id FROM crm_customers WHERE code = 'CUS-DEMO-003');

INSERT INTO crm_sites (
    customerId, code, name, address, latitude, longitude, contactName, contactPhone,
    installConditions, accessNote, status, createdAt, updatedAt, createdBy, updatedBy
) VALUES
    (@cust1, 'SITE-Q2', 'Cong trinh Quan 2', 'Mai Chi Tho, Thu Duc, TP HCM', 10.787100, 106.749900, 'Anh Nam', '0902000001', 'Mat bang rong, xe cau vao duoc.', 'Bao ve cong so 2 huong dan vao bai dat may.', 'hoat_dong', @now, @now, @salesId, @salesId),
    (@cust2, 'SITE-VSIP', 'Nha xuong VSIP II', 'Duong so 7, VSIP II, Binh Duong', 11.061500, 106.705400, 'Chi Linh', '0902000002', 'Can may du phong 24/7, co mai che.', 'Nhan the khach tai cong bao ve.', 'hoat_dong', @now, @now, @salesId, @salesId),
    (@cust3, 'SITE-TD', 'Cua hang Thu Duc', '88 Quoc lo 13, Thu Duc, TP HCM', 10.849900, 106.771200, 'Chu Phu', '0902000003', 'Hem rong 5m, xe tai nho vao duoc.', 'Hen truoc 30 phut.', 'hoat_dong', @now, @now, @salesId, @salesId)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    address = VALUES(address),
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    contactName = VALUES(contactName),
    contactPhone = VALUES(contactPhone),
    installConditions = VALUES(installConditions),
    accessNote = VALUES(accessNote),
    status = VALUES(status),
    updatedAt = @now,
    updatedBy = @salesId;

SET @site1 = (SELECT id FROM crm_sites WHERE customerId = @cust1 AND code = 'SITE-Q2');
SET @site2 = (SELECT id FROM crm_sites WHERE customerId = @cust2 AND code = 'SITE-VSIP');
SET @site3 = (SELECT id FROM crm_sites WHERE customerId = @cust3 AND code = 'SITE-TD');

DELETE FROM crm_contacts WHERE customerId IN (@cust1, @cust2, @cust3) AND note = 'demo_seed';
INSERT INTO crm_contacts (
    customerId, siteId, fullName, position, phone, email, isPrimary, note,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    (@cust1, @site1, 'Nguyen Van Nam', 'Chi huy truong', '0902000001', 'nam@minhan.example', 1, 'demo_seed', @now, @now, @salesId, @salesId),
    (@cust2, @site2, 'Tran Khanh Linh', 'Thu mua', '0902000002', 'linh@baotin.example', 1, 'demo_seed', @now, @now, @salesId, @salesId),
    (@cust3, @site3, 'Le An Phu', 'Chu cua hang', '0902000003', 'phu@anphu.example', 1, 'demo_seed', @now, @now, @salesId, @salesId);

INSERT INTO flt_generators (
    code, name, serialNumber, manufacturer, model, manufactureYear, capacityKva,
    fuelType, status, hourMeter, warehouseCode, currentLocation, latitude, longitude,
    note, extraContent, createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('DG-060-A', 'May phat 60 kVA A', 'DEMO-SN-060-A', 'Denyo', 'DCA-60', 2021, 60, 'diesel', 'san_sang', 486.5, 'KHO-HCM', 'Kho HCM', 10.809000, 106.700000, 'San sang giao nhanh.', JSON_OBJECT('voltage', '380V', 'phase', '3 pha'), @now, @now, @adminId, @adminId),
    ('DG-080-A', 'May phat 80 kVA A', 'DEMO-SN-080-A', 'Cummins', 'C80D5', 2022, 80, 'diesel', 'dang_van_chuyen', 312.0, 'KHO-HCM', 'Dang xep lich giao Cua hang Thu Duc', 10.849900, 106.771200, 'Dang cho giao cho don demo.', JSON_OBJECT('voltage', '380V', 'phase', '3 pha'), @now, @now, @adminId, @adminId),
    ('DG-125-A', 'May phat 125 kVA A', 'DEMO-SN-125-A', 'Cummins', 'C125D5', 2020, 125, 'diesel', 'dang_thue', 1278.5, 'KHO-HCM', 'Cong trinh Quan 2', 10.787100, 106.749900, 'Dang thue tai Minh An.', JSON_OBJECT('voltage', '380V', 'phase', '3 pha'), @now, @now, @adminId, @adminId),
    ('DG-180-A', 'May phat 180 kVA A', 'DEMO-SN-180-A', 'Mitsubishi', 'MGS1800', 2019, 180, 'diesel', 'dang_thue', 2210.0, 'KHO-BD', 'Nha xuong VSIP II', 11.061500, 106.705400, 'Don sap thu hoi.', JSON_OBJECT('voltage', '380V', 'phase', '3 pha'), @now, @now, @adminId, @adminId),
    ('DG-250-A', 'May phat 250 kVA A', 'DEMO-SN-250-A', 'Perkins', 'P250', 2021, 250, 'diesel', 'dang_giu_cho', 902.4, 'KHO-HCM', 'Kho HCM', 10.809000, 106.700000, 'Dang giu cho bao gia.', JSON_OBJECT('voltage', '380V', 'phase', '3 pha'), @now, @now, @adminId, @adminId),
    ('DG-500-A', 'May phat 500 kVA A', 'DEMO-SN-500-A', 'CAT', 'C15', 2018, 500, 'diesel', 'dang_bao_tri', 3980.0, 'KHO-HCM', 'Xuong bao tri', 10.809000, 106.700000, 'Dang bao tri dinh ky.', JSON_OBJECT('voltage', '380V', 'phase', '3 pha'), @now, @now, @adminId, @adminId)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    manufacturer = VALUES(manufacturer),
    model = VALUES(model),
    manufactureYear = VALUES(manufactureYear),
    capacityKva = VALUES(capacityKva),
    fuelType = VALUES(fuelType),
    status = VALUES(status),
    hourMeter = VALUES(hourMeter),
    warehouseCode = VALUES(warehouseCode),
    currentLocation = VALUES(currentLocation),
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    note = VALUES(note),
    extraContent = VALUES(extraContent),
    updatedAt = @now,
    updatedBy = @adminId;

SET @gen60 = (SELECT id FROM flt_generators WHERE code = 'DG-060-A');
SET @gen80 = (SELECT id FROM flt_generators WHERE code = 'DG-080-A');
SET @gen125 = (SELECT id FROM flt_generators WHERE code = 'DG-125-A');
SET @gen180 = (SELECT id FROM flt_generators WHERE code = 'DG-180-A');
SET @gen250 = (SELECT id FROM flt_generators WHERE code = 'DG-250-A');
SET @gen500 = (SELECT id FROM flt_generators WHERE code = 'DG-500-A');

INSERT INTO sal_price_lists (
    code, name, validFrom, validTo, isActive, note,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('PL-DEMO-2026', 'Bang gia demo 2026', '2026-01-01', NULL, 1, 'Bang gia phuc vu test local.', @now, @now, @managerId, @managerId)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    validFrom = VALUES(validFrom),
    validTo = VALUES(validTo),
    isActive = VALUES(isActive),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @managerId;

SET @priceList = (SELECT id FROM sal_price_lists WHERE code = 'PL-DEMO-2026');

INSERT INTO sal_price_list_items (
    priceListId, capacityFrom, capacityTo, durationTier, minDays, unitPrice,
    dailyRate, deliveryFee, installFee, depositAmount,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    (@priceList, 50, 99, 'thang', 30, 18000000, 800000, 1500000, 1000000, 10000000, @now, @now, @managerId, @managerId),
    (@priceList, 100, 199, 'thang', 30, 28000000, 1200000, 2500000, 1500000, 20000000, @now, @now, @managerId, @managerId),
    (@priceList, 200, 299, 'thang', 30, 42000000, 1800000, 3500000, 2500000, 30000000, @now, @now, @managerId, @managerId),
    (@priceList, 500, 600, 'thang', 30, 95000000, 3800000, 7000000, 5000000, 60000000, @now, @now, @managerId, @managerId)
ON DUPLICATE KEY UPDATE
    unitPrice = VALUES(unitPrice),
    dailyRate = VALUES(dailyRate),
    deliveryFee = VALUES(deliveryFee),
    installFee = VALUES(installFee),
    depositAmount = VALUES(depositAmount),
    updatedAt = @now,
    updatedBy = @managerId;

INSERT INTO sal_quotes (
    quoteNo, customerId, siteId, priceListId, rentFrom, rentTo, status, validUntil,
    rentAmount, deliveryFee, installFee, otherFee, discountAmount, vatRate, vatAmount,
    totalAmount, depositAmount, submittedAt, approvedBy, approvedAt, terms,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('Q-DEMO-2026-001', @cust1, @site1, @priceList, '2026-07-20', '2026-08-20', 'da_duyet', '2026-07-31', 28000000, 2500000, 1500000, 0, 1000000, 8, 2480000, 33480000, 20000000, TIMESTAMP('2026-07-18 03:00:00.000'), @managerId, TIMESTAMP('2026-07-18 05:00:00.000'), 'Dieu khoan demo cho hop dong dang thue.', @now, @now, @salesId, @managerId),
    ('Q-DEMO-2026-002', @cust3, @site3, @priceList, '2026-07-27', '2026-08-27', 'cho_duyet', '2026-07-29', 18000000, 1500000, 1000000, 0, 0, 8, 1640000, 22140000, 10000000, TIMESTAMP('2026-07-25 04:00:00.000'), NULL, NULL, 'Bao gia dang cho quan ly duyet.', @now, @now, @salesId, @salesId),
    ('Q-DEMO-2026-003', @cust2, @site2, @priceList, '2026-07-05', '2026-08-05', 'da_duyet', '2026-07-10', 42000000, 3500000, 2500000, 0, 0, 8, 3840000, 51840000, 30000000, TIMESTAMP('2026-07-02 02:00:00.000'), @managerId, TIMESTAMP('2026-07-02 06:00:00.000'), 'Bao gia cho don sap thu hoi.', @now, @now, @salesId, @managerId)
ON DUPLICATE KEY UPDATE
    customerId = VALUES(customerId),
    siteId = VALUES(siteId),
    priceListId = VALUES(priceListId),
    rentFrom = VALUES(rentFrom),
    rentTo = VALUES(rentTo),
    status = VALUES(status),
    validUntil = VALUES(validUntil),
    rentAmount = VALUES(rentAmount),
    deliveryFee = VALUES(deliveryFee),
    installFee = VALUES(installFee),
    discountAmount = VALUES(discountAmount),
    vatRate = VALUES(vatRate),
    vatAmount = VALUES(vatAmount),
    totalAmount = VALUES(totalAmount),
    depositAmount = VALUES(depositAmount),
    submittedAt = VALUES(submittedAt),
    approvedBy = VALUES(approvedBy),
    approvedAt = VALUES(approvedAt),
    terms = VALUES(terms),
    updatedAt = @now,
    updatedBy = VALUES(updatedBy);

SET @quote1 = (SELECT id FROM sal_quotes WHERE quoteNo = 'Q-DEMO-2026-001');
SET @quote2 = (SELECT id FROM sal_quotes WHERE quoteNo = 'Q-DEMO-2026-002');
SET @quote3 = (SELECT id FROM sal_quotes WHERE quoteNo = 'Q-DEMO-2026-003');

DELETE FROM sal_quote_lines WHERE quoteId IN (@quote1, @quote2, @quote3);
INSERT INTO sal_quote_lines (
    quoteId, generatorId, capacityKva, quantity, rentFrom, rentTo, durationTier,
    durationQty, unitPrice, oddDays, oddDayRate, lineAmount, suggestReason, note,
    createdAt, updatedAt
) VALUES
    (@quote1, @gen125, 125, 1, '2026-07-20', '2026-08-20', 'thang', 1.00, 28000000, 1, 1200000, 28000000, 'Dung cong suat yeu cau va dang san sang tai HCM.', 'demo_seed', @now, @now),
    (@quote2, @gen80, 80, 1, '2026-07-27', '2026-08-27', 'thang', 1.00, 18000000, 1, 800000, 18000000, 'May 80 kVA phu hop mat bang nho.', 'demo_seed', @now, @now),
    (@quote3, @gen250, 250, 1, '2026-07-05', '2026-08-05', 'thang', 1.00, 42000000, 1, 1800000, 42000000, 'Khach can may 250 kVA cho nha xuong.', 'demo_seed', @now, @now);

INSERT INTO sal_contracts (
    contractNo, quoteId, customerId, siteId, signedDate, effectiveFrom, effectiveTo,
    status, totalAmount, depositAmount, paymentTermDays, billingCycle,
    terms, createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('HD-DEMO-2026-001', @quote1, @cust1, @site1, '2026-07-18', '2026-07-20', '2026-08-20', 'dang_hieu_luc', 33480000, 20000000, 15, 'thang', 'Hop dong demo dang hieu luc.', @now, @now, @salesId, @managerId),
    ('HD-DEMO-2026-002', @quote3, @cust2, @site2, '2026-07-03', '2026-07-05', '2026-08-05', 'dang_hieu_luc', 51840000, 30000000, 7, 'thang', 'Hop dong demo sap thu hoi.', @now, @now, @salesId, @managerId)
ON DUPLICATE KEY UPDATE
    quoteId = VALUES(quoteId),
    customerId = VALUES(customerId),
    siteId = VALUES(siteId),
    signedDate = VALUES(signedDate),
    effectiveFrom = VALUES(effectiveFrom),
    effectiveTo = VALUES(effectiveTo),
    status = VALUES(status),
    totalAmount = VALUES(totalAmount),
    depositAmount = VALUES(depositAmount),
    paymentTermDays = VALUES(paymentTermDays),
    billingCycle = VALUES(billingCycle),
    terms = VALUES(terms),
    updatedAt = @now,
    updatedBy = @managerId;

SET @contract1 = (SELECT id FROM sal_contracts WHERE contractNo = 'HD-DEMO-2026-001');
SET @contract2 = (SELECT id FROM sal_contracts WHERE contractNo = 'HD-DEMO-2026-002');

INSERT INTO rnt_rental_orders (
    orderNo, contractId, customerId, siteId, generatorId, startDate, expectedEndDate,
    actualEndDate, status, startHourMeter, endHourMeter, handoverAt, recoveredAt,
    unitPrice, durationTier, withOperator, extendedTimes, settledAt, note,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('RO-DEMO-2026-001', @contract1, @cust1, @site1, @gen125, '2026-07-20', '2026-08-20', NULL, 'dang_thue', 1240.0, NULL, TIMESTAMP('2026-07-20 02:30:00.000'), NULL, 28000000, 'thang', 0, 0, NULL, 'Don dang thue, da ban giao.', @now, @now, @dispatcherId, @dispatcherId),
    ('RO-DEMO-2026-002', NULL, @cust3, @site3, @gen80, '2026-07-27', '2026-08-27', NULL, 'cho_giao', NULL, NULL, NULL, NULL, 18000000, 'thang', 0, 0, NULL, 'Don cho dieu phoi giao ngay mai.', @now, @now, @dispatcherId, @dispatcherId),
    ('RO-DEMO-2026-003', @contract2, @cust2, @site2, @gen180, '2026-07-05', '2026-08-05', NULL, 'cho_thu_hoi', 2150.0, NULL, TIMESTAMP('2026-07-05 03:00:00.000'), NULL, 42000000, 'thang', 1, 0, NULL, 'Don sap thu hoi, tao lenh thu hoi san.', @now, @now, @dispatcherId, @dispatcherId)
ON DUPLICATE KEY UPDATE
    contractId = VALUES(contractId),
    customerId = VALUES(customerId),
    siteId = VALUES(siteId),
    generatorId = VALUES(generatorId),
    startDate = VALUES(startDate),
    expectedEndDate = VALUES(expectedEndDate),
    status = VALUES(status),
    startHourMeter = VALUES(startHourMeter),
    endHourMeter = VALUES(endHourMeter),
    handoverAt = VALUES(handoverAt),
    recoveredAt = VALUES(recoveredAt),
    unitPrice = VALUES(unitPrice),
    durationTier = VALUES(durationTier),
    withOperator = VALUES(withOperator),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @dispatcherId;

SET @order1 = (SELECT id FROM rnt_rental_orders WHERE orderNo = 'RO-DEMO-2026-001');
SET @order2 = (SELECT id FROM rnt_rental_orders WHERE orderNo = 'RO-DEMO-2026-002');
SET @order3 = (SELECT id FROM rnt_rental_orders WHERE orderNo = 'RO-DEMO-2026-003');

DELETE FROM rnt_generator_occupancy WHERE rentalOrderId IN (@order1, @order2, @order3);
INSERT INTO rnt_generator_occupancy (generatorId, occupiedDate, rentalOrderId, holdType, sourceType, sourceId, expiresAt, createdAt, createdBy)
SELECT @gen125, DATE_ADD('2026-07-20', INTERVAL seq DAY), @order1, 'thue', 'don_thue', @order1, NULL, @now, @dispatcherId
FROM (
    SELECT 0 seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
    SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
    SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL
    SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL
    SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL
    SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
) d
UNION ALL
SELECT @gen80, DATE_ADD('2026-07-27', INTERVAL seq DAY), @order2, 'giu_cho', 'bao_gia', @quote2, TIMESTAMP('2026-07-29 17:00:00.000'), @now, @dispatcherId
FROM (
    SELECT 0 seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
    SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
    SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL
    SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL
    SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL
    SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
) d
UNION ALL
SELECT @gen180, DATE_ADD('2026-07-05', INTERVAL seq DAY), @order3, 'thue', 'don_thue', @order3, NULL, @now, @dispatcherId
FROM (
    SELECT 0 seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
    SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
    SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL
    SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL
    SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL
    SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
) d;

INSERT INTO dsp_vehicles (
    code, plateNumber, vehicleType, capacityKg, driverId, status, note,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('TRUCK-DEMO-01', '51C-123.45', 'xe_tai', 5000, @driverId, 'dang_chay', 'Xe dang di giao don demo.', @now, @now, @dispatcherId, @dispatcherId),
    ('CRANE-DEMO-01', '51LA-6789', 'xe_cau', 10000, @driverId, 'san_sang', 'Xe cau san sang.', @now, @now, @dispatcherId, @dispatcherId)
ON DUPLICATE KEY UPDATE
    plateNumber = VALUES(plateNumber),
    vehicleType = VALUES(vehicleType),
    capacityKg = VALUES(capacityKg),
    driverId = VALUES(driverId),
    status = VALUES(status),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @dispatcherId;

SET @truck1 = (SELECT id FROM dsp_vehicles WHERE code = 'TRUCK-DEMO-01');
SET @crane1 = (SELECT id FROM dsp_vehicles WHERE code = 'CRANE-DEMO-01');

INSERT INTO dsp_jobs (
    jobNo, jobType, rentalOrderId, generatorId, siteId, vehicleId,
    scheduledAt, departedAt, arrivedAt, completedAt, checklistJson, checklistCompletedAt,
    status, priority, note, createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('DSP-DEMO-2026-001', 'giao', @order1, @gen125, @site1, @truck1, TIMESTAMP('2026-07-20 01:30:00.000'), TIMESTAMP('2026-07-20 01:45:00.000'), TIMESTAMP('2026-07-20 02:20:00.000'), TIMESTAMP('2026-07-20 02:45:00.000'), JSON_ARRAY(JSON_OBJECT('key','hour_meter','label','Ghi chi so gio','value','1240.0')), TIMESTAMP('2026-07-20 02:40:00.000'), 'hoan_thanh', 'cao', 'Lenh giao da hoan thanh.', @now, @now, @dispatcherId, @dispatcherId),
    ('DSP-DEMO-2026-002', 'giao', @order2, @gen80, @site3, @truck1, TIMESTAMP('2026-07-27 02:00:00.000'), NULL, NULL, NULL, JSON_ARRAY(JSON_OBJECT('key','access','label','Kiem tra duong vao','value',NULL)), NULL, 'da_len_lich', 'binh_thuong', 'Lenh giao cho ngay mai.', @now, @now, @dispatcherId, @dispatcherId),
    ('DSP-DEMO-2026-003', 'thu_hoi', @order3, @gen180, @site2, @crane1, TIMESTAMP('2026-07-28 03:00:00.000'), NULL, NULL, NULL, JSON_ARRAY(JSON_OBJECT('key','recover_meter','label','Ghi chi so thu hoi','value',NULL)), NULL, 'da_len_lich', 'cao', 'Lenh thu hoi da len lich.', @now, @now, @dispatcherId, @dispatcherId)
ON DUPLICATE KEY UPDATE
    jobType = VALUES(jobType),
    rentalOrderId = VALUES(rentalOrderId),
    generatorId = VALUES(generatorId),
    siteId = VALUES(siteId),
    vehicleId = VALUES(vehicleId),
    scheduledAt = VALUES(scheduledAt),
    departedAt = VALUES(departedAt),
    arrivedAt = VALUES(arrivedAt),
    completedAt = VALUES(completedAt),
    checklistJson = VALUES(checklistJson),
    checklistCompletedAt = VALUES(checklistCompletedAt),
    status = VALUES(status),
    priority = VALUES(priority),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @dispatcherId;

SET @job1 = (SELECT id FROM dsp_jobs WHERE jobNo = 'DSP-DEMO-2026-001');
SET @job2 = (SELECT id FROM dsp_jobs WHERE jobNo = 'DSP-DEMO-2026-002');
SET @job3 = (SELECT id FROM dsp_jobs WHERE jobNo = 'DSP-DEMO-2026-003');

DELETE FROM dsp_assignments WHERE jobId IN (@job1, @job2, @job3);
INSERT INTO dsp_assignments (jobId, userId, roleInJob, isLead, acceptedAt, createdAt, createdBy) VALUES
    (@job1, @techId, 'ky_thuat', 1, TIMESTAMP('2026-07-20 01:20:00.000'), @now, @dispatcherId),
    (@job1, @driverId, 'tai_xe', 0, TIMESTAMP('2026-07-20 01:20:00.000'), @now, @dispatcherId),
    (@job2, @techId, 'ky_thuat', 1, NULL, @now, @dispatcherId),
    (@job2, @driverId, 'tai_xe', 0, NULL, @now, @dispatcherId),
    (@job3, @techId, 'ky_thuat', 1, NULL, @now, @dispatcherId),
    (@job3, @driverId, 'tai_xe', 0, NULL, @now, @dispatcherId);

INSERT INTO dsp_handover_records (
    recordNo, jobId, rentalOrderId, generatorId, recordType, hourMeter, fuelLevel,
    conditionNote, customerSignerName, idempotencyKey, syncStatus, recordedAt, createdAt, createdBy
) VALUES
    ('BB-DEMO-2026-001', @job1, @order1, @gen125, 'ban_giao', 1240.0, 'day', 'May khoi dong tot, day du cap va ATS.', 'Nguyen Van Nam', 'demo-handover-001', 'da_dong_bo', TIMESTAMP('2026-07-20 02:40:00.000'), @now, @techId)
ON DUPLICATE KEY UPDATE
    jobId = VALUES(jobId),
    rentalOrderId = VALUES(rentalOrderId),
    generatorId = VALUES(generatorId),
    recordType = VALUES(recordType),
    hourMeter = VALUES(hourMeter),
    fuelLevel = VALUES(fuelLevel),
    conditionNote = VALUES(conditionNote),
    customerSignerName = VALUES(customerSignerName),
    syncStatus = VALUES(syncStatus),
    recordedAt = VALUES(recordedAt),
    createdBy = @techId;

SET @handover1 = (SELECT id FROM dsp_handover_records WHERE recordNo = 'BB-DEMO-2026-001');

DELETE FROM flt_hour_meter_readings WHERE (contextType = 'demo_seed') OR (contextType = 'dsp_handover_records' AND contextId = @handover1);
INSERT INTO flt_hour_meter_readings (
    generatorId, hourMeter, previousValue, source, contextType, contextId,
    isDecrease, recordedAt, createdAt, createdBy
) VALUES
    (@gen60, 486.5, NULL, 'nhap_tay', 'demo_seed', NULL, 0, TIMESTAMP('2026-07-01 01:00:00.000'), @now, @adminId),
    (@gen80, 312.0, NULL, 'nhap_tay', 'demo_seed', NULL, 0, TIMESTAMP('2026-07-01 01:00:00.000'), @now, @adminId),
    (@gen125, 1240.0, 1235.0, 'ban_giao', 'dsp_handover_records', @handover1, 0, TIMESTAMP('2026-07-20 02:40:00.000'), @now, @techId),
    (@gen125, 1278.5, 1240.0, 'van_hanh', 'demo_seed', NULL, 0, TIMESTAMP('2026-07-25 10:00:00.000'), @now, @techId),
    (@gen180, 2150.0, 2144.0, 'ban_giao', 'demo_seed', NULL, 0, TIMESTAMP('2026-07-05 03:00:00.000'), @now, @techId),
    (@gen500, 3980.0, 3920.0, 'bao_tri', 'demo_seed', NULL, 0, TIMESTAMP('2026-07-24 03:00:00.000'), @now, @techId);

INSERT INTO bil_credit_limits (
    customerId, creditLimit, currentDebt, overdueAmount, isBlocked, lastCheckedAt,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    (@cust1, 100000000, 13480000, 0, 0, @now, @now, @now, @accountantId, @accountantId),
    (@cust2, 50000000, 51840000, 18000000, 1, @now, @now, @now, @accountantId, @accountantId),
    (@cust3, 30000000, 0, 0, 0, @now, @now, @now, @accountantId, @accountantId)
ON DUPLICATE KEY UPDATE
    creditLimit = VALUES(creditLimit),
    currentDebt = VALUES(currentDebt),
    overdueAmount = VALUES(overdueAmount),
    isBlocked = VALUES(isBlocked),
    lastCheckedAt = @now,
    updatedAt = @now,
    updatedBy = @accountantId;

INSERT INTO bil_invoices (
    invoiceNo, customerId, contractId, rentalOrderId, periodFrom, periodTo,
    issueDate, dueDate, status, rentAmount, surchargeAmount, discountAmount,
    vatRate, vatAmount, totalAmount, paidAmount, remainAmount, note,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('INV-DEMO-2026-001', @cust1, @contract1, @order1, '2026-07-20', '2026-08-20', '2026-07-21', '2026-08-05', 'da_thanh_toan_mot_phan', 28000000, 0, 1000000, 8, 2160000, 29160000, 15680000, 13480000, 'Hoa don demo da thu mot phan.', @now, @now, @accountantId, @accountantId),
    ('INV-DEMO-2026-002', @cust2, @contract2, @order3, '2026-07-05', '2026-08-05', '2026-07-06', '2026-07-13', 'qua_han', 42000000, 2500000, 0, 8, 3560000, 48060000, 0, 48060000, 'Hoa don demo qua han.', @now, @now, @accountantId, @accountantId)
ON DUPLICATE KEY UPDATE
    customerId = VALUES(customerId),
    contractId = VALUES(contractId),
    rentalOrderId = VALUES(rentalOrderId),
    periodFrom = VALUES(periodFrom),
    periodTo = VALUES(periodTo),
    issueDate = VALUES(issueDate),
    dueDate = VALUES(dueDate),
    status = VALUES(status),
    rentAmount = VALUES(rentAmount),
    surchargeAmount = VALUES(surchargeAmount),
    discountAmount = VALUES(discountAmount),
    vatRate = VALUES(vatRate),
    vatAmount = VALUES(vatAmount),
    totalAmount = VALUES(totalAmount),
    paidAmount = VALUES(paidAmount),
    remainAmount = VALUES(remainAmount),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @accountantId;

SET @invoice1 = (SELECT id FROM bil_invoices WHERE invoiceNo = 'INV-DEMO-2026-001');
SET @invoice2 = (SELECT id FROM bil_invoices WHERE invoiceNo = 'INV-DEMO-2026-002');

DELETE FROM bil_invoice_lines WHERE invoiceId IN (@invoice1, @invoice2);
INSERT INTO bil_invoice_lines (
    invoiceId, lineType, generatorId, description, quantity, unit, unitPrice,
    lineAmount, isVatable, createdAt, updatedAt
) VALUES
    (@invoice1, 'tien_thue', @gen125, 'Tien thue may 125 kVA thang 07/2026', 1.00, 'thang', 28000000, 28000000, 1, @now, @now),
    (@invoice1, 'khac', NULL, 'Chiet khau demo', 1.00, 'lan', -1000000, -1000000, 1, @now, @now),
    (@invoice2, 'tien_thue', @gen180, 'Tien thue may 180 kVA thang 07/2026', 1.00, 'thang', 42000000, 42000000, 1, @now, @now),
    (@invoice2, 'nhien_lieu', @gen180, 'Phu phi nhien lieu demo', 1.00, 'lan', 2500000, 2500000, 1, @now, @now);

INSERT INTO bil_payments (
    paymentNo, invoiceId, customerId, amount, paymentDate, method, referenceNo,
    status, note, createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('PAY-DEMO-2026-001', @invoice1, @cust1, 15680000, '2026-07-22', 'chuyen_khoan', 'VCB-DEMO-001', 'da_ghi_nhan', 'Thu mot phan hoa don demo.', @now, @now, @accountantId, @accountantId)
ON DUPLICATE KEY UPDATE
    invoiceId = VALUES(invoiceId),
    customerId = VALUES(customerId),
    amount = VALUES(amount),
    paymentDate = VALUES(paymentDate),
    method = VALUES(method),
    referenceNo = VALUES(referenceNo),
    status = VALUES(status),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @accountantId;

INSERT INTO bil_deposits (
    depositNo, customerId, contractId, rentalOrderId, amount, receivedDate,
    deductedAmount, refundedAmount, status, note,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('DEP-DEMO-2026-001', @cust1, @contract1, @order1, 20000000, '2026-07-18', 0, 0, 'dang_giu', 'Coc cho hop dong dang thue.', @now, @now, @accountantId, @accountantId),
    ('DEP-DEMO-2026-002', @cust2, @contract2, @order3, 30000000, '2026-07-03', 0, 0, 'dang_giu', 'Coc don sap thu hoi.', @now, @now, @accountantId, @accountantId)
ON DUPLICATE KEY UPDATE
    customerId = VALUES(customerId),
    contractId = VALUES(contractId),
    rentalOrderId = VALUES(rentalOrderId),
    amount = VALUES(amount),
    receivedDate = VALUES(receivedDate),
    deductedAmount = VALUES(deductedAmount),
    refundedAmount = VALUES(refundedAmount),
    status = VALUES(status),
    note = VALUES(note),
    updatedAt = @now,
    updatedBy = @accountantId;

INSERT INTO mnt_schedules (
    generatorId, scheduleType, intervalHours, intervalDays, lastServiceHour,
    lastServiceDate, nextDueHour, nextDueDate, isActive,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    (@gen125, 'ca_hai', 250.0, 90, 1100.0, '2026-05-10', 1350.0, '2026-08-08', 1, @now, @now, @techId, @techId),
    (@gen500, 'gio_may', 250.0, NULL, 3750.0, '2026-06-18', 4000.0, NULL, 1, @now, @now, @techId, @techId)
ON DUPLICATE KEY UPDATE
    intervalHours = VALUES(intervalHours),
    intervalDays = VALUES(intervalDays),
    lastServiceHour = VALUES(lastServiceHour),
    lastServiceDate = VALUES(lastServiceDate),
    nextDueHour = VALUES(nextDueHour),
    nextDueDate = VALUES(nextDueDate),
    isActive = VALUES(isActive),
    updatedAt = @now,
    updatedBy = @techId;

SET @schedule125 = (SELECT id FROM mnt_schedules WHERE generatorId = @gen125 AND scheduleType = 'ca_hai');
SET @schedule500 = (SELECT id FROM mnt_schedules WHERE generatorId = @gen500 AND scheduleType = 'gio_may');

INSERT INTO mnt_jobs (
    jobNo, generatorId, scheduleId, jobType, priority, status, triggerReason,
    triggerHourMeter, idempotencyKey, scheduledDate, startedAt, completedAt,
    assigneeId, laborCost, partsCost, totalCost, findings,
    createdAt, updatedAt, createdBy, updatedBy
) VALUES
    ('MNT-DEMO-2026-001', @gen500, @schedule500, 'bao_tri', 'cao', 'dang_thuc_hien', 'Gan nguong 4000 gio may.', 3980.0, 'demo-mnt-500-20260724', '2026-07-26', TIMESTAMP('2026-07-26 01:00:00.000'), NULL, @techId, 800000, 1250000, 2050000, 'Dang thay loc dau va kiem tra tai gia.', @now, @now, @techId, @techId),
    ('MNT-DEMO-2026-002', @gen125, @schedule125, 'bao_tri', 'binh_thuong', 'da_len_lich', 'Bao tri dinh ky sau hop dong.', 1278.5, 'demo-mnt-125-20260808', '2026-08-08', NULL, NULL, @techId, 0, 0, 0, 'Lich bao tri sau khi thu hoi may.', @now, @now, @techId, @techId)
ON DUPLICATE KEY UPDATE
    generatorId = VALUES(generatorId),
    scheduleId = VALUES(scheduleId),
    jobType = VALUES(jobType),
    priority = VALUES(priority),
    status = VALUES(status),
    triggerReason = VALUES(triggerReason),
    triggerHourMeter = VALUES(triggerHourMeter),
    idempotencyKey = VALUES(idempotencyKey),
    scheduledDate = VALUES(scheduledDate),
    startedAt = VALUES(startedAt),
    completedAt = VALUES(completedAt),
    assigneeId = VALUES(assigneeId),
    laborCost = VALUES(laborCost),
    partsCost = VALUES(partsCost),
    totalCost = VALUES(totalCost),
    findings = VALUES(findings),
    updatedAt = @now,
    updatedBy = @techId;

SET @mntJob1 = (SELECT id FROM mnt_jobs WHERE jobNo = 'MNT-DEMO-2026-001');
DELETE FROM mnt_parts_used WHERE jobId = @mntJob1;
INSERT INTO mnt_parts_used (
    jobId, partCode, partName, quantity, unit, unitPrice, lineAmount, supplier,
    createdAt, updatedAt, createdBy
) VALUES
    (@mntJob1, 'FILTER-OIL-01', 'Loc dau dong co', 2.00, 'cai', 350000, 700000, 'Phu tung Demo', @now, @now, @techId),
    (@mntJob1, 'OIL-15W40', 'Dau nhot 15W40', 11.00, 'lit', 50000, 550000, 'Phu tung Demo', @now, @now, @techId);

INSERT INTO rpt_fleet_utilization_daily (
    reportDate, warehouseCode, totalGenerators, activeGenerators, rentedCount,
    availableCount, heldCount, transitCount, maintenanceCount, repairCount,
    retiredCount, utilizationRate, computedAt
) VALUES
    (@today, 'KHO-HCM', 5, 5, 1, 1, 1, 1, 1, 0, 0, 20.00, @now),
    (@today, 'KHO-BD', 1, 1, 1, 0, 0, 0, 0, 0, 0, 100.00, @now)
ON DUPLICATE KEY UPDATE
    totalGenerators = VALUES(totalGenerators),
    activeGenerators = VALUES(activeGenerators),
    rentedCount = VALUES(rentedCount),
    availableCount = VALUES(availableCount),
    heldCount = VALUES(heldCount),
    transitCount = VALUES(transitCount),
    maintenanceCount = VALUES(maintenanceCount),
    repairCount = VALUES(repairCount),
    retiredCount = VALUES(retiredCount),
    utilizationRate = VALUES(utilizationRate),
    computedAt = @now;

INSERT INTO rpt_revenue_monthly (
    periodYear, periodMonth, customerId, invoicedAmount, collectedAmount,
    outstandingAmount, overdueAmount, orderCount, computedAt
) VALUES
    (2026, 7, @cust1, 29160000, 15680000, 13480000, 0, 1, @now),
    (2026, 7, @cust2, 48060000, 0, 48060000, 18000000, 1, @now)
ON DUPLICATE KEY UPDATE
    invoicedAmount = VALUES(invoicedAmount),
    collectedAmount = VALUES(collectedAmount),
    outstandingAmount = VALUES(outstandingAmount),
    overdueAmount = VALUES(overdueAmount),
    orderCount = VALUES(orderCount),
    computedAt = @now;

INSERT INTO rpt_receivables_snapshot (
    snapshotDate, customerId, bucket0To30, bucket31To60, bucket61To90,
    bucketOver90, totalDebt, dsoDays, computedAt
) VALUES
    (@today, @cust1, 13480000, 0, 0, 0, 13480000, 14.00, @now),
    (@today, @cust2, 30060000, 18000000, 0, 0, 48060000, 31.50, @now)
ON DUPLICATE KEY UPDATE
    bucket0To30 = VALUES(bucket0To30),
    bucket31To60 = VALUES(bucket31To60),
    bucket61To90 = VALUES(bucket61To90),
    bucketOver90 = VALUES(bucketOver90),
    totalDebt = VALUES(totalDebt),
    dsoDays = VALUES(dsoDays),
    computedAt = @now;

INSERT INTO pfm_notifications (
    userId, channel, title, body, linkUrl, objectType, objectId, readAt, createdAt
) VALUES
    (@managerId, 'in_app', 'Bao gia cho duyet', 'Q-DEMO-2026-002 dang cho duyet.', '/sales/quotes', 'sal_quotes', @quote2, NULL, @now),
    (@dispatcherId, 'in_app', 'Lenh giao ngay mai', 'DSP-DEMO-2026-002 da len lich giao.', '/dispatch/jobs', 'dsp_jobs', @job2, NULL, @now),
    (@accountantId, 'in_app', 'Hoa don qua han', 'INV-DEMO-2026-002 dang qua han.', '/billing/invoices', 'bil_invoices', @invoice2, NULL, @now);

COMMIT;
