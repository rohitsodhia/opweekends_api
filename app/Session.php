<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{

	protected $primaryKey = 'sessionId';
	protected $dates = [
		'created_at',
		'updated_at',
		'start',
		'end',
	];
	protected $casts = [
		'ownerId' => 'integer',
		'typeId' => 'integer',
		'numSeats' => 'integer',
		'seatsFilled' => 'interger',
		'approved' => 'boolean',
	];

	public function owner() {
		return $this->hasOne('App\User', 'userId', 'ownerId');
	}

	public function type() {
		return $this->hasOne('App\SessionType', 'typeId', 'typeId');
	}

}
