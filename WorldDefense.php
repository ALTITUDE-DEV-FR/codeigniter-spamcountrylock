<?php
/*
* Filters > WorldDefense.php
*/
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class WorldDefense implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if(!empty(session('worldcheckSessionLock'))){
            header('HTTP/1.0 403 Forbidden');
            $data = array_merge(['message' => 'Country are Locked, mesure SPAM prevention']);
            echo view('errors/html/error_403', $data);
            exit;
        }
        if(ENVIRONMENT == 'production' && !session('worldcheckSession')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.ip2location.io/?' . http_build_query([
                'ip'	  => $request->getIPAddress(),
                'key'     => '',
                'format'  => 'json'
            ]));
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $Read     = json_decode($response, false);
            session()->set('worldcheckSession', $request->getIPAddress());
            $BlockCountry = array(
                "RU",  // Russia
                "SG",  // Singapore
                "CN",  // China
                "HK",  // Hong Kong
                "TW",  // Taiwan
                "KP",  // Korea North
                "KR",  // Korea South
            );
            if(isset($Read->country_code)){
                if(in_array($Read->country_code, $BlockCountry)){
                	session()->set('worldcheckSessionLock', true);
                    header('HTTP/1.0 403 Forbidden');
                    $data = array_merge(['message' => 'Country <b>'.$Read->country_code.'</b> are Locked, mesure SPAM prevention']);
                    echo view('errors/html/error_403', $data);
                    exit;
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Code à exécuter après chaque action
    }
}
