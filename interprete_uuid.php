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

if ($uuid == 'CREATE') {
	$title = 'Generate an UUID';
} else {
	$title = 'Interprete an UUID';
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

<pre><?php

require_once __DIR__ . '/includes/uuid_utils.inc.php';
require_once __DIR__ . '/includes/mac_utils.inc.php';
require_once __DIR__ . '/includes/OidDerConverter.class.php';

if ($uuid == 'CREATE') {
	if (!isset($_REQUEST['version'])) $_REQUEST['version'] = '1'; // default: Version 1 / time based

	if ($_REQUEST['version'] == '1') {
		$uuid = gen_uuid_timebased();
	}

	else if ($_REQUEST['version'] == '2') {

		// TODO: these things should be checked in gen_uuid_* and thrown as Exception! (LengthException, UnexpectedValueException)
		if (!isset($_REQUEST['dce_domain'])) die("Domain ID missing");
		if ($_REQUEST['dce_domain'] == '') die("Domain ID missing");
		$domain = $_REQUEST['dce_domain'];
		if (!is_numeric($domain)) die("Invalid Domain ID");
		if (($domain < 0) || ($domain > 255)) die("Domain ID must be in range 0..255");

		if (!isset($_REQUEST['dce_id'])) die("ID value missing");
		if ($_REQUEST['dce_id'] == '') die("ID value missing");
		$id = $_REQUEST['dce_id'];
		if (!is_numeric($id)) die("Invalid ID value");
		if (($id < 0) || ($id > 4294967295)) die("ID value must be in range 0..4294967295");

		$uuid = gen_uuid_dce($domain, $id);
	}

	else if (($_REQUEST['version'] == '3') || ($_REQUEST['version'] == '5')) {
		if (!isset($_REQUEST['nb_ns'])) die("Namespace UUID missing");
		if ($_REQUEST['nb_ns'] == '') die("Namespace UUID missing");
		$ns = $_REQUEST['nb_ns'];
		if (!uuid_valid($ns)) die("Invalid namespace UUID '".htmlentities($ns)."'");
		if (!isset($_REQUEST['nb_val'])) $_REQUEST['nb_val'] = '';
		if ($_REQUEST['version'] == '3') {
			$uuid = gen_uuid_md5_namebased($ns, $_REQUEST['nb_val']);
		} else {
			$uuid = gen_uuid_sha1_namebased($ns, $_REQUEST['nb_val']);
		}
	}

	else if ($_REQUEST['version'] == '4') {
		$uuid = gen_uuid_random();
	}

	else if ($_REQUEST['version'] == '6') {
		$uuid = gen_uuid_reordered();
	}

	else if ($_REQUEST['version'] == '7') {
		$uuid = gen_uuid_unix_epoch();
	}

	else {
		echo "Unexpected version number\n";
	}
}
if (is_uuid_oid($uuid)) {
	$uuid = oid_to_uuid($uuid);
}

if (!uuid_valid($uuid)) {
	echo 'This is not a valid UUID.';
} else {
	$oid  = uuid_to_oid($uuid);
	echo sprintf("%-32s %s\n", "Your input:", $uuid);
	echo "\n";
	echo sprintf("%-32s %s\n", "URN:", 'urn:uuid:'.strtolower(oid_to_uuid(uuid_to_oid($uuid))));
	echo sprintf("%-32s %s\n", "URI:", 'uuid:'.strtolower(oid_to_uuid(uuid_to_oid($uuid))));
	echo sprintf("%-32s %s\n", "Microsoft GUID syntax:", '{'.strtoupper(oid_to_uuid(uuid_to_oid($uuid))).'}');
	echo sprintf("%-32s %s\n", "C++ struct syntax:", uuid_c_syntax($uuid));
	echo "\n";
	echo sprintf("%-32s %s\n", "As OID:", $oid);
	echo sprintf("%-32s %s\n", "DER encoding of OID:", OidDerConverter::hexarrayToStr(OidDerConverter::oidToDER($oid)));
	echo "\n";
	echo "Interpration of the UUID:\n\n";
	uuid_info($uuid);
}

?></pre>

<br>

</body>

</html>
