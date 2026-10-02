<?php
// Metadata describing every editable table for the generic admin CRUD screens.

return [
    'user' => [
        'label' => 'Пользователи',
        'pk' => 'id_user',
        'order_by' => 'id_user DESC',
        'list_columns' => ['id_user', 'email', 'first_name', 'last_name', 'city', 'role', 'created_at'],
        'columns' => [
            'email'      => ['label' => 'Email', 'type' => 'email', 'required' => true],
            'first_name' => ['label' => 'Имя', 'type' => 'text', 'required' => true],
            'last_name'  => ['label' => 'Фамилия', 'type' => 'text', 'required' => true],
            'phone'      => ['label' => 'Телефон', 'type' => 'text'],
            'city'       => ['label' => 'Город', 'type' => 'text'],
            'role'       => ['label' => 'Роль', 'type' => 'enum', 'options' => ['customer', 'admin'], 'required' => true],
            'password'   => ['label' => 'Новый пароль (оставьте пустым, чтобы не менять)', 'type' => 'password', 'virtual' => true],
        ],
    ],
    'brands' => [
        'label' => 'Бренды',
        'pk' => 'id_brand',
        'order_by' => 'name ASC',
        'list_columns' => ['id_brand', 'name', 'country', 'decade_origin'],
        'columns' => [
            'name'          => ['label' => 'Название', 'type' => 'text', 'required' => true],
            'country'       => ['label' => 'Страна', 'type' => 'text'],
            'decade_origin' => ['label' => 'Десятилетие', 'type' => 'text'],
        ],
    ],
    'categories' => [
        'label' => 'Категории',
        'pk' => 'id_category',
        'order_by' => 'name ASC',
        'list_columns' => ['id_category', 'name', 'description'],
        'columns' => [
            'name'        => ['label' => 'Название', 'type' => 'text', 'required' => true],
            'description' => ['label' => 'Описание', 'type' => 'textarea'],
        ],
    ],
    'products' => [
        'label' => 'Товары',
        'pk' => 'id_product',
        'order_by' => 'id_product DESC',
        'list_columns' => ['id_product', 'name', 'price', 'size', 'condition', 'production_year', 'stock_quantity'],
        'columns' => [
            'name'            => ['label' => 'Название', 'type' => 'text', 'required' => true],
            'description'     => ['label' => 'Описание', 'type' => 'textarea'],
            'price'           => ['label' => 'Цена', 'type' => 'number', 'step' => '0.01', 'required' => true],
            'size'            => ['label' => 'Размер', 'type' => 'enum', 'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'], 'required' => true],
            'condition'       => ['label' => 'Состояние', 'type' => 'enum', 'options' => ['Excellent', 'Good', 'Fair'], 'required' => true],
            'production_year' => ['label' => 'Год выпуска', 'type' => 'number'],
            'stock_quantity'  => ['label' => 'Остаток на складе', 'type' => 'number', 'required' => true],
            'image_url'       => ['label' => 'Ссылка на изображение', 'type' => 'text'],
            'id_category'     => ['label' => 'Категория', 'type' => 'fk', 'fk_table' => 'categories', 'fk_pk' => 'id_category', 'fk_label' => 'name'],
            'id_brand'        => ['label' => 'Бренд', 'type' => 'fk', 'fk_table' => 'brands', 'fk_pk' => 'id_brand', 'fk_label' => 'name'],
        ],
    ],
    'orders' => [
        'label' => 'Заказы',
        'pk' => 'id_order',
        'order_by' => 'id_order DESC',
        'list_columns' => ['id_order', 'id_user', 'order_date', 'total_amount', 'shipping_method', 'status', 'tracking_number'],
        'columns' => [
            'id_user'          => ['label' => 'Покупатель', 'type' => 'fk', 'fk_table' => 'user', 'fk_pk' => 'id_user', 'fk_label' => 'email', 'required' => true],
            'total_amount'     => ['label' => 'Сумма заказа', 'type' => 'number', 'step' => '0.01', 'required' => true],
            'shipping_address' => ['label' => 'Адрес доставки', 'type' => 'text'],
            'shipping_method'  => ['label' => 'Способ доставки', 'type' => 'text'],
            'shipping_cost'    => ['label' => 'Стоимость доставки', 'type' => 'number', 'step' => '0.01'],
            'tracking_number'  => ['label' => 'Трек-номер', 'type' => 'text'],
            'status'           => ['label' => 'Статус', 'type' => 'enum', 'options' => ['processing', 'shipped', 'delivered', 'cancelled'], 'required' => true],
        ],
    ],
    'order_items' => [
        'label' => 'Позиции заказов',
        'pk' => 'id_order_item',
        'order_by' => 'id_order_item DESC',
        'list_columns' => ['id_order_item', 'id_order', 'id_product', 'quantity', 'price_at_time'],
        'columns' => [
            'id_order'      => ['label' => 'Заказ', 'type' => 'fk', 'fk_table' => 'orders', 'fk_pk' => 'id_order', 'fk_label' => 'id_order', 'required' => true],
            'id_product'    => ['label' => 'Товар', 'type' => 'fk', 'fk_table' => 'products', 'fk_pk' => 'id_product', 'fk_label' => 'name', 'required' => true],
            'quantity'      => ['label' => 'Количество', 'type' => 'number', 'required' => true],
            'price_at_time' => ['label' => 'Цена на момент заказа', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ],
    ],
    'reviews' => [
        'label' => 'Отзывы',
        'pk' => 'id_review',
        'order_by' => 'id_review DESC',
        'list_columns' => ['id_review', 'id_user', 'id_product', 'rating', 'created_at'],
        'columns' => [
            'id_user'    => ['label' => 'Покупатель', 'type' => 'fk', 'fk_table' => 'user', 'fk_pk' => 'id_user', 'fk_label' => 'email', 'required' => true],
            'id_product' => ['label' => 'Товар', 'type' => 'fk', 'fk_table' => 'products', 'fk_pk' => 'id_product', 'fk_label' => 'name', 'required' => true],
            'rating'     => ['label' => 'Оценка (1-5)', 'type' => 'number', 'required' => true],
            'comment'    => ['label' => 'Комментарий', 'type' => 'textarea'],
        ],
    ],
    'favorites' => [
        'label' => 'Избранное',
        'pk' => 'id_favorite',
        'order_by' => 'id_favorite DESC',
        'list_columns' => ['id_favorite', 'id_user', 'id_product'],
        'columns' => [
            'id_user'    => ['label' => 'Покупатель', 'type' => 'fk', 'fk_table' => 'user', 'fk_pk' => 'id_user', 'fk_label' => 'email', 'required' => true],
            'id_product' => ['label' => 'Товар', 'type' => 'fk', 'fk_table' => 'products', 'fk_pk' => 'id_product', 'fk_label' => 'name', 'required' => true],
        ],
    ],
];
