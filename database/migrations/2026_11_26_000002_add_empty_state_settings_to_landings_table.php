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
        Schema::table('landings', function (Blueprint $table) {
            if (! Schema::hasColumn('landings', 'nodata_image')) {
                $table->string('nodata_image')->nullable()->after('icon');
            }
            if (! Schema::hasColumn('landings', 'nodata_title')) {
                $table->string('nodata_title')->nullable()->after('nodata_image');
            }
            if (! Schema::hasColumn('landings', 'nodata_subtitle')) {
                $table->text('nodata_subtitle')->nullable()->after('nodata_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('landings', 'nodata_image')) {
                $columns[] = 'nodata_image';
            }
            if (Schema::hasColumn('landings', 'nodata_title')) {
                $columns[] = 'nodata_title';
            }
            if (Schema::hasColumn('landings', 'nodata_subtitle')) {
                $columns[] = 'nodata_subtitle';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
