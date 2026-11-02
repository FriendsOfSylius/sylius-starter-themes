<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

// Product cards read like a line of a parts catalogue: the name, the part reference under it, the price and an order
// button.
return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            'sylius_shop.shared.product.card' => [
                'torque_order' => [
                    'template' => 'shop/shared/product/card/order.html.twig',
                    'priority' => -100,
                ],
            ],
            'sylius_shop.shared.product.card.details' => [
                'name' => [
                    'template' => 'shop/shared/product/card/details/name.html.twig',
                ],
                'torque_reference' => [
                    'template' => 'shop/shared/product/card/details/reference.html.twig',
                    'priority' => -50,
                ],
            ],
        ],
    ],
]);
