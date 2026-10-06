<?php

namespace App\Twig;

use App\Service\ResourceTypeCatalog;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ResourceTypeExtension extends AbstractExtension
{
    public function __construct(private readonly ResourceTypeCatalog $resourceTypeCatalog)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('reservation_navigation_types', [$this->resourceTypeCatalog, 'navigationTypes']),
        ];
    }
}