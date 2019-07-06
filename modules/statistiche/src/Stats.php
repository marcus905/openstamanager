<?php

namespace Modules\Statistiche;

use ArrayObject;
use DateTime;

class Stats
{
    public static function monthly($original, $start, $end)
    {
        // Copia dei dati
        $array = new ArrayObject($original);
        $data = $array->getArrayCopy();

        // Ordinamento
        array_multisort(array_column($data, 'year'), SORT_ASC,
            array_column($data, 'month'), SORT_ASC,
            $data);

        // Differenza delle date in mesi
        $d1 = new DateTime($start);
        $d2 = new DateTime($end);
        $count = $d1->diff($d2)->m + ($d1->diff($d2)->y * 12) + 1;

        $month = intval($d1->format('m')) - 1;
        for ($i = 0; $i < $count; ++$i) {
            $month = $month % 12;

            if (!isset($data[$i]) || intval($data[$i]['month']) != $month + 1) {
                array_splice($data, $i, 0, [[
                    'totale' => 0,
                ]]);
            }

            ++$month;
        }

        return $data;
    }
}
