<?php

namespace App\Support;

use App\Models\Organization;

class TenantContext
{
    public static function setId(?int $tenantId): void
    {
        app()->instance('tenantId', $tenantId);
    }

    public static function id(): ?int
    {
        if (app()->bound('tenantId')) {
            $id = app('tenantId');
            return $id ? (int) $id : null;
        }

        return null;
    }

    public static function defaultId(): int
    {
        static $defaultId = null;

        if ($defaultId !== null) {
            return (int) $defaultId;
        }

        $defaultId = (int) (Organization::query()->where('slug', 'default')->value('id') ?? 0);

        return (int) $defaultId;
    }
}
