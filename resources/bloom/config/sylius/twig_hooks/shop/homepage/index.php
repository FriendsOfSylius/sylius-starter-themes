<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            'sylius_shop.homepage.index' => [
                'latest_deals' => [
                    'enabled' => false,
                ],
                'new_collection' => [
                    'enabled' => false,
                ],
                'theme_ritual' => [
                    'template' => 'shop/homepage/ritual.html.twig',
                    'priority' => 150,
                ],
                'theme_shelves' => [
                    'template' => 'shop/homepage/shelves.html.twig',
                    'priority' => -50,
                ],
                'latest_products' => [
                    'props' => [
                        'limit' => 4,
                        'template' => 'shop/product/common/list.html.twig',
                    ],
                ],
            ],
        ],
    ],
]);
