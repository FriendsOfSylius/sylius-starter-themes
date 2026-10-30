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
                // The departments read the menu taxons themselves, as photo cards: they replace the roundels of the
                // imported collections, which list the same taxons.
                'import_collections' => [
                    'enabled' => false,
                ],
                'theme_aisles' => [
                    'component' => 'sylius_shop:common:taxon_menu',
                    'props' => [
                        'template' => 'shop/homepage/aisles.html.twig',
                    ],
                    'priority' => 250,
                ],
                'latest_products' => [
                    'props' => [
                        'limit' => 4,
                        'template' => 'shop/product/common/list.html.twig',
                    ],
                ],
                'theme_promise' => [
                    'template' => 'shop/homepage/promise.html.twig',
                    'priority' => -50,
                ],
                'theme_account' => [
                    'template' => 'shop/homepage/account.html.twig',
                    'priority' => -100,
                ],
            ],
        ],
    ],
]);
