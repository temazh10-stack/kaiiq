<?php
// Read-only SQL views, one per required report query.

return [
    'vw_products_in_stock' => [
        'label' => 'Запрос 1 — Товары в наличии',
        'description' => 'Все товары, которые сейчас есть в наличии (stock_quantity > 0).',
    ],
    'vw_customers_by_city' => [
        'label' => 'Запрос 2 — Покупатели из города',
        'description' => 'Покупатели из определённого города (пример: Остин).',
    ],
    'vw_popular_brands' => [
        'label' => 'Запрос 3 — Самые популярные бренды',
        'description' => 'Бренды по количеству проданных единиц товара во всех заказах.',
    ],
    'vw_products_by_year' => [
        'label' => 'Запрос 4 — Одежда определённого года',
        'description' => 'Товары определённого года выпуска (пример: 1998).',
    ],
    'vw_dresses' => [
        'label' => 'Запрос 5 — Категория «Платья»',
        'description' => 'Все товары категории «Платья».',
    ],
];
