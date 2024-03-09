<?php

/*
 * UUID interpreter for PHP
 * Copyright 2017 - 2024 Daniel Marschall, ViaThinkSoft
 * Version 2024-03-09
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : 'CREATE';

$version = $_REQUEST['version'] ?? null;
$hash_sqlserver_version = null;
$hash_algo = null;
if (!is_null($version)) {
	if (preg_match('@^(8)_sqlserver_v(.+)$@', $version, $m)) {
		$version = $m[1];
		$hash_sqlserver_version = $m[2];
	} else if (preg_match('@^(8)_namebased_(.+)$@', $version, $m)) {
		$version = $m[1];
		$hash_algo = $m[2];
	}
}
if (!is_numeric($version) || (strlen($version)!=1)) $version = 1; // default: Version 1 / time based

if ($uuid == 'CREATE') {
	$title = 'Generate a UUIDv'.$version;
} else {
	$title = 'Interprete a UUID';
}

?><html>

<head>
	<meta charset="iso-8859-1">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title><?php echo htmlentities($title); ?></title>
	<meta name=viewport content="width=device-width, initial-scale=1">
</head>

<body>

<h1><?php echo htmlentities($title); ?></h1>

<p><a href="index.php">Back</a></p>

<?php

if ($uuid != 'CREATE') {
	echo '<form method="GET" action="interprete_uuid.php">';
	echo '	UUID: <input style="font-family:Courier,Courier New,Serif;width:325px" type="text" name="uuid" value="'.htmlentities($uuid).'"> <input type="submit" value="Interprete">';
	echo '</form>';
} else if (($version!=3) && ($version!=5) && ($version!=8)) {
	echo '<p><i>Reload the page to receive another UUID.</i></p>';
}

echo '<pre>';

require_once __DIR__ . '/includes/uuid_utils.inc.php';

try {
	if ($uuid == 'CREATE') {
		if ($version == '1') {
			$uuid = gen_uuid_timebased();
		} else if ($version == '2') {
			$uuid = gen_uuid_dce(trim($_REQUEST['dce_domain']??''), trim($_REQUEST['dce_id']??''));
		} else if ($version == '3') {
			$uuid = gen_uuid_md5_namebased(trim($_REQUEST['nb_ns']??''), trim($_REQUEST['nb_val']??''));
		} else if ($version == '4') {
			$uuid = gen_uuid_random();
		} else if ($version == '5') {
			$uuid = gen_uuid_sha1_namebased(trim($_REQUEST['nb_ns']??''), trim($_REQUEST['nb_val']??''));
		} else if ($version == '6') {
			$uuid = gen_uuid_reordered();
		} else if ($version == '7') {
			$uuid = gen_uuid_unix_epoch();
		} else if ($version == '8') {
			if ($hash_sqlserver_version != null) {
				$uuid = gen_uuid_v8_sqlserver_sortable(intval($hash_sqlserver_version));
			} else if ($hash_algo != null) {
				$uuid = gen_uuid_v8_namebased($hash_algo, trim($_REQUEST['nb_ns']??''), trim($_REQUEST['nb_val']??''));
			} else {
				$uuid = gen_uuid_custom(trim($_REQUEST['block1']??'0'), trim($_REQUEST['block2']??'0'), trim($_REQUEST['block3']??'0'), trim($_REQUEST['block4']??'0'), trim($_REQUEST['block5']??'0'));
			}
		} else {
			throw new Exception("Unexpected version number");
		}
	}
	if (is_uuid_oid($uuid)) {
		$uuid = oid_to_uuid($uuid);
	}

	if (!uuid_valid($uuid)) {
		echo 'This is not a valid UUID.';
	} else {
		uuid_info($uuid);
	}
} catch (Exception $e) {
    echo "Error: " . htmlentities($e->getMessage());
}

?></pre>

<br>

</body>

</html>
