<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

// Shipped with every theme: the light / dark / auto switcher.
return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            // Before the stylesheets, so that the page is painted in the right scheme.
            'sylius_shop.base#stylesheets' => [
                'themes_color_scheme' => [
                    'template' => 'shop/shared/layout/color_scheme/init.html.twig',
                    'priority' => 100,
                ],
            ],
            // Between the account links and the cart.
            'sylius_shop.base.header.content' => [
                'themes_color_scheme' => [
                    'template' => 'shop/shared/layout/color_scheme/switcher.html.twig',
                    'priority' => 150,
                ],
            ],
        ],
    ],
]);
