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

DrCalculus::init($pdo_connection, ['article','report','page']);
```

# Записываем статистику:

```
DrCalculus::updateVisitCount($article['id'], 'article');
```

# TODO

- Отвязать ли от таблицы `stat_nviews` ?


