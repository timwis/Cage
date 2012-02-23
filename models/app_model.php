<?php

abstract class AppModel extends Model {
	var $virtualFields = array(
		'short_name' => 'CONCAT(Rank.`abbr`, " ", IF(Member.`name_prefix` != "", CONCAT(Member.`name_prefix`, " "), ""), Member.`last_name`) AS short_name',
		'full_name' => 'CONCAT(Member.`first_name`, " ", IF(Member.`middle_name` != "", CONCAT(LEFT(Member.`middle_name`, 1), ". "), ""), Member.`last_name`) AS full_name',
		'name_last_first' => 'CONCAT(Enlistment.`last_name`, " ", Enlistment.`first_name`, IF(Enlistment.`middle_name` != "", CONCAT(" ", LEFT(Enlistment.`middle_name`, 1), "."), "")) AS name_last_first',
		'unit_key' => 'REPLACE(REPLACE(REPLACE(REPLACE(Unit.`abbr`, " HQ", ""), " Co", ""), ".", ""), " ", "") AS unit_key'
	);
}
