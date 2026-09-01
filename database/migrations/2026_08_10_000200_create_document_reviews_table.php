<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('document_reviews',function(Blueprint $t){$t->id();$t->foreignUlid('applicant_document_id')->constrained()->cascadeOnDelete();$t->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();$t->string('before_status',30);$t->string('after_status',30);$t->text('note')->nullable();$t->timestamps();});} public function down():void{Schema::dropIfExists('document_reviews');} };
