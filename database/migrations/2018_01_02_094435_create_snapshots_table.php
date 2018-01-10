<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 监控结果（每一次的快照）
 * Class CreateSnapshotsTable
 */
class CreateSnapshotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('monitor_id');
            $table->boolean('is_match')->default(false); // 是否属于异常通知，例如包含内容已经找到，is_error为1的话，is_notice必定为1，因为错误肯定要提示的。
            $table->boolean('is_error')->default(false); // 是否请求错误，没有得到响应头的情况，都属于此类，包含 dns解析错误、TCP连接无法建立/响应前中断、得到的响应不符合HTTP规范等
            $table->text('error_message')->nullable(); // 自定义拼合文本，人类可读
            $table->string('http_status_code')->nullable();
            $table->binary('headers'); // 包含首行，如果有跳转，将包含多次的header。
            $table->binary('body_content');

            $table->unsignedInteger('time_total')->default(0); // 从dns解析，到获取到最后一字节的时间
            $table->unsignedInteger('time_dns')->default(0);
            $table->unsignedInteger('time_connection')->default(0);
            $table->unsignedInteger('time_transport')->default(0);
            // 暂未设计“已通知”字段，因为，通知一旦发起，是保证成功的，即使未成功，也是放在队列中和失败队列任务中。暂不考虑永远失败的情况。

            $table->timestamps();

            $table->foreign('monitor_id')->references('id')->on('monitors');
        });

        // 注意： header和body使用二进制储存，用以兼容所有编码格式，header中也可能出现非ASCII编码。
        \DB::statement('ALTER TABLE snapshots MODIFY headers BLOB NULL;');
	    \DB::statement('ALTER TABLE snapshots MODIFY body_content MEDIUMBLOB NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('snapshots', function (Blueprint $table) {
            $table->dropForeign(['monitor_id']);
        });
        Schema::dropIfExists('snapshots');
    }
}
