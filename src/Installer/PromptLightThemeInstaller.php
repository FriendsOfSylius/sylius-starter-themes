<?php

declare(strict_types=1);

namespace Sylius\Starter\Themes\Installer;

use Sylius\Starter\Core\App;
use Sylius\Starter\Core\Component\InstallerInterface;
use Sylius\Starter\Core\Storefront\Brand;
use Sylius\Starter\Core\Storefront\Hero;
use Sylius\Starter\Core\Util\Assets;
use Sylius\Starter\Core\Util\Javascript;
use Sylius\Starter\Core\Util\Symfony;
use Sylius\Starter\Core\Util\Yaml;

use function Castor\finder;
use function Castor\fs;

final class PromptLightThemeInstaller implements InstallerInterface
{
    public function name(): string
    {
        return 'prompt_light';
    }

    public function description(): ?string
    {
        return 'https://github.com/sylius-starter/sylius-starter/blob/main/docs/themes/prompt_light.md';
    }

    public function __invoke(App $app): void
    {
        Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/twig_hooks/**/**.php');
        Hero::install($app);
        Brand::install($app);

        $resourcesDir = \dirname(__DIR__, 2) . '/resources/prompt_light';

        foreach (finder()->files()->in($resourcesDir)->files() as $file) {
            fs()->copy($resourcesDir . '/' . $file->getRelativePathname(), $app->directory() . '/' . $file->getRelativePathname());
        }

        Javascript::addImport($app, 'assets/shop/entrypoint.js', './styles/app.scss');

        Assets::install($app);
        Assets::build($app);
        Symfony::cacheClear($app);
    }
}
