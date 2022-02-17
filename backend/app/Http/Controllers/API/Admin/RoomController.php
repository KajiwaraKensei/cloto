<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Events\RoomUpdated;

class RoomController extends Controller
{
    // 背景保存ディレクトリ
    const BACKGROUND_STORE_DIR = 'public/system/room/';


    /** @var Room */
    protected $room;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Room $room)
    {
        $this->room = $room;
    }


    /**
     * 部屋データ一覧の取得
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json($this->room->all());
    }

    /**
     * 部屋データの更新
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room  $room  更新する部屋
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Room $room)
    {
        $data = $request->all();

        // 時間割データの型変換
        if (!empty($data['timetable'])) {
            $data['timetable'] = json_decode($data['timetable']);
        }

        // 背景の保存
        if (!empty($request->file('background'))) {
            // 保存処理
            $request->file('background')->storeAs(self::BACKGROUND_STORE_DIR, 'room_' . $room->id . '.png');
        }

        $result = $room->fill($data)->save();

        if (empty($result)) {
            return response()->json(['message' => '部屋データの更新に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        // 入室中のユーザーにリロードさせる
        broadcast(new RoomUpdated($room));
        return response()->json(['message' => '部屋データが更新されました。']);
    }
}
