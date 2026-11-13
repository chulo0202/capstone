<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoDuplicateScopes('applications', ['farmer_id', 'program_id']);
        $this->assertNoDuplicateScopes('distributions', ['farmer_id', 'program_id']);
        $this->assertNoDuplicateScopes('recommendations', ['farmer_id', 'program_id']);
        $this->assertNoDuplicateScopes('eligibility_rules', ['program_id', 'program_benefit_id']);

        $mismatchedRules = DB::table('eligibility_rules')
            ->join('program_benefits', 'eligibility_rules.program_benefit_id', '=', 'program_benefits.id')
            ->whereNotNull('eligibility_rules.program_benefit_id')
            ->whereColumn('eligibility_rules.program_id', '!=', 'program_benefits.program_id')
            ->exists();

        if ($mismatchedRules) {
            throw new RuntimeException(
                'Benefit eligibility rules reference benefits owned by another program. Resolve these records before retrying; no data was changed.'
            );
        }

        Schema::table('programs', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
        });

        Schema::table('program_benefits', function (Blueprint $table) {
            $table->timestamp('eligibility_configured_at')->nullable();
        });

        DB::table('program_benefits')
            ->whereNull('archived_at')
            ->update(['eligibility_configured_at' => now()]);

        Schema::table('program_benefits', function (Blueprint $table) {
            $table->unique(['id', 'program_id'], 'program_benefits_id_program_unique');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('program_benefit_id')->nullable()->after('program_id');
            $this->addBenefitScopeColumn($table);
            $table->unique(
                ['farmer_id', 'program_id', 'program_benefit_scope_id'],
                'applications_farmer_program_scope_unique'
            );
            $table->foreign(
                ['program_benefit_id', 'program_id'],
                'applications_benefit_program_fk'
            )->references(['id', 'program_id'])->on('program_benefits')->restrictOnDelete();
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->dropUnique(['farmer_id', 'program_id']);

            $table->foreignId('program_benefit_id')->nullable()->after('program_id');
            $table->foreignId('application_id')->nullable()->after('program_benefit_id');

            $this->addBenefitScopeColumn($table);

            $table->unique(
                ['farmer_id', 'program_id', 'program_benefit_scope_id'],
                'distributions_farmer_program_scope_unique'
            );
            $table->unique('application_id', 'distributions_application_unique');

            $table->foreign('farmer_id')
                ->references('id')->on('farmers')->restrictOnDelete();

            $table->foreign(
                ['program_benefit_id', 'program_id'],
                'distributions_benefit_program_fk'
            )->references(['id', 'program_id'])->on('program_benefits')->restrictOnDelete();

            $table->foreign('application_id', 'distributions_application_fk')
                ->references('id')->on('applications')->restrictOnDelete();
        });

        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->dropUnique(['farmer_id', 'program_id']);

            $table->foreignId('program_benefit_id')->nullable()->after('program_id');
            $this->addBenefitScopeColumn($table);

            $table->unique(
                ['farmer_id', 'program_id', 'program_benefit_scope_id'],
                'recommendations_farmer_program_scope_unique'
            );

            $table->foreign('farmer_id')
                ->references('id')->on('farmers')->restrictOnDelete();

            $table->foreign(
                ['program_benefit_id', 'program_id'],
                'recommendations_benefit_program_fk'
            )->references(['id', 'program_id'])->on('program_benefits')->restrictOnDelete();
        });

        Schema::table('eligibility_rules', function (Blueprint $table) {
            $table->dropForeign(['program_benefit_id']);
            $this->addBenefitScopeColumn($table);
            $table->unique(
                ['program_id', 'program_benefit_scope_id'],
                'eligibility_rules_program_scope_unique'
            );
            $table->foreign(
                ['program_benefit_id', 'program_id'],
                'eligibility_rules_benefit_program_fk'
            )->references(['id', 'program_id'])->on('program_benefits')->restrictOnDelete();
        });

        foreach (['applications', 'distributions', 'recommendations', 'program_benefits', 'eligibility_rules'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['program_id']);
                $table->foreign('program_id')->references('id')->on('programs')->restrictOnDelete();
            });
        }

        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->foreign('farmer_id')
                ->references('id')->on('farmers')->restrictOnDelete();
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropForeign(['released_by']);
            $table->foreign('released_by')
                ->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('programs')->whereNotNull('archived_at')->exists()) {
            throw new RuntimeException(
                'Cannot roll back program archive state without losing archived program history.'
            );
        }

        if (DB::table('program_benefits')->whereNotNull('eligibility_configured_at')->exists()) {
            throw new RuntimeException(
                'Cannot roll back benefit configuration state without losing eligibility decisions.'
            );
        }

        foreach (['applications', 'distributions', 'recommendations'] as $tableName) {
            if (DB::table($tableName)->whereNotNull('program_benefit_id')->exists()) {
                throw new RuntimeException(
                    "Cannot roll back benefit-scoped {$tableName}; rows reference specific benefits."
                );
            }
        }

        if (DB::table('distributions')->whereNotNull('application_id')->exists()) {
            throw new RuntimeException(
                'Cannot roll back application-linked distributions without losing release history.'
            );
        }

        foreach (['applications', 'distributions', 'recommendations'] as $tableName) {
            $duplicates = DB::table($tableName)
                ->select('farmer_id', 'program_id')
                ->groupBy('farmer_id', 'program_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($duplicates) {
                throw new RuntimeException(
                    "Cannot restore the former program-scoped unique key because {$tableName} contains multiple benefit records."
                );
            }
        }

        foreach (['applications', 'distributions', 'recommendations', 'program_benefits', 'eligibility_rules'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['program_id']);
                $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            });
        }

        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->foreign('farmer_id')
                ->references('id')->on('farmers')->cascadeOnDelete();
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->dropForeign(['released_by']);

            $table->foreign('released_by')
                ->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
        });

        Schema::table('eligibility_rules', function (Blueprint $table) {
            $table->dropForeign('eligibility_rules_benefit_program_fk');
            $table->dropUnique('eligibility_rules_program_scope_unique');
            $table->dropColumn('program_benefit_scope_id');
            $table->foreign('program_benefit_id')
                ->references('id')->on('program_benefits')->nullOnDelete();
        });

        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropForeign('recommendations_benefit_program_fk');
            $table->dropUnique('recommendations_farmer_program_scope_unique');
            $table->dropColumn(['program_benefit_scope_id', 'program_benefit_id']);
            $table->unique(['farmer_id', 'program_id']);

            $table->foreign('farmer_id')
                ->references('id')->on('farmers')->cascadeOnDelete();
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropForeign('distributions_application_fk');
            $table->dropForeign('distributions_benefit_program_fk');
            $table->dropUnique('distributions_application_unique');
            $table->dropUnique('distributions_farmer_program_scope_unique');
            $table->dropColumn(['program_benefit_scope_id', 'application_id', 'program_benefit_id']);
            $table->unique(['farmer_id', 'program_id']);

            $table->foreign('farmer_id')
                ->references('id')->on('farmers')->cascadeOnDelete();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign('applications_benefit_program_fk');
            $table->dropUnique('applications_farmer_program_scope_unique');
            $table->dropColumn(['program_benefit_scope_id', 'program_benefit_id']);
        });

        Schema::table('program_benefits', function (Blueprint $table) {
            $table->dropUnique('program_benefits_id_program_unique');
            $table->dropColumn('eligibility_configured_at');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }

    private function addBenefitScopeColumn(Blueprint $table): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $table->bigInteger('program_benefit_scope_id')
                ->virtualAs('COALESCE(program_benefit_id, 0)');

            return;
        }

        $table->bigInteger('program_benefit_scope_id')
            ->storedAs('COALESCE(program_benefit_id, 0)');
    }

    private function assertNoDuplicateScopes(string $table, array $columns): void
    {
        $duplicates = DB::table($table)
            ->select($columns)
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException(
                "Conflicting historical {$table} scopes exist. Resolve the duplicate records before retrying; no data was changed."
            );
        }
    }
};
