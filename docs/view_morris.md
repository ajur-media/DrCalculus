показ:
```
$data = DrCalculus::getItemViewCount($id, 'article');

list($export, $visit_total) = DrCalculus::prepareDataForMorrisStatview($data);

$this->smarty->assign("json_dataset", json_encode($export));
$this->smarty->assign("dataset", $data);
$this->smarty->assign("visit_total", $visit_total);
$this->smarty->assign("id", $id);

$this->smarty->assign('object_type', 'статье');
$this->smarty->assign('object_title', $title);

$this->parent->tpl = "common/admin/stats_morris.tpl";
```

+ статистика Morris.JS

```

{*
Универсальный шаблон визуализации статистики посещения информационного объекта
показывается по URL /<item>/stats/<id>/
Метод: class->stats()
*}
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Статистика просмотров</title>

    <link rel="stylesheet" href="/frontend/morris/morris-0.5.1.css">
    <script type="text/javascript" src="/frontend/morris/jquery-1.9.0.min.js"></script>
    <script type="text/javascript" src="/frontend/morris/raphael-2.1.0.min.js"></script>
    <script type="text/javascript" src="/frontend/morris/morris-0.5.1.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $('.spoiler_link').click(function(){
                $(this).parent().children('div.spoiler_body').toggle('normal');
                return false;
            });
        });
    </script>
    <style type="text/css">
        .spoiler_body { display:none; cursor:pointer; }
    </style>
</head>
<body>
Статистика по <strong>{$object_type}</strong> (id: {$id}):
<h3>&laquo;{$object_title}&raquo;</h3>
<div id="visits" style="height: 350px;"></div>

<script type="text/javascript">
    new Morris.Bar({
        element: 'visits',
        data: {$json_dataset},
        xkey: 'date',
        ykeys: [ 'value' ],
        labels: [ 'Просмотры' ]
    });
</script>

<div>
    <button class="spoiler_link">Статистика в виде таблицы</button>
    <div class="spoiler_body">
        <table border="1" width="30%" style="text-align: center">
            <tr>
                <th>Дата</th>
                <th>Посещений</th>
            </tr>
            {foreach $dataset as $row}
                <tr>
                    <td>{$row.event_date|date_format:"%d.%m.%Y"}</td>
                    <td>{$row.event_count}</td>
                </tr>
            {/foreach}
            <tr>
                <td style="text-align: right">Всего:&nbsp;&nbsp;&nbsp;</td>
                <td><span style="color:#DB574D">{$visit_total}</span></td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>

```