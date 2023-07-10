<?php

/*
 * MAC interpreter for PHP
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

header('Content-Type:text/html; charset=utf-8');

?><html>

<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>Interprete a MAC address</title>
</head>

<body>

<h1>Interprete a MAC address</h1>

<p><a href="index.php">Back</a></p>

<pre><?php

include_once __DIR__ . '/includes/mac_utils.inc.php';

$mac = isset($_GET['mac']) ? trim($_GET['mac']) : '';

if (!mac_valid($mac)) {
	echo 'This is not a valid MAC address.';
} else {
	decode_mac($mac);
}

?></pre>

<br>

</body>

</html>
