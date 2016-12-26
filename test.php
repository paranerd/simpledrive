<?php
	date_default_timezone_set("UTC");
	$midyear = gmmktime(0, 0, 0, 7, 1, date('Y'));

	echo "mid: " . $midyear;
	echo "<br>now: " . time();
	$semester = (time() > $midyear) ? 1 : 2;

	$from = ($semester == 1) ? gmmktime(0, 0, 0, 1, 1, date('Y')) : gmmktime(0, 0, 0, 6, 30, date('Y') - 1);
	$to = ($semester == 1) ? gmmktime(23, 59, 59, 6, 30, date('Y')) : gmmktime(23, 59, 59, 12, 31, date('Y') - 1);

	echo "<br>from " . $from . " to " . $to;

	echo "<br>from " . date("d.m.Y", $from) . " to " . date("d.m.Y - H:i:s", $to);

	echo "<br>" . gmmktime(23, 59, 59, 12, 31, date('Y') - 1) . " | " . gmmktime(0, 0, 0, 1, 1, date('Y'));