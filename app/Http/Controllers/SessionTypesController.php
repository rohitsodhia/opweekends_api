<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\SessionType;
use App\Http\Controllers\AuthController;

class SessionTypesController extends Controller
{

	public function index(Request $request, $org)
	{
		$org = findOrg($org);

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg']];
		} else {
			$types = [];
			foreach ($org->sessionTypes as $type) {
				$types[] = [
					'typeId' => $type->typeId,
					'type' => $type->type
				];
			}
			$response = [
				'success' => true,
				'types' => $types,
			];
			return $response;
		}
	}

	public function store(Request $request, $org)
	{
		$data = $request->json();
		$org = findOrg($org);

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg']];
		} else {
			if (AuthController::isOrgAdmin($org->orgId)) {
				return ['success' => false, 'errors' => ['unauthorized']];
			}

			$errors = [];
			$type = trim($data->get('type'));
			if (strlen($type) === 0) {
				$errors[] = 'Invalid type';
			} else {
				$typeCheck = SessionType::where([['orgId', $org->orgId], ['type', $type]])->first();
				if ($typeCheck) {
					$errors[] = 'Duplicate type';
				}
			}

			if (count($errors)) {
				$response = [
					'success' => false,
					'errors' => $errors,
				];
			} else {
				$newType = new SessionType();
				$newType->orgId = $org->orgId;
				$newType->type = $type;
				$newType->save();

				$response = [
					'success' => true,
					'type' => [
						'typeId' => $newType->typeId,
						'type' => $type
					],
				];
			}

			return $response;
		}
	}

	public function update(Request $request, $org, int $typeId) {
		$data = $request->json();
		$org = findOrg($org);

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg']];
		} else {
			if (AuthController::isOrgAdmin($org->orgId)) {
				return ['success' => false, 'errors' => ['unauthorized']];
			}

			$errors = [];
			$type = trim($data->get('type'));
			if ($typeId <= 0 || strlen($type) === 0) {
				$errors[] = 'Invalid type/typeId';
			} else {
				$typeCheck = SessionType::find($typeId);
				if (!$typeCheck) {
					$errors[] = 'Invalid type';
				}
			}

			if (count($errors)) {
				$response = [
					'success' => false,
					'errors' => $errors,
				];
			} else {
				$typeCheck->type = $type;
				$typeCheck->save();
				$response = [
					'success' => true
				];
			}

			return $response;
		}
	}

	public function destroy(Request $request, $org, int $typeId) {
		$org = findOrg($org);

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg']];
		} else {
			if (AuthController::isOrgAdmin($org->orgId)) {
				return ['success' => false, 'errors' => ['unauthorized']];
			}

			$errors = [];
			if (strlen($typeId) === 0) {
				$errors[] = 'Invalid typeId';
			} else {
				$typeCheck = SessionType::find($typeId);
				if (!$typeCheck) {
					$errors[] = 'Invalid type';
				}
			}

			if (count($errors)) {
				$response = [
					'success' => false,
					'errors' => $errors,
				];
			} else {
				$typeCheck->delete();
				$response = [
					'success' => true
				];
			}

			return $response;
		}
	}

}
