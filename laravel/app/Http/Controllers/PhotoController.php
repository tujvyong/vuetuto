<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhoto;
use App\Photo;
use App\Comment;
use App\Http\Requests\StoreComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth')->except(['index', 'download', 'show']);
	}

	public function index()
	{
		$photos = Photo::with(['owner'])
			->orderBy(Photo::CREATED_AT, 'desc')->paginate();

		return $photos;
	}

	/**
	 * SELECT * FROM `photos` WHERE `id` = "abcd1234EFGH";
	 * SELECT * FROM `users` WHERE `id` IN (1); -- ownerリレーションを解決する
	 * SELECT * FROM `comments` WHERE `photo_id` = "abcd1234EFGH"; -- commentsリレーションを解決する
	 * SELECT * FROM `users` WHERE `id` IN (2, 3, 4); -- comments.authorリレーションを解決する
	 * @param string $id
	 * @return Photo
	 */
	public function show(string $id)
	{
		$photo = Photo::where('id', $id)->with(['owner', 'comments.author'])->first();

		return $photo ?? abort(404);
	}

	/**
	 * 写真投稿
	 * @param StorePhoto $request
	 * @return \Illuminate\Http\Response
	 */
	public function create(StorePhoto $request)
	{
		// 投稿写真の拡張子を取得する
		$extension = $request->photo->extension();

		$photo = new Photo();

		// インスタンス生成時に割り振られたランダムなID値と
		// 本来の拡張子を組み合わせてファイル名とする
		$photo->filename = $photo->id . '.' . $extension;

		// S3にファイルを保存する
		// 第三引数の'public'はファイルを公開状態で保存するため
		Storage::cloud()
			->putFileAs('', $request->photo, $photo->filename, 'public');

		// データベースエラー時にファイル削除を行うため
		// トランザクションを利用する
		DB::beginTransaction();

		try {
			Auth::user()->photos()->save($photo);
			DB::commit();
		} catch (\Exception $exception) {
			DB::rollBack();
			// DBとの不整合を避けるためアップロードしたファイルを削除
			Storage::cloud()->delete($photo->filename);
			throw $exception;
		}

		// リソースの新規作成なので
		// レスポンスコードは201(CREATED)を返却する
		return response($photo, 201);
	}

	/**
	 * 写真ダウンロード
	 * @param Photo $photo
	 * @return \Illuminate\Http\Response
	 */
	public function download(Photo $photo)
	{
		// 写真の存在チェック
		if (!Storage::cloud()->exists($photo->filename)) {
			abort(404);
		}

		// https://developer.mozilla.org/ja/docs/Web/HTTP/Headers/Content-Disposition
		// Content-Disposition に attachment および filename を指定することで、レスポンスの内容（S3 から取得した画像ファイル）を Web ページとして表示するのではなく、ダウンロードさせるために保存ダイアログを開くようにブラウザに指示
		$disposition = 'attachment; filename="' . $photo->filename . '"';
		$headers = [
			'Content-Type' => 'application/octet-stream',
			'Content-Disposition' => $disposition,
		];

		return response(Storage::cloud()->get($photo->filename), 200, $headers);
	}

	/**
	 * コメント投稿
	 * @param Photo $photo
	 * @param StoreComment $request
	 * @return \Illuminate\Http\Response
	 */
	public function addComment(Photo $photo, StoreComment $request)
	{
		$comment = new Comment();
		$comment->content = $request->get('content');
		$comment->user_id = Auth::user()->id;
		$photo->comments()->save($comment);

		// authorリレーションをロードするためにコメントを取得しなおす
		$new_comment = Comment::where('id', $comment->id)->with('author')->first();

		return response($new_comment, 201);
	}
}