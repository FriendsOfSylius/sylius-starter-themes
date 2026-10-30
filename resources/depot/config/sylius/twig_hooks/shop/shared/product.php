<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

// Product cards read like a trade catalogue line: the stock badge above the name, the reference under it, and an
// order button under the price.
return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            'sylius_shop.shared.product.card' => [
                'depot_order' => [
                    'template' => 'shop/shared/product/card/order.html.twig',
                    'priority' => -100,
                ],
            ],
            'sylius_shop.shared.product.card.details' => [
                'depot_stock' => [
                    'template' => 'shop/shared/product/card/details/stock.html.twig',
                    'priority' => 50,
                ],
                'depot_reference' => [
                    'template' => 'shop/shared/product/card/details/reference.html.twig',
                    'priority' => -50,
                ],
            ],
        ],
    ],
]);
