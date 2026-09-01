<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::table('payments',function(Blueprint $t){$t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();$t->dateTime('verified_at')->nullable();$t->text('verification_note')->nullable();});} public function down():void{Schema::table('payments',function(Blueprint $t){$t->dropConstrainedForeignId('verified_by');$t->dropColumn(['verified_at','verification_note']);});} };
