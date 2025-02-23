<?php

use App\Models\AdSpaces;
use App\Models\Analytic;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Followers;
use App\Models\Language;
use App\Models\MailSetting;
use App\Models\Menu;
use App\Models\Navigation;
use App\Models\Page;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Poll;
use App\Models\PollResult;
use App\Models\Post;
use App\Models\SeoTool;
use App\Models\Setting;
use App\Models\SubCategory;
use App\Models\Subscription;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

/**
 * @return Authenticatable|null
 */
function getLogInUser()
{

    return Auth::user();
}

/**
 * @return mixed
 */
function getAppName()
{
    static $appName;

    if (empty($appName)) {
        $appName = Setting::where('key', '=', 'application_name')->first()->value;
    }

    return $appName;
}

/**
 * @return int
 */
function getLogInUserId()
{
    return Auth::user()->id;
}

/**
 * @return string
 */
function getDashboardURL()
{

    if (Auth::user()->hasRole('customer')) {
        return RouteServiceProvider::CUSTOMER;
    }
    if (Auth::user()->hasRole('clinic_admin')) {
        return RouteServiceProvider::HOME;
    }

    return RouteServiceProvider::HOME;
}

/**
 * @return \Illuminate\Support\Collection
 */
function getLanguage()
{
    static $language;
    if (empty($language)) {
        $language = Language::pluck('name', 'id');
    }

    return $language;
}

/**
 * @return \Illuminate\Support\Collection
 */
function getLanguageSet()
{
    $language = Language::pluck('name', 'iso_code');

    return $language;
}

/**
 * @return mixed
 */
function getAlbums($langId)
{
    return \App\Models\Album::where('lang_id', $langId)->toBase()->pluck('name', 'id')->toArray();
}

function getAlbumCategory($albumId, $langId): array
{
    return \App\Models\AlbumCategory::where('lang_id', $langId)->where('album_id', $albumId)->pluck(
        'name',
        'id'
    )->toArray();
}

function getRandomColor($index): string
{
    $badgeColors = [
        'primary',
        'success',
        'info',
        'danger',
        'warning',
    ];
    $number = ceil($index % 5);

    return $badgeColors[$number];
}

/**
 * @param  $index
 * @return string
 */
function getParentMenu()
{
    $menu = Menu::whereNotNull('link')->pluck('link', 'id')->sort();

    return $menu;
}

/**
 * @return mixed
 */
function getHeaderElement()
{
    $data['navigations'] = Navigation::with('navigationable')
        ->whereHas('navigationable', function ($q) {
            $q->where('show_in_menu', 1);
        })->whereNull('parent_id')->orderBy('order_id')->get();

    //child
    $data['navigationsTakeData'] = [];
    foreach ($data['navigations'] as $item) {
        $navigationType = $item->navigationable_type == Category::class ? SubCategory::class : $item->navigationable_type;
        $data['navigationsTakeData'][$item->id] = Navigation::with('navigationable')
            ->whereHas('navigationable', function ($q) {
                $q->where('show_in_menu', 1);
            })->where('navigationable_type', $navigationType)
            ->where('parent_id', $item->navigationable_id)->orderBy('order_id')->get();
    }

    $data['pages'] = Page::where('location', Page::MAIN_MENU)->where('visibility', 1)->get()->sort();

    return $data;
}

/**
 * @return mixed
 */
function getRecentPost()
{
    return Post::with('language', 'category')->whereVisibility(Post::VISIBILITY_ACTIVE)->latest('id')->take(3)->get();
}

/**
 * @return Category[]|Collection
 */
function getCategory()
{
    return Category::active()->where('show_in_menu', 1)->get();
}

/**
 * @return mixed
 */
function getSettingValue()
{
    static $settingValues = [];

    if (empty($settingValues)) {
        $settingValues = Setting::pluck('value', 'key')->toArray();
    }

    return $settingValues;
}

function getUrl()
{
    return Request::url();
}

function getNavigationDetails(): array
{
    //parent navigation get
    $data['navigations'] = Navigation::with('navigationable')
        ->whereHas('navigationable', function ($q) {
            $q->where('show_in_menu', 1);
        })->whereNull('parent_id')->orderBy('order_id')->get();

    $data['menus'] = [];

    foreach ($data['navigations'] as $menu) {
        if ($menu['navigationable']['lang_id'] == getFrontSelectLanguage()) {
            $data['menus'][] = $menu;
        } elseif ($menu->navigationable_type == Menu::class) {
            $data['menus'][] = $menu;
        }
    }

    $data['navigations'] = collect($data['menus'])->take(6);
    //child
    $data['navigationsTakeData'] = [];
    foreach ($data['navigations'] as $item) {
        $navigationType = $item->navigationable_type == Category::class ? SubCategory::class : $item->navigationable_type;
        $data['navigationsTakeData'][$item->id] = Navigation::with('navigationable')
            ->whereHas('navigationable', function ($q) {
                $q->where('show_in_menu', 1);
            })->where('navigationable_type', $navigationType)
            ->where('parent_id', $item->navigationable_id)->orderBy('order_id')->get();
    }

    $data['menuCount'] = [];
    foreach ($data['navigationsTakeData'] as $menuGet) {
        if ($menuGet->isEmpty()) {
            $data['menuCount'];
        }
    }

    //remaining navigation
    $data['navigationsSkipData'] = Navigation::with('navigationable')
        ->whereHas('navigationable', function ($q) {
            $q->where('show_in_menu', 1);
        })->whereNull('parent_id')
        ->whereNotIn('id', $data['navigations']->pluck('id')->toArray())->orderBy('order_id')->get();

    // child
    $data['navigationsSkipItem'] = [];
    foreach ($data['navigationsSkipData'] as $item) {
        $navigationType = $item->navigationable_type == Category::class ? SubCategory::class : $item->navigationable_type;
        $data['navigationsSkipItem'][$item->id] = Navigation::with('navigationable')
            ->whereHas('navigationable', function ($q) {
                $q->where('show_in_menu', 1);
            })->where('navigationable_type', $navigationType)
            ->where('parent_id', $item->navigationable_id)->orderBy('order_id')->get();
    }

    //categoryCount
    $countMenu = Category::whereShowInMenu(1)->where('lang_id', '!=', getFrontSelectLanguage())->count();
    //total navigation
    $data['navigationsCount'] = $data['navigationsSkipData']->count() + $data['navigations']->count() - $countMenu;
    //pages
    $data['pages'] = Page::whereLangId(getFrontSelectLanguage())->where('location', Page::MAIN_MENU)->where('visibility', 1)->get()->sort();

    return $data;
}

/**
 * @return array
 */
function getPopularNews()
{
    $countPosts = Analytic::withCount('posts')
        ->join('posts', 'analytics.post_id', '=', 'posts.id')
        ->where('posts.visibility', Post::VISIBILITY_ACTIVE)
        ->select('post_id', \DB::raw('COUNT(*) as posts_count'))
        ->groupBy('post_id')
        ->orderBy('posts_count', 'desc')
        ->limit(7)
        ->get();

    $categories = Category::toBase()->pluck('name', 'id')->toArray();
    $postData = Post::with('postVideo')->whereVisibility(Post::VISIBILITY_ACTIVE)
        ->whereIn('id', $countPosts->pluck('post_id')->toArray())->get();
    static $popularNews = [];
    if (empty($popularNews)) {
        foreach ($countPosts as $countPost) {
            $post = $postData->where('id', $countPost->post_id)->first();
            if (! empty($post)) {
                $post = $post->toArray();
                $post['category'] = ['name' => ! empty($categories[$post['category_id']]) ? $categories[$post['category_id']] : ''];
                $popularNews[] = $post;
            }
        }
    }

    return $popularNews;
}

/**
 * @return int
 */
function getPostViewCount($id)
{
    $postViewCount = Analytic::wherePostId($id)->count();

    return $postViewCount;
}

/**
 * @return array
 */
function getPopularTags()
{
    $countPostsTags = Analytic::withCount('posts')
        ->join('posts', 'analytics.post_id', '=', 'posts.id')
        ->where('posts.visibility', Post::VISIBILITY_ACTIVE)
        ->select('post_id', \DB::raw('COUNT(*) as posts_count'))
        ->groupBy('post_id')
        ->orderBy('posts_count', 'desc')
        ->limit(7)
        ->get();

    static $popularTags = [];
    $postData = Post::toBase()->whereVisibility(Post::VISIBILITY_ACTIVE)
        ->whereIn('id', $countPostsTags->pluck('post_id')->toArray())->get();
    if (empty($popularTags)) {
        foreach ($countPostsTags as $countPostsTag) {
            $postTag = $postData->where('id', $countPostsTag->post_id)->pluck('tags', 'id')->sort()->first();
            if (! empty($postTag)) {
                $popularTags[] = $postTag;
            }
        }
    }

    $tagArr = [];
    foreach (array_filter($popularTags) as $tags) {
        foreach (explode(',', $tags) as $tag) {
            $tagArr[] = $tag;
        }
    }

    return array_unique($tagArr);
}

/**
 * @return Poll[]|Builder[]|Collection
 */
function getPoll()
{
    if (! Auth::check()) {
        return Poll::where('lang_id', getFrontSelectLanguage())->where('vote_permission', 1)->whereStatus(1)->limit(3)->get();
    } else {
        return Poll::where('lang_id', getFrontSelectLanguage())->whereStatus(1)->limit(3)->get();
    }
}

/**
 * @return string[]
 */
function getOption(): array
{
    return [
        'option1', 'option2', 'option3', 'option4', 'option5', 'option6', 'option7', 'option8', 'option9', 'option10',
    ];
}

/**
 * @param  int  $pollId
 */
function getPollStatistics($pollId): array
{
    $pollResults = PollResult::with('poll')->wherePollId($pollId)->get();
    $resultsAns = $pollResults->pluck('answer')->toArray();
    $totalPollResults = count($pollResults);
    $totalPerAns = array_count_values($resultsAns);
    $optionAns = [];
    foreach ($pollResults as $result) {
        $poll = $result->poll;
        foreach (getOption() as $option) {
            if (! empty($poll->$option)) {
                $optionAns[$poll->$option] = ! empty($totalPerAns[$poll->$option])
                    ? intval($totalPerAns[$poll->$option] * 100 / $totalPollResults) : 0;
            }
        }
    }

    $data['totalPollResults'] = $totalPollResults;
    $data['optionAns'] = $optionAns;
    $data['pollId'] = $pollId;

    return $data;
}

/**
 * @return string
 */
function getColorClass($id)
{
    $randomClass = ['world', 'technology', 'travel', 'fashion', 'music', 'animal'];
    $index = $id % 5;

    return $randomClass[$index];
}

/**
 * @return array
 */
function getPopulerCategories()
{
    $postCount = DB::table('analytics')->select(
        'post_id',
        DB::raw('count("post_id") as total_count')
    )->limit(10)
        ->groupBy('post_id')
        ->orderBy('total_count', 'desc')
        ->get();

    $popularCategory = [];

    $posts = Post::toBase()->whereIn('id', $postCount->pluck('post_id')->toArray())->where('visibility', Post::VISIBILITY_ACTIVE)->get()->groupBy('category_id');
    $categories = Category::toBase()->where('show_in_menu', Category::SHOW_IN_MENU_ACTIVE)->get();
    $cnt = 0;
    foreach ($posts as $id => $post) {
        $category = $categories->where('id', $id)->first();
        if (! empty($category)) {
            if ($cnt > 10) {
                continue;
            }
            $popularCategory[$id]['name'] = $category->name;
            $popularCategory[$id]['slug'] = $category->slug;
            $popularCategory[$id]['posts_count'] = $post->count();
            $cnt++;
        }
    }

    return array_values($popularCategory);
}

function getNavUrl($url)
{
    $contain = Str::contains($url, 'https');
    if ($contain) {
        return $url;
    } else {
        return 'http://'.$url;
    }
}

/**
 * @return string
 */
function getReadingTime($body)
{
    $myContent = $body;
    $word = str_word_count(strip_tags($myContent));
    $m = floor($word / 200);
    $s = floor($word % 200 / (200 / 60));

    if ($s > 30) {
        $m += 1;
        $s = 00;
    } else {
        $s = 00;
    }

    if ($m == 0) {
        $m += 1;
    }

    $time = $m.__('messages.min_read').($m == 1 ? '' : 's');

    return $time;
}

/**
 * @return array
 */
function getTrendingPost()
{
    start_measure('render', 'getTrendingPost');
    start_measure('render', 'postsAnalytics');
    $postsAnalytics = DB::table('analytics')->select(
        'post_id',
        DB::raw('count("post_id") as total_count')
    )->limit(10)
        ->groupBy('post_id')
        ->orderBy('total_count', 'desc')
        ->get();
    stop_measure('render', 'postsAnalytics');
    $postIds = $postsAnalytics->pluck('post_id')->toArray();
    start_measure('render', 'posts');
    $posts = Post::with('category', 'postVideo')
        ->whereVisibility(Post::VISIBILITY_ACTIVE)
        ->whereIn('id', $postIds)
        ->limit(6)
        ->get(['id', 'category_id', 'post_types', 'slug', 'title', 'created_at'])
        ->toArray();
    stop_measure('render', 'posts');
    static $trendingPosts = [];
    if (empty($trendingPosts)) {
        $trendingPosts = $posts;
    }
    stop_measure('render', 'getTrendingPost');

    return $trendingPosts;
}

/**s
 *
 * @return Post[]|Builder[]|Collection
 */
function getBreakingPost()
{
    $getBreakingPost = Post::with('category', 'user')->whereBreaking(1)->whereVisibility(Post::VISIBILITY_ACTIVE)->orderBy('updated_at', 'DESC')->get();

    return $getBreakingPost;
}

/**
 * @return Post[]|Builder[]|Collection
 */
function getRecommendedPost()
{
    $recommendedPosts = Post::with('category', 'postVideo')->whereRecommended(1)->whereVisibility(Post::VISIBILITY_ACTIVE)->orderBy('updated_at', 'desc')->take(6)->get();

    return $recommendedPosts;
}

/**
 * @return mixed|null
 */
function getSelectLanguage()
{
    $langIdLanguage = empty(Session::get('languageChange')['data']);

    if ($langIdLanguage) {
        $langId = 1;
    } else {
        $langId = Session::get('languageChange')['data'];
    }

    return $langId;
}

/**
 * @return mixed
 */
function getSelectLanguageName()
{
    return Language::find(getSelectLanguage())->name;
}

function getFrontSelectLanguage()
{
    $langIdLanguage = empty(Session::get('frontLanguageChange'));

    if ($langIdLanguage) {
        $langId = getSettingValue()['front_language'];
    } else {
        $langId = Session::get('frontLanguageChange');
    }

    return $langId;
}

/**
 * @return mixed
 */
function getFrontSelectLanguageName()
{
    static $languageName;

    if (empty($languageName)) {
        $languageName = ! empty(Language::find(getFrontSelectLanguage())) ? Language::find(getFrontSelectLanguage())->name : '';
    }

    return $languageName;
}

/**
 * @return \Anhskohbo\NoCaptcha\NoCaptcha
 */
function reCaptcha()
{
    $settings = Setting::pluck('value', 'key')->toArray();
    $secret = $settings['secret_key'];
    $sitekey = $settings['site_key'];
    $captcha = new Anhskohbo\NoCaptcha\NoCaptcha($secret, $sitekey);

    return $captcha;
}

/**
 * @return mixed
 */
function getSEOTools()
{
    static $seoTool;

    if (empty($seoTool)) {
        $seoTool = SeoTool::with('language')->first();
    }
    if ($seoTool->language->name == getFrontSelectLanguageName()) {
        return $seoTool;
    }
}

function getCategoryNumbers($range): array
{
    $result = [];
    $count = 1;
    $start = 1;
    foreach ($range as $val) {
        if ($val % 2 == 0) {
            $skip = 1;
        } else {
            $skip = 3;
        }
        $result[] = $start;
        $start += $skip;
        $count++;
    }

    return array_values(array_unique($result));
}

function getCurrentVersion()
{
    $composerFile = file_get_contents('../composer.json');

    $composerData = json_decode($composerFile, true);

    return $composerData['version'];
}
function checkAdSpaced($name)
{
    $check = Setting::where('key', $name)->pluck('value')->first();

    return $check;
}
function getAdImageDesktop($id)
{
    $agent = new Agent();
    if ($agent->isMobile() && $agent->isTablet()) {
        $image = AdSpaces::whereAdSpaces($id)->whereAdView(AdSpaces::MOBILE)->first();
    } else {
        $image = AdSpaces::whereAdSpaces($id)->whereAdView(AdSpaces::DESKTOP)->first();
    }

    return $image;
}
function getAdImageMobile($id)
{
    $image = AdSpaces::whereAdSpaces($id)->whereAdView(AdSpaces::MOBILE)->first();

    return $image;
}
function GetMail()
{
    return MailSetting::first();
}

function getCurrencies()
{
    $currencies = Currency::all();
    foreach ($currencies as $currency) {
        $currencyList[$currency->id] = $currency->currency_icon.' - '.$currency->currency_name;
    }

    return $currencyList;
}

function removeCommaFromNumbers($number)
{
    return (gettype($number) == 'string' && ! empty($number)) ? str_replace(',', '', $number) : $number;
}

function getCurrentSubscription()
{
    $subscription = Subscription::with(['plan.currency'])
        ->whereUserId(getLogInUserId())
        ->where('status', Subscription::ACTIVE)->latest()->first();

    return $subscription;
}

function currencyFormat($number, $currencyCode = null)
{
    return $currencyCode.number_format($number, 2);
}

function getCurrentPlanDetails()
{
    $currentSubscription = getCurrentSubscription();
    $isExpired = $currentSubscription->isExpired();
    $currentPlan = $currentSubscription->plan;

    if ($currentPlan->price != $currentSubscription->plan_amount) {
        $currentPlan->price = $currentSubscription->plan_amount;
    }

    $startsAt = Carbon::now();
    $totalDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($currentSubscription->ends_at);
    $usedDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($startsAt);
    if ($totalDays > $usedDays) {
        $usedDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($startsAt);
    } else {
        $usedDays = $totalDays;
    }
    if ($totalDays > $usedDays) {
        $remainingDays = $totalDays - $usedDays;
    } else {
        $remainingDays = 0;
    }

    if ($totalDays == 0) {
        $totalDays = 1;
    }

    $frequency = $currentSubscription->plan_frequency == Plan::MONTHLY ? 'Monthly' : 'Yearly';

    //    $days = $currentSubscription->plan_frequency == Plan::MONTHLY ? 30 : 365;

    $perDayPrice = round($currentPlan->price / $totalDays, 2);
    if (! empty($currentSubscription->trial_ends_at) || $isExpired) {
        $remainingBalance = 0.00;
        $usedBalance = 0.00;
    } else {
        $isJPYCurrency = ! empty($currentPlan->currency) && isJPYCurrency($currentPlan->currency->currency_code);
        $remainingBalance = $currentPlan->price - ($perDayPrice * $usedDays);
        $remainingBalance = $isJPYCurrency ? round($remainingBalance) : $remainingBalance;
        $usedBalance = $currentPlan->price - $remainingBalance;
        $usedBalance = $isJPYCurrency ? round($usedBalance) : $usedBalance;
    }

    return [
        'name' => $currentPlan->name.' / '.$frequency,
        'trialDays' => $currentPlan->trial_days,
        'startAt' => Carbon::parse($currentSubscription->starts_at)->format('jS M, Y'),
        'endsAt' => Carbon::parse($currentSubscription->ends_at)->format('jS M, Y'),
        'usedDays' => $usedDays,
        'remainingDays' => $remainingDays,
        'totalDays' => $totalDays,
        'usedBalance' => $usedBalance,
        'remainingBalance' => $remainingBalance,
        'isExpired' => $isExpired,
        'currentPlan' => $currentPlan,
    ];
}

function getProratedPlanData($planIDChosenByUser)
{
    /** @var Plan $subscriptionPlan */
    $subscriptionPlan = Plan::findOrFail($planIDChosenByUser);

    if ($subscriptionPlan->frequency == Plan::MONTHLY) {

        $newPlanDays = 30;
        $frequency = 'Monthly';
    } else {
        if ($subscriptionPlan->frequency == Plan::YEARLY) {
            $newPlanDays = 365;
            $frequency = 'Yearly';
        } else {
            $newPlanDays = 36500;
            $frequency = 'Unlimited';
        }
    }

    $currentSubscription = getCurrentSubscription();
    $startsAt = Carbon::now();

    $carbonParseStartAt = Carbon::parse($currentSubscription->starts_at);
    $currentSubsTotalDays = $carbonParseStartAt->diffInDays($currentSubscription->ends_at);
    $usedDays = $carbonParseStartAt->copy()->diffInDays($startsAt);
    $totalExtraDays = 0;
    $totalDays = $newPlanDays;

    $endsAt = Carbon::now()->addDays($newPlanDays);

    $startsAt = $startsAt->copy()->format('jS M, Y');

    if ($usedDays <= 0) {
        $startsAt = $carbonParseStartAt->copy()->format('jS M, Y');
    }

    if (! $currentSubscription->isExpired() && ! checkIfPlanIsInTrial($currentSubscription)) {
        $amountToPay = 0;

        $currentPlan = $currentSubscription->plan; // TODO: take fields from subscription

        // checking if the current active subscription plan has the same price and frequency in order to process the calculation for the proration
        $planPrice = $currentPlan->price;
        $planFrequency = $currentPlan->frequency;
        if ($planPrice != $currentSubscription->plan_amount || $planFrequency != $currentSubscription->plan_frequency) {
            $planPrice = $currentSubscription->plan_amount;
            $planFrequency = $currentSubscription->plan_frequency;
        }

        $perDayPrice = round($planPrice / $currentSubsTotalDays, 2);
        $isJPYCurrency = ! empty($subscriptionPlan->currency) && isJPYCurrency($subscriptionPlan->currency->currency_code);

        $remainingBalance = $isJPYCurrency
            ? round($planPrice - ($perDayPrice * $usedDays))
            : round($planPrice - ($perDayPrice * $usedDays), 2);

        if ($remainingBalance < $subscriptionPlan->price) { // adjust the amount in plan
            $amountToPay = $isJPYCurrency
                ? round($subscriptionPlan->price - $remainingBalance)
                : round($subscriptionPlan->price - $remainingBalance, 2);
        } else {
            $perDayPriceOfNewPlan = round($subscriptionPlan->price / $newPlanDays, 15);
            $totalExtraDays = round($remainingBalance / $perDayPriceOfNewPlan);
            $endsAt = Carbon::now()->addDays($totalExtraDays);
            $totalDays = $totalExtraDays;
        }

        return [
            'startDate' => $startsAt,
            'name' => $subscriptionPlan->name.' / '.$frequency,
            'trialDays' => $subscriptionPlan->trial_days,
            'remainingBalance' => $remainingBalance,
            'endDate' => $endsAt->format('jS M, Y'),
            'amountToPay' => $amountToPay,
            'usedDays' => $usedDays,
            'totalExtraDays' => $totalExtraDays,
            'totalDays' => $totalDays,
        ];
    }

    return [
        'name' => $subscriptionPlan->name.' / '.$frequency,
        'trialDays' => $subscriptionPlan->trial_days,
        'startDate' => $startsAt,
        'endDate' => $endsAt->format('jS M, Y'),
        'remainingBalance' => 0,
        'amountToPay' => $subscriptionPlan->price,
        'usedDays' => $usedDays,
        'totalExtraDays' => $totalExtraDays,
        'totalDays' => $totalDays,
    ];
}

function checkIfPlanIsInTrial($currentSubscription)
{
    $now = Carbon::now();
    if (! empty($currentSubscription->trial_ends_at)) {
        return true;
    }

    return false;
}

function isJPYCurrency($code)
{
    return $code == Currency::JPY_CODE;
}

function getPaymentGateway()
{
    $paymentGateway = Subscription::PAYMENT_GATEWAY;
    $selectedPaymentGateways = PaymentGateway::pluck('payment_gateway')->toArray();
    foreach ($selectedPaymentGateways as $key => $gateway) {
        $gateWayKey = array_search($gateway, $paymentGateway, true);

        if (! checkPaymentGateway($gateWayKey)) {
            unset($selectedPaymentGateways[$key]);
        }
    }

    return array_intersect($paymentGateway, $selectedPaymentGateways);
}

function zeroDecimalCurrencies(): array
{
    return [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];
}

if (! function_exists('getStripeAPIKey')) {
    function getStripeAPIKey()
    {
        $checkStripeCreds = Setting::where('key', 'stripe_checkbox_btn')->value('value');
        $apiKey = Setting::where('key', 'stripe_key')->value('value');
        $stripeApiKey = (isset($checkStripeCreds) && $checkStripeCreds == 1) && ! empty($apiKey) ? $apiKey : config('services.stripe.key');

        return $stripeApiKey;
    }
}

function getStripeSecretKey()
{
    $checkStripeCreds = Setting::where('key', 'stripe_checkbox_btn')->value('value');
    $secretKey = Setting::where('key', 'stripe_secret')->value('value');
    $apiSecret = (isset($checkStripeCreds) && $checkStripeCreds == 1) && ! empty($secretKey) ? $secretKey : config('services.stripe.secret_key');

    return $apiSecret;
}

function getPayPalSupportedCurrencies()
{
    return [
        'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK',
        'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD',
    ];
}

function getloginuserplan()
{

    return Subscription::with('plan')->whereUserId(getLogInUserId())->whereStatus(Subscription::ACTIVE)->first();
}

function checkPaymentGateway($paymentGateway): bool
{

    if ($paymentGateway == Plan::STRIPE) {
        $checkStripeCreds = Setting::where('key', 'stripe_checkbox_btn')->value('value');
        $apiKey = Setting::where('key', 'stripe_key')->value('value');
        $secretKey = Setting::where('key', 'stripe_secret')->value('value');
        $apiKey = (isset($checkStripeCreds) && $checkStripeCreds == 1) && ! empty($apiKey) ? $apiKey : config('services.stripe.key');
        $apiSecret = (isset($checkStripeCreds) && $checkStripeCreds == 1) && ! empty($secretKey) ? $secretKey : config('services.stripe.secret_key');

        if (! empty($apiKey) && ! empty($apiSecret)) {
            return true;
        }

        return false;
    }

    if ($paymentGateway == Plan::PAYPAL) {
         $checkPaypalCreds = Setting::where('key', 'paypal_checkbox_btn')->value('value');
         $paypalKey = Setting::where('key', 'paypal_client_id')->value('value');
         $paypalSecretKey = Setting::where('key', 'paypal_secret')->value('value');
         $mode = Setting::where('key', 'paypal_mode')->value('value');
         $apiPaypalKey = (isset($checkPaypalCreds) && $checkPaypalCreds == 1) && ! empty($paypalKey) ? $paypalKey : config('paypal.sandbox.client_id');
         $apiPaypalSecret = (isset($checkPaypalCreds) && $checkPaypalCreds == 1) && ! empty($paypalSecretKey) ? $paypalSecretKey : config('paypal.sandbox.client_secret');
         $paypalmode = (isset($checkPaypalCreds) && $checkPaypalCreds == 1) && ! empty($mode) ? $mode : config('paypal.mode');
         if (! empty($apiPaypalKey) && ! empty($apiPaypalSecret) && ! empty($paypalmode)) {
             return true;
         }
        return false;
    }
    $manuallyEnabled = Setting::where('key', 'manually_checkbox_btn')->value('value');

    if (! empty($manuallyEnabled)) {
        return true;
    } else {
        return false;
    }

    return true;
}

function checkManuallyPaymentStatus()
{
    return Subscription::whereUserId(getLogInUserId())->latest()->first();
}

function getLanguageCategory($langId)
{
    $category = Category::whereLangId($langId)->pluck('name', 'id')->toArray();

    return $category;
}
function getCategorySubCategory($categoryId)
{
    $subCategory = SubCategory::whereParentCategoryId($categoryId)->pluck('name', 'id')->toArray();

    return $subCategory;
}
function getFrontLanguage()
{
    $language = Language::whereFrontLanguageStatus(Language::ACTIVE)->pluck('name', 'id');

    return $language;
}
function getLoginUserRole()
{
    return getLogInUser()->role_name;
}
function checkLoginUserFollow($userId)
{
    $following = Followers::whereFollowing(getLogInUserId())->whereFollowers($userId)->first();

    return $following;
}

function getCurrentTheme()
{
    return Setting::where('key', 'theme_configuration')->first()->value;
}

function make_slug($string) {

     return preg_replace('/[^\p{L}\p{N}]+/u', '-', trim($string));
}
function getFrontSelectLanguageIsoCode()
{
    return Language::find(getFrontSelectLanguage())->iso_code;
}
