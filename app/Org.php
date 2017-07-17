<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Org extends Model
{

	protected $primaryKey = 'orgId';
	protected $dates = [
		'created_at',
		'updated_at',
		'start',
		'end',
	];
	protected $casts = [
		'blocks' => 'array',
		'sessionSubmission' => 'boolean',
	];

/*	public function getBlocksAttribute($value) {
		return unserialize($value);
	}

	public function setBlocksAttribute($value) {
		$this->attributes['blocks'] = serialize($value);
	}*/

	public function sessionTypes() {
		return $this->hasMany('App\SessionType', 'orgId');
	}

	public function users() {
		return $this
			->belongsToMany('App\User', 'orgMemberships', 'orgId', 'userId')
			->withPivot('admin');
	}

}
