<?php

namespace Vectorcoder\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use Vectorcoder\LaravelInstaller\Helpers\EnvironmentManager;
use Vectorcoder\LaravelInstaller\Helpers\FinalInstallManager;
use Vectorcoder\LaravelInstaller\Helpers\InstalledFileManager;
use Vectorcoder\LaravelInstaller\Events\LaravelInstallerFinished;
use Vectorcoder\LaravelInstaller\Helpers\DatabaseManager;
use DB;

class FinalController extends Controller
{
    /**
     * Update installed file and display finished view.
     *
     * @param InstalledFileManager $fileManager
     * @return \Illuminate\View\View
     */
    public function finish(DatabaseManager $databaseManager,InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment)
    {

        $response = $databaseManager->migrateAndSeed();
        $finalMessages = $finalInstall->runFinal();
       
        if($finalMessages != ''){

          if(config('app.url') == 'production'){
            $environments = 'Live';
          }else{
            $environments = 'Maintenance';
          }

          DB::table('settings')->where('key', 'external_website_link')->update([	 'value' => config('app.url')]);
          DB::table('settings')->where('key', 'app_name')->update(['value' => config('app.name')]);
          DB::table('settings')->where('key', 'environment')->update(['value' => $environments]);

          $finalStatusMessage = $fileManager->update();
        }else{
          $finalStatusMessage = 'Error Check Your Database Credentials. You Might Be something Missing!';
        }
        $finalEnvFile = $environment->getEnvContent();

        event(new LaravelInstallerFinished);
        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
