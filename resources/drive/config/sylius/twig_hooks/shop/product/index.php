<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            'sylius_shop.product.index.content.body.sidebar' => [
                'taxonomy' => [
                    'props' => [
                        'template' => 'shop/product/index/taxonomy.html.twig',
                    ],
                ],
            ],
        ],
    ],
]);
