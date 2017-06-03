<?php

namespace App;

use App\User;

class CurrentUser
{

	protected $user;

	public function __construct()
	{
	}

	public function set(User $user = null)
	{
		$this->user = $user;
	}

	public function get()
	{
		return $this->user;
	}

	public function __get($name) {
		return $this->user->{$name};
	}

	public function isOrgAdmin($orgId) {
		return $this->user->isOrgAdmin($orgId);
	}
}
