<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $t) {
            $t->id();
            $t->string('type')->index();                 // automatic, manual, safety
            $t->string('scope');                         // database, full (database + uploaded files)
            $t->string('status')->index();               // in_progress, completed, failed
            $t->string('file_name')->nullable();
            $t->unsignedBigInteger('size_bytes')->default(0);
            $t->string('checksum', 64)->nullable();
            $t->unsignedInteger('tables_count')->default(0);
            $t->text('error')->nullable();
            $t->unsignedBigInteger('user_id')->nullable()->index();   // no foreign key: restores replace the users table
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('backups'); }
};
