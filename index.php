<?php

/*
* UUID & MAC Utils
* Copyright 2017 - 2023 Daniel Marschall, ViaThinkSoft
* Version 2023-08-16
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
	<meta name=viewport content="width=device-width, initial-scale=1">
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
            <li><a href="#gen_uuid_ncs">NCS (variant 0) UUID</a></li>
            <li><a href="#gen_uuidv2">Generate DCE Security (version 2) UUID</a></li>
            <li><a href="#gen_uuidv35">Generate name-based (version 3 / 5 / <font color="green">New: 8</font>) UUID</a></li>
            <li><a href="#gen_uuidv8"><font color="green">New:</font> Generate Custom (version 8) UUID</a></li>
        </ul></li>
    <li><a href="#interpret_uuid">Interpret a UUID</a></li>
    <li><a href="#interpret_mac">Interpret a MAC address (MAC / EUI / ELI / SAI / AAI)</a><ul>
        <li><a href="#gen_aai">Generate an AAI</a></li>
    </ul></li>
</ul>

<h2 id="gen_uuid">Generate random and/or time-based UUIDs</h2>

<h3 id="gen_uuidv7"><font color="green">New:</font> Generate Unix Epoch Time (version 7) UUID &#11088;</h3>

<p><i>A UUIDv7 is made of time and 74 random&nbsp;bits.
        Since the time is at the beginning, the UUIDs are monotonically increasing.
        Due to the missing MAC address, this UUID version is recommended due to
        improved privacy.</i></p>

<script>
function show_uuidv7_info() {
	document.getElementById("uuidv7_info_button").style.display = "none";
	document.getElementById("uuidv7_info").style.display = "block";
}
</script>
<p><a id="uuidv7_info_button" href="javascript:show_uuidv7_info()">Show format</a>
<pre id="uuidv7_info" style="display:none">Variant 1, Version 7 UUID:
- 48 bit <abbr title="Count of 1ms intervals passed since 1 Jan 1970 00:00:00 GMT">Unix Time in milliseconds</abbr>
-  4 bit Version (fix 0x7)
- 12 bit Random
-  2 bit Variant (fix 0b10)
- 62 bit Random</pre></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
	echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs that were created just for you! (Reload the page to get more)</p>';

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

<h3 id="gen_uuidv6"><font color="green">New:</font> Generate reordered time-based (version 6) UUID &#9200;</h3>

<p><i>Like UUIDv1, this kind of UUID is made of the MAC address of the generating computer,
        the time, and a clock sequence. However, the components in UUIDv6 are reordered (time is at the beginning),
        so that UUIDs are monotonically increasing.</i></p>

<script>
function show_uuidv6_info() {
	document.getElementById("uuidv6_info_button").style.display = "none";
	document.getElementById("uuidv6_info").style.display = "block";
}
</script>
<p><a id="uuidv6_info_button" href="javascript:show_uuidv6_info()">Show format</a>
<pre id="uuidv6_info" style="display:none">Variant 1, Version 6 UUID:
- 48 bit High <abbr title="Count of 100ns intervals passed since 15 Oct 1582 00:00:00 GMT">Time</abbr>
-  4 bit Version (fix 0x6)
- 12 bit Low <abbr title="Count of 100ns intervals passed since 15 Oct 1582 00:00:00 GMT">Time</abbr>
-  2 bit Variant (fix 0b10)
-  6 bit Clock Sequence High
-  8 bit Clock Sequence Low
- 48 bit MAC Address</pre></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
	echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs that were created just for you! (Reload the page to get more)</p>';

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

<p><i>A UUIDv4 is made of 122 random&nbsp;bits. No other information is encoded in this kind of UUID.</i></p>

<script>
function show_uuidv4_info() {
	document.getElementById("uuidv4_info_button").style.display = "none";
	document.getElementById("uuidv4_info").style.display = "block";
}
</script>
<p><a id="uuidv4_info_button" href="javascript:show_uuidv4_info()">Show format</a>
<pre id="uuidv4_info" style="display:none">Variant 1, Version 4 UUID:
- 48 bit Random High
-  4 bit Version (fix 0x4)
- 12 bit Random Mid
-  2 bit Variant (fix 0b10)
- 62 bit Random Low</pre></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
	echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs that were created just for you! (Reload the page to get more)</p>';

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

<h3 id="gen_uuidv1">Generate time-based (version 1) UUID &#9200;</h3>

<p><i>A UUIDv1 is made of the MAC address of the generating computer,
the time, and a clock sequence.</i></p>

<script>
function show_uuidv1_info() {
	document.getElementById("uuidv1_info_button").style.display = "none";
	document.getElementById("uuidv1_info").style.display = "block";
}
</script>
<p><a id="uuidv1_info_button" href="javascript:show_uuidv1_info()">Show format</a>
<pre id="uuidv1_info" style="display:none">Variant 1, Version 1 UUID:
- 32 bit Low <abbr title="Count of 100ns intervals passed since 15 Oct 1582 00:00:00 GMT">Time</abbr>
- 16 bit Mid <abbr title="Count of 100ns intervals passed since 15 Oct 1582 00:00:00 GMT">Time</abbr>
-  4 bit Version (fix 0x1)
- 12 bit High <abbr title="Count of 100ns intervals passed since 15 Oct 1582 00:00:00 GMT">Time</abbr>
-  2 bit Variant (fix 0b10)
-  6 bit Clock Sequence High
-  8 bit Clock Sequence Low
- 48 bit MAC Address</pre></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
    echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs that were created just for you! (Reload the page to get more)</p>';

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

<p><i>The following types of UUIDs are less common and/or require special knowledge. Please only use the following
generators if you know what you are doing.</i></p>

<h3 id="gen_uuid_ncs">NCS (variant 0) UUID</h3>

<p>The <abbr title="Network Computing System">NCS</abbr> UUIDs are a legacy format
initially designed by Apollo Computer that cannot be generated anymore, because the
amount of available timestamp bits was exhausted on <strong>5 September 2015</strong>.
As an example, here is the last possible NCS UUID (all bits of the timestamp are set to 1) for IP address 127.0.0.1:
<a href="interprete_uuid.php?uuid=ffffffff-ffff-0000-027f-000001000000"><code>ffffffff-ffff-0000-027f-000001000000</code></a>.</p>

<script>
function show_uuidnce_info() {
	document.getElementById("uuidnce_info_button").style.display = "none";
	document.getElementById("uuidnce_info").style.display = "block";
}
</script>
<p><a id="uuidnce_info_button" href="javascript:show_uuidnce_info()">Show format</a>
<pre id="uuidnce_info" style="display:none">Variant 0 UUID:
- 32 bit High <abbr title="Count of 4&#xB5;s intervals passed since 1 Jan 1980 00:00:00 GMT">Time</abbr>
- 16 bit Low <abbr title="Count of 4&#xB5;s intervals passed since 1 Jan 1980 00:00:00 GMT">Time</abbr>
- 16 bit Reserved
-  1 bit Variant (fix 0b0)
-  7 bit <abbr title="socket_$unspec (0x0)
socket_$unix (0x1)
socket_$internet (0x2)
socket_$implink (0x3)
socket_$pup (0x4)
socket_$chaos (0x5)
socket_$ns (0x6)
socket_$nbs (0x7)
socket_$ecma (0x8)
socket_$datakit (0x9)
socket_$ccitt (0xA)
socket_$sna (0xB)
socket_$unspec2 (0xC)
socket_$dds (0xD)">Family</abbr>
- 56 bit Node</pre></p>

<h3 id="gen_uuidv2">Generate DCE Security (version 2) UUID</h3>

<p><i>An UUIDv2 contains information about the creator (person, group, or organization), the generating system (MAC address), and time.
The creator information replaced parts of the time bits, therefore the time resolution is very low.</i></p>

<script>
function show_uuidv2_info() {
	document.getElementById("uuidv2_info_button").style.display = "none";
	document.getElementById("uuidv2_info").style.display = "block";
}
</script>
<p><a id="uuidv2_info_button" href="javascript:show_uuidv2_info()">Show format</a>
<pre id="uuidv2_info" style="display:none">Variant 1, Version 2 UUID:
- 32 bit Local Domain Number
- 16 bit Mid <abbr title="Count of 429.4967296s intervals passed since 15 Oct 1582 00:00:00 GMT">Time</abbr>
-  4 bit Version (fix 0x2)
- 12 bit High <abbr title="Count of 429.4967296s intervals passed since 15 Oct 1582 00:00:00 GMT">Time</abbr>
-  2 bit Variant (fix 0b10)
-  6 bit Clock Sequence
-  8 bit <abbr title="0 = person
1 = group
2 = org
3-255 = site-defined">Local Domain</abbr>
- 48 bit MAC Address</pre></p>

<form method="GET" action="interprete_uuid.php">
	<input type="hidden" name="version" value="2">
	<label>Domain (8&nbsp;bits):</label><select name="domain_choose" id="dce_domain_choice" onchange="javascript:dce_domain_choose();">
		<option value="uid">Person (e.g. POSIX UID)</option>
		<option value="gid">Group (e.g. POSIX GID)</option>
		<option value="org">Organization</option>
		<option value="site">Site-defined</option>
	</select> = Address Family ID: <input type="number" min="0" max="255" name="dce_domain" value="" id="dce_domain" style="width:50px" pattern="[0-9]+"> (decimal notation)<br>
	<label>Value (32&nbsp;bits):</label><input type="number" min="0" max="4294967295" name="dce_id" value="0" id="dce_id" style="width:200px" pattern="[0-9]+"> (decimal notation)<br>
	<font color="red">Warning</font>: The timestamp has an accuracy of 7:10 minutes,
	therefore the uniqueness of these UUIDs is not guaranteed!<br><br>
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create UUIDv2">
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

<h3 id="gen_uuidv35">Generate name-based (version 3 / 5 / <font color="green">New: 8</font>) UUID</h3>

<p><i>An UUIDv3 is made out of a MD5 hash and an UUIDv5 is made out of a SHA1 hash.
The revision of RFC4122 also contains an example for a custom UUIDv8 that
allows SHA2, SHA3 and SHAKE hash algorithms. ViaThinkSoft added more hash
algorithms and assigned Hash Space IDs to them.</i></p>

<script>
function show_uuidv35_info() {
	document.getElementById("uuidv35_info_button").style.display = "none";
	document.getElementById("uuidv35_info").style.display = "block";
}
</script>
<p><a id="uuidv35_info_button" href="javascript:show_uuidv35_info()">Show format</a>
<pre id="uuidv35_info" style="display:none">Variant 1, Version 3/5/8 UUID:
- 48 bit Hash High
-  4 bit Version (fix 0x3, 0x5, or 0x8)
- 12 bit Hash Mid
-  2 bit Variant (fix 0b10)
- 62 bit Hash Low



<u>Overview of namebased UUIDs:</u>
UUIDv3(<i>NamespaceUuid</i>, <i>Data</i>)           := <abbr title="Adds UUID variant 0b10 and version 3">ConvertRawBytesToUuid_v3</abbr>(MD5( Binary[<i>NameSpaceUuid</i>] || <i>Data</i> )).
UUIDv5(<i>NamespaceUuid</i>, <i>Data</i>)           := <abbr title="Adds UUID variant 0b10 and version 5">ConvertRawBytesToUuid_v5</abbr>(SHA1( Binary[<i>NameSpaceUuid</i>] || <i>Data</i> )).
UUIDv8(<i>HashAlgo</i>, <i>NameSpaceUuid</i>, <i>Data</i>) := <abbr title="Adds UUID variant 0b10 and version 8">ConvertRawBytesToUuid_v8</abbr>(<i>HashAlgo</i>( Binary[HashSpaceUuid&lt;<i>HashAlgo</i>&gt;] || Binary[<i>NameSpaceUuid</i>] || <i>Data</i> )).

<u>As defined by <a href="https://datatracker.ietf.org/doc/rfc4122/">RFC4122</a> Appendix C / <a href="https://datatracker.ietf.org/doc/draft-ietf-uuidrev-rfc4122bis/">RFC4122bis</a> Appendix A:</u><!-- TODO: When new RFC is published, replace the RFC number -->
NameSpaceUuid&lt;DNS&gt;          := "6ba7b810-9dad-11d1-80b4-00c04fd430c8".
NameSpaceUuid&lt;URL&gt;          := "6ba7b811-9dad-11d1-80b4-00c04fd430c8".
NameSpaceUuid&lt;OID&gt;          := "6ba7b812-9dad-11d1-80b4-00c04fd430c8".
NameSpaceUuid&lt;X500&gt;         := "6ba7b814-9dad-11d1-80b4-00c04fd430c8".

<u>As defined by <a href="https://datatracker.ietf.org/doc/draft-ietf-uuidrev-rfc4122bis/">RFC4122bis</a> Appendix B:</u><!-- TODO: When new RFC is published, replace the RFC number -->
HashSpaceUuid&lt;SHA2_224&gt;     := "59031ca3-fbdb-47fb-9f6c-0f30e2e83145".
HashSpaceUuid&lt;SHA2_256&gt;     := "3fb32780-953c-4464-9cfd-e85dbbe9843d".
HashSpaceUuid&lt;SHA2_384&gt;     := "e6800581-f333-484b-8778-601ff2b58da8".
HashSpaceUuid&lt;SHA2_512&gt;     := "0fde22f2-e7ba-4fd1-9753-9c2ea88fa3f9".
HashSpaceUuid&lt;SHA2_512_224&gt; := "003c2038-c4fe-4b95-a672-0c26c1b79542".
HashSpaceUuid&lt;SHA2_512_256&gt; := "9475ad00-3769-4c07-9642-5e7383732306".
HashSpaceUuid&lt;SHA3_224&gt;     := "9768761f-ac5a-419e-a180-7ca239e8025a".
HashSpaceUuid&lt;SHA3_256&gt;     := "2034d66b-4047-4553-8f80-70e593176877".
HashSpaceUuid&lt;SHA3_384&gt;     := "872fb339-2636-4bdd-bda6-b6dc2a82b1b3".
HashSpaceUuid&lt;SHA3_512&gt;     := "a4920a5d-a8a6-426c-8d14-a6cafbe64c7b".
HashSpaceUuid&lt;SHAKE_128&gt;    := "7ea218f6-629a-425f-9f88-7439d63296bb".
HashSpaceUuid&lt;SHAKE_256&gt;    := "2e7fc6a4-2919-4edc-b0ba-7d7062ce4f0a".

<u>As defined by ViaThinkSoft for all other algorithms:</u>
HashSpaceUuid&lt;<i>HashAlgo</i>&gt;     := UUIDv5("1ee317e2-1853-64b2-8fe9-3c4a92df8582", <a href="https://www.php.net/manual/de/function.hash-algos.php">PhpName</a>[<i>HashAlgo</i>]).
which results in the following UUIDs:
<?php

$tmp = [];

foreach (get_uuidv8_hash_space_ids() as list($algo,$space,$friendlyName,$author,$available)) {
	$line = str_pad('HashSpaceUuid&lt;'.htmlentities($friendlyName).'&gt;', 34, ' ', STR_PAD_RIGHT);
	$line .= ':= "'.$space.'".';
	if (!$available) $line .= " (Currently not available on this system)";
	$line .= "\n";

	$tmp[$friendlyName] = $line;
}

ksort($tmp);
foreach ($tmp as $line) {
	echo $line;
}

?>


</pre></p>

<style>
label {
	width:120px;
	text-align:left;
	margin-right: 20px;
	display:inline-block;
}
</style>

<form method="GET" action="interprete_uuid.php">
	<label>Hash algorithm:</label><select name="version" id="nb_version" onchange="javascript:nb_version_choose();">
		<?php
		$tmp = [];
		$tmp['MD5'] = '<option value="3">MD5 (UUIDv3)</option>';
		$tmp['SHA1'] = '<option value="5" selected>SHA1 (UUIDv5)</option>';
		foreach (get_uuidv8_hash_space_ids() as list($algo,$space,$friendlyName,$author,$available)) {
			if ($available) {
				$tmp[$friendlyName] = '<option value="8_namebased_'.$space.'">'.htmlentities($friendlyName).' (UUIDv8 defined by '.htmlentities($author).')</option>';
			}
		}
		ksort($tmp);
		foreach ($tmp as $html) {
			echo "\t\t$html\n";
		}
		?>
	</select><font size="-1"><span id="nb_hash_info"></span></font><br>
	<label>Namespace:</label><select name="namespace_choose" id="nb_nsc" onchange="javascript:nb_ns_choose();">
		<option value="dns">DNS</option>
		<option value="url">URL</option>
		<option value="oid">OID</option>
		<option value="x500">X.500 DN</option>
		<!-- <option value="oidplus_ns">OIDplus ns only</option> -->
		<!-- <option value="oidplus_ns_val">OIDplus ns+val</option> -->
		<!-- <option value="oidplus_pubkey">OIDplus pubkey</option> -->
		<option value="other">Other</option>
	</select> = Namespace UUID: <input type="text" name="nb_ns" value="" id="nb_ns" style="width:270px" onchange="javascript:nb_ns_textchange();" pattern="[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}"><br>
	<label>Value:</label><input type="text" name="nb_val" value="" id="nb_val" style="width:300px"><br>
	<font color="red">Warning</font>: These UUIDs do not contain a timestamp,
	therefore the uniqueness of these UUIDs is not guaranteed!<br><br>
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" id="nb_create_btn" value="Create UUID">
</form>
<script>
function nb_version_choose() {
	var ver = document.getElementById('nb_version').value;
	document.getElementById('nb_create_btn').value = 'Create UUIDv' + ver.substr(0,1);
	var x = ver.split('_namebased_');
	if (x.length == 2) {
		document.getElementById('nb_hash_info').innerHTML = ' (UUIDv8 Hash Space ID: ' + x[1] + ')';
	} else {
		document.getElementById('nb_hash_info').innerHTML = '';
	}

}
function nb_ns_textchange() {
	var ns = document.getElementById('nb_ns').value.toLowerCase();
	if (ns == "6ba7b810-9dad-11d1-80b4-00c04fd430c8") {
		if (document.getElementById('nb_nsc').value != "dns") {
			document.getElementById('nb_nsc').value = "dns";
			document.getElementById('nb_val').value = "www.example.com";
		}
	}
	else if (ns == "6ba7b811-9dad-11d1-80b4-00c04fd430c8") {
		if (document.getElementById('nb_nsc').value != "url") {
			document.getElementById('nb_nsc').value = "url";
			document.getElementById('nb_val').value = "http://www.example.com/";
		}
	}
	else if (ns == "6ba7b812-9dad-11d1-80b4-00c04fd430c8") {
		if (document.getElementById('nb_nsc').value != "oid") {
			document.getElementById('nb_nsc').value = "oid";
			document.getElementById('nb_val').value = "2.999";
		}
	}
	else if (ns == "6ba7b814-9dad-11d1-80b4-00c04fd430c8") {
		if (document.getElementById('nb_nsc').value != "x500") {
			document.getElementById('nb_nsc').value = "x500";
			document.getElementById('nb_val').value = "UID=jsmith,DC=example,DC=net";
		}
	}
	else {
		if (document.getElementById('nb_nsc').value != "other") {
			document.getElementById('nb_nsc').value = "other";
			document.getElementById('nb_val').value = "";
		}
	}
}
function nb_ns_choose() {
	var ns = document.getElementById('nb_nsc').value;
	if (ns == "dns") {
		document.getElementById('nb_ns').value = "6ba7b810-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "www.example.com";
	}
	else if (ns == "url") {
		document.getElementById('nb_ns').value = "6ba7b811-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "http://www.example.com/";
	}
	else if (ns == "oid") {
		document.getElementById('nb_ns').value = "6ba7b812-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "2.999";
	}
	else if (ns == "x500") {
		document.getElementById('nb_ns').value = "6ba7b814-9dad-11d1-80b4-00c04fd430c8";
		document.getElementById('nb_val').value = "UID=jsmith,DC=example,DC=net";
	}
	/*
	else if (ns == "oidplus_ns") {
		document.getElementById('nb_ns').value = "0943e3ce-4b79-11e5-b742-78e3b5fc7f22";
		document.getElementById('nb_val').value = "ipv4";
	}
	else if (ns == "oidplus_ns_val") {
		document.getElementById('nb_ns').value = "ad1654e6-7e15-11e4-9ef6-78e3b5fc7f22";
		document.getElementById('nb_val').value = "ipv4:8.8.8.8";
	}
	else if (ns == "oidplus_ns_pubkey") {
		document.getElementById('nb_ns').value = "fd16965c-8bab-11ed-8744-3c4a92df8582";
		document.getElementById('nb_val').value = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqg/PnsC1WX3C1/mUSLuk0DIaDHtEsxBnG0auYJRJ1hBtbUUvItbK0odlKrX2SFo1MJJpu/SSxTzAgqkKZsZe3cCFkgA1svfuH9i94oGLjJ4n0kRJEGlanCmGndJBfIqGDJaQE2BJ8tLxeBrpkd9l0KvJsjhRmqJAb9KYK3KYFsWvT+wyjD3UJ1eHcgLbF/Qb3cwMU/u7Fs7ZpsNMW4phDPlsYsk9XHFpJ1/UCj6G53mYRfOC/ouDdGShlbVLB15s0V95QpnU/7lL8mJ2lE+sTZekGNBA4XbJv2gs21cR4E8zc/z+NyZS7117DYZoJqrAN8sKz6xGoKgQF6wueCK5qQIDAQAB";
	}
	*/
	else if (ns == "other") {
		document.getElementById('nb_ns').value = "";
		document.getElementById('nb_val').value = "";
	}
}
nb_version_choose();
nb_ns_choose();
</script>

<h3 id="gen_uuidv8"><font color="green">New:</font> Generate Custom (version 8) UUID</h3>

<p><i>UUIDv8 is made of 122 bits application-specific / custom data. The other 6 bits are used to specify the variant and version of the UUID, to make it RFC-compatible.</i></p>

<script>
function show_uuidv8_info() {
	document.getElementById("uuidv8_info_button").style.display = "none";
	document.getElementById("uuidv8_info").style.display = "block";
}
function uuidv8_changedec(block, len) {
	var x = document.getElementById("v8_block"+block+"_dec").value;
	if (x.trim() == "") x = 0;
	x = parseInt(x);
	if (isNaN(x)) {
		x = "???";
	} else {
		x = x.toString(16).padStart(len, '0');
		if ((len > 0) && (x.length > len)) x = "Overflow";
	}
	document.getElementById("v8_block"+block+"_hex").value = x;
}
function uuidv8_changehex(block, len) {
	var x = document.getElementById("v8_block"+block+"_hex").value;
	if (x.trim() == "") x = 0;
	x = parseInt(x, 16);
	if (isNaN(x)) {
		x = "???";
	} else {
		x = x.toString().padStart(len, '0');
		if ((len > 0) && (x.length > len)) x = "Overflow"; // Note: For block 3/4, the overflow actually happens at 12/14 bits, not at 4 nibbles (16 bits)
	}
	document.getElementById("v8_block"+block+"_dec").value = x;
}
</script>
<p><a id="uuidv8_info_button" href="javascript:show_uuidv8_info()">Show format</a>
<pre id="uuidv8_info" style="display:none">Variant 1, Version 8 UUID:
- 48 bit Custom data [Block 1+2]
-  4 bit Version (fix 0x8)
- 12 bit Custom data [Block 3]
-  2 bit Variant (fix 0b10)
- 62 bit Custom data [Block 4+5]</pre></p>

<form method="GET" action="interprete_uuid.php">
	<input type="hidden" name="version" value="8">

	<label>Block&nbsp;1 (32&nbsp;bits):</label>0x<input type="text" name="block1" value="00000000" maxlength="8" id="v8_block1_hex" onkeyup="uuidv8_changehex(1, 0)" style="width:150px" pattern="[0-9a-fA-F]+"> = Decimal
	<input type="number" name="block1dec" value="0" min="0" maxlength="20" id="v8_block1_dec" onmouseup="uuidv8_changedec(1, 8)" onkeyup="uuidv8_changedec(1, 8)" style="width:150px"><br>

	<label>Block&nbsp;2 (16&nbsp;bits):</label>0x<input type="text" name="block2" value="0000" maxlength="4" id="v8_block2_hex" onkeyup="uuidv8_changehex(2, 0)" style="width:150px" pattern="[0-9a-fA-F]+"> = Decimal
	<input type="number" name="block2dec" value="0" min="0" maxlength="20" id="v8_block2_dec" onmouseup="uuidv8_changedec(2, 4)" onkeyup="uuidv8_changedec(2, 4)" style="width:150px"><br>

	<label>Block&nbsp;3 (<abbr title="The high 4 bits are occupied by the UUID version = 8">12&nbsp;bits</abbr>):</label>0x<input type="text" name="block3" value="0000" maxlength="4" id="v8_block3_hex" onkeyup="uuidv8_changehex(3, 0)" style="width:150px" pattern="[0-9a-fA-F]+"> = Decimal
	<input type="number" name="block3dec" value="0" min="0" maxlength="20" id="v8_block3_dec" onmouseup="uuidv8_changedec(3, 4)" onkeyup="uuidv8_changedec(3, 4)" style="width:150px"><br>

	<label>Block&nbsp;4 (<abbr title="The high 2 bits are occupied by the UUID variant = 0b10">14&nbsp;bits</abbr>):</label>0x<input type="text" name="block4" value="0000" maxlength="4" id="v8_block4_hex" onkeyup="uuidv8_changehex(4, 0)" style="width:150px" pattern="[0-9a-fA-F]+"> = Decimal
	<input type="number" name="block4dec" value="0" min="0" maxlength="20" id="v8_block4_dec" onmouseup="uuidv8_changedec(4, 4)" onkeyup="uuidv8_changedec(4, 4)" style="width:150px"><br>

	<label>Block&nbsp;5 (48&nbsp;bits):</label>0x<input type="text" name="block5" value="000000000000" maxlength="12" id="v8_block5_hex" onkeyup="uuidv8_changehex(5, 0)" style="width:150px" pattern="[0-9a-fA-F]+"> = Decimal
	<input type="number" name="block5dec" value="0" min="0" maxlength="20" id="v8_block5_dec" onmouseup="uuidv8_changedec(5, 12)" onkeyup="uuidv8_changedec(5, 12)" style="width:150px"><br>

	<font color="red">Warning</font>: These UUIDs do not contain a timestamp,
	therefore the uniqueness of these UUIDs is not guaranteed!<br><br>
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create UUIDv8">
</form>

<h2 id="interpret_uuid">Interpret a UUID</h2>

<p>You can enter a UUID in the following notations:</p>

<ul>
	<li>Classic notation (case insensitive, curly braces optional): <code>9e83839a-5967-11e4-8c1c-78e3b5fc7f22</code></li>
	<li>As OID: <code>2.25.210700883446948645633376489934419689250</code></li>
</ul>

<p>The script will output:</p>

<ul>
	<li>Notation as UUID and OID</li>
	<li>Version, variant, and additional data (date and time, clock sequence, node id, etc.)</li>
</ul>

<p>Please enter a UUID or UUID OID:</p>

<form method="GET" action="interprete_uuid.php">
	<input type="text" name="uuid" value="" style="width:300px"> <input type="submit" value="Interprete">
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
	<input type="text" name="mac" value="" style="width:250px"> <input type="submit" value="Interprete">
</form>

<h3 id="gen_aai">Generate an <abbr title="Administratively Assigned Identifier">AAI</abbr></h3>

<p><i>An Administratively Assigned Identifier (AAI) is a MAC address which can be locally defined
by applications or an administrator. Unlike the EUI, an AAI is NOT worldwide unique.</i></p>

<form method="GET" action="interprete_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="48">
    <input type="hidden" name="aai_gen_multicast" value="0">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate AAI-48">
</form>

<br>

<form method="GET" action="interprete_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="64">
    <input type="hidden" name="aai_gen_multicast" value="0">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate AAI-64">
</form>

<p>The following options are rather unusual, but are implemented for the sake of completeness:</p>

<form method="GET" action="interprete_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="48">
    <input type="hidden" name="aai_gen_multicast" value="1">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate Multicast AAI-48">
</form>

<br>

<form method="GET" action="interprete_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="64">
    <input type="hidden" name="aai_gen_multicast" value="1">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate Multicast AAI-64">
</form>


<br><br><br>

</body>

</html>
