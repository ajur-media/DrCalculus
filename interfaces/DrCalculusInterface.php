<?php

namespace Arris\DrCalculus;


use Monolog\Logger;

interface DrCalculusInterface
{
    public static function init(\PDO $pdo_connection, $allowed_item_types = [], Logger $logger = null);
    public static function updateVisitCount($item_id, $item_type);
    public static function getItemViewCount(int $id, string $type, $last_days_interval = null);
    public static function getVisitCountToday($item_id, $item_type);
    public static function getVisitCountTotal($item_id, $item_type);
    public static function getVisitCountTodaySummary($item_id, $item_type);
}