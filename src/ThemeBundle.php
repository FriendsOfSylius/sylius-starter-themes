<?php

declare(strict_types=1);

namespace SyliusStarter\Themes;

use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use SyliusStarter\Core\App;
use SyliusStarter\Core\Storefront\Brand;
use SyliusStarter\Core\Storefront\Hero;
use SyliusStarter\Core\Util\Docker;
use SyliusStarter\Core\Util\Yaml;
use Symfony\Component\Finder\Finder;

use function Castor\fs;

/**
 * The theme-bundle install target: every theme lives in its own Sylius theme, and each channel picks one.
 *
 * The flat target copies a theme over the app (one theme for every channel). This one installs it next to
 * the others, from the same resources:
 *  - templates and translations go to themes/<theme>/ (sylius/theme-bundle, "sylius-starter/<theme>");
 *  - the twig hooks are generated in config/sylius/twig_hooks/themes/<theme>/ with their hook names prefixed by
 *    "<theme>:", rendered by App\Themes\ThemeHookRenderer for the channels using the theme only. Twig Hooks
 *    merges the hookables of several hook names shallowly at runtime, so each hookable a theme overrides is
 *    completed with the app definition it overrides, as the Symfony config merges them under one name;
 *  - the styles go to assets/themes/<theme>/, built as the "theme-<theme>" Encore entry and linked after the
 *    app styles;
 *  - the color scheme switcher is installed in every theme, so the channels without a theme keep the Sylius
 *    look. The other config files (filter sets...) are shared by the app.
 *
 * install() and remove() do not build the assets nor clear the cache: the caller does it once for all the
 * themes it changes.
 */
final readonly class ThemeBundle
{
    public const TARGET = 'theme-bundle';

    public const THEMES_DIR = 'themes';

    public const ASSETS_DIR = 'assets/themes';

    public const HOOKS_DIR = 'config/sylius/twig_hooks/themes';

    private const HOOKS_SOURCE_DIR = 'config/sylius/twig_hooks';

    private const WEBPACK_ANCHOR = '  const appShopConfig = await Encore.getWebpackConfig();';

    private const WEBPACK_MARKER = '// sylius-starter/themes: one Encore entry per theme installed with the theme-bundle target.';

    /** The themes this target can install: the ones whose resources ship with this package. */
    public static function supports(string $theme): bool
    {
        return is_dir(self::resourcesDir($theme));
    }

    /**
     * @return list<string>
     */
    public static function installed(App $app): array
    {
        $directory = $app->directory() . '/' . self::THEMES_DIR;

        if (!is_dir($directory)) {
            return [];
        }

        $themes = [];

        foreach ((new Finder())->directories()->in($directory)->depth(0) as $themeDirectory) {
            if (is_file($themeDirectory->getPathname() . '/composer.json')) {
                $themes[] = $themeDirectory->getFilename();
            }
        }

        sort($themes);

        return $themes;
    }

    /** The name sylius/theme-bundle gives the theme, stored in sylius_channel.theme_name. */
    public static function themeName(string $theme): string
    {
        return 'sylius-starter/' . $theme;
    }

    public static function hookPrefix(string $theme): string
    {
        return $theme . ':';
    }

    public static function install(App $app, string $theme): void
    {
        if (!self::supports($theme)) {
            throw new \InvalidArgumentException(\sprintf('The "%s" theme cannot be installed with the theme-bundle target.', $theme));
        }

        self::setup($app);
        self::remove($app, $theme);

        $themeDir = $app->directory() . '/' . self::THEMES_DIR . '/' . $theme;
        $assetsDir = $app->directory() . '/' . self::ASSETS_DIR . '/' . $theme;
        $hooksDir = $app->directory() . '/' . self::HOOKS_DIR . '/' . $theme;

        /** @var array<string, string> $hooksFiles path in the hooks directory => source file */
        $hooksFiles = [];

        foreach ([self::resourcesDir($theme), ColorScheme::resourcesDir()] as $resourcesDir) {
            foreach ((new Finder())->files()->in($resourcesDir) as $file) {
                $path = $file->getRelativePathname();

                match (true) {
                    str_starts_with($path, 'templates/'), str_starts_with($path, 'translations/') => fs()->copy($file->getPathname(), $themeDir . '/' . $path, true),
                    str_starts_with($path, 'assets/shop/') => fs()->copy($file->getPathname(), $assetsDir . '/' . substr($path, \strlen('assets/shop/')), true),
                    str_starts_with($path, self::HOOKS_SOURCE_DIR . '/') => $hooksFiles[substr($path, \strlen(self::HOOKS_SOURCE_DIR . '/'))] = $file->getPathname(),
                    default => fs()->copy($file->getPathname(), $app->directory() . '/' . $path, true),
                };
            }
        }

        fs()->dumpFile($themeDir . '/composer.json', json_encode([
            'name' => self::themeName($theme),
            'description' => \sprintf('The "%s" storefront theme of sylius-starter/themes, installed with the theme-bundle target.', $theme),
            'extra' => [
                'sylius-theme' => [
                    'title' => ucwords(str_replace('_', ' ', $theme)),
                ],
            ],
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) . "\n");

        fs()->dumpFile($assetsDir . '/entrypoint.js', "import './styles/app.scss';\n");

        // The app hooks, without the ones of this theme (removed above).
        $appHooks = self::resolvedHooks($app);

        foreach ($hooksFiles as $path => $source) {
            fs()->dumpFile($hooksDir . '/' . $path, self::themeHooksFile($theme, $path, $source, $appHooks));
        }

        fs()->dumpFile($hooksDir . '/stylesheets.php', self::stylesheetsHooks($theme));
    }

    public static function remove(App $app, string $theme): void
    {
        fs()->remove([
            $app->directory() . '/' . self::THEMES_DIR . '/' . $theme,
            $app->directory() . '/' . self::ASSETS_DIR . '/' . $theme,
            $app->directory() . '/' . self::HOOKS_DIR . '/' . $theme,
        ]);

        if (!self::supports($theme)) {
            return;
        }

        // The shared config files of the theme (filter sets...).
        foreach ((new Finder())->files()->in(self::resourcesDir($theme))->path('config/')->notPath(self::HOOKS_SOURCE_DIR . '/') as $file) {
            fs()->remove($app->directory() . '/' . $file->getRelativePathname());
        }
    }

    /**
     * Installs what every theme of this target needs: the theme-aware hook renderer, the Encore entries and
     * the storefront slots. Idempotent.
     */
    public static function setup(App $app): void
    {
        Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/twig_hooks/**/**.php');
        Hero::install($app);
        Brand::install($app);

        foreach ((new Finder())->files()->in(self::sharedResourcesDir()) as $file) {
            fs()->copy($file->getPathname(), $app->directory() . '/' . $file->getRelativePathname(), true);
        }

        self::addWebpackEntries($app);
    }

    /**
     * Undoes setup() once no theme of this target is left (the storefront slots stay: other packages use them).
     */
    public static function teardown(App $app): void
    {
        if ([] !== self::installed($app)) {
            return;
        }

        foreach ((new Finder())->files()->in(self::sharedResourcesDir()) as $file) {
            fs()->remove($app->directory() . '/' . $file->getRelativePathname());
        }

        self::removeWebpackEntries($app);

        // The empty directories this target created (themes/ ships with the app).
        foreach ([self::ASSETS_DIR, self::HOOKS_DIR, 'src/Themes', 'templates/themes'] as $directory) {
            $path = $app->directory() . '/' . $directory;

            if (is_dir($path) && [] === array_diff(scandir($path) ?: [], ['.', '..'])) {
                fs()->remove($path);
            }
        }
    }

    /**
     * The config of a theme hooks file ("sylius_shop.homepage.index" => "cellar:sylius_shop.homepage.index"),
     * each hookable completed with the app definition it overrides.
     *
     * @param array<string, array<string, array<string, mixed>>> $appHooks
     */
    public static function themeHooksFile(string $theme, string $path, string $source, array $appHooks): string
    {
        $hooks = [];

        foreach (self::readHooks($source) as $hookName => $hookables) {
            foreach ($hookables as $hookableName => $override) {
                $hookable = self::completeHookable($appHooks[$hookName][$hookableName] ?? null, $override);

                // Overrides a hookable of a package that is not installed: nothing to render.
                if (null !== $hookable) {
                    $hooks[self::hookPrefix($theme) . $hookName][$hookableName] = $hookable;
                }
            }
        }

        $config = self::export(['sylius_twig_hooks' => ['hooks' => $hooks]]);

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace Symfony\\Component\\DependencyInjection\\Loader\\Configurator;

            // Generated by sylius-starter/themes from {$path} of the "{$theme}" theme: do not edit, reinstall the
            // theme instead. Hook names are prefixed with "{$theme}:", and each hookable the theme overrides is
            // completed with the app definition it overrides.
            return App::config({$config});

            PHP;
    }

    /**
     * Twig Hooks merges the hookables of several hook names shallowly (even a null priority wins), so the theme
     * hookable carries the whole definition, merged like the Symfony config merges the same hook name.
     *
     * @param array<string, mixed>|null $appHookable
     * @param array<string, mixed>      $override
     *
     * @return array<string, mixed>|null
     */
    public static function completeHookable(?array $appHookable, array $override): ?array
    {
        if (false === ($override['enabled'] ?? true)) {
            return ['enabled' => false];
        }

        $hookable = $appHookable ?? [];
        unset($hookable['type']);

        if (isset($override['template'])) {
            $hookable['component'] = null;
            $hookable['props'] = [];
        }

        if (isset($override['component'])) {
            $hookable['template'] = null;
        }

        foreach ($override as $key => $value) {
            $hookable[$key] = \is_array($value) && \is_array($hookable[$key] ?? null) ? array_replace($hookable[$key], $value) : $value;
        }

        if (!isset($hookable['template']) && !isset($hookable['component']) && false !== ($hookable['enabled'] ?? true)) {
            return null;
        }

        return array_filter(
            $hookable,
            static fn(mixed $value, string $key): bool => null !== $value && [] !== $value && !('enabled' === $key && true === $value),
            \ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * The hooks of the app, as the Symfony config resolves them.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function resolvedHooks(App $app): array
    {
        $file = 'var/theme_bundle_hooks.json';

        Docker::run($app, 'bin/console debug:config sylius_twig_hooks hooks --format=json > ' . $file);

        $output = fs()->readFile($app->directory() . '/' . $file);
        fs()->remove($app->directory() . '/' . $file);

        // The command prints a title before the JSON.
        $start = strpos($output, '{');

        return false === $start ? [] : json_decode(substr($output, $start), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * The hooks of a twig hooks config file: return App::config([...]) of literal values.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function readHooks(string $file): array
    {
        $statements = (new ParserFactory())->createForNewestSupportedVersion()->parse(fs()->readFile($file)) ?? [];
        $config = (new NodeFinder())->findFirst(
            $statements,
            static fn(Node $node): bool => $node instanceof Node\Expr\Array_
                && [] !== array_filter($node->items, static fn(Node\ArrayItem $item): bool => $item->key instanceof Node\Scalar\String_ && 'sylius_twig_hooks' === $item->key->value),
        );

        if (!$config instanceof Node\Expr\Array_) {
            throw new \RuntimeException(\sprintf('No twig hooks config found in "%s".', $file));
        }

        return (new ConstExprEvaluator())->evaluateDirectly($config)['sylius_twig_hooks']['hooks'] ?? [];
    }

    private static function export(mixed $value, int $depth = 0): string
    {
        if (!\is_array($value)) {
            return var_export($value, true);
        }

        if ([] === $value) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth + 1);
        $lines = [];

        foreach ($value as $key => $item) {
            $lines[] = $indent . (array_is_list($value) ? '' : var_export($key, true) . ' => ') . self::export($item, $depth + 1) . ',';
        }

        return "[\n" . implode("\n", $lines) . "\n" . str_repeat('    ', $depth) . ']';
    }

    private static function stylesheetsHooks(string $theme): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace Symfony\\Component\\DependencyInjection\\Loader\\Configurator;

            // Generated by sylius-starter/themes: the styles of the "{$theme}" theme, after the app styles.
            return App::config([
                'sylius_twig_hooks' => [
                    'hooks' => [
                        '{$theme}:sylius_shop.base#stylesheets' => [
                            'themes_styles' => [
                                'template' => 'themes/stylesheets.html.twig',
                                'configuration' => [
                                    'entry' => 'theme-{$theme}',
                                ],
                                'priority' => -100,
                            ],
                        ],
                    ],
                ],
            ]);

            PHP;
    }

    private static function addWebpackEntries(App $app): void
    {
        $file = $app->directory() . '/webpack.config.mjs';
        $content = fs()->readFile($file);

        if (str_contains($content, self::WEBPACK_MARKER)) {
            return;
        }

        if (!str_contains($content, self::WEBPACK_ANCHOR)) {
            throw new \RuntimeException('Unable to add the theme entries to webpack.config.mjs: the app shop config was not found.');
        }

        fs()->dumpFile($file, str_replace(self::WEBPACK_ANCHOR, self::webpackEntries() . self::WEBPACK_ANCHOR, $content));
    }

    private static function removeWebpackEntries(App $app): void
    {
        $file = $app->directory() . '/webpack.config.mjs';

        if (!is_file($file)) {
            return;
        }

        $content = fs()->readFile($file);

        if (str_contains($content, self::webpackEntries())) {
            fs()->dumpFile($file, str_replace(self::webpackEntries(), '', $content));
        }
    }

    private static function webpackEntries(): string
    {
        $marker = self::WEBPACK_MARKER;

        return <<<JS
              {$marker}
              const themesDir = path.resolve(__dirname, './assets/themes');
              for (const theme of fs.existsSync(themesDir) ? fs.readdirSync(themesDir) : []) {
                if (fs.existsSync(path.join(themesDir, theme, 'entrypoint.js'))) {
                  Encore.addEntry(`theme-\${theme}`, `./assets/themes/\${theme}/entrypoint.js`);
                }
              }


            JS;
    }

    private static function resourcesDir(string $theme): string
    {
        return \dirname(__DIR__) . '/resources/' . $theme;
    }

    /** Outside resources/, which only holds themes (one directory each). */
    private static function sharedResourcesDir(): string
    {
        return \dirname(__DIR__) . '/theme_bundle';
    }
}
