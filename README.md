# Создаем таблицу для статистики 

```
-- создание таблицы 
CREATE TABLE `stat_nviews` (
    `item_id` int(11) DEFAULT NULL,
    `item_type` enum('article','report','page') NOT NULL,
    `event_count` int(11) DEFAULT NULL,
    `event_date` date DEFAULT NULL,
    UNIQUE KEY `id_type_eventdate` (`item_id`,`item_type`,`event_date`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
```

# Инициализируем
```
use \Arris\DrCalculus\DrCalculus;

$pdo_connection = DB::C(); // or other PDO connector

DrCalculus::init($pdo_connection, ['article','report','page'] );
```

# Записываем статистику:

```
DrCalculus::updateVisitCount($article['id'], 'article');
```


# Сигнатуры методов

```
/**
 * Инициализирует движок DrCalculus
 *
 * @param PDO $pdo_connection       -- PDO connection
 * @param array $allowed_item_types -- словарь-список допустимых значений для поля item_type
 * @param string $stat_table        -- таблица для хранения статистики (stat_nviews по умолчанию)
 * @param bool $is_engine_disabled  -- разрешено ли DrCalculus заполнять статистику. Рекомендуется передавать сюда что-то вроде `getenv('DEBUG.DISABLE_DRCALCULUS_STATS_ENGINE')` )
 * 
 */
public static function init(PDO $pdo_connection, array $allowed_item_types = [], $is_engine_disabled = false, $stat_table = 'stat_nviews')
```

```
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
```

```
/**
 * Удаляет статистические записи из таблицы по ITEM_ID + ITEM_TYPE
 *
 * @param $item_id
 * @param $item_type
 * @return array
 */
public static function removeVisitData($item_id, $item_type)
```

```
/**
 * Возвращает записи в статистике по элементу указанного типа за последние N дней
 *
 * @param int $id
 * @param string $type
 * @param null $last_days_interval
 * @return array
 */
public static function getItemViewCount(int $id, string $type, $last_days_interval = null)
```

```
/**
 * Возвращает количество посещений для указанного элемента указанного типа сегодня (это важно, TODAY, а не за последние сутки) 
 * 
 * @param $item_id
 * @param $item_type
 * @return mixed
 */
public static function getVisitCountToday($item_id, $item_type)
```

```
/**
 * Возвращает количество посещений для указанного элемента указанного типа всего
 * 
 * @param $item_id
 * @param $item_type
 * @return mixed
 */
public static function getVisitCountTotal($item_id, $item_type)
```

```
/**
 * Функция-хелпер: посещений сущности суммарно: всего и сегодня
 *
 * @param $item_id
 * @param $item_type
 * @return array array ['total', 'today']
 * @throws Exception
 */
public static function getVisitCountTodaySummary($item_id, $item_type)
```




