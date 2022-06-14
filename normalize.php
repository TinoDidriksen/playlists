#!/usr/bin/env php
<?php

$xml = file_get_contents('iTunes Music Library.xml');

$start = strpos($xml, "\n\t<key>Tracks</key>\n\t<dict>");
if ($start === false) {
	echo "No start!\n";
	exit(-1);
}
$stop = strpos($xml, "\n\t</dict>", $start);
if ($stop === false) {
	echo "No stop!\n";
	exit(-1);
}

$dict = substr($xml, $start, $stop - $start);
preg_match_all('~\n\t\t<key>\d+</key>(\n\t\t<dict>\n\t\t\t<key>Track ID</key>.+?\t\t</dict>)~s', $dict, $ms, PREG_SET_ORDER);

$uniq = [];
foreach ($ms as $t) {
	if (strpos($t[1], '<key>Podcast</key><true/>') !== false) {
		//fprintf(STDERR, "Skipping podcast\n");
		continue;
	}
	if (!preg_match('~<key>Persistent ID</key><string>(.+?)</string>~s', $t[1], $m)) {
		fprintf(STDERR, "No persistent ID!\n");
		continue;
	}

	$t = [
		'pid' => $m[1],
		'tid' => 0,
		'artist' => null,
		'album' => null,
		'name' => null,
		'kind' => null,
		'location' => null,
		'xml' => $t[1],
		];

	if (preg_match('~<key>Track ID</key><integer>(\d+)</integer>~s', $t['xml'], $m)) {
		$t['tid'] = $m[1];
	}
	if (preg_match('~<key>Artist</key><string>(.+?)</string>~s', $t['xml'], $m)) {
		$t['artist'] = $m[1];
	}
	if (preg_match('~<key>Album</key><string>(.+?)</string>~s', $t['xml'], $m)) {
		$t['album'] = $m[1];
	}
	if (preg_match('~<key>Name</key><string>(.+?)</string>~s', $t['xml'], $m)) {
		$t['name'] = $m[1];
	}
	if (preg_match('~<key>Kind</key><string>(.+?)</string>~s', $t['xml'], $m)) {
		$t['kind'] = $m[1];
	}
	if (preg_match('~<key>Location</key><string>(.+?)</string>~s', $t['xml'], $m)) {
		$t['location'] = $m[1];
	}

	$hash = sha1($t['artist'].'|'.$t['album'].'|'.$t['name'].'|'.$t['kind'].'|'.$t['location'], true);
	$hash = base64_encode($hash);
	$hash = preg_replace('~[^a-zA-Z0-9]+~', '', $hash);
	$hash = substr($hash, 0, 8);
	$uniq[$hash] = $t;
}

function sort_tracks_level4($a, $b) {
	if ($a['location']) {
		if ($b['location']) {
			return strcmp($a['location'], $b['location']);
		}
		return -1;
	}
	if ($b['location']) {
		return 1;
	}
	return 0;
}

function sort_tracks_level3($a, $b) {
	if ($a['name']) {
		if ($b['name']) {
			if ($a['name'] !== $b['name']) {
				return strcmp($a['name'], $b['name']);
			}
			return sort_tracks_level4($a, $b);
		}
		return -1;
	}
	if ($b['name']) {
		return 1;
	}
	return sort_tracks_level4($a, $b);
}

function sort_tracks_level2($a, $b) {
	if ($a['album']) {
		if ($b['album']) {
			if ($a['album'] !== $b['album']) {
				return strcmp($a['album'], $b['album']);
			}
			return sort_tracks_level3($a, $b);
		}
		return -1;
	}
	if ($b['album']) {
		return 1;
	}
	return sort_tracks_level3($a, $b);
}

function sort_tracks_level1($a, $b) {
	if ($a['artist']) {
		if ($b['artist']) {
			if ($a['artist'] !== $b['artist']) {
				return strcmp($a['artist'], $b['artist']);
			}
			return sort_tracks_level2($a, $b);
		}
		return -1;
	}
	if ($b['artist']) {
		return 1;
	}
	return sort_tracks_level2($a, $b);
}

uasort($uniq, 'sort_tracks_level1');

$nd = "\n\t<key>Tracks</key>\n\t<dict>";
foreach ($uniq as $h => $u) {
	$nd .= "\n\t\t<key>{$h}</key>".$u['xml'];
}

$xml = str_replace($dict, $nd, $xml);
$strs = [];
foreach ($uniq as $h => $u) {
	$k = "<key>Track ID</key><integer>{$u['tid']}</integer>";
	$v = "<key>Track ID</key><integer>{$h}</integer>";
	$strs[$k] = $v;
}
$xml = strtr($xml, $strs);
$xml = preg_replace('~<key>Playlist ID</key><integer>\d+</integer>~s', '<key>Playlist ID</key><integer>0</integer>', $xml);
echo $xml;
