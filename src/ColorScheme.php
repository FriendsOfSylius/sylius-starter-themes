<?php

declare(strict_types=1);

namespace SyliusStarter\Themes;

use SyliusStarter\Core\App;
use Symfony\Component\Finder\Finder;

use function Castor\fs;

/**
 * Light / dark / auto switcher shared by every theme.
 *
 * A theme compiles its styles twice, scoped on <html data-bs-theme="light|dark"> (see
 * docs/themes/color-schemes.md); this ships the script that sets that attribute and the
 * header switcher. Every theme installer calls install(), every theme remover calls remove():
 * removers run before the installer of the new theme, so the files are always there.
 */
final readonly class ColorScheme
{
    public static function resourcesDir(): string
    {
        return \dirname(__DIR__) . '/resources/color_scheme';
    }

    public static function install(App $app): void
    {
        foreach ((new Finder())->files()->in(self::resourcesDir()) as $file) {
            fs()->copy($file->getPathname(), $app->directory() . '/' . $file->getRelativePathname(), true);
        }
    }

    public static function remove(App $app): void
    {
        foreach ((new Finder())->files()->in(self::resourcesDir()) as $file) {
            fs()->remove($app->directory() . '/' . $file->getRelativePathname());
        }
    }
}
