<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->optimizeCourses();
        $this->optimizeEnrollments();
    }

    public function down(): void
    {
        $this->rollbackCourses();
        $this->rollbackEnrollments();
    }

    private function optimizeCourses(): void
    {
        if (!Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'status') && !$this->indexExists('courses', 'courses_status_id_index')) {
                $table->index(['status', 'id'], 'courses_status_id_index');
            }

            if (Schema::hasColumn('courses', 'is_paid') && Schema::hasColumn('courses', 'status') && !$this->indexExists('courses', 'courses_paid_status_id_index')) {
                $table->index(['is_paid', 'status', 'id'], 'courses_paid_status_id_index');
            }

            if (Schema::hasColumn('courses', 'updated_at') && !$this->indexExists('courses', 'courses_updated_at_id_index')) {
                $table->index(['updated_at', 'id'], 'courses_updated_at_id_index');
            }
        });
    }

    private function optimizeEnrollments(): void
    {
        if (!Schema::hasTable('enrollments')) {
            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'user_id') && Schema::hasColumn('enrollments', 'course_id') && !$this->indexExists('enrollments', 'enrollments_user_course_id_index')) {
                $table->index(['user_id', 'course_id', 'id'], 'enrollments_user_course_id_index');
            }

            if (Schema::hasColumn('enrollments', 'expiry_date') && !$this->indexExists('enrollments', 'enrollments_expiry_date_id_index')) {
                $table->index(['expiry_date', 'id'], 'enrollments_expiry_date_id_index');
            }
        });
    }

    private function rollbackCourses(): void
    {
        if (!Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            foreach ([
                'courses_status_id_index',
                'courses_paid_status_id_index',
                'courses_updated_at_id_index',
            ] as $index) {
                if ($this->indexExists('courses', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function rollbackEnrollments(): void
    {
        if (!Schema::hasTable('enrollments')) {
            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            foreach ([
                'enrollments_user_course_id_index',
                'enrollments_expiry_date_id_index',
            ] as $index) {
                if ($this->indexExists('enrollments', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection();
        $prefixedTable = $connection->getTablePrefix() . $table;
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $result = $connection->select("SHOW INDEX FROM `{$prefixedTable}` WHERE Key_name = ?", [$index]);

            return !empty($result);
        }

        if ($driver === 'sqlite') {
            $result = $connection->select("PRAGMA index_list('{$prefixedTable}')");

            return collect($result)->contains(fn ($item) => ($item->name ?? null) === $index);
        }

        return false;
    }
};
