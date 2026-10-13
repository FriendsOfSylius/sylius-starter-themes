<?php

declare(strict_types=1);

namespace Sylius\Starter\Themes\Installer;

use Sylius\Starter\Core\Component\InstallerInterface;
use Sylius\Starter\Themes\Attribute\AsThemeInstaller;

final readonly class ThemeInstallerDescriptor
{
    public function __construct(
        public AsThemeInstaller $attribute,
        public \ReflectionFunction|InstallerInterface $installer,
    ) {}
}
