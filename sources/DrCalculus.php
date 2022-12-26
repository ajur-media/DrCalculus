<?php

namespace Arris\DrCalculus;

use Exception;
use PDO;

class DrCalculus implements DrCalculusInterface
{
    /**
     * @var string
     */
    private static $sql_table = 'stat_nviews';

    /**
     * @var array
     */
    public static $allowed_item_types;

    /**
     * @var bool
     */
    public static $is_engine_disabled = true;

    /**
     * @var PDO
     */
    public static $pdo;

    /**
     * Инициализирует движок DrCalculus
     *
     * @param PDO $pdo_connection       -- PDO connection
     * @param array $allowed_item_types -- словарь-список допустимых значений для поля item_type
     * @param string $stat_table        -- таблица для хранения статистики (stat_nviews по умолчанию)
     * @param bool $is_engine_disabled  -- разрешено ли DrCalculus заполнять статистику. Рекомендуется передавать сюда что-то вроде `getenv('DEBUG.DISABLE_DRCALCULUS_STATS_ENGINE')` )
     *
     */
    public static function init($pdo_connection, array $allowed_item_types = [], bool $is_engine_disabled = false, string $stat_table = 'stat_nviews')
    {
        self::$pdo = $pdo_connection;

        if (!empty($allowed_item_types)) {
            self::$allowed_item_types = $allowed_item_types;
            self::$is_engine_disabled = false;
        }

        self::$sql_table = $stat_table;

        self::$is_engine_disabled = self::$is_engine_disabled || $is_engine_disabled || getenv('DEBUG.DISABLE_DRCALCULUS_STATS_ENGINE');
    }

    /**
     * Обновляет таблицу статистики.
     *
     * @param $item_id   - id сущности
     * @param $item_type - тип сущности (значение из словаря, заданного при инициализации)
     *
     * @return array     - [ 'state' => статус, 'lid' => id вставленного/обновленного элемента ]
     *@throws Exception
     */
    public static function updateVisitCount($item_id, $item_type)
    {
        $sql_table = self::$sql_table;
        if (self::$is_engine_disabled) {
            return [
                'state'     =>  'Dr.Calculus stats engine not ready',
                'lid'       =>  0
            ];
        }

        $sql_query = "
 INSERT INTO
    {$sql_table}
SET
    `item_id` = :item_id,
    `item_type` = :item_type,
    `event_count` = 1,
    `event_date` = NOW()
ON DUPLICATE KEY UPDATE
    `event_count` = `event_count` + 1;
        ";

        $sth = self::$pdo->prepare($sql_query);
        $r = $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type,
        ]);
        return [
            'state'     =>  $r,
            'lid'       =>  self::$pdo->lastInsertId()
        ];
    }

    /**
     * Удаляет статистические записи из таблицы по ITEM_ID + ITEM_TYPE
     *
     * @param $item_id
     * @param $item_type
     * @return array
     */
    public static function removeVisitData($item_id, $item_type)
    {
        $sql_table = self::$sql_table;

        if (self::$is_engine_disabled) {
            return [
                'state'     =>  'Dr.Calculus stats engine not ready',
                'lid'       =>  0
            ];
        }
        $sql_query = "DELETE FROM {$sql_table} WHERE item_id = :item_id AND item_type = :item_type";

        $sth = self::$pdo->prepare($sql_query);
        $r = $sth->execute(['item_id' => $item_id, 'item_type' => $item_type]);
        return [
            'state'     =>  $r,
        ];
    }

    /**
     * Возвращает записи в статистике по элементу указанного типа за последние N дней
     *
     * @param int $id
     * @param string $type
     * @param null $last_days_interval
     * @return array
     */
    public static function getItemViewCount(int $id, string $type, $last_days_interval = null)
    {
        $sql_table = self::$sql_table;

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
SELECT * FROM {$sql_table} 
WHERE item_type = :type 
  AND item_id = :id 
  AND event_date between date(now() - interval :days day) and date(now())
ORDER BY event_date        
            ";

            // и значение в плейсхолдер
            $sql_conditions['days'] = $last_days_interval;

        } else {

            $sql = "
SELECT * FROM {$sql_table} 
WHERE item_type = :type 
  AND item_id = :id 
ORDER BY event_date                  
            ";

        }

        $sth = self::$pdo->prepare($sql);
        $sth->execute($sql_conditions);

        return $sth->fetchAll();
    }

    /**
     * Возвращает количество посещений для указанного элемента указанного типа сегодня (это важно, TODAY, а не за последние сутки)
     *
     * @param $item_id
     * @param $item_type
     * @return mixed
     */
    public static function getVisitCountToday($item_id, $item_type)
    {
        $sql_table = self::$sql_table;

        $sql_query = "
SELECT `event_count` 
FROM {$sql_table} 
WHERE `item_id` = :item_id AND `item_type` = :item_type AND `event_date` = NOW()
        ";
        $sth = self::$pdo->prepare($sql_query);
        $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type
        ]);

        return $sth->fetchColumn();
    }

    /**
     * Возвращает количество посещений для указанного элемента указанного типа всего
     *
     * @param $item_id
     * @param $item_type
     * @return mixed
     */
    public static function getVisitCountTotal($item_id, $item_type)
    {
        $sql_table = self::$sql_table;

        $sql_query = "
SELECT SUM(`event_count`) 
FROM {$sql_table} 
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
    public static function getVisitCountTodaySummary($item_id, $item_type)
    {
        $sql_table = self::$sql_table;

        $sql_query = "
SELECT `event_count`, `event_date` 
FROM {$sql_table} 
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

# -eof-
