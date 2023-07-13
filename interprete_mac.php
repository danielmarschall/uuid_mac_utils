<?php

/*
 * MAC interpreter for PHP
 * Copyright 2017 - 2023 Daniel Marschall, ViaThinkSoft
 * Version 2023-07-13
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

header('Content-Type:text/html; charset=utf-8');

include_once __DIR__ . '/includes/mac_utils.inc.php';

if ($gen_aai = (($_REQUEST['aai_gen'] ?? '0') == '1')) {
	$bits = $_REQUEST['aai_gen_bits'] ?? 48;
	$multicast = $_REQUEST['aai_gen_multicast'] ?? '0';
	if (!is_numeric($bits)) die();
	$mac = gen_aai((int)$bits, $multicast=='1');
	$title = 'Generate a MAC address (AAI)';
} else {
	$mac = isset($_REQUEST['mac']) ? trim($_REQUEST['mac']) : '';
	$title = 'Interprete a MAC address';
}

?><html>

<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title><?php echo htmlentities($title); ?></title>
	<meta name=viewport content="width=device-width, initial-scale=1">
</head>

<body>

<h1><?php echo htmlentities($title); ?></h1>

<p><a href="index.php">Back</a></p>

<?php

if ($gen_aai) {
	echo '<p><i>Reload the page to receive another AAI.</i></p>';
} else {
	echo '<form method="GET" action="interprete_mac.php">';
	echo '	MAC: <input type="text" name="mac" value="'.htmlentities($mac).'" style="width:250px"> <input type="submit" value="Interprete">';
	echo '</form>';
}

echo '<pre>';

if (!mac_valid($mac)) {
	echo 'This is not a valid MAC address.';
} else {
	decode_mac($mac);
}

?></pre>

<br>

</body>

</html>
