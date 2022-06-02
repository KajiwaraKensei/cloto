<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PostMessage;
use App\Models\Channel;

use function PHPUnit\Framework\isNull;

class PostMessageController extends Controller
{
    /** @var DM */
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
     * ログインユーザーの問い合わせ一覧を取得
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json($this->auth->dm);
    }

    /**
     * 問い合わせの投稿
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $postMessage = new PostMessage;
        $postMessage->user_id = $this->auth->id;
        $postMessage->channel_id = 1;
        $postMessage->type = $request->type;
        $postMessage->content = $request->content;
        
        $postMessage->save();
        return response()->json();
    }
    public function store(Request $request)
    {
        $data = $request->all();
        $channelId = $this->channel->getChannelID($this->auth->id, $data['to']);
        if (empty($channelId)){
            $res =  $this->channel->create([
                'members' => $this->auth->id . ":" . $data['to']
            ]);
            $channelId = $res->id;
        }
        $data['channel_id'] = $channelId;
        $result = $this->post_message->create($data);

        if (empty($result)) {
            return response()->json(['message' => 'チャットの送信に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        return response()->json();
    }
}
