<?php

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
        Schema::create('stored_cards', function (Blueprint $table) {
            $table->id();
            $table->string('vendor')->index();
            $table->string('buyer_userid')->index();
            $table->string('gateway_buyer_uuid')->nullable()->index();
            $table->string('card_token', 255);
            $table->string('card_brand')->nullable();
            $table->string('last4_digits', 4)->nullable();
            $table->unsignedTinyInteger('expiry_month')->nullable();
            $table->unsignedSmallInteger('expiry_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['vendor', 'buyer_userid', 'card_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stored_cards');
    }
};
