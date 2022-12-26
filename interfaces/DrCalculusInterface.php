<?php

namespace Arris\DrCalculus;

interface DrCalculusInterface
{
    public static function init($pdo_connection, array $allowed_item_types = [], bool $is_engine_disabled = false, string $stat_table = 'stat_nviews');

    public static function updateVisitCount($item_id, $item_type);

    public static function getItemViewCount(int $id, string $type, $last_days_interval = null);

    public static function getVisitCountToday($item_id, $item_type);
    public static function getVisitCountTotal($item_id, $item_type);
    public static function getVisitCountTodaySummary($item_id, $item_type);

    public static function removeVisitData($item_id, $item_type);
}

# -eof-
