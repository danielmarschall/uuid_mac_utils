<!DOCTYPE html>
<html>

<head>
	<meta charset="iso-8859-1">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>UUID &amp; MAC Utils by Daniel Marschall</title>
</head>

<body>

<h1>UUID &amp; MAC Utils by Daniel Marschall</h1>

<!-- <p><a href="https://svn.viathinksoft.com/cgi-bin/viewvc.cgi/uuid_mac_utils/">View the source code</a></p> -->
<p><a href="https://github.com/danielmarschall/uuid_mac_utils/">View the source code</a></p>

<h2>Generate an UUID (according to RFC 4122)</h2>

<h3>Generate time based (version 1) UUID</h3>

<form method="GET" action="interprete_uuid.php">
	<input type="hidden" name="version" value="1">
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create an UUID">
</form>

<h3>Generate DCE Security (version 2) UUID</h3>

<p><font color="red">Attention: The uniqueness of these UUIDs is not guaranteed! The use of this UUID version is not recommended!</font></p>

<form method="GET" action="interprete_uuid.php">
	<input type="hidden" name="version" value="2">
	Domain (8 bits): <select name="domain_choose" id="dce_domain_choice" onchange="javascript:dce_domain_choose();">
		<option value="uid">POSIX UID</option>
		<option value="gid">POSIX GID</option>
		<option value="org">Org</option>
		<option value="site">Site-defined</option>
	</select> <input type="text" name="dce_domain" value="" id="dce_domain" style="width:50px"> (decimal notation)<br>
	Value (32 bits): <input type="text" name="dce_id" value="0" id="dce_id" style="width:200px"> (decimal notation)<br><br>
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

<h3>Generate name based (version 3/5) UUID</h3>

<form method="GET" action="interprete_uuid.php">
	Hash: <select name="version">
		<option value="3">MD5 (version 3 UUID)</option>
		<option value="5">SHA1 (version 5 UUID)</option>
	</select><br>
	Namespace: <select name="namespace_choose" id="nb_nsc" onchange="javascript:nb_ns_choose();">
		<option value="dns">DNS</option>
		<option value="url">URL</option>
		<option value="oid">OID</option>
		<option value="x500">X.500 DN</option>
		<option value="oidplus_ns">OIDplus 1.x ns only</option>
		<option value="oidplus_ns_val">OIDplus 1.x ns+val</option>
		<option value="other">Other</option>
	</select> <input type="text" name="nb_ns" value="" id="nb_ns" style="width:300px"><br>
	Value: <input type="text" name="nb_val" value="" id="nb_val" style="width:300px"><br><br>
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
	if (ns == "other") {
		document.getElementById('nb_ns').value = "";
		document.getElementById('nb_val').value = "";
	}
}
nb_ns_choose();
</script>

<h3>Generate random (version 4) UUID</h3>

<form method="GET" action="interprete_uuid.php">
	<input type="hidden" name="version" value="4">
	<input type="hidden" name="uuid" value="CREATE"> <input type="submit" value="Create an UUID">
</form>

<h2>Interprete an UUID</h2>

<p>You can enter an UUID in the following notations:</p>

<ul>
	<li>Classic notation (case insensitive, curly braces optional): <code>9e83839a-5967-11e4-8c1c-78e3b5fc7f22</code></li>
	<li>As OID: <code>2.25.210700883446948645633376489934419689250</code></li>
</ul>

<p>The script will output:</p>

<ul>
	<li>Notation as UUID and OID</li>
	<li>Version, variant and additional data (date and time, clock seq, node id etc.)</li>
</ul>

<p>Please enter an UUID or UUID OID:</p>

<form method="GET" action="interprete_uuid.php">
	<input type="text" name="uuid" value="" style="width:500px"> <input type="submit" value="Go">
</form>

<h2>Interprete a MAC address (EUI-48 or EUI-64)</h2>

<p>You can enter an UUID in the following notations:</p>

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

<p>Please enter a MAC address:</p>

<form method="GET" action="interprete_mac.php">
	<input type="text" name="mac" value=""> <input type="submit" value="Go">
</form>

</body>

</html>
