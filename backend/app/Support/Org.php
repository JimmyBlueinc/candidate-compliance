<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Http\Request;

class Org
{
    public static function id(Request $request): ?int
    {
        $id = $request->attributes->get('organization_id');
        return $id ? (int) $id : null;
    }

    public static function model(Request $request): ?Organization
    {
        $org = $request->attributes->get('organization');
        return $org instanceof Organization ? $org : null;
    }
}
