<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\CurrentUser;
use App\Org;

class UserController extends Controller
{

	private $currentUser = null;

	public function __construct(CurrentUser $currentUser) {
		$this->currentUser = &$currentUser;
	}

	public function store(Request $request) {
		if (
			!$this->currentUser->admin &&
			(
				(
					$request->has('org') &&
					!$this->currentUser->isOrgAdmin($request->org['orgId'])
				) ||
				!$request->has('org')
			)
		) {
			return [
				'success' => false,
				'errors' => [
					'notAdmin'
				]
			];
		}

		$email = $request->email;
		if ($request->has('org')) {
			$org = [
				'orgId' => (int) $request->org['orgId'],
				'admin' => (bool) $request->org['admin']
			];
			$password = '';
		} else {
			$password = $request->password;
		}
		$name = $request->name;
		$admin = $request->has('admin') ? (bool) $request->admin : false;

		if (strlen($email) === 0 || strlen($name) === 0) {
			$errors = [];
			if (strlen($email) === 0) {
				$errors[] = 'noEmail';
			}
			if (strlen($name) === 0) {
				$errors[] = 'noName';
			}
			return [
				'success' => false,
				'errors' => $errors
			];
		}

		$userExists = User::where('email', $email)->first();
		if ($userExists) {
			return [
				'success' => false,
				'errors' => [
					'userExists'
				]
			];
		}

		$user = new User;
		$user->email = $email;
		$user->setPassword($password);
		$user->name = $name;
		$user->admin = $admin;
		$user->save();

		$userReturn = [
			'userId' => $user->userId,
			'email' => $user->email,
			'name' => $user->name,
			'admin' => $user->admin,
			'memberships' => []
		];

		if (isset($org)) {
			$user->orgs()->attach($org['orgId']);
			$org = Org::find($org['orgId']);
			$emailVars = [
				'org' => $org->name,
				'orgSlug' => $org->slug,
				'activationLink' => $user->getActivationLink($org->slug)
			];
			ob_start();
			include(base_path() . '/resources/emails/newOrgUser.php');
			$email = ob_get_contents();
			ob_end_clean();
			mail($email, "You've been registered to {$emailVars['org']}", $email, "Content-type: text/html\r\nFrom: OpWeeknds <contact@opweekends.com>");
			$userReturn['memberships'][$org->orgId] = [
				'member' => true,
				'admin' => $org['admin']
			];
		}

		return [
			'success' => true,
			'user' => $userReturn,
			'activationLink' => $emailVars['activationLink']
		];
	}

	public function activateAccount(Request $request) {
		if (
			!$request->has('email') || strlen($request->email) === 0 ||
			!$request->has('hash') || strlen($request->hash) === 0
		) {
			return [
				'success' => false,
				'errors' => ['invalidData']
			];
		}
		$email = $request->email;
		$hash = $request->hash;
		$user = User::where('email', $email)->first();
		if ($user && $user->activatedOn === null && $user->getActivationHash() === $hash) {
			$user->activatedOn = \Carbon\Carbon::now();
			$user->save();
			$response = [
				'success' => true,
			];
			if ($user->password === '') {
				$response['setPass'] = true;
			}
			return $response;
		} else {
			return [
				'success' => false
			];
		}
	}

	public function setPassword(Request $request) {
		if (
			!$this->currentUser->get() &&
			(
				!$request->has('auth') ||
				!isset($request->auth['email']) || strlen($request->auth['email']) === 0 ||
				!isset($request->auth['hash']) || strlen($request->auth['hash']) === 0
			)
		) {
			return [
				'success' => false,
				'errors' => ['invalidData']
			];
		}

		if (!$request->has('password') || strlen($request->password) === 0) {
			return [
				'success' => false,
				'errors' => ['noPassword']
			];
		}
		$password = $request->password;

		if ($this->currentUser->get()) {

		} else {
			$email = $request->auth['email'];
			$hash = $request->auth['hash'];
			$user = User::where('email', $email)->first();
			if ($user && $user->getActivationHash() === $hash) {
				$user->setPassword($password);
				$user->save();
				return [
					'success' => true,
				];
			} else {
				return [
					'success' => false,
					'errors' => ['invalidData']
				];
			}
		}
	}

	public function toggleOrgAdmin(Request $request) {
		$userId = (int) $request->userId;
		$orgId = (int) $request->orgId;
		if ($userId <= 0 || $orgId <= 0) {
			return [
				'success' => false,
				'errors' => [
					'invalidData'
				]
			];
		}

		if (
			!$this->currentUser->admin &&
			!$this->currentUser->isOrgAdmin($orgId)
		) {
			return [
				'success' => false,
				'errors' => [
					'insufficientPermissions'
				]
			];
		}

		app('db')->update('UPDATE orgMemberships SET admin = !admin WHERE userId = :userId AND orgId = :orgId LIMIT 1', ['userId' => $userId, 'orgId' => $orgId]);

		return [
			'success' => true
		];
	}

	public function removeFromOrg(Request $request, $userId, $orgId) {
		if (
			!$this->currentUser->admin &&
			!$this->currentUser->isOrgAdmin($orgId)
		) {
			return [
				'success' => false,
				'errors' => [
					'notAdmin'
				]
			];
		}

		$detached = app('db')->table('orgMemberships')->where('userId', $userId)->where('orgId', $orgId)->delete();
		return [
			'success' => (bool) $detached
		];
	}

}
