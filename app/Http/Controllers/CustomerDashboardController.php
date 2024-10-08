<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Scopes\LanguageScope;
use App\Scopes\PostDraftScope;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function index(): View
    {

        $posts = Post::withoutGlobalScope(LanguageScope::class)->withoutGlobalScope(PostDraftScope::class)->whereCreatedBy(getLogInUserId())
            ->count();
        $postsDraft = Post::withoutGlobalScope(LanguageScope::class)->withoutGlobalScope(PostDraftScope::class)->where('status',
            Post::STATUS_DRAFT)->whereCreatedBy(getLogInUserId())->count();

        return view('dashboard.customer-dashboard', compact('posts', 'postsDraft'));
    }
}
