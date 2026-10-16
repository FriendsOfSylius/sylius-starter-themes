<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            'sylius_shop.base.header' => [
                'top_bar' => [
                    'template' => 'shop/shared/layout/base/header/top_bar.html.twig',
                ],
                'content' => [
                    'template' => 'shop/shared/layout/base/header/content.html.twig',
                ],
            ],
            'sylius_shop.base.header.top_bar' => [
                'info_message' => [
                    'enabled' => false,
                ],
            ],
            'sylius_shop.base.header.content' => [
                'logo' => [
                    'template' => 'shop/shared/layout/header/logo.html.twig',
                ],
                'drive_search' => [
                    'component' => 'sylius_shop:common:taxon_menu',
                    'props' => [
                        'template' => 'shop/shared/layout/base/header/content/search.html.twig',
                    ],
                    'priority' => 250,
                ],
                'drive_slot' => [
                    'template' => 'shop/shared/layout/base/header/content/slot.html.twig',
                    'priority' => 225,
                ],
                'cart' => [
                    'component' => 'sylius_shop:cart:widget',
                    'props' => [
                        'template' => 'shop/shared/components/header/cart.html.twig',
                    ],
                ],
                'taxon_hamburger' => [
                    'template' => '@SyliusShop/shared/layout/base/header/content/taxon_hamburger.html.twig',
                    'enabled' => true,
                ],
            ],
            'sylius_shop.base.header.navbar' => [
                'menu' => [
                    'props' => [
                        'template' => 'shop/shared/layout/base/header/navbar/menu.html.twig',
                    ],
                ],
            ],
        ],
    ],
]);
