<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Studio;

use Symfinity\UiDesigner\Authoring\DtcgRolePathMapper;
use Symfinity\UiKernel\Token\SemanticColorMap;

final class TokenGroupRegistry
{
    public const GROUP_CORE = 'core';

    public const GROUP_ADVANCED = 'advanced';

    /** @var list<string> */
    private const CORE_ROLES = [
        'primary',
        'secondary',
        'surface',
        'text',
        'text_muted',
        'border',
        'danger',
    ];

    public function __construct(
        private readonly DtcgRolePathMapper $pathMapper,
    ) {
    }

    /**
     * @return list<array{id: string, label: string, roles: list<string>}>
     */
    public function groups(): array
    {
        $allRoles = $this->pathMapper->allRoles();
        $advancedRoles = array_values(array_diff($allRoles, self::CORE_ROLES));

        return [
            [
                'id' => self::GROUP_CORE,
                'label' => 'Core',
                'roles' => self::CORE_ROLES,
            ],
            [
                'id' => self::GROUP_ADVANCED,
                'label' => 'Advanced',
                'roles' => $advancedRoles,
            ],
        ];
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
}
