<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class CommentController extends AppBaseController
{
    /**
     * @return Application|Factory|View
     */
    public function index(): \Illuminate\View\View
    {
        return view('comment.index');
    }

    public function delete(Comment $comment)
    {
        $comment->delete();

        return $this->sendSuccess(__('messages.placeholder.comment_deleted_successfully'));
    }

    /**
     * @return mixed
     */
    public function commentStatus($id)
    {
        $approved = ($id == 1) ? '0' : '1';
        $setting = Setting::where('key', 'comment_approved')->first();
        $setting->update(['value' => $approved]);

        return $this->sendSuccess(__('messages.placeholder.comment_setting_updated_successfully'));
    }
}
