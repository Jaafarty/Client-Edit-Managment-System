<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $usesMySql = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('edits', function (Blueprint $table) use ($usesMySql) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            if ($usesMySql) {
                $table->string('priority', 6);
                $table->string('status', 11);
            } else {
                $table->enum('priority', ['High', 'Medium', 'Low']);
                $table->enum('status', ['Pending', 'Confirmed', 'In Progress', 'Done', 'Cancelled']);
            }
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->date('due_date')->nullable();
        });

        if ($usesMySql) {
            // A required MySQL ENUM supplies its first value when omitted.
            $connection->statement(<<<'SQL'
                ALTER TABLE edits
                    ADD CONSTRAINT edits_priority_check CHECK (CAST(priority AS BINARY) IN ('High', 'Medium', 'Low')),
                    ADD CONSTRAINT edits_status_check CHECK (CAST(status AS BINARY) IN ('Pending', 'Confirmed', 'In Progress', 'Done', 'Cancelled'))
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('edits');
    }
};
