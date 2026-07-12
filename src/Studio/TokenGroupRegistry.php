<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Studio;

use Symfinity\UiDesigner\Authoring\DtcgRolePathMapper;
use Symfinity\UiKernel\Token\SemanticColorMap;

final class TokenGroupRegistry
{
    public const GROUP_BRAND = 'brand';

    public const GROUP_BASE = 'base';

    public const GROUP_OTHER = 'other';

    public const GROUP_SIDEBAR = 'sidebar';

    public const GROUP_CHART = 'chart';

    /** @var list<string> */
    private const BRAND_ROLES = ['primary', 'secondary', 'accent'];

    /** @var list<string> */
    private const BASE_ROLES = ['neutral', 'surface', 'surface_elevated', 'text', 'text_muted', 'border'];

    /** @var list<string> */
    private const OTHER_ROLES = ['danger', 'success', 'warning', 'info', 'focus', 'overlay'];

    /** @var list<string> */
    private const SIDEBAR_PREFIXES = ['sidebar_'];

    /** @var list<string> */
    private const CHART_PREFIXES = ['chart_'];

    public function __construct(
        private readonly DtcgRolePathMapper $pathMapper,
    ) {
    }

    /**
     * @return list<string>
     */
    public function orderedRoles(?\Symfinity\UiDesigner\Model\DesignerDraft $draft = null): array
    {
        $available = array_flip($this->pathMapper->allRoles());
        $ordered = [];

        foreach (array_keys(SemanticColorMap::ROLE_TO_CSS) as $role) {
            if (isset($available[$role])) {
                $ordered[] = $role;
            }
        }

        foreach ($this->groups($draft) as $group) {
            foreach ($group['roles'] as $role) {
                if (!in_array($role, $ordered, true)) {
                    $ordered[] = $role;
                }
            }
        }

        return $ordered;
    }

    /**
     * @return list<array{id: string, label: string, roles: list<string>, default_open: bool}>
     */
    public function groups(?\Symfinity\UiDesigner\Model\DesignerDraft $draft = null): array
    {
        $allRoles = $this->pathMapper->allRoles();
        $groups = [
            [
                'id' => self::GROUP_BRAND,
                'label' => 'Brand',
                'roles' => $this->filterExisting(self::BRAND_ROLES, $allRoles),
                'default_open' => true,
            ],
            [
                'id' => self::GROUP_BASE,
                'label' => 'Base',
                'roles' => $this->filterExisting(self::BASE_ROLES, $allRoles),
                'default_open' => true,
            ],
            [
                'id' => self::GROUP_OTHER,
                'label' => 'Other',
                'roles' => $this->filterExisting(self::OTHER_ROLES, $allRoles),
                'default_open' => false,
            ],
        ];

        $sidebarRoles = $this->rolesMatchingPrefixes($allRoles, self::SIDEBAR_PREFIXES);
        if ($sidebarRoles !== []) {
            $groups[] = [
                'id' => self::GROUP_SIDEBAR,
                'label' => 'Sidebar',
                'roles' => $sidebarRoles,
                'default_open' => false,
            ];
        }

        $chartRoles = $this->rolesMatchingPrefixes($allRoles, self::CHART_PREFIXES);
        if ($chartRoles !== []) {
            $groups[] = [
                'id' => self::GROUP_CHART,
                'label' => 'Chart',
                'roles' => $chartRoles,
                'default_open' => false,
            ];
        }

        return array_values(array_filter(
            $groups,
            static fn (array $group): bool => $group['roles'] !== [],
        ));
    }

    /**
     * @return list<string>
     */
    public function allRoles(): array
    {
        return $this->pathMapper->allRoles();
    }

    /**
     * @return array<string, string>
     */
    public function roleCssVars(): array
    {
        return SemanticColorMap::ROLE_TO_CSS;
    }

    /**
     * @param list<string> $needles
     * @param list<string> $haystack
     *
     * @return list<string>
     */
    private function filterExisting(array $needles, array $haystack): array
    {
        return array_values(array_filter(
            $needles,
            static fn (string $role): bool => in_array($role, $haystack, true),
        ));
    }

    /**
     * @param list<string> $roles
     * @param list<string> $prefixes
     *
     * @return list<string>
     */
    private function rolesMatchingPrefixes(array $roles, array $prefixes): array
    {
        return array_values(array_filter(
            $roles,
            static function (string $role) use ($prefixes): bool {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($role, $prefix)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }
}
