<?php

function fun_snake_routes(): array
{
    return [
        'home' => [
            'path' => '/',
            'template' => __DIR__ . '/../index.php',
        ],
    ];
}
