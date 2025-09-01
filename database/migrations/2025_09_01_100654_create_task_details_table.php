<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_details', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('task_id')->unsigned()->nullable();
            $table->string('document_link', 250);
            $table->float('score')->unsigned()->default(0);

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign(columns: 'task_id')->references('id')->on('tasks')->onDelete('set null');

            $table->unique(['user_id', 'task_id'], 'task_details_user_task_unique');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_details');
    }
};
