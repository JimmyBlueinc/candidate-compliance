<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $allowed = ['pending', 'verified', 'rejected', 'needs_correction', 'expired'];
        $this->applyStatusConstraint($allowed);
    }

    public function down(): void
    {
        DB::table('candidate_credentials')
            ->where('status', 'needs_correction')
            ->update(['status' => 'rejected']);

        $allowed = ['pending', 'verified', 'rejected', 'expired'];
        $this->applyStatusConstraint($allowed);
    }

    private function applyStatusConstraint(array $allowed): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $allowedSql = implode(', ', array_map(static fn (string $s): string => '"' . $s . '"', $allowed));

            DB::statement('DROP TABLE IF EXISTS candidate_credentials_new');
            DB::statement('
                CREATE TABLE candidate_credentials_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id INTEGER NOT NULL,
                    candidate_id INTEGER NOT NULL,
                    credential_type_id INTEGER NOT NULL,
                    document_path VARCHAR(255) NULL,
                    issued_at DATETIME NULL,
                    expires_at DATETIME NULL,
                    status VARCHAR(255) NOT NULL DEFAULT "pending" CHECK(status IN (' . $allowedSql . ')),
                    verified_at DATETIME NULL,
                    verified_by INTEGER NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY (tenant_id) REFERENCES organizations(id) ON DELETE CASCADE,
                    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
                    FOREIGN KEY (credential_type_id) REFERENCES credential_types(id) ON DELETE CASCADE,
                    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ');

            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_candidate_id_index ON candidate_credentials_new(tenant_id, candidate_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_credential_type_id_index ON candidate_credentials_new(tenant_id, credential_type_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_status_index ON candidate_credentials_new(tenant_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_expires_at_index ON candidate_credentials_new(tenant_id, expires_at)');

            DB::statement('
                INSERT INTO candidate_credentials_new (
                    id,
                    tenant_id,
                    candidate_id,
                    credential_type_id,
                    document_path,
                    issued_at,
                    expires_at,
                    status,
                    verified_at,
                    verified_by,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    tenant_id,
                    candidate_id,
                    credential_type_id,
                    document_path,
                    issued_at,
                    expires_at,
                    CASE
                        WHEN status IN (' . $allowedSql . ') THEN status
                        ELSE "pending"
                    END,
                    verified_at,
                    verified_by,
                    created_at,
                    updated_at
                FROM candidate_credentials
            ');

            DB::statement('DROP TABLE candidate_credentials');
            DB::statement('ALTER TABLE candidate_credentials_new RENAME TO candidate_credentials');
            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_candidate_id_index ON candidate_credentials(tenant_id, candidate_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_credential_type_id_index ON candidate_credentials(tenant_id, credential_type_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_status_index ON candidate_credentials(tenant_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS candidate_credentials_tenant_id_expires_at_index ON candidate_credentials(tenant_id, expires_at)');
            return;
        }

        if ($driver === 'pgsql') {
            $allowedSql = implode(', ', array_map(static fn (string $s): string => "'" . $s . "'", $allowed));
            DB::statement('ALTER TABLE candidate_credentials ALTER COLUMN status TYPE VARCHAR(255)');
            DB::statement("ALTER TABLE candidate_credentials ALTER COLUMN status SET DEFAULT 'pending'");
            DB::statement('ALTER TABLE candidate_credentials ALTER COLUMN status SET NOT NULL');
            DB::statement('ALTER TABLE candidate_credentials DROP CONSTRAINT IF EXISTS candidate_credentials_status_check');
            DB::statement("ALTER TABLE candidate_credentials ADD CONSTRAINT candidate_credentials_status_check CHECK (status IN ({$allowedSql}))");
            return;
        }

        $allowedSql = implode("','", array_map(static fn (string $s): string => str_replace("'", "''", $s), $allowed));
        DB::statement("ALTER TABLE candidate_credentials MODIFY COLUMN status ENUM('{$allowedSql}') NOT NULL DEFAULT 'pending'");
    }
};
