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
                'theme_offers' => [
                    'component' => 'sylius_shop:common:taxon_menu',
                    'props' => [
                        'template' => 'shop/homepage/offers.html.twig',
                    ],
                    'priority' => 200,
                ],
                'theme_promise' => [
                    'template' => 'shop/homepage/promise.html.twig',
                    'priority' => 150,
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
