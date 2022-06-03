<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Events\SeatStatusUpdated;
use App\Notifications\UserFollowed;
use App\Notifications\UserFriend;
use App\Notifications\UserFriendAgree;
use App\Events\NotificationPosted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use DB;

class UserController extends Controller
{
    // デフォルトアイコン名
    const DEFAULT_ICON_FILENAME = 'default.jpg';


    /** @var User */
    protected $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->middleware(function ($request, $next) {
            $this->auth = Auth::user();
            $this->updateStatus();
            return $next($request);
        });

        $this->user = $user;
    }


    /**
     * ログインユーザーの取得
     *
     * @return \Illuminate\Http\Response
     */
    public function auth()
    {
        if (empty($this->auth)) {
            return response()->json();
        }

        return response()->json($this->auth);
    }

    /**
     * ステータスの更新
     *
     * @param  String  $status  更新するステータス
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(String $status = null)
    {
        if (empty($this->auth)) {
            return response()->json();
        }

        // 10分で期限切れ
        $expires_at = Carbon::now()->addMinutes(10);

        if ($status != null) {
            // ステータスの変更
            Cache::put('user-' . $this->auth->id . '-status', $status, $expires_at);

            if (!empty($this->auth->seat)) {
                broadcast(new SeatStatusUpdated($this->auth->room['id']));
            }
        } else {
            // 現在のステータスで更新する
            Cache::put('user-' . $this->auth->id . '-status', $this->auth->status, $expires_at);
        }

        $this->setLoginLog();

        return response()->json();
    }

    /**
     * ログインの記録
     *
     * @return void
     */
    public function setLoginLog()
    {
        if (!Cache::has('user-' . $this->auth->id . '-login')) {
            // 翌日0:10で期限切れ
            $expires_at = Carbon::tomorrow()->addMinutes(10);

            Cache::put('user-' . $this->auth->id . '-login', true, $expires_at);
        }
    }

    /**
     * 通知の取得
     *
     * @return \Illuminate\Http\Response
     */
    public function getNotifications()
    {
        return response()->json($this->auth->getNotifications());
    }

    /**
     * 通知の既読
     *
     * @return \Illuminate\Http\Response
     */
    public function markNotificationsAsRead()
    {
        $this->auth->unreadNotifications()->update(['read_at' => now()]);

        return $this->getNotifications();
    }

    /**
     * ユーザーの取得
     *
     * @param  String  $user_param  ユーザーIDまたはユーザー名
     * @return \App\Models\User  ユーザーデータ
     */
    public function get_user(String $user_param)
    {
        if (preg_match('/^[0-9]+$/', $user_param)) {
            $user = $this->user->where('id', $user_param)->first();
        } else {
            $user = $this->user->where('username', $user_param)->first();
        }

        return $user;
    }

    /**
     * ユーザーデータの表示
     *
     * @param  String  $user_param  ユーザーIDまたはユーザー名
     * @return \Illuminate\Http\Response
     */
    public function show(String $user_param)
    {
        $user = $this->get_user($user_param);

        // フォロー関係の追加
        $user['following'] = $this->auth->isFollowing($user->id);
        $user['followed'] = $this->auth->isFollowed($user->id);
        $user['friending'] = $this->auth->isFriending($user->id);
        $user['friended'] = $this->auth->isFriended($user->id);

        return response()->json($user);
    }

    /**
     * ユーザーデータの更新
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $data = $request->all();

        // SNSデータの型変換
        if (!empty($data['sns'])) {
            $data['sns'] = json_decode($data['sns']);
        }

        // アイコンの保存
        if (!empty($request->file('icon'))) {
            // 削除処理
            if ($this->auth->icon != self::DEFAULT_ICON_FILENAME) {
                // 初期アイコン以外の場合には登録中のアイコンを削除
                Storage::delete(config('consts.storage.icon') . $this->auth->icon);
            }

            // 保存処理
            $filename = $request->file('icon')->hashName();
            $request->file('icon')->storeAs(config('consts.storage.icon'), $filename);

            $data['icon'] = $filename;
        }

        $result = $this->auth->fill($data)->save();

        if (empty($result)) {
            return response()->json(['message' => 'ユーザーデータの更新に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        // 取り組み中のタスクが更新された場合
        if (array_key_exists('in_progress', $data)) {
            broadcast(new SeatStatusUpdated($this->auth->room['id']));
            return response()->json();
        }

        return response()->json(['message' => 'ユーザーデータが更新されました。']);
    }

    /**
     * フォロー一覧の取得
     *
     * @param  \App\Models\User  $user  一覧を取得するユーザー
     * @return \Illuminate\Http\Response
     */
    public function follows(User $user)
    {
        return response()->json($user->follows);
    }

    /**
     * フォロワー一覧の取得
     *
     * @param  \App\Models\User  $user  一覧を取得するユーザー
     * @return \Illuminate\Http\Response
     */
    public function followers(User $user)
    {
        return response()->json($user->followers);
    }

    /**
     * フォロー/フォロー解除
     *
     * @param  \App\Models\User  $user  フォロー/フォロー解除するユーザー
     * @return \Illuminate\Http\Response
     */
    public function follow(User $user)
    {
        if ($this->auth->id === $user->id) {
            return response()->json(['message' => '自分はフォローできません。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        // フォロー/フォロー解除処理
        $result = $this->auth->follows()->toggle($user->id);

        if (empty($result)) {
            return response()->json(['message' => 'フォロー/フォロー解除に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        // フォロー時には通知を発行
        if (count($result['attached'])) {
            $user->notify(new UserFollowed($this->auth));
            broadcast(new NotificationPosted($user));
        }

        return $this->show($user->id);
    }

    /**
     * 友達かも一覧の取得
     *
     * @param  \App\Models\User  $user  一覧を取得するユーザー
     * @return \Illuminate\Http\Response
     */
    public function maybeFriends(User $user)
    {
        return response()->json($user->maybeFriends);
    }

    /**
     * 友達一覧の取得
     *
     * @param  \App\Models\User  $user  一覧を取得するユーザー
     * @return \Illuminate\Http\Response
     */
    public function friends(User $user)
    {
        return response()->json($user->friends);
    }

    /**
     * 友達追加
     *
     * @param  \App\Models\User  $user  友達/絶交するユーザー
     * @return \Illuminate\Http\Response
     */
    public function friend(User $user)
    {
        $friending = $this->auth->isFriending($user->id);
        $friended = $this->auth->isFriended($user->id);
        if ($this->auth->id === $user->id) {
            return response()->json(['message' => '自分は友達追加できません。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        // 友達追加処理
        $result = $this->auth->friends()->toggle($user->id);

        if (empty($result)) {
            return response()->json(['message' => '友達追加に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        // 友達追加には通知を発行
        if ($result) {
            if(!$friended){
                if(!$friending){
                    //友達申請
                    $user->notify(new UserFriend($this->auth));
                    broadcast(new NotificationPosted($user));
                }else{
                    //申請承認
                    $user->notify(new UserFriendAgree($this->auth));
                    broadcast(new NotificationPosted($user));
                }
            }
        }
        return $this->show($user->id);

    }
    /**
     * 友達絶交
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function notFriend(User $user)
    {
        // 友達追加/絶交処理
        $result = DB::table('friends')->where('friending_id', $user->id)->where('friended_id',$this->auth->id)->delete();
        if (empty($result)) {
            return response()->json(['message' => '絶交に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        return $this->show($user->id);
    }

    /**
     * ユーザーの削除
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        // 着席中の座席の状態を初期化
        $seat = $this->auth->seat;
        if (!empty($seat)) {
            $seat->fill(['status' => null, 'reservation_user_id' => null])->save();

            // 予約中の座席の状態を初期化
            $reservation_seat = $seat->where('reservation_user_id', $this->auth->id)->first();
            if (!empty($reservation_seat)) {
                $reservation_seat->fill(['status' => null, 'reservation_user_id' => null])->save();
            }
        }

        // 初期アイコン以外の場合にはアイコンを削除
        if ($this->auth->icon != self::DEFAULT_ICON_FILENAME) {
            Storage::delete(config('consts.storage.icon') . $this->auth->icon);
        }

        // カルテ保存ディレクトリを削除
        Storage::deleteDirectory(config('consts.storage.karte') . $this->auth->username);

        $result = $this->auth->delete();

        if (empty($result)) {
            return response()->json(['message' => '退会に失敗しました。'], config('consts.status.INTERNAL_SERVER_ERROR'));
        }

        return response()->json(['message' => '退会が完了しました。']);
    }
}
