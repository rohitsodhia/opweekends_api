<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\User;
use App\CurrentUser;
use App\Http\Controllers\AuthController;

class AuthController extends Controller
{

	private $currentUser = null;

	public function __construct(CurrentUser $currentUser) {
		$this->currentUser = &$currentUser;
	}

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

			$token = $this->generateToken($user->userId);
			$userData = [
				'userId' => $user->userId,
				'email' => $user->email,
				'name' => $user->name,
				'admin' => $user->admin,
				'orgs' => []
			];
			$orgs = $this->orgMemberships($request, $user->userId);
			$userData['orgs'] = $orgs['orgs'];
			return [
				'success' => true,
				'user' => $user,
				'token' => (string) $token
			];
		}
	}

	public function validateToken(Request $request) {
		if ($this->currentUser) {
			$user = [
				'userId' => $this->currentUser->userId,
				'email' => $this->currentUser->email,
				'name' => $this->currentUser->name,
				'admin' => $this->currentUser->admin,
				'orgs' => []
			];
			foreach ($this->currentUser->orgs as $org) {
				$user['orgs'][$org->orgId] = [
					'orgId' => $org->orgId,
					'name' => $org->name,
					'slug' => $org->slug,
					'admin' => (bool) $org->pivot->admin
				];
			}
			return [
				'success' => true,
				'user' => $user,
				'token' => $this->generateToken($user['userId'])
			];
		} else {
			return [
				'success' => false,
				'errors' => ['invalidToken']
			];
		}
	}

	private function generateToken($userId) {
		$signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
		$now = time();
		$token = (new \Lcobucci\JWT\Builder())
					->setIssuer('http://api.' . env('APP_DOMAIN'))
					->setIssuedAt($now)
					->setExpiration($now + 60 * 60 * 24 * 7)
					->set('userId', $userId)
					->sign($signer, env('JWT_SECRET'))
					->getToken();

		return (string) $token;
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

	static public function checkMembership($orgId, $userId = null) {
		if ($userId === null) {
			$currentUser = app('App\CurrentUser')->get();
			if (!$currentUser) {
				return ['member' => false];
			}
			$userId = $currentUser->userId;
		}
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

	static public function isOrgAdmin($orgId, $userId = null) {
		$membership = AuthController::checkMembership($orgId, $userId);
		if (!$membership['member'] || !$membership['admin']) {
			return false;
		} else {
			return true;
		}
	}

	public function validateMembership(Request $request) {
		$response = [
			'userId' => (int) $request->userId,
			'orgId' => (int) $request->orgId,
		];
		$response = array_merge($response, AuthController::checkMembership($request->orgId, $request->userId));
		return $response;
	}

}
