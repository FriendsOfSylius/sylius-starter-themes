<?php

declare(strict_types=1);

namespace SyliusStarter\Themes\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SyliusStarter\Themes\ThemeBundle;

#[CoversClass(ThemeBundle::class)]
final class ThemeBundleTest extends TestCase
{
    /**
     * @param array<string, mixed>|null $appHookable
     * @param array<string, mixed>      $override
     * @param array<string, mixed>|null $expected
     */
    #[DataProvider('provideHookables')]
    public function testCompleteHookable(?array $appHookable, array $override, ?array $expected): void
    {
        static::assertSame($expected, ThemeBundle::completeHookable($appHookable, $override));
    }

    public static function provideHookables(): iterable
    {
        $latestProducts = [
            'component' => 'sylius_shop:product:list',
            'template' => null,
            'context' => [],
            'props' => ['limit' => 8, 'template' => '@SyliusShop/product/common/list.html.twig'],
            'configuration' => [],
            'priority' => 100,
            'type' => 'component',
            'enabled' => true,
            'condition' => null,
        ];

        yield 'a disabled hookable stays disabled' => [
            $latestProducts,
            ['enabled' => false],
            ['enabled' => false],
        ];

        yield 'a partial override keeps the app definition' => [
            $latestProducts,
            ['props' => ['limit' => 4]],
            [
                'component' => 'sylius_shop:product:list',
                'props' => ['limit' => 4, 'template' => '@SyliusShop/product/common/list.html.twig'],
                'priority' => 100,
            ],
        ];

        yield 'a template replaces a component' => [
            $latestProducts,
            ['template' => 'shop/homepage/latest.html.twig'],
            ['template' => 'shop/homepage/latest.html.twig', 'priority' => 100],
        ];

        yield 'a new hookable is kept as it is' => [
            null,
            ['template' => 'shop/homepage/promise.html.twig', 'priority' => 150],
            ['template' => 'shop/homepage/promise.html.twig', 'priority' => 150],
        ];

        yield 'an override of a missing hookable is dropped' => [
            null,
            ['priority' => 450],
            null,
        ];
    }

    public function testHookPrefix(): void
    {
        // sylius/theme-bundle rejects underscores in theme names, and the hook renderer reads the prefix from it.
        static::assertSame('retro-gaming:', ThemeBundle::hookPrefix('retro_gaming'));
        static::assertSame('sylius-starter/retro-gaming', ThemeBundle::themeName('retro_gaming'));
        static::assertMatchesRegularExpression('/^[a-zA-Z0-9\-]+\/[a-zA-Z0-9\-]+$/', ThemeBundle::themeName('retro_gaming'));
    }
}
