<?php

declare(strict_types=1);

namespace Sylius\Starter\Themes\Remover;

use Sylius\Starter\Core\Component\RemoverInterface;
use Sylius\Starter\Themes\Attribute\AsThemeRemover;

final readonly class ThemeRemoverDescriptor
{
    public function __construct(
        public AsThemeRemover $attribute,
        public \ReflectionFunction|RemoverInterface $remover,
    ) {}
}
