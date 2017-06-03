<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\User;

class AuthController extends Controller
{

	public function validateCredentials(Request $request) {
		$email = $request->email;
		$password = $request->password;

		$user = User::where('email', $email)->first();
		if ($user && $user->activatedOn === null) {
			return [
				'success' => false,
				'errors' => [
					'accountInactive'
				]
			];
		} elseif (!$user || !password_verify($password, $user->password)) {
			return [
				'success' => false,
				'errors' => [
					'invalidLogin'
				]
			];
		} else {
			$user->lastLogin = \Carbon\Carbon::now();
			$user->save();

			$signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
			$now = time();
			$token = (new \Lcobucci\JWT\Builder())
						->setIssuer('http://api.' . env('APP_DOMAIN'))
						->setIssuedAt($now)
						->setExpiration($now + 60 * 60 * 24 * 7)
						->set('userId', $user->userId)
						->sign($signer, env('JWT_SECRET'))
						->getToken();
			return [
				'success' => true,
				'user' => [
					'userId' => $user->userId,
					'email' => $user->email,
					'name' => $user->name,
					'admin' => $user->admin,
				],
				'token' => (string) $token
			];
		}
	}

	public function orgMemberships(Request $request, $userId) {
		$rOrgs = DB::table('orgMemberships')
			->select('orgId', 'admin')
			->where('userId', $userId)
			->get();
		$orgs = [];
		foreach ($rOrgs as $org) {
			$orgs[] = [
				'orgId' => (int) $org->orgId,
				'admin' => (bool) $org->admin
			];
		}
		return [
			'userId' => (int) $userId,
			'orgs' => $orgs
		];
	}

	static public function checkMembership($userId, $orgId) {
		$membership = DB::table('orgMemberships')
			->select('admin')
			->where('userId', $userId)
			->where('orgId', $orgId)
			->first();
		if ($membership) {
			return ['member' => true, 'admin' => (bool) $membership];
		} else {
			return ['member' => false];
		}
	}

	public function validateMembership(Request $request) {
		$response = [
			'userId' => (int) $request->userId,
			'orgId' => (int) $request->orgId,
		];
		$response = array_merge($response, AuthController::checkMembership($request->userId, $request->orgId));
		return $response;
	}

}
