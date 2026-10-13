<?php

declare(strict_types=1);

namespace Sylius\Starter\Themes;

use Castor\Attribute\AsListener;
use Castor\Event\AfterBootEvent;
use Sylius\Starter\Core\App;
use Sylius\Starter\Core\Component\CallableInstaller;
use Sylius\Starter\Core\Component\CallableRemover;
use Sylius\Starter\Core\Component\ComponentResolver;
use Sylius\Starter\Core\Installer\SyliusInstallerExtensions;
use Sylius\Starter\Core\Service\SyliusService;
use Sylius\Starter\Core\Task\TaskProviderRegistry;
use Sylius\Starter\Themes\Attribute\AsThemeInstaller;
use Sylius\Starter\Themes\Attribute\AsThemeRemover;
use Sylius\Starter\Themes\Installer\AuroraInstaller;
use Sylius\Starter\Themes\Installer\BlushInstaller;
use Sylius\Starter\Themes\Installer\CanvasInstaller;
use Sylius\Starter\Themes\Installer\LagoonInstaller;
use Sylius\Starter\Themes\Installer\PromptDarkThemeInstaller;
use Sylius\Starter\Themes\Installer\PromptLightThemeInstaller;
use Sylius\Starter\Themes\Installer\ThemeInstallerDescriptor;
use Sylius\Starter\Themes\Installer\ThemesSyliusInstallerExtension;
use Sylius\Starter\Themes\Installer\VoltInstaller;
use Sylius\Starter\Themes\Remover\AuroraRemover;
use Sylius\Starter\Themes\Remover\BlushRemover;
use Sylius\Starter\Themes\Remover\CanvasRemover;
use Sylius\Starter\Themes\Remover\LagoonRemover;
use Sylius\Starter\Themes\Remover\PromptDarkThemeRemover;
use Sylius\Starter\Themes\Remover\PromptLightThemeRemover;
use Sylius\Starter\Themes\Remover\ThemeRemoverDescriptor;
use Sylius\Starter\Themes\Remover\VoltRemover;
use Sylius\Starter\Themes\Tasks\ThemeTasks;

TaskProviderRegistry::register(
    'themes',
    static fn(SyliusService $service): iterable => (new ThemeTasks($service->getName(), $service->getDirectory()))(),
);

SyliusInstallerExtensions::register('themes', new ThemesSyliusInstallerExtension());

#[AsListener(AfterBootEvent::class)]
function initialize(AfterBootEvent $afterBootEvent): void
{
    Themes::addInstaller(new CanvasInstaller());
    Themes::addInstaller(new PromptDarkThemeInstaller());
    Themes::addInstaller(new PromptLightThemeInstaller());
    Themes::addInstaller(new BlushInstaller());
    Themes::addInstaller(new VoltInstaller());
    Themes::addInstaller(new LagoonInstaller());
    Themes::addInstaller(new AuroraInstaller());

    Themes::addRemover(new CanvasRemover());
    Themes::addRemover(new PromptDarkThemeRemover());
    Themes::addRemover(new PromptLightThemeRemover());
    Themes::addRemover(new BlushRemover());
    Themes::addRemover(new VoltRemover());
    Themes::addRemover(new LagoonRemover());
    Themes::addRemover(new AuroraRemover());

    foreach (ComponentResolver::candidates() as $reflection) {
        $descriptor = resolve_theme_installer($reflection);

        if (null !== $descriptor) {
            Themes::addInstaller($descriptor->installer instanceof \ReflectionFunction
                ? new CallableInstaller($descriptor->attribute->name, $descriptor->installer->getClosure())
                : $descriptor->installer);
        }

        $descriptor = resolve_theme_remover($reflection);

        if (null !== $descriptor) {
            Themes::addRemover($descriptor->remover instanceof \ReflectionFunction
                ? new CallableRemover($descriptor->attribute->name, $descriptor->remover->getClosure())
                : $descriptor->remover);
        }
    }
}

function resolve_theme_installer(\ReflectionFunction|\ReflectionClass $reflection): ?ThemeInstallerDescriptor
{
    $resolved = ComponentResolver::resolve($reflection, AsThemeInstaller::class);

    if (null === $resolved) {
        return null;
    }

    [$attribute, $installer] = $resolved;

    if ($installer instanceof \ReflectionFunction) {
        return new ThemeInstallerDescriptor($attribute, $installer);
    }

    return new ThemeInstallerDescriptor($attribute, new CallableInstaller($attribute->name, static fn(App $app) => $installer($app)));
}

function resolve_theme_remover(\ReflectionFunction|\ReflectionClass $reflection): ?ThemeRemoverDescriptor
{
    $resolved = ComponentResolver::resolve($reflection, AsThemeRemover::class);

    if (null === $resolved) {
        return null;
    }

    [$attribute, $remover] = $resolved;

    if ($remover instanceof \ReflectionFunction) {
        return new ThemeRemoverDescriptor($attribute, $remover);
    }

    return new ThemeRemoverDescriptor($attribute, new CallableRemover($attribute->name, static fn(App $app) => $remover($app)));
}
