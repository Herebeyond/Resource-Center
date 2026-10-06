<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResourceTypeCatalog
{
    private const ROUTES = [
        'salle' => 'room',
        'vehicule' => 'vehicle',
        'portable' => 'laptop',
        'audiovisuel' => 'projector',
        'equipement' => 'equipment',
    ];

    private const LABEL_KEYS = [
        'salle' => 'resource.rooms',
        'vehicule' => 'resource.vehicles',
        'portable' => 'resource.laptops',
        'audiovisuel' => 'resource.projectors',
        'equipement' => 'resource.equipment',
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function navigationTypes(): array
    {
        $types = $this->connection->fetchAllAssociative(
            "SELECT name, description
             FROM resource_types
             WHERE name NOT IN ('portable', 'audiovisuel')
             ORDER BY name"
        );

        return array_map(function (array $type): array {
            $name = (string) $type['name'];
            $fallback = (string) ($type['description'] ?: $name);
            $translationKey = self::LABEL_KEYS[$name] ?? null;

            return [
                'route' => $this->routeForName($name),
                'label_en' => $translationKey ? $this->translator->trans($translationKey, [], 'messages', 'en') : $fallback,
                'label_fr' => $translationKey ? $this->translator->trans($translationKey, [], 'messages', 'fr') : $fallback,
            ];
        }, $types);
    }

    public function resolveName(string $route): ?string
    {
        $knownName = array_search($route, self::ROUTES, true);
        if ($knownName !== false) {
            return $knownName;
        }

        foreach ($this->connection->fetchFirstColumn('SELECT name FROM resource_types') as $name) {
            if ($this->routeForName((string) $name) === $route) {
                return (string) $name;
            }
        }

        return null;
    }

    private function routeForName(string $name): string
    {
        return self::ROUTES[$name] ?? (new AsciiSlugger())->slug($name)->lower()->toString();
    }
}