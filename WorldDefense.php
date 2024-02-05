<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class WorldDefense implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {

        // enable or disable spam prevention, use a constant: ex WORLDEFENSE = true
        if(true){

            /**
             * User are locked from list and session are destroyed
             */
            $ipAddress = $request->getIPAddress();

            // Use a file storage .json or use here this example
            $addressLocked = [
                '::2',             // IP ::2
                '127\.0\.',        // IP start from 127.0.*
                '127\.0\.0\.1',    // IP Static 127.0.0.1
                'ec2-47-128'       // Proxy Start from
            ];

            foreach ($addressLocked as $pattern) {
                if (preg_match("/$pattern/", $ipAddress)) {
                    session_destroy();
                    header('HTTP/1.0 403 Forbidden');
                    $data = array_merge(['message' => 'Country are Locked, SPAM prevention']);
                    die(view('errors/html/error_403', $data));
                }
            }
            
            /**
             * Check if the session "worldcheckSession" exist, if true ? lock for prevent call API.
             */
            if (session()->has('worldcheckSession')) {
                header('HTTP/1.0 403 Forbidden');
                $data = array_merge(['message' => 'Country are Locked, SPAM prevention']);
                die(view('errors/html/error_403', $data));
            }

            /**
             * Check the session "worldcheckSession" exist and CI is production, and entry condition for check continent user.
             */
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
                $BlockCountry = array(
                    "RU",  // Russia
                    "SG",  // Singapore
                    "CN",  // China
                    "HK",  // Hong Kong
                    "TW",  // Taiwan
                    "KP",  // Korea North
                    "KR",  // Korea South
                );
                // Country are find, lock the user by session and prevent call API
                if(isset($Read->country_code)){
                    if(in_array($Read->country_code, $BlockCountry)){
                        session()->set('worldcheckSession', $request->getIPAddress());
                        session()->set('worldcheckSessionLock', true);
                        header('HTTP/1.0 403 Forbidden');
                        $data = array_merge(['message' => 'Country are Locked, SPAM prevention']);
                        die(view('errors/html/error_403', $data));
                    }
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null){}

}
