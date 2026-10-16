<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            'sylius_shop.product.show.content.info.summary' => [
                'prices' => [
                    'priority' => 450,
                ],
                'drive_pickup' => [
                    'template' => 'shop/product/show/pickup.html.twig',
                    'priority' => 50,
                ],
            ],
        ],
    ],
]);
