<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionType extends Model
{

	protected $table = 'sessionTypes';
	protected $primaryKey = 'typeId';

	public function session() {
		return $this
			->belongsTo('App\Org', 'orgId', 'orgId');
	}

}
