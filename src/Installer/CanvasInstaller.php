<?php

declare(strict_types=1);

namespace Sylius\Starter\Themes\Installer;

use Sylius\Starter\Core\App;
use Sylius\Starter\Core\Component\InstallerInterface;
use Sylius\Starter\Core\Storefront\Hero;
use Sylius\Starter\Core\Util\Assets;
use Sylius\Starter\Core\Util\Docker;
use Sylius\Starter\Core\Util\Javascript;
use Sylius\Starter\Core\Util\Symfony;
use Sylius\Starter\Core\Util\Yaml;

use function Castor\finder;
use function Castor\fs;

final class CanvasInstaller implements InstallerInterface
{
    public function name(): string
    {
        return 'canvas';
    }

    public function description(): ?string
    {
        return 'https://github.com/Sylius/Starter/blob/main/docs/themes/canvas.md';
    }

    public function __invoke(App $app): void
    {
        Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/twig_hooks/**/**.php');
        Hero::install($app);

        $resourcesDir = \dirname(__DIR__, 2) . '/resources/canvas';

        foreach (finder()->files()->in($resourcesDir)->files() as $file) {
            fs()->copy($resourcesDir . '/' . $file->getRelativePathname(), $app->directory() . '/' . $file->getRelativePathname());
        }

        Javascript::addImport($app, 'assets/shop/entrypoint.js', './styles/app.scss');

        Assets::install($app);
        Assets::build($app);
        Symfony::cacheClear($app);
    }
}
