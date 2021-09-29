<?php

namespace Vectorcoder\LaravelInstaller\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Vectorcoder\LaravelInstaller\Helpers\EnvironmentManager;
use Vectorcoder\LaravelInstaller\Helpers\DatabaseManager;
use Vectorcoder\LaravelInstaller\Events\EnvironmentSaved;
use Validator;
use Session;
use Illuminate\Validation\Rule;
use DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Artisan;

class EnvironmentController extends Controller
{
    /**
     * @var EnvironmentManager
     */
    protected $EnvironmentManager;
    private $databaseManager;
    private $ticketRepository;
    private $api_url = 'https://api.themes-coder.com';
    /**
     * @param EnvironmentManager $environmentManager
     */
    public function __construct(EnvironmentManager $environmentManager,DatabaseManager $databaseManager)
    {
        $this->EnvironmentManager = $environmentManager;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Display the Environment menu page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentMenu()
    {
        return view('vendor.installer.environment')->with('error',0)->with('msg','');
    }

    protected function curl( $url ) {

        if ( empty( $url) ) return false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		$response = curl_exec($ch);
		curl_close($ch);
		return json_decode($response);

}

    /**
     * Display the Environment page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentWizard()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();
        return view('vendor.installer.environment-wizard', compact('envConfig'))->with('error',0)->with('msg','');
    }

    /**
     * Display the Environment page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentClassic()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();
        return view('vendor.installer.environment-classic', compact('envConfig'));
    }


    /**
     * Processes the newly saved environment configuration (Classic).
     *
     * @param Request $input
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveClassic(Request $input, Redirector $redirect)
    {
        $message = $this->EnvironmentManager->saveFileClassic($input);

        event(new EnvironmentSaved($input));

        return $redirect->route('LaravelInstaller::environmentClassic')
                        ->with(['message' => $message]);
    }

    /**
     * Processes the newly saved environment configuration (Form Wizard).
     *
     * @param Request $request
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveWizard(Request $request, Redirector $redirect)
    {
   try{
      $rules = array('rules' => array(
        'app_name'              => 'required|string|max:50',
        'environment'           => 'required|string|max:50',
        'environment_custom'    => 'required_if:environment,other|max:50',
        'app_debug' => array(
          Rule::in(['true', 'false']),
        )
      ),
      'app_log_level'         => 'required|string|max:50',
      'app_url'               => 'required|url',
      'database_connection'   => 'required|string|max:50',
      'database_hostname'     => 'required|string|max:50',
      'database_port'         => 'required|numeric',
      'database_name'         => 'required|string|max:50',
      'database_username'     => 'string|max:50',
      'broadcast_driver'      => 'string|max:50',
      'cache_driver'          => 'string|max:50',
      'session_driver'        => 'string|max:50',
      'queue_driver'          => 'string|max:50',
      'redis_hostname'        => 'string|max:50',
      'redis_password'        => 'string|max:50',
      'redis_port'            => 'numeric',
      'mail_driver'           => 'required',
      'mail_host'             => 'required',
      'mail_port'             => 'required',
      'mail_username'         => 'required',
      'mail_password'         => 'required',
      'mail_encryption'       => 'required',
      'pusher_app_id'         => 'max:50',
      'pusher_app_key'        => 'max:50',
      'pusher_app_secret'     => 'max:50',
      'purchase_code'         => 'required',
      // 'database_password'     => 'required'

    );

    $messages = [
        'environment_custom.required_if' =>'Ops!! Something Went Wrong!',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {

        $errors = $validator->errors();
      //  dd(Input::all());
        return redirect()->back()->withInput($request->all())->withErrors($validator);
    }


    $purchase_code = $request->purchase_code;
      // Check for empty fields
      if ( empty( $purchase_code ) ) {
        return false;
      }
      // Gets author data & prepare verification vars
      $purchase_code 	= urlencode( $purchase_code );
      $current_site_url = $_SERVER['REQUEST_URI'];
      $url = $this->api_url. '/api.php?code=' . $purchase_code."&url=".$current_site_url;
      $response = $this->curl( $url );
      $messages = [
          'You have entered invalid purchase code. Please enter a valid purchase code!' =>'You have entered invalid purchase code. Please enter a valid purchase code!',
      ];
      if (isset($response->error) && $response->error == '404' ) {
        return redirect()->back()->withInput($request->all())->withErrors($messages);
      }elseif(isset($response->id) and !empty($response->id)){
          $purchase_id = $response->id;
          $ids = array("31354329","31137293", "26827707", "26840547","20952416", "20757378", "22334657", "28681648", "27944998");

          if(!in_array($purchase_id,$ids)){

            $messages = [
                'You have entered invalid purchase code. Please enter a valid purchase code!' =>'You have entered invalid purchase code. Please enter a valid purchase code!',
            ];
            return redirect()->back()->withInput($request->all())->withErrors($messages);
          }
      }
    

        if(file_exists(base_path('bootstrap/cache/config.php'))){
         unlink(base_path('bootstrap/cache/config.php'));
        }
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        $results = $this->EnvironmentManager->saveFileWizard($request);
        Artisan::call('config:cache'); 
        $response = $this->databaseManager->migrateAndSeed();

       
        event(new EnvironmentSaved($request));

        return $redirect->route('LaravelInstaller::database')
                        ->with(['results' => $results]);
      }
      catch (\Exception $e) {
        
        $msg = $e->getCode();
         
        if ($e->getCode() == '1045')
        {
            return $redirect->to('error/'.$msg); 
        } else {     
        echo $e->getMessage();
        }

      }
    }
}
