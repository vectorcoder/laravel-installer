<?php

namespace Vectorcoder\LaravelInstaller\Controllers;

use App\Http\Requests;
use Illuminate\Routing\Controller;
use Vectorcoder\LaravelInstaller\Helpers\PermissionsChecker;
use Session;

class PermissionsController extends Controller
{

    /**
     * @var PermissionsChecker
     */
    protected $permissions;

    /**
     * @param PermissionsChecker $checker
     */
    public function __construct(PermissionsChecker $checker)
    {
        $this->permissions = $checker;
    }

    /**
     * Display the permissions check page.
     *
     * @return \Illuminate\View\View
     */
    public function permissions()
    {
      $permission = array(
        'storage/framework/'     => '775',
        'storage/logs/'          => '775',
        'bootstrap/cache/'       => '775');
        $permissions = $this->permissions->check(
            $permission
        );
        return view('vendor.installer.permissions', compact('permissions'));
    }
}
