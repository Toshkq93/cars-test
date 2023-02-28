<?php
const START_DAY_TIME = '07:00';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=autos', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
$query = $pdo->query('select * from autos WHERE A_PALLETSFORMATION IS NOT NULL');

$info_cars = [];

while ($row = $query->fetchObject()) {
    $row->A_PALLETSFORMATION = json_decode($row->A_PALLETSFORMATION);
    $info_cars[] = $row;
}

$detailStatistic = getDetailStatisticByEmployees($info_cars);
$statisticByEmployee = getStatisticByEmployees($info_cars);
?>
<table border="1" style="text-align: center">
    <caption>Cтатистика</caption>
    <tr>
        <th>Код сотрудника</th>
        <th>Количество машин</th>
        <th>Количество сформированных паллет</th>
    </tr>
    <?php foreach ($statisticByEmployee as $badge => $info): ?>
        <tr>
            <td><?= $badge ?></td>
            <td><?= $info['count_pallets'] ?></td>
            <td><?= count($info['cars']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>


<table border="1" style="text-align: center">
    <caption>Детальная статистика</caption>
    <tr>
        <th>ID авто</th>
        <th>Номер авто</th>
        <th>Код сотрудника</th>
        <th>Количество паллетов</th>
        <th>Время старта машины (unix)</th>
        <th>Фактическая дата смены</th>
    </tr>
    <?php foreach ($detailStatistic as $info): ?>
        <tr>
            <td><?= $info['car_id'] ?></td>
            <td><?= $info['car_number'] ?></td>
            <td><?= $info['code_employee'] ?></td>
            <td><?= $info['count_pallets'] ?></td>
            <td><?= $info['date_start'] ?></td>
            <td><?= $info['date_end'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php
function getStatisticByEmployees($info_cars)
{
    $result = [];

    foreach ($info_cars as $info_car) {
        $pallets = $info_car->A_PALLETSFORMATION;

        foreach ($pallets as $pallet) {
            if (!empty($pallet->badge)) {
                $result[$pallet->badge]['count_pallets'] += $pallet->pallets;
                $result[$pallet->badge]['cars'][] = $info_car->A_NOMER;

                if (isset($pallet->sort_dop[0])) {
                    $helpEmployee = $pallet->sort_dop[0];
                    $result[$helpEmployee->badge]['count_pallets'] += $helpEmployee->pallets;
                    $result[$helpEmployee->badge]['cars'][] = $info_car->A_NOMER;
                }
            }
        }
    }

    return $result;
}

function getDetailStatisticByEmployees($info_cars)
{
    $result = [];

    foreach ($info_cars as $info_car) {
        $pallets = $info_car->A_PALLETSFORMATION;
        foreach ($pallets as $pallet) {
            if (isset($pallet->badge)) {
                $date = date_create(date('Y-m-d', $info_car->A_STARTTIME));
                $newTime = date('H:i', $info_car->A_STARTTIME);
                if ($newTime < START_DAY_TIME) {
                    $date = date_modify($date, '-1 day');
                }

                $result[$info_car->A_ID . '.' . $info_car->A_NOMER] = [
                    'car_id'        => $info_car->A_ID,
                    'car_number'    => $info_car->A_NOMER,
                    'code_employee' => $pallet->badge,
                    'date_start'    => $info_car->A_STARTTIME,
                    'count_pallets' => $pallet->pallets ?? 0,
                    'date_end'      => $date->format('Y-m-d')
                ];

                if (isset($pallet->sotr_dop)) {
                    $secondEmployee = $pallet->sort_dop[0];

                    if (isset($secondEmployee->badge)) {
                        $result[$info_car->A_ID . '.' . $info_car->A_NOMER] = [
                            'car_id'        => $info_car->A_ID,
                            'car_number'    => $info_car->A_NOMER,
                            'code_employee' => $secondEmployee->badge,
                            'date_start'    => $info_car->A_STARTTIME,
                            'count_pallets' => $pallet->pallets ?? 0,
                            'date_end'      => $date->format('Y-m-d')
                        ];
                    }
                }
            }
        }
    }

    return $result;
}
?>