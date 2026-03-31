<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = Illuminate\Support\Facades\DB::select('PRAGMA table_info(activity_logs)');
foreach ($cols as $c) {
    echo $c->name . ' notnull=' . $c->notnull . ' default=' . ($c->dflt_value ?? 'null') . PHP_EOL;
}

echo "\nFILLABLE:\n";
$log = new App\Models\ActivityLog();
print_r($log->getFillable());

echo "\nTEST CREATE (expect old_action present):\n";
$u = App\Models\User::query()->first();
if ($u) {
    $row = App\Models\ActivityLog::create([
        'tenant_id' => null,
        'organization_id' => null,
        'user_id' => $u->id,
        'old_action' => 'event',
        'entity_type' => 'debug',
        'entity_id' => 1,
        'event' => 'DebugEvent',
        'source' => 'system',
        'data' => [],
        'description' => 'Debug',
    ]);
    echo 'CREATED_ID=' . $row->id . PHP_EOL;
    echo 'CREATED_OLD_ACTION=' . ($row->old_action ?? 'null') . PHP_EOL;
} else {
    echo "No users found; skipping create test.\n";
}
