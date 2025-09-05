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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('user_id')->unsigned()->nullable();
            $table->smallInteger('student_user_id')->unsigned()->nullable();
            $table->bigInteger('schedule_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign(columns: 'student_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign(columns: 'schedule_id')->references('id')->on('schedules')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
