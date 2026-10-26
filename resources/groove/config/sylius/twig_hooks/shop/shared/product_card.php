<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            // Square sleeves: the card image uses the Groove thumbnail filter (config/packages/groove_imagine.yaml).
            'sylius_shop.shared.product.card.details' => [
                'image' => [
                    'template' => 'shop/shared/product/card/details/image.html.twig',
                ],
            ],
        ],
    ],
]);
