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
		$data = $request->json();
		if (
			!$this->currentUser->admin &&
			(
				(
					$data->has('org') &&
					!$this->currentUser->isOrgAdmin($data->get('org')['orgId'])
				) ||
				!$data->has('org')
			)
		) {
			return [
				'success' => false,
				'errors' => [
					'notAdmin'
				]
			];
		}

		$email = $data->get('email');
		if ($data->has('org')) {
			$org = [
				'orgId' => (int) $data->get('org')['orgId'],
				'admin' => (bool) $data->get('org')['admin']
			];
			$password = '';
		} else {
			$password = $data->get('password');
		}
		$name = $data->get('name');
		$admin = $data->has('admin') ? (bool) $data->get('admin') : false;

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
		$data = $request->json();
		if (
			!$data->has('email') || strlen($data->get('email')) === 0 ||
			!$data->has('hash') || strlen($data->get('hash')) === 0
		) {
			return [
				'success' => false,
				'errors' => ['invalidData']
			];
		}
		$email = $data->get('email');
		$hash = $data->get('hash');
		$user = User::where('email', $email)->first();
		if ($user && $user->activatedOn === null && $user->getActivationHash() === $hash) {
			$user->activatedOn = \Carbon\Carbon::now();
			$user->save();
			$response = [
				'success' => true,
			];
			var_dump($user->password); return 1;
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
		$data = $request->json();
		if (
			!$this->currentUser->get() &&
			(
				!$data->has('auth') ||
				!isset($data->get('auth')['email']) || strlen($data->get('auth')['email']) === 0 ||
				!isset($data->get('auth')['hash']) || strlen($data->get('auth')['hash']) === 0
			)
		) {
			return [
				'success' => false,
				'errors' => ['invalidData']
			];
		}

		if (!$data->has('password') || strlen($data->get('password')) === 0) {
			return [
				'success' => false,
				'errors' => ['noPassword']
			];
		}
		$password = $data->get('password');

		if ($this->currentUser->get()) {

		} else {
			$email = $data->get('auth')['email'];
			$hash = $data->get('auth')['hash'];
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
		$data = $request->json();
		$userId = (int) $data->get('userId');
		$orgId = (int) $data->get('orgId');
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
