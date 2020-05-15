<?php

namespace Arris\DrCalculus;

use Exception;

interface DrCalculusFunctionsInterface {

    function invoke():array;
    function prepareDataForMorrisStatview(array $data):array;

}

if (!function_exists('Arris\DrCalculus\invoke')) {

    /**
     * Проверяет параметры в $_REQUEST и делает вызов updateVisitCount() с нужными параметрами
     *
     * @NB: Возможно, это костыль, потому что используется для сокращения дублирующегося кода в
     * SteamBoatEngine/ajax_site_stats (легаси)
     * и
     * коллбэке вызова с мобильной версии POST /ajax/stats:updateVisitCount/ { }
     *
     *  {
    id: <int>,
    item_type: <string>,
    cookie_name: <string>
    },
     *
     * @return array
     */
    function invoke()
    {
        try {
            if (DrCalculus::$is_engine_disabled)
                throw new \Exception('Dr.Calculus stats engine not ready', 999);

            $id = intval($_REQUEST['id']);

            if (intval($_REQUEST['id']) == 0)
                throw new Exception('Неправильный ID элемента', 0);

            $item_type = $_REQUEST['item_type'];
            if (!in_array($item_type, DrCalculus::$allowed_item_types))
                throw new Exception('Неправильный ID семейства', 1);

            $cookie_prefix = $_REQUEST['cookie_name'];

            if (isset($_SESSION[ $cookie_prefix ][ $id ]))
                throw new Exception('Страницу уже посещали', 2);

            $updateState = DrCalculus::updateVisitCount($id, $item_type);

            if (!$updateState['state'])
                throw new Exception("Ошибка вставки данных в БД", 3);

            $response = [
                'status'=>  'ok',
                'message'=> 'ok',
                'id'    =>  $id,
                'type'  =>  $item_type,
                'lid'   =>  $updateState['lid'],
            ];
        } catch (Exception $e) {
            $response = [
                'status'    =>  'error',
                'message'   =>  $e->getMessage(),
                'errorCode' =>  $e->getCode(),
                'errorMsg'  =>  $e->getMessage()
            ];
        }

        return $response;
    }

}

if (!function_exists('Arris\DrCalculus\prepareDataForMorrisStatview')) {

    /**
     * Готовит данные для отображения библиотекой Morris.JS
     *
     * @param array $data
     * @return array
     */
    function prepareDataForMorrisStatview(array $data):array
    {
        if (empty($data)) return [];

        $export = [];
        $visit_total = 0;
        foreach ($data as $row) {
            $export[] = [
                'date'  =>  date('d.m.Y', strtotime($row['event_date'])),
                'value' =>  $row['event_count']
            ];
            $visit_total += $row['event_count'];
        }

        return [ $export, $visit_total ];
    }
}

# -eof-