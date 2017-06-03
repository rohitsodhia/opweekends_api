<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

	protected $primaryKey = 'userId';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
	protected $dates = [
		'created_at',
		'updated_at',
		'activatedOn',
		'lastLogin',
	];


	public function setPassword($password) {
		$this->password = password_hash($password, PASSWORD_DEFAULT);
	}

	public function orgs() {
		return $this
			->belongsToMany('App\Org', 'orgMemberships', 'userId', 'orgId')
			->withPivot('admin');
	}

	public function getActivationHash() {
		return md5($this->name . $this->created_at);
	}

	public function getActivationLink($orgSlug = null) {
		return '//' . eng('APP_DOMAIN') . '/account/activate/?e=' . $this->email . '&h=' . $this->getActivationHash() . ($orgSlug ? '&o=' . $orgSlug : '');
	}

	public function isOrgAdmin($orgId) {
		$orgData = $this->orgs()->where('orgMemberships.orgId', $orgId)->first();
		if ($orgData && $orgData->pivot->admin) {
			return true;
		} else {
			return false;
		}
	}
}
