<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Org;

class OrgController extends Controller
{

	public function show(Request $request, $org)
	{
		if (is_numeric($org)) {
			$org = Org::find((int) $org);
		} else {
			$org = Org::where('slug', $org)->first();
		}

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg' => 'Invalid organization Id/slug']];
		} else {
			$response = [
				'success' => true,
				'org' => [
					'orgId' => $org->orgId,
					'name' => $org->name,
					'slug' => $org->slug,
				],
			];
			if ($request->has('full') && $request->full === 'true') {
				$response['org']['start'] = $org['start']->timestamp > 0 ? $org['start']->timestamp : null;
				$response['org']['end'] = $org['end']->timestamp > 0 ? $org['end']->timestamp : null;
				$response['org']['blocks'] = $org['blocks'] ? $org['blocks'] : null;
			}
			if ($request->has('hasPermission') && $request->hasPermission === 'true') {
				$currentUser = app('App\CurrentUser')->get();
				if ($currentUser) {
					$permission = AuthController::checkMembership($currentUser->userId, $org->orgId);
					$response['permission'] = $permission;
				} else {
					$response['permission'] = false;
				}
			}
			return $response;
		}
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

	public function update(Request $request, $orgId) {
		$data = $request->json();
		$org = Org::find((int) $orgId);
		$changed = false;
		if ($data->has('start')) {
			$org->start = $data->get('start');
			$changed = true;
		}
		if ($data->has('end')) {
			$org->end = $data->get('end');
			$changed = true;
		}
		if ($data->has('blocks')) {
			$org->blocks = $data->get('blocks');
			$changed = true;
		}
		if ($changed) {
			$org->save();
			return ['success' => true];
		} else {
			return ['success' => false];
		}
	}

}
