<?php

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
