<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class Photo extends Model
{
	// プライマリキーの値を初期設定（int）から変更したい場合は $keyType を上書きする。
	protected $keyType = 'string';
	const ID_LENGTH = 12;

	// ユーザー定義のアクセサを JSON 表現に含めるためには、明示的に $appends プロパティに登録する必要がある。
	protected $appends = [
		'url', 'likes_count', 'liked_by_user',
	];

	protected $visible = [
		'id', 'owner', 'url', 'comments', 'likes_count', 'liked_by_user',
	];

	protected $perPage = 15;

	// Photo 作成時に忘れずに setId を呼ばなくてはいけないのはデフォルトのルールと違っていて分かりにくい。そのためコンストラクタで自動的に setId を呼び出している。
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);

		if (!Arr::get($this->attributes, 'id')) {
			$this->setId();
		}
	}

	private function setId()
	{
		$this->attributes['id'] = $this->getRandomId();
	}

	public function owner()
	{
		return $this->belongsTo('App\User', 'user_id', 'id', 'users');
	}

	public function comments()
	{
		return $this->hasMany('App\Comment')->orderBy('id', 'desc');
	}

	public function likes()
	{
		// withTimestamps はこのリレーションメソッドを使って likes テーブルにデータを挿入したとき、created_at および updated_at カラムを更新させるための指定
		return $this->belongsToMany('App\User', 'likes')->withTimestamps();
	}

	public function getUrlAttribute()
	{
		return Storage::cloud()->url($this->attributes['filename']);
	}

	private function getRandomId()
	{
		$characters = array_merge(
			range(0, 9),
			range('a', 'z'),
			range('A', 'Z'),
			['-', '_']
		);

		$length = count($characters);

		$id = "";

		for ($i = 0; $i < self::ID_LENGTH; $i++) {
			$id .= $characters[random_int(0, $length - 1)];
		}

		return $id;
	}

	public function getLikesCountAttribute()
	{
		return $this->likes->count();
	}

	public function getLikedByUserAttribute()
	{
		if (Auth::guest()) {
			return false;
		}

		return $this->likes->contains(function ($user) {
			return $user->id === Auth::user()->id;
		});
	}
}