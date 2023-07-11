<?php

/*
* UUID & MAC Utils
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

include_once __DIR__.'/includes/uuid_utils.inc.php';

const AUTO_NEW_UUIDS = 10;

?><!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="iso-8859-1">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>UUID &amp; MAC Utils by Daniel Marschall</title>
</head>

<body>

<h1>UUID &amp; MAC Utils by Daniel Marschall</h1>

<p><a href="https://github.com/danielmarschall/uuid_mac_utils/">View the source code</a></p>

<h2>Overview</h2>

<ul>
    <li><a href="#gen_uuid">Generate random and/or time-based UUIDs</a><ul>
            <li><a href="#gen_uuidv7"><font color="green">New:</font> Generate Unix Epoch Time (version 7) UUID</a></li>
            <li><a href="#gen_uuidv6"><font color="green">New:</font> Generate reordered time-based (version 6) UUID</a></li>
            <li><a href="#gen_uuidv4">Generate random (version 4) UUID</a></li>
            <li><a href="#gen_uuidv1">Generate time-based (version 1) UUID</a></li>
        </ul></li>
    <li><a href="#gen_other_uuid">Generate other UUID types</a><ul>
            <li><a href="#gen_uuid_nce">NCE (variant 0) UUID</a></li>
            <li><a href="#gen_uuidv2">Generate DCE Security (version 2) UUID</a></li>
            <li><a href="#gen_uuidv35">Generate name-based (version 3/5) UUID</a></li>
        </ul></li>
    <li><a href="#interpret_uuid">Interpret a UUID</a></li>
    <li><a href="#interpret_mac">Interpret a MAC address (MAC / EUI / ELI / SAI / AAI)</a></li>
</ul>

<h2 id="gen_uuid">Generate random and/or time-based UUIDs</h2>

<h3 id="gen_uuidv7"><font color="green">New:</font> Generate Unix Epoch Time (version 7) UUID &#11088</h3>

<p><i>A UUIDv7 is made of time and 74 random bits.
        Since the time is at the beginning, the UUIDs are monotonically increasing.
        Due to the missing MAC address, this UUID version is recommended due to
        improved privacy.</i></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
	echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs which were created just for you! (Reload the page to get more)</p>';

	echo '<pre>';
	for ($i=0; $i<10; $i++) {
		$uuid = gen_uuid_v7();
		echo '<a href="interprete_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
	}
	echo '</pre>';
}
?>

<form method="GET" action="interprete_uuid.php">
    <input type="hidden" name="version" value="7">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID">
</form>

<h3 id="gen_uuidv6"><font color="green">New:</font> Generate reordered time-based (version 6) UUID</h3>

<p><i>Like UUIDv1, this kind of UUID is made of the MAC address of the generating computer,
        the time, and a clock sequence. However, the components in UUIDv6 are reordered (time is at the beginning),
        so that UUIDs are monotonically increasing.</i></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
	echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs which were created just for you! (Reload the page to get more)</p>';

	echo '<pre>';
	for ($i=0; $i<10; $i++) {
		$uuid = gen_uuid_v6();
		echo '<a href="interprete_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
	}
	echo '</pre>';
}
?>

<form method="GET" action="interprete_uuid.php">
    <input type="hidden" name="version" value="6">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID">
</form>

<h3 id="gen_uuidv4">Generate random (version 4) UUID &#x1F3B2;</h3>

<p><i>A UUIDv4 is made of 122 random bits. No other information is encoded in this kind of UUID.</i></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
	echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs which were created just for you! (Reload the page to get more)</p>';

	echo '<pre>';
	for ($i=0; $i<10; $i++) {
		$uuid = gen_uuid_v4();
		echo '<a href="interprete_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
	}
	echo '</pre>';
}
?>

<form method="GET" action="interprete_uuid.php">
    <input type="hidden" name="version" value="4">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID">
</form>

<h3 id="gen_uuidv1">Generate time-based (version 1) UUID</h3>

<p><i>A UUIDv1 is made of the MAC address of the generating computer,
the time, and a clock sequence.</i></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
    echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs which were created just for you! (Reload the page to get more)</p>';

    echo '<pre>';
    for ($i=0; $i<10; $i++) {
        $uuid = gen_uuid_v1();
        echo '<a href="interprete_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
    }
    echo '</pre>';
}
?>

<form method="GET" action="interprete_uuid.php">
    <input type="hidden" name="version" value="1">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID">
</form>

<h2 id="gen_other_uuid">Generate other UUID types</h2>

<p><i>The following types of UUIDs are less common. Please only use the following
generators if you know what you are doing.</i></p>

<h3 id="gen_uuid_nce">NCE (variant 0) UUID</h3>

<p>The NCE UUIDs are a legacy format that cannot be generated anymore, because the
amount of available timestamp bits was exhausted on <strong>5 September 2015</strong>.
Here is an example of the last possible NCE UUID (all bits of the timestamp are set to 1):
<a href="interprete_uuid.php?uuid=ffffffff-ffff-0000-027f-000001000000">ffffffff-ffff-0000-027f-000001000000</a>.</p>

<h3 id="gen_uuidv2">Generate DCE Security (version 2) UUID</h3>

<form method="GET" action="interprete_uuid.php">
	<input type="hidden" name="version" value="2">
	Domain (8 bits): <select name="domain_choose" id="dce_domain_choice" onchange="javascript:dce_domain_choose();">
		<option value="uid">POSIX UID</option>
		<option value="gid">POSIX GID</option>
		<option value="org">Org</option>
		<option value="site">Site-defined</option>
	</select> <input type="text" name="dce_domain" value="" id="dce_domain" style="width:50px"> (decimal notation)<br>
	Value (32 bits): <input type="text" name="dce_id" value="0" id="dce_id" style="width:200px"> (decimal notation)<br>
	<font color="red">Warning</font>: The timestamp has an accuracy of approx. 7 minutes,
	therefore the uniqueness of these UUIDs is not guaranteed!<br><br>
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create UUID">
</form>
<script>
function dce_domain_choose() {
	var ns = document.getElementById('dce_domain_choice').value;
	if (ns == "uid") {
		document.getElementById('dce_domain').value = "0";
	}
	if (ns == "gid") {
		document.getElementById('dce_domain').value = "1";
	}
	if (ns == "org") {
		document.getElementById('dce_domain').value = "2";
	}
	if (ns == "site") {
		document.getElementById('dce_domain').value = "";
	}
}
dce_domain_choose();
</script>

<h3 id="gen_uuidv35">Generate name-based (version 3/5) UUID</h3>

<form method="GET" action="interprete_uuid.php">
	Hash algorithm: <select name="version">
		<option value="3">MD5 (version 3 UUID)</option>
		<option value="5">SHA1 (version 5 UUID)</option>
	</select><br>
	Namespace: <select name="namespace_choose" id="nb_nsc" onchange="javascript:nb_ns_choose();">
		<option value="dns">DNS</option>
		<option value="url">URL</option>
		<option value="oid">OID</option>
		<option value="x500">X.500 DN</option>
		<option value="oidplus_ns">OIDplus ns only</option>
		<option value="oidplus_ns_val">OIDplus ns+val</option>
		<!-- <option value="oidplus_pubkey">OIDplus pubkey</option> -->
		<option value="other">Other</option>
	</select> <input type="text" name="nb_ns" value="" id="nb_ns" style="width:300px"><br>
	Value: <input type="text" name="nb_val" value="" id="nb_val" style="width:300px"><br>
	<font color="red">Warning</font>: These UUIDs do not contain a timestamp,
	therefore the uniqueness of these UUIDs is not guaranteed!<br><br>
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create UUID">
</form>
<script>
function nb_ns_choose() {
	var ns = document.getElementById('nb_nsc').value;
	if (ns == "dns") {
		document.getElementById('nb_ns').value = "6ba7b810-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "www.example.org";
	}
	if (ns == "url") {
		document.getElementById('nb_ns').value = "6ba7b811-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "http://www.example.org/";
	}
	if (ns == "oid") {
		document.getElementById('nb_ns').value = "6ba7b812-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "2.999";
	}
	if (ns == "x500") {
		document.getElementById('nb_ns').value = "6ba7b814-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "UID=jsmith,DC=example,DC=net";
	}
	if (ns == "oidplus_ns") {
		document.getElementById('nb_ns').value = "0943e3ce-4b79-11e5-b742-78e3b5fc7f22";
		document.getElementById('nb_val').value = "ipv4";
	}
	if (ns == "oidplus_ns_val") {
		document.getElementById('nb_ns').value = "ad1654e6-7e15-11e4-9ef6-78e3b5fc7f22";
		document.getElementById('nb_val').value = "ipv4:8.8.8.8";
	}
	/*
	if (ns == "oidplus_ns_pubkey") {
		document.getElementById('nb_ns').value = "fd16965c-8bab-11ed-8744-3c4a92df8582";
		document.getElementById('nb_val').value = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqg/PnsC1WX3C1/mUSLuk0DIaDHtEsxBnG0auYJRJ1hBtbUUvItbK0odlKrX2SFo1MJJpu/SSxTzAgqkKZsZe3cCFkgA1svfuH9i94oGLjJ4n0kRJEGlanCmGndJBfIqGDJaQE2BJ8tLxeBrpkd9l0KvJsjhRmqJAb9KYK3KYFsWvT+wyjD3UJ1eHcgLbF/Qb3cwMU/u7Fs7ZpsNMW4phDPlsYsk9XHFpJ1/UCj6G53mYRfOC/ouDdGShlbVLB15s0V95QpnU/7lL8mJ2lE+sTZekGNBA4XbJv2gs21cR4E8zc/z+NyZS7117DYZoJqrAN8sKz6xGoKgQF6wueCK5qQIDAQAB";
	}
	*/
	if (ns == "other") {
		document.getElementById('nb_ns').value = "";
		document.getElementById('nb_val').value = "";
	}
}
nb_ns_choose();
</script>

<h2 id="interpret_uuid">Interpret a UUID</h2>

<p>You can enter a UUID in the following notations:</p>

<ul>
	<li>Classic notation (case insensitive, curly braces optional): <code>9e83839a-5967-11e4-8c1c-78e3b5fc7f22</code></li>
	<li>As OID: <code>2.25.210700883446948645633376489934419689250</code></li>
</ul>

<p>The script will output:</p>

<ul>
	<li>Notation as UUID and OID</li>
	<li>Version, variant, and additional data (date and time, clock seq, node id, etc.)</li>
</ul>

<p>Please enter a UUID or UUID OID:</p>

<form method="GET" action="interprete_uuid.php">
	<input type="text" name="uuid" value="" style="width:500px"> <input type="submit" value="Go">
</form>

<h2 id="interpret_mac">Interpret a MAC address (<abbr title="Media Access Control">MAC</abbr> /
<abbr title="Extended Unique Identifier">EUI</abbr> /
<abbr title="Extended Local Identifier">ELI</abbr> /
<abbr title="Standard Assigned Identifier">SAI</abbr> /
<abbr title="Administratively Assigned Identifier">AAI</abbr>)</h2>

<p>You can enter a UUID in the following notations:</p>

<ul>
	<li><code>AA-BB-CC-DD-EE-FF</code></li>
	<li><code>AA:BB:CC:DD:EE:FF</code></li>
	<li><code>AABBCC.DDEEFF</code> (case insensitive)</li>
	<li><code>AA-BB-CC-DD-EE-FF-11-22</code> (EUI-64)</li>
	<li><code>AA:BB:CC:DD:EE:FF-11-22</code> (EUI-64)</li>
	<li><code>fe80::1322:33ff:fe44:5566</code> (IPv6 Link Local / EUI-64)</li>
</ul>

<p>The script will output:</p>

<ul>
	<li>Information about the I/G and U/L flags.</li>
	<li>Information about the entry in the IEEE registry, if available.</li>
	<li>Information about the registrant, if available.</li>
</ul>

<p>Please enter a MAC (EUI, ELI, SAI, AAI), or IPv6-Link-Local address:</p>

<form method="GET" action="interprete_mac.php">
	<input type="text" name="mac" value="" style="width:250px"> <input type="submit" value="Go">
</form>

<br><br><br>

</body>

</html>
