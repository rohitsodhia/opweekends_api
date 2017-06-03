<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;

use Closure;

use App\User;
use App\CurrentUser;

use Exception;

class AuthMiddleware
{

	private $currentUser = null;

	public function __construct(CurrentUser $currentUser) {
		$this->currentUser = &$currentUser;
	}

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role = null)
    {
		$currentUser = null;
		$token = substr($request->headers->get('Authorization'), 7);
		if ($token) {
			try {
				$token = (new \Lcobucci\JWT\Parser())->parse($token);
			} catch (Exception $e) {
				return response()->json([
					'success' => false,
					'errors' => ['invalidToken']
				]);
			}
			$signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
			if ($token->verify($signer, env('JWT_SECRET'))) {
				$currentUser = User::find($token->getClaim('userId'));
			} else {
				return response()->json([
					'success' => false,
					'errors' => ['invalidToken']
				]);
			}
		}

		if ($role) {
			if (!$token || !$currentUser) {
				return response()->json([
					'success' => false,
					'errors' => ['notLoggedIn']
				]);
			}
		}

		$this->currentUser->set($currentUser);

        return $next($request);
    }

}
