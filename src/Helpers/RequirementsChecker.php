<?php

namespace Vectorcoder\LaravelInstaller\Helpers;

class RequirementsChecker
{

    /**
     * Minimum PHP Version Supported (Override is in installer.php config file).
     *
     * @var _minPhpVersion
     */
    private $_minPhpVersion = '7.0.0';

    /**
     * Check for the server requirements.
     *
     * @param array $requirements
     * @return array
     */
    public function check(array $requirements)
    {
        $results = [];
        $extensions_limit_array = array('max_execution_time'=> 180, 'upload_max_filesize'=> 128, 'post_max_size'=> 128);
        $results['extensions_limit_array'] = $extensions_limit_array;        
        $results['errors'] = '';
        foreach($requirements as $type => $requirement)
        {
            switch ($type) {
                // check php requirements
                case 'php':
                    foreach($requirements[$type] as $requirement)
                    {
                        $results['requirements'][$type][$requirement] = true;
                        //dd($requirement);

                        if($requirement == 'proc_open'){
                            $descriptorspec = array(
                                0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                                1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
                                2 => array("file", public_path("error-output.txt"), "a") // stderr is a file to write to
                             );
                             
                             $cwd = public_path();
                             $env = array('some_option' => 'aeiou');
                             
                             $process = proc_open('php', $descriptorspec, $pipes, $cwd, $env);
                             
                             if (is_resource($process)) {
                                 // $pipes now looks like this:
                                 // 0 => writeable handle connected to child stdin
                                 // 1 => readable handle connected to child stdout
                                 // Any error output will be appended to /tmp/error-output.txt
                             
                                 fwrite($pipes[0], '');
                                 fclose($pipes[0]);
                             
                                 echo stream_get_contents($pipes[1]);
                                 fclose($pipes[1]);
                             
                                 // It is important that you close any pipes before calling
                                 // proc_close in order to avoid a deadlock
                                 $return_value = proc_close($process);
                                 if($return_value == false){                                     
                                    $results['requirements'][$type][$requirement] = true;
                                    $results['errors'] = false;
                                 }

                                 //dd ($return_value);
                             }
                             
                        }elseif($requirement == 'max_execution_time'){
                            $execution_time = ini_get('max_execution_time');
                            
                            if( $execution_time < $extensions_limit_array['max_execution_time'] ){
                                $results['requirements'][$type][$requirement] = false;
                                $results['errors'] = true;
                            }

                        }elseif($requirement == 'upload_max_filesize'){

                            $upload_size = ini_get('upload_max_filesize'); 
                            $upload_size = str_replace('M','',$upload_size);
                            if( $upload_size < $extensions_limit_array['upload_max_filesize'] ){
                                $results['requirements'][$type][$requirement] = false;
                                $results['errors'] = true;
                            }

                        }elseif($requirement == 'post_max_size'){

                            $post_size = ini_get('post_max_size');  
                            $post_size = str_replace('M','',$post_size); 

                            if( $post_size < $extensions_limit_array['post_max_size'] ){
                                $results['requirements'][$type][$requirement] = false;
                                $results['errors'] = true;
                            }
                        }elseif(!extension_loaded($requirement)){

                            $results['requirements'][$type][$requirement] = false;
                            $results['errors'] = true;
                        }
                    }
                    break;
                // check apache requirements
                case 'apache':
                    foreach ($requirements[$type] as $requirement) {
                        // if function doesn't exist we can't check apache modules
                        if(function_exists('apache_get_modules'))
                        {
                            $results['requirements'][$type][$requirement] = true;

                            if(!in_array($requirement,apache_get_modules()))
                            {
                                $results['requirements'][$type][$requirement] = false;

                                $results['errors'] = true;
                            }
                        }
                    }
                    break;
            }
        }

        return $results;
    }

    /**
     * Check PHP version requirement.
     *
     * @return array
     */
    public function checkPHPversion(string $minPhpVersion = null)
    {
        $minVersionPhp = $minPhpVersion;
        $currentPhpVersion = $this->getPhpVersionInfo();
        $supported = false;

        if ($minPhpVersion == null) {
            $minVersionPhp = $this->getMinPhpVersion();
        }

        if (version_compare($currentPhpVersion['version'], $minVersionPhp) >= 0) {
            $supported = true;
        }

        $phpStatus = [
            'full' => $currentPhpVersion['full'],
            'current' => $currentPhpVersion['version'],
            'minimum' => $minVersionPhp,
            'supported' => $supported
        ];

        return $phpStatus;
    }

    /**
     * Get current Php version information
     *
     * @return array
     */
    private static function getPhpVersionInfo()
    {
        $currentVersionFull = PHP_VERSION;
        preg_match("#^\d+(\.\d+)*#", $currentVersionFull, $filtered);
        $currentVersion = $filtered[0];

        return [
            'full' => $currentVersionFull,
            'version' => $currentVersion
        ];
    }

    /**
     * Get minimum PHP version ID.
     *
     * @return string _minPhpVersion
     */
    protected function getMinPhpVersion()
    {
        return $this->_minPhpVersion;
    }

}
