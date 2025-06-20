<?php

/*
* UUID & MAC Utils
* Copyright 2017 - 2025 Daniel Marschall, ViaThinkSoft
* Version 2025-06-16
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

const AUTO_NEW_UUIDS = 15;

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
            <li><a href="#gen_uuidv7"><font color="green">New:</font> Generate Unix Epoch time-based (version 7) UUID</a></li>
            <li><a href="#gen_uuidv6"><font color="green">New:</font> Generate reordered Gregorian time-based (version 6) UUID</a></li>
            <li><a href="#gen_uuidv4">Generate random (version 4) UUID</a></li>
            <li><a href="#gen_uuidv1">Generate Gregorian time-based (version 1) UUID</a></li>
            <li><a href="#gen_uuidv8_sqlserver"><font color="green">New:</font> Generate SQL Server sortable time-based (version 8) UUID</a></li>
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

<h3 id="gen_uuidv7"><font color="green">New:</font> Generate Unix Epoch time-based (version 7) UUID &#11088;</h3>

<p><i>A UUIDv7 measures time in the Unix Epoch with an accuracy
between 1ms and 245ns, depending on how many bits are spent for the timestamp (48-60 bits).
The rest of the UUID (62-74 bits) is filled with random data.
The timestamp is at the front of the UUID, therefore the UUIDs are monotonically increasing,
which is good for using them in database indexes.
Since this UUID version does not contain a MAC address, it is also
recommended due to the improved privacy.</i></p>

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
- 12 bit Data
-  2 bit Variant (fix 0b10)
- 62 bit Data

Structure of data (74 bits):
- OPTIONAL : Sub-millisecond timestamp fraction (0-12 bits)
- OPTIONAL : Carefully seeded counter
- Random generated bits for any remaining space

Time resolution for various sub-millisecond bits:
<?php
for ($num_ms_frac_bits=0; $num_ms_frac_bits<=12; $num_ms_frac_bits++) {
	$resolution_ns = 1000000 / pow(2,$num_ms_frac_bits);
	if ($resolution_ns >= 1000000) $resolution_ns_hf = ($resolution_ns/1000000)." ms";
	else if ($resolution_ns >= 1000) $resolution_ns_hf = ($resolution_ns/1000)." &micro;s";
	else $resolution_ns_hf = "$resolution_ns ns";
	echo "$num_ms_frac_bits bits fraction = $resolution_ns_hf\n";
}
?>

This implementation outputs:
- 12 bits sub-millisecond timestamp (~245ns resolution)
- no counter
- 62 bits random data
</pre></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
	echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs that were created just for you! (Reload the page to get more)</p>';

	echo '<pre>';
	for ($i=0; $i<AUTO_NEW_UUIDS; $i++) {
		$uuid = gen_uuid_v7();
		echo '<a href="interpret_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
	}
	echo '</pre>';
}
?>

<form method="GET" action="interpret_uuid.php">
    <input type="hidden" name="version" value="7">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID">
</form>

<h3 id="gen_uuidv6"><font color="green">New:</font> Generate reordered Gregorian time-based (version 6) UUID &#9200;</h3>

<p><i>Like UUIDv1, this kind of UUID is made of the MAC address of the generating computer,
        the time, and a clock sequence. However, the components in UUIDv6 are reordered (time is at the beginning),
        so that UUIDs are monotonically increasing, which is good for using them in database indexes.</i></p>

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
	for ($i=0; $i<AUTO_NEW_UUIDS; $i++) {
		$uuid = gen_uuid_v6();
		echo '<a href="interpret_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
	}
	echo '</pre>';
}
?>

<form method="GET" action="interpret_uuid.php">
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
	for ($i=0; $i<AUTO_NEW_UUIDS; $i++) {
		$uuid = gen_uuid_v4();
		echo '<a href="interpret_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
	}
	echo '</pre>';
}
?>

<form method="GET" action="interpret_uuid.php">
    <input type="hidden" name="version" value="4">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID">
</form>

<h3 id="gen_uuidv1">Generate Gregorian time-based (version 1) UUID &#9200;</h3>

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
    for ($i=0; $i<AUTO_NEW_UUIDS; $i++) {
        $uuid = gen_uuid_v1();
        echo '<a href="interpret_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
    }
    echo '</pre>';
}
?>

<form method="GET" action="interpret_uuid.php">
    <input type="hidden" name="version" value="1">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID">
</form>

<h3 id="gen_uuidv8_sqlserver">Generate SQL Server sortable time-based (version 8) UUID</h3>

<p><i>The sorting of UUIDs in SQL Server is rather confusing and incompatible with UUIDv6 and UUIDv7.<br>
Therefore this method developed by <a href="https://www.hickelsoft.de/">HickelSOFT</a>
generates UUIDs which are sortable by SQL Server.<br>
They have a time resolution of 1 milliseconds combined with 16 bits of random data.</i><br>
<a href="https://gist.github.com/danielmarschall/7fafd270a3bc107d38e8449ce7420c25">C# implementation</a> |
<a href="https://github.com/danielmarschall/uuid_mac_utils/blob/master/includes/uuid_utils.inc.php">PHP implementation</a>
</p>

<script>
function show_uuidv8_sqlserver_info() {
	document.getElementById("uuidv8_sqlserver_info_button").style.display = "none";
	document.getElementById("uuidv8_sqlserver_info").style.display = "block";
}
</script>
<p><a id="uuidv8_sqlserver_info_button" href="javascript:show_uuidv8_sqlserver_info()">Show format</a>
<pre id="uuidv8_sqlserver_info" style="display:none">Version 3: Resolution of 1 milliseconds, random part of 16 bits, UTC time, 48 bit random "signature", UUIDv8 compliant:
- 16 bit Random data
-  8 bit UTC Milliseconds transformed from 1000ms to 0..255, deviation -2ms..2ms (hex encoded)
-  8 bit UTC Seconds (hex encoded)
- 16 bit UTC Minute of the day (1..1440, hex encoded) LITTLE ENDIAN (CD AB = 0xABCD)
-  4 bit UUID version 8
- 12 bit UTC Day of the year (1..366, hex encoded) LITTLE ENDIAN (8C AB = 0xABC)
-  2 bit UUID Variant (0b10)
-  2 bit Unused (must be zero)
- 12 bit UTC Year (hex encoded)
- 48 bit Signature 0x5ce32bd83b97

<s>Version 2: Resolution of 1 milliseconds, random part of 16 bits, UTC time, 48 bit random "signature", UUIDv8 compliant:
- 16 bit Random data
-  8 bit UTC Milliseconds transformed from 1000ms to 0..255, deviation -2ms..2ms (hex encoded)
-  8 bit UTC Seconds (hex encoded)
- 16 bit UTC Minute of the day (1..1440, hex encoded) BIG ENDIAN (AB CD = 0xABCD) = WRONG!!!
-  4 bit UUID version 8
- 12 bit UTC Day of the year (1..366, hex encoded) BIG ENDIAN (8A BC = 0xABC) = WRONG!!!
-  2 bit UUID Variant (0b10)
-  2 bit Unused (must be zero)
- 12 bit UTC Year (hex encoded)
- 48 bit Signature 0x5ce32bd83b96</s>

Version 1: Resolution of 1 milliseconds, random part of 16 bits, local timezone, 48 zero bits "signature", *NOT* UUIDv8 compliant:
- 16 bit Random data
-  8 bit Generator's local timezone Milliseconds transformed from 1000ms to 0..255, deviation -4ms..0ms (hex encoded)
-  8 bit Generator's local timezone Seconds (BCD encoded)
-  8 bit Generator's local timezone Minute (BCD encoded)
-  8 bit Generator's local timezone Hour (BCD encoded)
-  8 bit Generator's local timezone Day (BCD encoded)
-  8 bit Generator's local timezone Month (BCD encoded)
-  8 bit Generator's local timezone 2-digit year (BCD encoded)
-  8 bit Generator's local timezone 2-digit century (BCD encoded)
- 48 bit Signature 0x000000000000</pre></p>

<?php
if (AUTO_NEW_UUIDS > 0) { /** @phpstan-ignore-line */
    echo '<p>Here are '.AUTO_NEW_UUIDS.' UUIDs that were created just for you! (Reload the page to get more)</p>';

    echo '<pre>';
    for ($i=0; $i<AUTO_NEW_UUIDS; $i++) {
        $uuid = gen_uuid_v8_sqlserver_sortable();
        echo '<a href="interpret_uuid.php?uuid='.$uuid.'">'.$uuid.'</a><br>';
    }
    echo '</pre>';
}
?>

<form method="GET" action="interpret_uuid.php">
    <input type="hidden" name="version" value="8_sqlserver_v3">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID (new version)">
</form><br>

<!--<form method="GET" action="interpret_uuid.php">
    <input type="hidden" name="version" value="8_sqlserver_v2">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID (broken version)">
</form><br>-->

<form method="GET" action="interpret_uuid.php">
    <input type="hidden" name="version" value="8_sqlserver_v1">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create and display another UUID (old version)">
</form>



<h2 id="gen_other_uuid">Generate other UUID types</h2>

<p><i>The following types of UUIDs are less common and/or require special knowledge. Please only use the following
generators if you know what you are doing.</i></p>

<h3 id="gen_uuid_ncs">NCS (variant 0) UUID</h3>

<p>The <abbr title="Network Computing System">NCS</abbr> UUIDs are a legacy format
initially designed by Apollo Computer that cannot be generated anymore, because the
amount of available timestamp bits was exhausted on <strong>5 September 2015</strong>.
As an example, here is the last possible NCS UUID (all bits of the timestamp are set to 1) for IP address 127.0.0.1:
<a href="interpret_uuid.php?uuid=ffffffff-ffff-0000-027f-000001000000"><code>ffffffff-ffff-0000-027f-000001000000</code></a>.</p>

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

<form method="GET" action="interpret_uuid.php">
	<input type="hidden" name="version" value="2">
	<label>Domain (8&nbsp;bits):</label><select name="domain_choose" id="dce_domain_choice" onchange="javascript:dce_domain_choice_choose();">
		<option value="uid">Person (e.g. POSIX UID)</option>
		<option value="gid">Group (e.g. POSIX GID)</option>
		<option value="org">Organization</option>
		<option value="site">Site-defined</option>
	</select> = Address Family ID: <input type="number" min="0" max="255" name="dce_domain" value="0" id="dce_domain" style="width:50px" pattern="[0-9]+" onchange="javascript:dce_domain_choose();"> (decimal notation)<br>
	<label>Value (32&nbsp;bits):</label><input type="number" min="0" max="4294967295" name="dce_id" value="0" id="dce_id" style="width:200px" pattern="[0-9]+"> (decimal notation)<br>
	<font color="red">Warning</font>: The timestamp has an accuracy of 7:10 minutes,
	therefore the uniqueness of these UUIDs is not guaranteed!<br><br>
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create UUIDv2">
</form>
<script>
function dce_domain_choose() {
	var ns = document.getElementById('dce_domain').value;
	if (ns == "0") {
		document.getElementById('dce_domain_choice').value = "uid";
	} else if (ns == "1") {
		document.getElementById('dce_domain_choice').value = "gid";
	} else if (ns == "2") {
		document.getElementById('dce_domain_choice').value = "org";
	} else {
		document.getElementById('dce_domain_choice').value = "site";
	}
}
function dce_domain_choice_choose() {
	var ns = document.getElementById('dce_domain_choice').value;
	if (ns == "uid") {
		document.getElementById('dce_domain').value = "0";
	} else if (ns == "gid") {
		document.getElementById('dce_domain').value = "1";
	} else if (ns == "org") {
		document.getElementById('dce_domain').value = "2";
	} else if (ns == "site") {
		document.getElementById('dce_domain').value = "";
	}
}
dce_domain_choose();
</script>

<h3 id="gen_uuidv35">Generate name-based (version 3 / 5 / <font color="green">New: 8</font>) UUID</h3>

<p><i>An UUIDv3 is made out of a MD5 hash and an UUIDv5 is made out of a SHA1 hash.
RFC 9562 also contains an example for a custom UUIDv8 implementation that
uses modern hash algorithms.</i></p>

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


<u>As shown in <a href="https://www.ietf.org/rfc/rfc9562.txt">RFC 9562</a> Appendix B.2:</u>
UUIDv8(<i>HashAlgo</i>, <i>NameSpaceUuid</i>, <i>Data</i>) := <abbr title="Adds UUID variant 0b10 and version 8">ConvertRawBytesToUuid_v8</abbr>(<i>HashAlgo</i>( Binary[<i>NameSpaceUuid</i>] || <i>Data</i> )).

</pre></p>

<style>
label {
	width:120px;
	text-align:left;
	margin-right: 20px;
	display:inline-block;
}
</style>

<form method="GET" action="interpret_uuid.php">
	<label>Hash algorithm:</label><select name="version" id="nb_version" onchange="javascript:nb_version_choose();">
		<?php

		echo "\t\t<option disabled>--- UUIDv3 (defined in RFC 4122/9562) ---</option>\n";
		echo "\t\t<option value=\"3\">MD5</option>\n";
		echo "\t\t<option disabled>--- UUIDv5 (defined in RFC 4122/9562) ---</option>\n";
		echo "\t\t<option value=\"5\" selected>SHA1</option>\n";
		echo "\t\t<option disabled>--- UUIDv8 (shown in RFC 9562, Appendix B.2) ---</option>\n";
		$tmp = [];
		$algos = hash_algos();
		$algos[] = 'shake128';
		$algos[] = 'shake256';
		foreach ($algos as $algo) {
			if ($algo == 'md5') continue; // use UUIDv3 instead
			if ($algo == 'sha1') continue; // use UUIDv5 instead
			$friendlyName = strtoupper($algo);

			if ($algo == 'shake128') $bits = 999;
			else if ($algo == 'shake256') $bits = 999;
			else $bits = strlen(hash($algo, '', true)) * 8;
			if ($bits < 128) $friendlyName .= " (Small hash size! $bits bits)"; // <-- this is not described in Appendix C.2

			$tmp[$friendlyName] = '<option value="8_namebased_'.$algo.'">'.htmlentities($friendlyName).'</option>';
		}
		natsort($tmp);
		foreach ($tmp as $html) {
			echo "\t\t$html\n";
		}

		?>
	</select><br>
	<label>Namespace:</label><select name="namespace_choose" id="nb_nsc" onchange="javascript:nb_ns_choose();">
		<option value="dns">DNS</option>
		<option value="url">URL</option>
		<option value="oid">OID</option>
		<option value="x500">X.500 DN</option>
		<option value="r74n">R74n Namespace</option>
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
	else if (ns == "ca069732-780c-11ee-b962-000000000074") { // https://r74n.com/id/uuid
		if (document.getElementById('nb_nsc').value != "r74n") {
			document.getElementById('nb_nsc').value = "r74n";
			document.getElementById('nb_val').value = "ants";
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
	else if (ns == "r74n") { // https://r74n.com/id/uuid
		document.getElementById('nb_ns').value = "ca069732-780c-11ee-b962-000000000074";
		document.getElementById('nb_val').value = "ants";
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

<form method="GET" action="interpret_uuid.php">
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

<form method="GET" action="interpret_uuid.php">
	<input type="text" name="uuid" value="" style="width:300px"> <input type="submit" value="Interpret">
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

<form method="GET" action="interpret_mac.php">
	<input type="text" name="mac" value="" style="width:250px"> <input type="submit" value="Interpret">
</form>

<h3 id="gen_aai">Generate an <abbr title="Administratively Assigned Identifier">AAI</abbr></h3>

<p><i>An Administratively Assigned Identifier (AAI) is a MAC address which can be locally defined
by applications or an administrator. Unlike the EUI, an AAI is NOT worldwide unique.</i></p>

<form method="GET" action="interpret_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="48">
    <input type="hidden" name="aai_gen_multicast" value="0">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate AAI-48">
</form>

<br>

<form method="GET" action="interpret_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="64">
    <input type="hidden" name="aai_gen_multicast" value="0">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate AAI-64">
</form>

<p>The following options are rather unusual, but are implemented for the sake of completeness:</p>

<form method="GET" action="interpret_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="48">
    <input type="hidden" name="aai_gen_multicast" value="1">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate Multicast AAI-48">
</form>

<br>

<form method="GET" action="interpret_mac.php">
    <input type="hidden" name="aai_gen" value="1">
    <input type="hidden" name="aai_gen_bits" value="64">
    <input type="hidden" name="aai_gen_multicast" value="1">
    <input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Generate Multicast AAI-64">
</form>

<br><br><br>

</body>

</html>
