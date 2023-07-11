<?php

/*
 * UUID interpreter for PHP
 * Copyright 2017 - 2023 Daniel Marschall, ViaThinkSoft
 * Version 2023-07-11
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
	<title><?php echo $title; ?></title>
</head>

<body>

<h1><?php echo $title; ?></h1>

<p><a href="index.php">Back</a></p>

<?php

if ($uuid != 'CREATE') {
	echo '<form method="GET" action="interprete_uuid.php">';
	echo '	<input type="text" name="uuid" value="'.htmlentities($uuid).'" style="width:300px"> <input type="submit" value="Interprete">';
	echo '</form>';
} else {
	echo '<p><i>Reload the page to receive another UUID.</i></p>';
}

echo '<pre>';

require_once __DIR__ . '/includes/uuid_utils.inc.php';
require_once __DIR__ . '/includes/mac_utils.inc.php';
require_once __DIR__ . '/includes/OidDerConverter.class.php';

try {
	if ($uuid == 'CREATE') {
		if ($version == '1') {
			$uuid = gen_uuid_timebased();
		} else if ($version == '2') {
			$uuid = gen_uuid_dce($_REQUEST['dce_domain'] ?? '', $_REQUEST['dce_id'] ?? '');
		} else if ($version == '3') {
			$uuid = gen_uuid_md5_namebased($_REQUEST['nb_ns'] ?? '', $_REQUEST['nb_val'] ?? '');
		} else if ($version == '4') {
			$uuid = gen_uuid_random();
		} else if ($version == '5') {
			$uuid = gen_uuid_sha1_namebased($_REQUEST['nb_ns'] ?? '', $_REQUEST['nb_val'] ?? '');
		} else if ($version == '6') {
			$uuid = gen_uuid_reordered();
		} else if ($version == '7') {
			$uuid = gen_uuid_unix_epoch();
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
		$oid = uuid_to_oid($uuid);
		echo sprintf("%-32s %s\n", "Your input:", $uuid);
		echo "\n";
		echo sprintf("%-32s %s\n", "URN:", 'urn:uuid:' . strtolower(oid_to_uuid(uuid_to_oid($uuid))));
		echo sprintf("%-32s %s\n", "URI:", 'uuid:' . strtolower(oid_to_uuid(uuid_to_oid($uuid))));
		echo sprintf("%-32s %s\n", "Microsoft GUID syntax:", '{' . strtoupper(oid_to_uuid(uuid_to_oid($uuid))) . '}');
		echo sprintf("%-32s %s\n", "C++ struct syntax:", uuid_c_syntax($uuid));
		echo "\n";
		echo sprintf("%-32s %s\n", "As OID:", $oid);
		echo sprintf("%-32s %s\n", "DER encoding of OID:", OidDerConverter::hexarrayToStr(OidDerConverter::oidToDER($oid)));
		echo "\n";
		echo "Interpration of the UUID:\n\n";
		uuid_info($uuid);
	}
} catch (Exception $e) {
    echo "Error: " . htmlentities($e->getMessage());
}

?></pre>

<br>

</body>

</html>
