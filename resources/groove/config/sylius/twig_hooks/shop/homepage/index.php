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
                'theme_genres' => [
                    'template' => 'shop/homepage/genres.html.twig',
                    'priority' => 280,
                ],
                'theme_promise' => [
                    'template' => 'shop/homepage/promise.html.twig',
                    'priority' => 150,
                ],
                'theme_release' => [
                    'template' => 'shop/homepage/release.html.twig',
                    'priority' => -50,
                ],
                'latest_products' => [
                    'props' => [
                        'limit' => 8,
                        'template' => 'shop/product/common/list.html.twig',
                    ],
                ],
            ],
        ],
    ],
]);
