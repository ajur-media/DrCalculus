<?php

namespace Arris\DrCalculus;

use Arris\AppLogger;
use Exception;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PDO;
use function Arris\DBC;

class DrCalculus implements DrCalculusInterface
{
    /**
     * Monolog logger instance
     * @var Logger
     */
    public static $logger;

    /**
     * @var array
     */
    public static $allowed_item_types;

    /**
     * @var array|false|string
     */
    public static $is_engine_disabled;

    /**
     * @var PDO
     */
    public static $pdo;

    public static function init(PDO $pdo_connection, $allowed_item_types = [], Logger $logger = null)
    {
        self::$pdo = $pdo_connection;

        if (!empty($allowed_item_types)) {
            self::$allowed_item_types = $allowed_item_types;
        }

        self::$logger
            = $logger instanceof Logger
            ? $logger
            : (new Logger('null'))->pushHandler(new NullHandler());

        self::$is_engine_disabled = getenv('DEBUG.DISABLE_DRCALCULUS_STATS_ENGINE');
    }

    /**
     * Обновляет таблицу статистики.
     *
     * @param $item_id   - id сущности
     * @param $item_type - тип сущности (значение из словаря, заданного при инициализации)
     * @return array     - [ 'state' => статус, 'lid' => id вставленного/обновленного элемента ]
     *@throws Exception
     */
    public static function updateVisitCount($item_id, $item_type)
    {
        $sql_query = "
 INSERT INTO
    stat_nviews
SET
    `item_id` = :item_id,
    `item_type` = :item_type,
    `event_count` = 1,
    `event_date` = NOW()
ON DUPLICATE KEY UPDATE
    `event_count` = `event_count` + 1;
        ";

        $sth = DBC()->prepare($sql_query);
        $r = $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type,
        ]);
        return [
            'state'     =>  $r,
            'lid'       =>  DBC()->lastInsertId()
        ];
    }

    public static function getItemViewCount(int $id, string $type, $last_days_interval = null)
    {
        if (!in_array($type, self::$allowed_item_types)) return [];

        // значения для плейсхолдеров в запросе
        $sql_conditions = [
            'id'    =>  $id,
            'type'  =>  $type
        ];

        // длиннее, но понятнее
        if ($last_days_interval !== null && is_numeric($last_days_interval)) {
            // добавляем AND event_date between date(now() - interval :days day) and date(now())

            $sql = "
SELECT * FROM stat_nviews 
WHERE item_type = :type 
  AND item_id = :id 
  AND event_date between date(now() - interval :days day) and date(now())
ORDER BY event_date ASC                  
            ";

            // и значение в плейсхолдер
            $sql_conditions['days'] = $last_days_interval;

        } else {

            $sql = "
SELECT * FROM stat_nviews 
WHERE item_type = :type 
  AND item_id = :id 
ORDER BY event_date ASC                  
            ";

        }

        $sth = self::$pdo->prepare($sql);
        $sth->execute($sql_conditions);

        return $sth->fetchAll();
    }



    public static function getVisitCountToday($item_id, $item_type)
    {
        $sql_query = "
SELECT `event_count` 
FROM `stat_nviews`
WHERE `item_id` = :item_id AND `item_type` = :item_type AND `event_date` = NOW()
        ";
        $sth = self::$pdo->prepare($sql_query);
        $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type
        ]);

        return $sth->fetchColumn();
    }

    public static function getVisitCountTotal($item_id, $item_type)
    {
        $sql_query = "
SELECT SUM(`event_count`) 
FROM `stat_nviews`
WHERE `item_id` = :item_id and `item_type` = :item_type;
        ";
        $sth = self::$pdo->prepare($sql_query);
        $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type
        ]);

        return $sth->fetchColumn();
    }

    /**
     * Функция-хелпер: посещений сущности суммарно: всего и сегодня
     *
     * @param $item_id
     * @param $item_type
     * @return array array ['total', 'today']
     * @throws Exception
     */

    /**
     * @param $item_id
     * @param $item_type
     * @return array
     * @throws Exception
     */
    public static function getVisitCountTodaySummary($item_id, $item_type)
    {
        $sql_query = "
SELECT `event_count`, `event_date` 
FROM `stat_nviews`
WHERE `item_id` = :item_id and `item_type` = :item_type
ORDER BY `event_date` DESC 
        ";
        $sth = self::$pdo->prepare($sql_query);
        $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type
        ]);

        $row = $sth->fetch();
        $result = [
            'total' =>  $row['count'],
            'today' =>  $row['count']
        ];
        while ($row = $sth->fetch()) {
            $result['total'] += $row['count'];
        }
        return $result;
    }



}