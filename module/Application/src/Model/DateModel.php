<?php

namespace Application\Model;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;

class DateModel extends DateTime
{
    /** Định dạng ghi cột DATETIME(3) — có mili giây, luôn UTC (.claude/rules/database.md). */
    const string UTC_DATETIME_FORMAT = 'Y-m-d H:i:s.v';

    // Định dạng format ngày dùng truy vấn trong ES
    const string COMMON_DATE_ELASTIC_FORMAT = 'yyyy-MM-dd';
    const string COMMON_DATE_FORMAT = 'Y-m-d';

    const string COMMON_DATETIME_FORMAT  = 'Y-m-d H:i:s';
    const string DISPLAY_DATE_FORMAT = 'd-m-Y';
    const string DISPLAY_DATETIME_FORMAT = 'd-m-Y H:i';
    const FILEPATH_DATE_FORMAT = 'YmdHis';

    /**
     * Thời điểm hiện tại để ghi vào cột DATETIME(3).
     *
     * Đây là ĐIỂM TẬP TRUNG DUY NHẤT cho `createdAt`/`updatedAt`. Mapper không được gọi
     * `gmdate()` hay `date()` trực tiếp: rải mỗi nơi một kiểu là cách nhanh nhất để lẫn giờ
     * địa phương vào cột UTC, và test không mock được ngày biên.
     */
    public static function getUtcNow(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->format(self::UTC_DATETIME_FORMAT);
    }

    public static function getCurrentDate(): string
    {
        return date(self::COMMON_DATE_FORMAT);
    }

    public static function getCurrentDateTime(): string
    {
        return date(self::COMMON_DATETIME_FORMAT);
    }


    /**
     * convert display date to common datetime format
     */
    public static function toCommonDateTime($d): string
    {
        if ($d) {
            $date = DateTime::createFromFormat(self::DISPLAY_DATETIME_FORMAT, $d);
            if ($date) {
                return $date->format(self::COMMON_DATETIME_FORMAT);
            }
        }
        return '';
    }

    public static function displayDate(?string $date, string $fallback = '-', ?string $format = null): string
    {
        $date = is_string($date) ? trim($date) : '';
        if ($date === '') {
            return $fallback;
        }

        $parsed = self::parseDateValue($date);
        return $parsed ? $parsed->format($format ?? self::DISPLAY_DATE_FORMAT) : $date;
    }

    public static function displayDateTime(?string $dateTime, string $fallback = '-', ?string $format = null): string
    {
        $dateTime = is_string($dateTime) ? trim($dateTime) : '';
        if ($dateTime === '') {
            return $fallback;
        }

        $parsed = self::parseDateValue($dateTime);
        return $parsed ? $parsed->format($format ?? self::DISPLAY_DATETIME_FORMAT) : $dateTime;
    }

    private static function parseDateValue(string $value): ?DateTimeImmutable
    {
        $normalized = str_replace('T', ' ', trim($value));
        $formats = [
            self::UTC_DATETIME_FORMAT,
            'Y-m-d H:i:s.u',
            self::COMMON_DATETIME_FORMAT,
            'Y-m-d H:i',
            self::COMMON_DATE_FORMAT,
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $normalized);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        try {
            return new DateTimeImmutable($normalized);
        } catch (\Exception) {
            return null;
        }
    }

    public function addMinutes($minutes): string
    {
        $newDateTime = strtotime('+' . $minutes . ' minutes');
        return date('Y-m-d H:i:s', $newDateTime);
    }


    /**
     * Lấy thời gian hiện tại theo timestamps
     */
    public static function getTimeStampsCurrent(): int
    {
        return time();
    }

    /**
     * Hàm kiểm tra giá trị có phải timestamps hợp lệ không
     */
    public static function validateTimestamp(mixed $time): int|null
    {
        return is_numeric($time) && (int)$time == $time ? (int)$time : null;
    }
    public static function getCurrentDateUpload()
    {
        return date(self::FILEPATH_DATE_FORMAT);
    }
    public static function subtractMonth($months) {
        if(!$months) {
            return false;
        }

        return strtotime("-".$months." months");
    }

    public static function addMonth($months) {
        if(!$months) {
            return false;
        }

        return strtotime("+".$months." months");
    }

    /**
     * Chuyển định dạng yyyy-MM-dd sang timestamps epoch (giây)
     */
    public static function fromElasticDateToTimestamp(string $date): ?int
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        if ($dateTime) {
            return $dateTime->getTimestamp();
        }
        return null;
    }

    /**
     * Chuyển timestamp (epoch giây) thành chuỗi yyyy-MM-dd.
     *
     * @param int|string $timestamp Epoch-second (hợp lệ)
     * @return string Chuỗi ngày theo định dạng COMMON_DATE_FORMAT; trả '' nếu sai.
     */
    public static function fromTimestampToCommonDate(int|string $timestamp): string
    {
        if (!self::validateTimestamp($timestamp)) {
            return '';
        }
        return date(self::COMMON_DATE_FORMAT, (int) $timestamp);
    }
    public static function subtractDay($day, $fromDate = null){
        if($fromDate) {
            $numberDayStrToTime = $day * (60 * 60 * 24);
            return date(self::COMMON_DATE_FORMAT, strtotime($fromDate) - $numberDayStrToTime);
        } else {
            return date(self::COMMON_DATE_FORMAT, strtotime("-".$day." days"));
        }
    }
}
