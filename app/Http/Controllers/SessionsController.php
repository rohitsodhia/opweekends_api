<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Session;
use App\SessionType;
use App\Http\Controllers\AuthController;

class SessionsController extends Controller
{

	public function index(Request $request)
	{
		if (!$request->has('org')) {
			return ['success' => false, 'errors' => ['invalidRequest']];
		}
		$org = findOrg($request->get('org'));

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg']];
		} else {
			$rawSessions = Session::where('orgId', $org->orgId);
			if ($request->has('approved')) {
				$rawSessions->where('approved', $request->get('approved') === 'true');
			}
			$rawSessions = $rawSessions->get();
			$sessions = [];
			foreach ($rawSessions as $session) {
				$sessions[] = [
					'sessionId' => $session->sessionId,
					'title' => $session->title,
					'owner' => [
						'userId' => $session->owner->userId,
						'name' => $session->owner->name
					],
					'gm' => $session->gm,
					'type' => $session->type->type,
					'description' => $session->description,
					'location' => $session->location,
					'proposedStart' => $session->proposedStart,
					'start' => $session->start !== null ? $session->start->timestamp : null,
					'end' => $session->end !== null ? $session->end->timestamp : null,
					'numSeats' => $session->numSeats,
					'seatsFilled' => $session->seatsFilled,
					'approved' => $session->approved
				];
			}
			return [
				'success' => true,
				'sessions' => $sessions,
			];
		}
	}

	public function store(Request $request)
	{
		$data = $request->json();
		$org = findOrg($data->get('org'));

		if (!$org) {
			return ['success' => false, 'errors' => ['invalidOrg']];
		} else {
			if (!AuthController::checkMembership($org->orgId)['member']) {
				return ['success' => false, 'errors' => ['unauthorized']];
			}

			$errors = [];
			$fields = [
				'title',
				'gm',
				'typeId',
				'description',
				'proposedStart',
				'numSeats'
			];
			$errors['missingField'] = [];
			foreach ($fields as $field) {
				if (!$data->has($field) || strlen($data->get($field)) === 0) {
					$errors['missingField'][] = $field;
				}
			}
			if (count($errors['missingField']) === 0) {
				unset($errors['missingField']);
			}
			$typeId = (int) $data->get('typeId');
			if ($typeId <= 0) {
				$errors[] = 'Invalid type';
			} else {
				$typeCheck = SessionType::find($typeId);
				if (!$typeCheck || $typeCheck->orgId !== $org->orgId) {
					$errors[] = 'Invalid type';
				}
			}
			$numSeats = (int) $data->get('numSeats');
			if ($numSeats <= 0) {
				$errors[] = 'Invalid number of seats';
			}

			if (count($errors)) {
				$response = [
					'success' => false,
					'errors' => $errors,
				];
			} else {
				$newSession = new Session();
				$newSession->orgId = $org->orgId;
				foreach ($fields as $field) {
					$newSession->{$field} = $data->get($field);
				}
				$newSession->ownerId = app('App\CurrentUser')->get()->userId;
				$newSession->save();

				$response = [
					'success' => true,
					'session' => $newSession->sessionId,
				];
			}

			return $response;
		}
	}

	public function update(Request $request, int $sessionId)
	{
		$data = $request->json();
		$session = Session::find($sessionId);

		if (!$session) {
			return ['success' => false, 'errors' => ['invalidSession']];
		} else {
			$orgAdmin = AuthController::isOrgAdmin($session->orgId);

			$errors = [];
			$fields = [
				'title',
				'gm',
				'typeId',
				'description',
				'location',
				'proposedStart',
				'start',
				'end',
				'numSeats',
				'approved'
			];
			$errors['missingField'] = [];
			foreach ($fields as $field) {
				if ($data->get('approved') !== true && in_array($field, ['start', 'end'])) {
					continue;
				}
				if (!$data->has($field) || $data->get($field) === null) {
					$errors['missingField'][] = $field;
				}
			}
			if (count($errors['missingField']) === 0) {
				unset($errors['missingField']);
			}
			$typeId = (int) $data->get('typeId');
			if ($typeId <= 0) {
				$errors[] = 'Invalid type';
			} else {
				$typeCheck = SessionType::find($typeId);
				if (!$typeCheck || $typeCheck->orgId !== $session->orgId) {
					$errors[] = 'Invalid type';
				}
			}
			$numSeats = (int) $data->get('numSeats');
			if ($numSeats <= 0) {
				$errors[] = 'Invalid number of seats';
			}
			if ($data->get('start') > $data->get('end')) {
				$errors[] = 'Invalid date range';
			}

			if (count($errors)) {
				$response = [
					'success' => false,
					'errors' => $errors,
				];
			} else {
				foreach ($fields as $field) {
					$session->{$field} = $data->get($field);
				}
				$session->save();

				$response = [
					'success' => true,
					'session' => [
						'sessionId' => $session->sessionId,
						'title' => $session->title,
						'owner' => [
							'userId' => $session->owner->userId,
							'name' => $session->owner->name
						],
						'gm' => $session->gm,
						'type' => $session->type->type,
						'description' => $session->description,
						'location' => $session->location,
						'proposedStart' => $session->proposedStart,
						'start' => $session->start !== null ? $session->start->timestamp : null,
						'end' => $session->end !== null ? $session->end->timestamp : null,
						'numSeats' => $session->numSeats,
						'seatsFilled' => $session->seatsFilled,
						'approved' => $session->approved
					]
				];
			}

			return $response;
		}
	}

	public function destroy(Request $request, int $sessionId) {
		$session = Session::find($sessionId);

		if (!$session) {
			return ['success' => false, 'errors' => ['invalidSession']];
		} else {
			if (!AuthController::isOrgAdmin($org->orgId)) {
				return ['success' => false, 'errors' => ['unauthorized']];
			}

			$session->delete();
			$response = [
				'success' => true,
			];

			return $response;
		}
	}

}
