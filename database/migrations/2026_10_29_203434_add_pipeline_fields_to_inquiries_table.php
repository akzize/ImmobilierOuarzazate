<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. "Closed" is split into won and lost; earlier closed inquiries cannot be told apart, so they become lost.
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('source')->default('form')->after('lang');
            $table->dateTime('preferred_visit_at')->nullable()->after('message');
            $table->timestamp('contacted_at')->nullable()->after('admin_notes');
            $table->timestamp('closed_at')->nullable()->after('contacted_at');
        });

        DB::table('inquiries')->where('status', 'closed')->update(['status' => 'lost', 'closed_at' => DB::raw('updated_at')]);
        DB::table('inquiries')->where('status', '!=', 'new')->update(['contacted_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inquiries')->whereIn('status', ['won', 'lost'])->update(['status' => 'closed']);
        DB::table('inquiries')->where('status', 'visit_scheduled')->update(['status' => 'contacted']);

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['source', 'preferred_visit_at', 'contacted_at', 'closed_at']);
        });
    }
};
