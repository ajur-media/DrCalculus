```

SELECT a.*
,
COALESCE((SELECT SUM(event_count) FROM stat_nviews WHERE item_type = 'article' AND item_id = a.id ), 0) AS stat_total
,
COALESCE(sn.event_count, 0) AS stat_today  

FROM articles as a

LEFT JOIN stat_nviews AS sn ON ( (sn.item_id = a.id AND sn.event_date = DATE(NOW()) AND sn.item_type = 'article') )

```