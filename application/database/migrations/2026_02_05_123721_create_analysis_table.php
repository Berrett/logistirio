<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id');
            $table->foreignId('ai_request_id');
            $table->string('identifier');
            $table->dateTime('date');
            $table->decimal('total_amount');
            $table->integer('tax');
            $table->decimal('tax_amount', 6);
            $table->decimal('net_price', 6);
            $table->decimal('gross_price', 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
