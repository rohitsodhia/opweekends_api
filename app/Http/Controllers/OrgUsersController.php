<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Org;

class OrgUsersController extends Controller
{

	public function index(Request $request, $org)
	{
		$org = findOrg($org);

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg']];
		} elseif (!AuthController::isOrgAdmin($org->orgId)) {
			return ['success' => false, 'errors' => ['unauthorized']];
		}

		$users = [];
		foreach ($org->users as $user) {
			$users[$user->userId] = [
				'userId' => $user->userId,
				'email' => $user->email,
				'name' => $user->name,
				'admin' => (bool) $user->pivot->admin
			];
		}
		$response = [
			'success' => true,
			'users' => $users,
		];
		return $response;
	}

	public function store(Request $request)
	{
		$errors = [];
		$name = trim($request->input('name'));
		if (strlen($name) === 0) {
			$errors['noName'] = 'No name provided';
		}
		$nameChars = strlen(preg_replace('#\W#', '', $name));
		if ($nameChars < 3) {
			$errors['shortName'] = 'Name too short (3 char minimum)';
		}
		$slug = trim($request->input('slug'));
		if (strlen($slug) === 0) {
			$slug = $name;
		}
		$slug = generateSlug($slug);
		if (strlen($slug) === 0) {
			$errors['noSlug'] = 'No slug provided or name too short to generate slug';
		} elseif (strlen($slug) < 3) {
			$errors['shortSlug'] = 'Slug too short (3 char minimum)';
		}

		if (count($errors)) {
			return ['success' => false, 'errors' => $errors];
		}

		$org = new Org;
		$org->name = $name;
		$org->slug = $slug;
		$org->save();

		return ['success' => true, 'orgId' => $org->orgId];
	}

}
