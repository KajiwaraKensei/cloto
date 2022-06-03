<?php

use Illuminate\Database\Migrations\Migration;
use App\Database\Blueprint;
use App\Facades\Schema;

class CreatePostMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_message', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')
                ->constrained()
                ->cascadeOnDelete();        // チャンネルID
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();      // ユーザーID
            $table->string('type');         // 問い合わせタイプ
            $table->string('content');           // 問い合わせ内容
            $table->dateTimes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('talk');
    }
}
