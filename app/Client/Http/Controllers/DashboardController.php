<?php

namespace App\Client\Http\Controllers;

use App\Client\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DashboardController extends Controller
{
    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_html(
            $this->getBlade($httpConnection, 'client.internal_dashboard', [
                'page' => [  
                    'user' => Client::$user,
                    'subdomains' => Client::$subdomains,
                    'max_logs' => config()->get('expose.max_logged_requests', 10),
                ],

                'jsFile' => $this->getJsFilePath(),
                'cssFile' => $this->getCssFilePath(),
            ])
        ));
    }

    private function getJsFilePath()
    {
        return '/files/build/internal-dashboard/assets/'.collect(scandir(app()->basePath().'/public/build/internal-dashboard/assets/'))->filter(function ($file) {
            return str($file)->startsWith('index-') && str($file)->endsWith('.js');
        })->first();
    }

    private function getCssFilePath()
    {
        return '/files/build/internal-dashboard/assets/'.collect(scandir(app()->basePath().'/public/build/internal-dashboard/assets/'))->filter(function ($file) {
            return str($file)->startsWith('index-') && str($file)->endsWith('.css');
        })->first();
    }
}
