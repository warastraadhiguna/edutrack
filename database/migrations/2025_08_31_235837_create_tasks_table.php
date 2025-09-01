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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('schedule_id')->unsigned()->nullable();
            $table->string('name', 191);
            $table->float('percentage')->unsigned()->default(0);
            $table->tinyInteger('index')->unsigned()->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign(columns: 'schedule_id')->references('id')->on('schedules')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
