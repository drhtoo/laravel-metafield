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
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->morphs('object');
            $table->string('key', 255);
            $table->longText('value');
            if (config('metafield.timestamps', true)) {
                $table->timestamps();
                if (config('metafield.use_soft_delete', false)) {
                    $table->softDeletes();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
