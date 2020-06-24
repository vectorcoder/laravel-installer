<?php

namespace Vectorcoder\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use Vectorcoder\LaravelInstaller\Helpers\RequirementsChecker;

class RequirementsController extends Controller
{
    /**
     * @var RequirementsChecker
     */
    protected $requirements;

    /**
     * @param RequirementsChecker $checker
     */
    public function __construct(RequirementsChecker $checker)
    {
        $this->requirements = $checker;
    }

    /**
     * Display the requirements page.
     *
     * @return \Illuminate\View\View
     */
    public function requirements()
    {
        $requirements = array('php' => array(
            'openssl',
            'pdo',
            'mbstring',
            'tokenizer',
            'JSON',
            'cURL',
            'xml',
            'intl',
            'ctype',
            'proc_open',
            'zip',
            'gd',
            'max_execution_time',
            'upload_max_filesize',
            'post_max_size',
        ));

        $minPhpVersion = 'PHP 7.2';

        $phpSupportInfo = $this->requirements->checkPHPversion(
            $minPhpVersion
        );
        $requirements = $this->requirements->check(
            $requirements
        );
        return view('vendor.installer.requirements', compact('requirements', 'phpSupportInfo'));
    }
}
