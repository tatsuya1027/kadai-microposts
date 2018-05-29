<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function followings()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id')->withTimestamps();
    }
    
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'follow_id', 'user_id')->withTimestamps();
    }

    public function follow($userId)
    {
        // 既にフォローしているかの確認 
        $exist = $this->is_following($userId);
        // 自分自身ではないかの確認 
        $its_me = $this->id == $userId;
    
        if ($exist || $its_me) {
    
            // 既にフォローしていれば何もしない
    
            return false;
    
        } else {
    
            // 未フォローであればフォローする
    
            $this->followings()->attach($userId);
    
            return true;
    
        }
    }
    
    public function unfollow($userId)
    {
        // 既にフォローしているかの確認
        $exist = $this->is_following($userId);
        // 自分自身ではないかの確認
        $its_me = $this->id == $userId;
    
        if ($exist && !$its_me) {
            // 既にフォローしていればフォローを外す
            $this->followings()->detach($userId);
            return true;
        } else {
            // 未フォローであれば何もしない
            return false;
        }
    }
    
    public function is_following($userId) {
        return $this->followings()->where('follow_id', $userId)->exists();
    }

    public function feed_microposts() {
        $follow_user_ids = $this->followings()->lists('users.id')->toArray();
        $follow_user_ids[] = $this->id;
        return Micropost::whereIn('user_id', $follow_user_ids);
    }

    public function microposts()
    {
        return $this->hasMany(Micropost::class);
    }

    /*
    public function followings()
    {
        $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id')
                          // -----------   -----------    -------    ---------
                          //     ①             ②            ③          ④
    }
    
    ① どのクラスでできた配列を返して欲しいの？ →　User::class
    ② どのテーブルを見るの？　→　'user_follow'
    ③ ②のテーブルの中であなた自身はどのカラム？　→　'user_id'
    ④ ②のテーブルの中でどのカラムを ①のクラスのidとすればいいの？　→　'follow_id'
    
    */

    public function favorites()
    {
        return $this->belongsToMany(Micropost::class, 'micropost_favorite', 'user_id', 'favorite_id')->withTimestamps();
    }

    public function favorite($id)
    {
        // 既にお気に入り登録しているかの確認 
        $exist = $this->is_favorite($id);

        if ($exist) {

            // 既にお気に入り登録していれば何もしない

            return false;

        } else {

            // お気に入りでなければお気に入り登録する

            $this->favorites()->attach($id);

            return true;

        }
    }

    public function unfavorite($id)
    {
        // 既にお気に入り登録しているかの確認
        $exist = $this->is_favorite($id);

        if ($exist) {
            // 既にお気に入り登録していればお気に入りを外す
            $this->favorites()->detach($id);
            return true;
        } else {
            // お気に入りでなければ何もしない
            return false;
        }
    }

    public function is_favorite($id) {
        return $this->favorites()->where('favorite_id', $id)->exists();
    }
}
