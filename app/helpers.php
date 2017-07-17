<?php
function generateSlug($value) {
	$value = strtolower($value);
	$value = preg_replace('#\s#', '-', $value);
	$value = preg_replace('#[^\w-]#', '', $value);

	return $value;
}

function findOrg($org) {
	if (is_numeric($org)) {
		$org = App\Org::find((int) $org);
	} else {
		$org = App\Org::where('slug', $org)->first();
	}

	return $org;
}
