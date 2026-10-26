<?php

declare(strict_types=1);

namespace SyliusStarter\Themes\Tasks;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use SyliusStarter\Core\App;
use SyliusStarter\Core\Component\ComponentChoices;
use SyliusStarter\Core\Util\Assets;
use SyliusStarter\Core\Util\Docker;
use SyliusStarter\Core\Util\Symfony;
use SyliusStarter\Themes\ThemeBundle;
use SyliusStarter\Themes\Themes;

use function Castor\io;

final class ThemeTasks
{
    public function __construct(
        private readonly string $name,
        private readonly string $directory,
    ) {}

    public function __invoke(): iterable
    {
        $app = new App($this->name, $this->directory);

        yield [
            'task' => new AsTask('setup', 'sylius:theme', 'Setup themes', ['setup-theme']),
            'function' => static function (
                #[AsArgument]
                ?string $theme = null,
                #[AsOption(description: 'Where to install the theme: "flat" (over the app, for every channel) or "theme-bundle" (next to the other themes, picked by each channel)')]
                string $target = 'flat',
            ) use ($app): void {
                if (!\in_array($target, ['flat', ThemeBundle::TARGET], true)) {
                    io()->error(\sprintf('Unknown target "%s": use "flat" or "%s".', $target, ThemeBundle::TARGET));

                    return;
                }

                $themeInstallers = Themes::installers();
                $availableThemes = array_keys($themeInstallers);
                $availableThemes[] = 'default';
                sort($availableThemes);

                $installers = array_map(
                    static fn(callable $installer): callable => static fn() => $installer($app),
                    $themeInstallers,
                );

                $installers['default'] = static function () use ($app): void {
                    Assets::build($app);
                    // The removed theme files (hooks, hook renderer...) are still in the compiled container.
                    Symfony::cacheClear($app);
                };

                $choices = ComponentChoices::build($themeInstallers);
                $choices['default'] = 'default';
                ksort($choices);

                $removers = array_map(
                    static fn(callable $remover): callable => static fn() => $remover($app),
                    Themes::removers(),
                );

                $removers['default'] = static function (): void {};

                if (null === $theme) {
                    $theme = io()->choice(
                        'Which theme would you like to use?',
                        $choices,
                    );
                }

                if (!isset($installers[$theme])) {
                    io()->error(\sprintf('Unknown theme installer "%s", skipping.', $theme));

                    return;
                }

                if (ThemeBundle::TARGET === $target) {
                    if (!ThemeBundle::supports($theme)) {
                        io()->error(\sprintf('The "%s" theme cannot be installed with the theme-bundle target.', $theme));

                        return;
                    }

                    // The hooks and templates of a flat theme would apply to every channel.
                    foreach ($availableThemes as $themeToRemove) {
                        ($removers[$themeToRemove] ?? static function (): void {})();
                    }

                    ThemeBundle::install($app, $theme);
                    Assets::install($app);
                    Assets::build($app);
                    Symfony::cacheClear($app);

                    io()->success(\sprintf(
                        '"%s" theme has been installed next to the other themes. Pick it for a channel with "castor sylius:theme:channel <channel> %s".',
                        $theme,
                        $theme,
                    ));

                    return;
                }

                // A flat theme applies to every channel: drop the themes picked by channels.
                foreach (ThemeBundle::installed($app) as $installedTheme) {
                    ThemeBundle::remove($app, $installedTheme);
                }

                ThemeBundle::teardown($app);

                $themesToRemove = array_diff($availableThemes, $theme ? [$theme] : []);

                foreach ($themesToRemove as $themeToRemove) {
                    if (!isset($removers[$themeToRemove])) {
                        io()->warning(\sprintf('Unknown theme remover "%s", skipping.', $themeToRemove));

                        continue;
                    }
                    $removers[$themeToRemove]();
                }

                $installers[$theme]();

                io()->success(\sprintf('"%s" theme has been installed successfully.', $theme));
            },
        ];

        yield [
            'task' => new AsTask('channel', 'sylius:theme', 'Pick the theme of a channel, among the themes installed with the theme-bundle target'),
            'function' => static function (
                #[AsArgument(description: 'Channel code')]
                string $channel,
                #[AsArgument(description: 'Theme, or "default" for the app look')]
                string $theme = 'default',
            ) use ($app): void {
                if (!preg_match('/^[A-Za-z0-9_.-]+$/', $channel)) {
                    io()->error(\sprintf('Invalid channel code "%s".', $channel));

                    return;
                }

                $installedThemes = ThemeBundle::installed($app);

                if ('default' !== $theme && !\in_array($theme, $installedThemes, true)) {
                    io()->error(\sprintf(
                        'The "%s" theme is not installed with the theme-bundle target. Installed themes: %s.',
                        $theme,
                        [] === $installedThemes ? 'none' : implode(', ', $installedThemes),
                    ));

                    return;
                }

                $themeName = 'default' === $theme ? 'NULL' : \sprintf("'%s'", ThemeBundle::themeName($theme));

                Docker::run($app, \sprintf(
                    'bin/console dbal:run-sql "UPDATE sylius_channel SET theme_name = %s WHERE code = \'%s\'"',
                    $themeName,
                    $channel,
                ));

                io()->success(\sprintf('The "%s" channel now uses the "%s" theme.', $channel, $theme));
            },
        ];
    }
}
