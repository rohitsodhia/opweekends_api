<?php
function generateSlug($value) {
	$value = strtolower($value);
	$value = preg_replace('#\s#', '-', $value);
	$value = preg_replace('#[^\w-]#', '', $value);

	return $value;
}
