<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Repositories\RoleRepository;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Laracasts\Flash\Flash;

class RoleController extends AppBaseController
{
    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(RoleRepository $roleRepo)
    {
        $this->roleRepository = $roleRepo;
    }

    /**
     * @return Application|Factory|View
     *
     * @throws Exception
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $permissions = Permission::toBase()->get();

        return view('roles.index', compact('permissions'));
    }

    /**
     * @return Application|Factory|View
     */
    public function create(): \Illuminate\View\View
    {
        $permissions = $this->roleRepository->getPermissions();

        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created Role in storage.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function store(CreateRoleRequest $request): RedirectResponse
    {
        $input = $request->all();
        $input['name'] = str_replace(' ', '_', strtolower($input['display_name']));
        $role = Role::where('name', $input['name'])->exists();

        if ($role) {
            Flash::error(__('messages.placeholder.role_already_exists'));

            return redirect(route('roles.index'));
        }

        $this->roleRepository->store($input);
        Flash::success(__('messages.placeholder.role_created_successfully'));

        return redirect(route('roles.index'));
    }

    /**
     * Show the form for editing the specified Role.
     *
     * @return Application|Factory|View
     */
    public function edit(Role $role): \Illuminate\View\View
    {
        $permissions = $this->roleRepository->getPermissions();
        $selectedPermissions = $role->getAllPermissions()->keyBy('id');

        return view('roles.edit', compact('role', 'permissions', 'selectedPermissions'));
    }

    /**
     * Update the specified Role in storage.
     *
     * @return Application|Redirector|RedirectResponse
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $input['name'] = str_replace(' ', '_', strtolower($request->get('display_name')));
        $roleExists = Role::where('name', $input['name'])->where('id', '!=', $role->id)->exists();

        if ($roleExists) {
            Flash::error(__('messages.placeholder.role_already_exists'));

            return redirect(route('roles.index'));
        }

        $this->roleRepository->update($request->all(), $role->id);
        Flash::success(__('messages.placeholder.role_updated_successfully'));

        return redirect(route('roles.index'));
    }

    /**
     * Remove the specified Role from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_default == 1) {
            return $this->sendError(__('messages.placeholder.default_role_do_not_deleted'));
        }
        $role->delete();

        return $this->sendSuccess(__('messages.placeholder.role_deleted_successfully'));
    }
}
