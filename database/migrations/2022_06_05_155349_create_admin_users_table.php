<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
			//$table->foreign('id')->references('user_id')->on('admin_user_role')->onDelete('cascade');
			//$table->foreign('id')->references('user_id')->on('admin_user_permission')->onDelete('cascade');
			
            $table->string('name')->nullable();
            $table->string('pseudoname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
			$table->string('locale', 3)->nullable();
			$table->boolean('is_main_admin')->default(false);
			$table->unsignedInteger('_sort')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('admin_users');
    }
};