<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AESEncryption
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $decrypted = $request->payload ? $this->cryptoJsAesDecrypt(env('PASSPHRASE'), $request->payload) : null;

        if ($decrypted) {
            $request->merge($decrypted);
            $request->replace($request->except('payload'));
        }

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setContent($this->cryptoJsAesEncrypt(env('PASSPHRASE'), $response->content()));
        }

        return $response;
    }

    /**
     * Decrypt data from a CryptoJS json encoding string
     *
     * @param mixed $passphrase
     * @param mixed $data
     * @return mixed
     */
    public function cryptoJsAesDecrypt($passphrase, $data): ?object
    {
        $data = json_decode($data, true);

        try {

            $salt = hex2bin($data["s"]);
            $iv  = hex2bin($data["iv"]);

        } catch(\Exception $e) { return null; }

        $ct = base64_decode($data["ct"]);
        $concatedPassphrase = $passphrase.$salt;

        $md5 = [];
        $md5[0] = md5($concatedPassphrase, true);

        $result = $md5[0];

        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5($md5[$i - 1].$concatedPassphrase, true);
            $result .= $md5[$i];
        }

        $key = substr($result, 0, 32);
        $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);

        return json_decode($data, true);
    }

    /**
     * Encrypt value to a cryptojs compatiable json encoding string
     *
     * @param mixed $passphrase
     * @param mixed $value
     * @return string
     */
    public function cryptoJsAesEncrypt($passphrase, $value): string
    {
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';

        while (strlen($salted) < 48) {
            $dx = md5($dx.$passphrase.$salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);

        $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
        $data = ['payload' => ["ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt)]];

        return json_encode($data);
    }
}
