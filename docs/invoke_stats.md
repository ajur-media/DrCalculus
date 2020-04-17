На проектах DP & 47news это включаемый шаблон  `_invoke_stats_collector.tpl`. 

Подключается в подвале страницы после всего контента.

Основная функциональность - ставится кука для каждого просмотренного элемента вида `item[id]` 

```
{*
Вызов запроса обновления статистики просмотра страниц указанного типа.

Должен подключаться как-то так:
{include
    file="_inner/_invoke_stats_collector.tpl"
    item_id="{$article.id}"
    item_type="article" }

Использует jQuery.
*}
<script type="text/javascript">
    /* BEGIN: DrCalculus Stats  */
    $(function () {
        var item_id = {$item_id};
        var cookie_prefix = '{$item_type}_view';
        var cookie_name = '{$item_type}[{$item_id}]';

        if ($.cookie(cookie_name) != 1) {
            $.ajax({
                url: '/stats/views/',
                type: "POST",
                dataType: "JSON",
                data: {
                    id: item_id,
                    item_type: '{$item_type}',
                    cookie_name: cookie_prefix
                },
                success: function (response)
                {
                    if (response.status === 'ok') {
                        $.cookie(cookie_name, 1);
                    }
                }
            });
        }
    });
    /* END: DrCalculus Stats  */
</script>
```

Этот код вызывает роут `/stats/views/`, который имеет следующий обработчик:

```
    use function Arris\DrCalculus\invoke as DrCalculusInvoke;
    
    public function views()
    {

        if (getenv('DISABLE_DRCALCULUS_STATS_ENGINE')) {
            $response = [
                'status'=>  'fail',
                'message'=> 'Dr. Calculus stats engine not ready',
            ];
            die(json_encode($response));
        }
        die(json_encode(DrCalculusInvoke()));
    }
```

Метод `invoke()` определен как хэлпер в неймспейсе `Arris\DrCalculus`.

Что он делает? Определяет параметры печенек, проверяет их наличие и обновляет данные в БД. Возвращает array вида:
```
$response = [
                'id'    =>  $id,
                'type'  =>  $item_type,
                'status'=>  'ok'|'error'
                'message'=> 'ok'|$e->getMessage()
                'lid'   =>  $updateState['lid'],
                'errorCode' =>  $e->getCode(),  // в случае ошибки
                'errorMsg'  =>  $e->getMessage() // в случае ошибки
            ];
```