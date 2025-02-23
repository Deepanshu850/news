<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdSpacesRequest;
use App\Models\AdSpaces;
use App\Repositories\AdSpacesRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Laracasts\Flash\Flash;

class AdSpacesController extends Controller
{
    /**
     * @var AdSpacesRepository
     */
    private $adSpacesRepository;

    /**
     * SettingController constructor.
     */
    public function __construct(AdSpacesRepository $adSpacesRepository)
    {
        $this->adSpacesRepository = $adSpacesRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index(): \Illuminate\View\View
    {
        return view('ad_space.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create(Request $request): \Illuminate\View\View
    {
        $sectionID = ($request['id'] === null) ? '1' : $request['id'];
        $adBanner = AdSpaces::where('ad_spaces', $sectionID)->get();

        return view('ad_space.index', compact('sectionID', 'adBanner'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function store(CreateAdSpacesRequest $request): RedirectResponse
    {
        $input = $request->all();
        $this->adSpacesRepository->store($input);
        Flash::success(__('messages.placeholder.adSpaces_updated_successfully'));

        return redirect(route('ad-spaces.create'));
    }
}
