<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PostMessage;
use App\Models\Channel;
use App\Events\PostMessaged;


class PostMessageController extends Controller
{
    /** @var PostMessage */
    protected $post_message;

    /** @var Channel */
    protected $channel;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PostMessage $dm, Channel $channel)
    {
        $this->middleware(function ($request, $next) {
            $this->auth = Auth::user();
            return $next($request);
        });

        $this->post_message = $dm;
        $this->channel = $channel;
    }

    /**
     * 問い合わせの投稿
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // チャンネルIDを取得
        $channelId = $this->channel->getChannelID($this->auth->id, $request['to']);

        // 取得できなかった場合は作成
        if (empty($channelId)) {
            $res =  $this->channel->create([
                'members' => $this->auth->id . ":" . $request['to']
            ]);

            $channelId = $res->id;
        }

        // 取得したチャンネルIDのメッセージを取得
        $result = $this->post_message->getPostMessages($channelId);
        if (empty($result)) {
            return response()->json(['message' => 'チャットの取得に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        // チャンネルIDを取得
        $channelId = $this->channel->getChannelID($this->auth->id, $data['to']);

        // 空の場合は作成
        if (empty($channelId)) {
            $res =  $this->channel->create([
                'members' => $this->auth->id . ":" . $data['to']
            ]);
            $channelId = $res->id;
        }
        $data['channel_id'] = $channelId;

        // メッセージを作成
        $result = $this->post_message->create($data);

        if (empty($result)) {
            return response()->json(['message' => 'チャットの送信に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        broadcast(new PostMessaged($data['to'], $result));

        return response()->json();
    }
}
