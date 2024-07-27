<?php

// вебхук для работы с REST API
define('WEBHOOK_URL', 'https://b24-u8i9wx.bitrix24.ru/rest/1/0oqwm1oftg1fmgf3/');

// функция для выполнения запросов к REST API
function callAPI($method, $params = []) {
    $url = WEBHOOK_URL . $method . '.json';
    $query = http_build_query($params);
    $response = file_get_contents($url . '?' . $query);
    return json_decode($response, true);
}

// узнать количество контактов с заполненным полем COMMENTS
$contacts = callAPI('crm.contact.list', [
    'filter' => ['!COMMENTS' => ''],
    'select' => ['ID', 'NAME', 'COMMENTS']
]);

$countContactsWithComments = isset($contacts['result']) ? count($contacts['result']) : 0;

// найти все сделки без контактов
$deals = callAPI('crm.deal.list', [
    'select' => ['ID', 'TITLE', 'CONTACT_ID']
]);

$dealsWithoutContacts = isset($deals['result']) ? array_filter($deals['result'], function($deal) {
    return empty($deal['CONTACT_ID']);
}) : [];

$countDealsWithoutContacts = count($dealsWithoutContacts);

// узнать сколько сделок в каждой из существующих направлений
$categories = callAPI('crm.dealcategory.list');

$dealCountsByCategory = [];
if (isset($categories['result'])) {
    foreach ($categories['result'] as $category) {
        $dealsInCategory = callAPI('crm.deal.list', [
            'filter' => ['CATEGORY_ID' => $category['ID']],
            'select' => ['ID']
        ]);
        $dealCountsByCategory[$category['NAME']] = isset($dealsInCategory['result']) ? count($dealsInCategory['result']) : 0;
    }
}

// подсчитать сумму значений поля "баллы" из всех элементов смарт-процесса
$smartProcessItems = callAPI('crm.item.list', [
    'entityTypeId' => 1038,
    'select' => ['ID', 'TITLE', 'ufCrm6_1721814262']
]);

$totalScores = 0;
if (isset($smartProcessItems['result']['items'])) {
    foreach ($smartProcessItems['result']['items'] as $item) {
        $score = isset($item['ufCrm6_1721814262']) ? $item['ufCrm6_1721814262'] : 0;
        if (is_numeric($score)) {
            $totalScores += $score;
        }
    }
}

// результат
echo "Результаты анализа CRM\n";
echo "=======================\n";

// контакты с заполненным полем COMMENTS
echo "Контакты с заполненным полем COMMENTS: $countContactsWithComments\n";

// сделки без контактов
echo "Сделки без контактов: $countDealsWithoutContacts\n";

// сделки в каждой из направлений
echo "Сделки в каждой из направлений:\n";
foreach ($dealCountsByCategory as $category => $count) {
    echo " - $category: $count сделок\n";
}

// значения поля "баллы" в смарт-процессе
echo "Сумма значений поля 'Баллы' в смарт-процессе: $totalScores\n";

?>
