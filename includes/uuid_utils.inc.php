<?php

/*
 * UUID utils for PHP
 * Copyright 2011 - 2023 Daniel Marschall, ViaThinkSoft
 * Version 2023-07-12
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

# This library requires either the GMP extension (or BCMath if gmp_supplement.inc.php is present)

if (file_exists(__DIR__ . '/mac_utils.inc.phps')) include_once __DIR__ . '/mac_utils.inc.phps'; // optionally used for uuid_info()
if (file_exists(__DIR__ . '/gmp_supplement.inc.php')) include_once __DIR__ . '/gmp_supplement.inc.php';

const UUID_NAMEBASED_NS_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8'; // FQDN
const UUID_NAMEBASED_NS_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
const UUID_NAMEBASED_NS_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
const UUID_NAMEBASED_NS_X500_DN = '6ba7b814-9dad-11d1-80b4-00c04fd430c8'; // DER according to https://github.com/cjsv/uuid/blob/master/Doc ?!

function _random_int($min, $max) {
	// This function tries a CSRNG and falls back to a RNG if no CSRNG is available
	try {
		return random_int($min, $max);
	} catch (Exception $e) {
		return mt_rand($min, $max);
	}
}

function uuid_valid($uuid) {
	$uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$uuid = strtoupper($uuid);
	#$uuid = trim($uuid);

	if (strlen($uuid) != 32) return false;

	$uuid = preg_replace('@[0-9A-F]@i', '', $uuid);

	return ($uuid == '');
}

function uuid_info($uuid, $echo=true) {
	if (!uuid_valid($uuid)) return false;

	if (!$echo) ob_start();

	#$uuid = trim($uuid);
	# $uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$uuid = strtolower($uuid);
	$uuid = preg_replace('@[^0-9A-F]@i', '', $uuid);

	$x = hexdec(substr($uuid, 16, 1));
	     if ($x >= 14 /* 0b1110 */) $variant = 3;
	else if ($x >= 12 /* 0b110_ */) $variant = 2;
	else if ($x >=  8 /* 0b10__ */) $variant = 1;
	else if ($x >=  0 /* 0b0___ */) $variant = 0;
	else $variant = -1; // should not happen

	if ($uuid == '00000000000000000000000000000000') {
		echo sprintf("%-32s %s\n", "Special Use:", "Nil UUID");
		echo "\n";
	}
	else if ($uuid == 'ffffffffffffffffffffffffffffffff') {
		echo sprintf("%-32s %s\n", "Special Use:", "Max UUID");
		echo "\n";
	}

	switch ($variant) {
		case 0:
			echo sprintf("%-32s %s\n", "Variant:", "[0b0__] Network Computing System (NCS)");

			/*
			 * Internal structure of variant #0 UUIDs
			 *
			 * The first 6 octets are the number of 4 usec units of time that have
			 * passed since 1/1/80 0000 GMT.  The next 2 octets are reserved for
			 * future use.  The next octet is an address family.  The next 7 octets
			 * are a host ID in the form allowed by the specified address family.
			 *
			 * Note that while the family field (octet 8) was originally conceived
			 * of as being able to hold values in the range [0..255], only [0..13]
			 * were ever used.  Thus, the 2 MSB of this field are always 0 and are
			 * used to distinguish old and current UUID forms.
			 */

			/*
			Variant 0 UUID
			- 32 bit High Time
			- 16 bit Low Time
			- 16 bit Reserved
			-  1 bit Variant (fix 0b0)
			-  7 bit Family
			- 56 bit Node
			*/

			// Example of an UUID: 333a2276-0000-0000-0d00-00809c000000

			// TODO: also show legacy format, e.g. 458487b55160.02.c0.64.02.03.00.00.00

			# see also some notes at See https://github.com/cjsv/uuid/blob/master/Doc

			/*
			NOTE: A generator is not possible, because there are no timestamps left!
			The last possible timestamp was:
			    [0xFFFFFFFFFFFF] 2015-09-05 05:58:26'210655 GMT
			That is in the following UUID:
			    ffffffff-ffff-0000-027f-000001000000
			Current timestamp generator:
			    echo dechex(round((microtime(true)+315532800)*250000));
			*/

			# Timestamp: Count of 4us intervals since 01 Jan 1980 00:00:00 GMT
			# 1/0,000004 = 250000
			# Seconds between 1970 and 1980 : 315532800
			# 250000*315532800=78883200000000
			$timestamp = substr($uuid, 0, 12);
			$ts = gmp_init($timestamp, 16);
			$ts = gmp_add($ts, gmp_init("78883200000000", 10));
			$ms = gmp_mod($ts, gmp_init("250000", 10));
			$ts = gmp_div($ts, gmp_init("250000", 10));
			$ts = gmp_strval($ts, 10);
			$ms = gmp_strval($ms, 10);
			$ts = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 6/*us*/, '0', STR_PAD_LEFT).' GMT';
			echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts");

			$reserved = substr($uuid, 12, 4);
			echo sprintf("%-32s %s\n", "Reserved:", "[0x$reserved]");

			$family_hex = substr($uuid, 16, 2);
			$family_dec = hexdec($family_hex);
			$nodeid_hex = substr($uuid, 18, 14);
			$nodeid_dec = hexdec($nodeid_hex);
			if ($family_dec == 2) {
				$family_name = 'IP';
				// https://www.ibm.com/docs/en/aix/7.1?topic=u-uuid-gen-command-ncs (AIX 7.1) shows the following example output for /etc/ncs/uuid_gen -P
				// := [
				//    time_high := 16#458487df,
				//    time_low := 16#9fb2,
				//    reserved := 16#000,
				//    family := chr(16#02),
				//    host := [chr(16#c0), chr(16#64), chr(16#02), chr(16#03),
				//             chr(16#00), chr(16#00), chr(16#00)]
				//    ]
				// This means that the IP address is 32 bits hex, and 32 bits are unused
				$nodeid_desc = hexdec(substr($nodeid_hex,0,2)).'.'.
				               hexdec(substr($nodeid_hex,2,2)).'.'.
				               hexdec(substr($nodeid_hex,4,2)).'.'.
				               hexdec(substr($nodeid_hex,6,2));
				$rest = substr($nodeid_hex,8,6);
				if ($rest != '000000') $nodeid_desc .= " + unexpected rest 0x$rest";
			} else if ($family_dec == 13) {
				$family_name = 'DDS (Data Link)';
				// https://www.ibm.com/docs/en/aix/7.1?topic=u-uuid-gen-command-ncs (AIX 7.1) shows the following example output for /etc/ncs/uuid_gen -C
				// = { 0x34dc23af,
				//    0xf000,
				//    0x0000,
				//    0x0d,
				//    {0x00, 0x00, 0x7c, 0x5f, 0x00, 0x00, 0x00} };
				// https://github.com/cjsv/uuid/blob/master/Doc writes:
				//    "Family 13 (dds) looks like node is 00 | nnnnnn 000000."

				$nodeid_desc = '';

				$start = substr($nodeid_hex,0,2);
				if ($start != '00') $nodeid_desc .= "unexpected start 0x$start + ";

				$nodeid_desc .= ($nodeid_dec >> 24) & 0xFFFFFF;

				$rest = substr($nodeid_hex,8,6);
				if ($rest != '000000') $nodeid_desc .= " + unexpected rest 0x$rest";
			} else {
				$family_name = "Unknown (Family $family_dec)"; # There are probably no more families
				$nodeid_desc = "Unknown";
			}
			echo sprintf("%-32s %s\n", "Family:", "[0x$family_hex] $family_name");

			echo sprintf("%-32s %s\n", "Node ID:", "[0x$nodeid_hex] $nodeid_desc");

			break;
		case 1:
			// TODO: Show byte order: 00112233-4455-6677-8899-aabbccddeeff => 00 11 22 33 44 55 66 77 88 99 aa bb cc dd ee ff

			$version = hexdec(substr($uuid, 12, 1));

			if ($version <= 2) {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] RFC 4122 (Leach-Mealling-Salz) / DCE 1.1");
			} else if (($version >= 3) && ($version <= 5)) {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] RFC 4122 (Leach-Mealling-Salz)");
			} else if (($version >= 6) && ($version <= 8)) {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] RFC 4122bis (Leach-Mealling-Peabody-Davis)");
			} else {
				echo sprintf("%-32s %s\n", "Variant:", "[0b10_] RFC 4122 ?");
			}

			switch ($version) {
				case 6:
					/*
					Variant 1, Version 6 UUID
					- 48 bit High Time
					-  4 bit Version (fix 0x6)
					- 12 bit Low Time
					-  2 bit Variant (fix 0b10)
					-  6 bit Clock Sequence High
					-  8 bit Clock Sequence Low
					- 48 bit MAC Address
					*/
					echo sprintf("%-32s %s\n", "Version:", "[6] Reordered Time");
					$uuid = substr($uuid,  0, 8).'-'.
					        substr($uuid,  8, 4).'-'.
					        substr($uuid, 12, 4).'-'.
					        substr($uuid, 16, 4).'-'.
					        substr($uuid, 20, 12);
					$uuid = uuid6_to_uuid1($uuid);
					$uuid = str_replace('-', '', $uuid);

				/* fallthrough */
				case 1:
					/*
					Variant 1, Version 1 UUID
					- 32 bit Low Time
					- 16 bit Mid Time
					-  4 bit Version (fix 0x1)
					- 12 bit High Time
					-  2 bit Variant (fix 0b10)
					-  6 bit Clock Sequence High
					-  8 bit Clock Sequence Low
					- 48 bit MAC Address
					*/

					if ($version == 1) echo sprintf("%-32s %s\n", "Version:", "[1] Time-based with unique host identifier");

					# Timestamp: Count of 100ns intervals since 15 Oct 1582 00:00:00
					# 1/0,0000001 = 10000000
					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).substr($uuid, 0, 8);
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000", 10));
					$ms = gmp_mod($ts, gmp_init("10000000", 10));
					$ts = gmp_div($ts, gmp_init("10000000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 7/*0.1us*/, '0', STR_PAD_LEFT).' GMT';
					echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts");

					$x = hexdec(substr($uuid, 16, 4));
					$dec = $x & 0x3FFF; // The highest 2 bits are used by "variant" (10x)
					$hex = substr($uuid, 16, 4);
					echo sprintf("%-32s %s\n", "Clock ID:", "[0x$hex] $dec");

					$x = substr($uuid, 20, 12);
					$nodeid = '';
					for ($i=0; $i<6; $i++) {
						$nodeid .= substr($x, $i*2, 2);
						if ($i != 5) $nodeid .= '-';
					}
					$nodeid = strtoupper($nodeid);
					echo sprintf("%-32s %s\n", "Node ID:", "[0x$x] $nodeid");

					if (function_exists('decode_mac')) {
						echo "\nIn case that this Node ID is a MAC address, here is the interpretation of that MAC address:\n\n";
						decode_mac(strtoupper($nodeid));
					}

					break;
				case 2:
					/*
					Variant 1, Version 2 UUID
					- 32 bit Local Domain Number
					- 16 bit Mid Time
					-  4 bit Version (fix 0x2)
					- 12 bit High Time
					-  2 bit Variant (fix 0b10)
					-  6 bit Clock Sequence
					-  8 bit Local Domain
					- 48 bit MAC Address
					*/

					// see also https://unicorn-utterances.com/posts/what-happened-to-uuid-v2

					echo sprintf("%-32s %s\n", "Version:", "[2] DCE Security version");

					# The clock_seq_low field (which represents an integer in the range [0, 28-1]) is interpreted as a local domain (as represented by sec_rgy_domain_t; see sec_rgy_domain_t ); that is, an identifier domain meaningful to the local host. (Note that the data type sec_rgy_domain_t can potentially hold values outside the range [0, 28-1]; however, the only values currently registered are in the range [0, 2], so this type mismatch is not significant.) In the particular case of a POSIX host, the value sec_rgy_domain_person is to be interpreted as the "POSIX UID domain", and the value sec_rgy_domain_group is to be interpreted as the "POSIX GID domain".
					$x = substr($uuid, 18, 2);
					if ($x == '00') $domain_info = 'Person (POSIX: User-ID)';
					else if ($x == '01') $domain_info = 'Group (POSIX: Group-ID)';
					else if ($x == '02') $domain_info = 'Organization';
					else $domain_info = 'site-defined (Domain '.hexdec($x).')';
					echo sprintf("%-32s %s\n", "Local Domain:", "[0x$x] $domain_info");

					# The time_low field (which represents an integer in the range [0, 232-1]) is interpreted as a local-ID; that is, an identifier (within the domain specified by clock_seq_low) meaningful to the local host. In the particular case of a POSIX host, when combined with a POSIX UID or POSIX GID domain in the clock_seq_low field (above), the time_low field represents a POSIX UID or POSIX GID, respectively.
					$x = substr($uuid, 0, 8);
					$dec = hexdec($x);
					echo sprintf("%-32s %s\n", "Local Domain Number:", "[0x$x] $dec");

					# Timestamp: Count of 100ns intervals since 15 Oct 1582 00:00:00
					# 1/0,0000001 = 10000000
					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).'00000000';
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000", 10));
					$ms = gmp_mod($ts, gmp_init("10000000", 10));
					$ts = gmp_div($ts, gmp_init("10000000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts_min = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 7/*0.1us*/, '0', STR_PAD_LEFT).' GMT';

					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4).'FFFFFFFF';
					$ts = gmp_init($timestamp, 16);
					$ts = gmp_sub($ts, gmp_init("122192928000000000", 10));
					$ms = gmp_mod($ts, gmp_init("10000000", 10));
					$ts = gmp_div($ts, gmp_init("10000000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts_max = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 7/*0.1us*/, '0', STR_PAD_LEFT).' GMT';

					$timestamp = substr($uuid, 13, 3).substr($uuid, 8, 4)/*.'xxxxxxxx'*/;
					echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts_min - $ts_max");

					$x = hexdec(substr($uuid, 16, 2));
					$dec = $x & 0x3F; // The highest 2 bits are used by "variant" (10xx)
					$hex = substr($uuid, 16, 2);
					echo sprintf("%-32s %s\n", "Clock ID:", "[0x$hex] $dec");

					$x = substr($uuid, 20, 12);
					$nodeid = '';
					for ($i=0; $i<6; $i++) {
						$nodeid .= substr($x, $i*2, 2);
						if ($i != 5) $nodeid .= '-';
					}
					$nodeid = strtoupper($nodeid);
					echo sprintf("%-32s %s\n", "Node ID:", "[0x$x] $nodeid");

					if (function_exists('decode_mac')) {
						echo "\nIn case that this Node ID is a MAC address, here is the interpretation of that MAC address:\n\n";
						decode_mac(strtoupper($nodeid));
					}

					break;
				case 3:
					/*
					Variant 1, Version 3 UUID
					- 48 bit Hash High
					-  4 bit Version (fix 0x3)
					- 12 bit Hash Mid
					-  2 bit Variant (fix 0b10)
					- 62 bit Hash Low
					*/

					echo sprintf("%-32s %s\n", "Version:", "[3] Name-based (MD5 hash)");

					$hash = str_replace('-', '', strtolower($uuid));

					$hash[12] = '?'; // was overwritten by version

					$var16a = strtoupper(dechex((hexdec($hash[16])&3) + 0x0/*00__*/));
					$var16b = strtoupper(dechex((hexdec($hash[16])&3) + 0x4/*01__*/));
					$var16c = strtoupper(dechex((hexdec($hash[16])&3) + 0x8/*10__*/));
					$var16d = strtoupper(dechex((hexdec($hash[16])&3) + 0xC/*11__*/));
					$hash[16] = '?'; // was partially overwritten by variant

					echo sprintf("%-32s %s\n", "MD5(Namespace+Subject):", "[0x$hash]");
					echo sprintf("%-32s %s\n", "", "                   ^");
					echo sprintf("%-32s %s\n", "", "                   $var16a, $var16b, $var16c, or $var16d");

					break;
				case 4:
					/*
					Variant 1, Version 4 UUID
					- 48 bit Random High
					-  4 bit Version (fix 0x4)
					- 12 bit Random Mid
					-  2 bit Variant (fix 0b10)
					- 62 bit Random Low
					*/

					echo sprintf("%-32s %s\n", "Version:", "[4] Random");

					$rand_line1 = '';
					$rand_line2 = '';
					for ($i=0; $i<16; $i++) {
						$bin = base_convert(substr($uuid, $i*2, 2), 16, 2);
						$bin = str_pad($bin, 8, "0", STR_PAD_LEFT);

						if ($i == 6) {
							// was overwritten by version
							$bin[0] = '?';
							$bin[1] = '?';
							$bin[2] = '?';
							$bin[3] = '?';
						} else if ($i == 8) {
							// was partially overwritten by variant
							$bin[0] = '?';
							$bin[1] = '?';
						}

						if ($i<8) $rand_line1 .= "$bin ";
						if ($i>=8) $rand_line2 .= "$bin ";
					}
					echo sprintf("%-32s %s\n", "Random bits:", trim($rand_line1));
					echo sprintf("%-32s %s\n", "",             trim($rand_line2));

					$rand_bytes = str_replace('-', '', strtolower($uuid));
					$rand_bytes[12] = '?'; // was overwritten by version
					$var16a = strtoupper(dechex((hexdec($rand_bytes[16])&3) + 0x0/*00__*/));
					$var16b = strtoupper(dechex((hexdec($rand_bytes[16])&3) + 0x4/*01__*/));
					$var16c = strtoupper(dechex((hexdec($rand_bytes[16])&3) + 0x8/*10__*/));
					$var16d = strtoupper(dechex((hexdec($rand_bytes[16])&3) + 0xC/*11__*/));
					$rand_bytes[16] = '?'; // was partially overwritten by variant
					echo sprintf("%-32s %s\n", "Random bytes:", "[0x$rand_bytes]");
					echo sprintf("%-32s %s\n", "", "                   ^");
					echo sprintf("%-32s %s\n", "", "                   $var16a, $var16b, $var16c, or $var16d");

					break;
				case 5:
					/*
					Variant 1, Version 5 UUID
					- 48 bit Hash High
					-  4 bit Version (fix 0x5)
					- 12 bit Hash Mid
					-  2 bit Variant (fix 0b10)
					- 62 bit Hash Low
					*/

					echo sprintf("%-32s %s\n", "Version:", "[5] Name-based (SHA-1 hash)");

					$hash = str_replace('-', '', strtolower($uuid));

					$hash[12] = '?'; // was overwritten by version

					$var16a = strtoupper(dechex((hexdec($hash[16])&3) + 0x0/*00__*/));
					$var16b = strtoupper(dechex((hexdec($hash[16])&3) + 0x4/*01__*/));
					$var16c = strtoupper(dechex((hexdec($hash[16])&3) + 0x8/*10__*/));
					$var16d = strtoupper(dechex((hexdec($hash[16])&3) + 0xC/*11__*/));
					$hash[16] = '?'; // was partially overwritten by variant

					$hash .= '????????'; // was cut off

					echo sprintf("%-32s %s\n", "SHA1(Namespace+Subject):", "[0x$hash]");
					echo sprintf("%-32s %s\n", "", "                   ^");
					echo sprintf("%-32s %s\n", "", "                   $var16a, $var16b, $var16c, or $var16d");

					break;
				case 7:
					/*
					Variant 1, Version 7 UUID
					- 48 bit Unix Time in milliseconds
					-  4 bit Version (fix 0x7)
					- 12 bit Random
					-  2 bit Variant (fix 0b10)
					- 62 bit Random
					*/

					echo sprintf("%-32s %s\n", "Version:", "[7] Unix Epoch Time");

					$timestamp = substr($uuid, 0, 12);

					// Timestamp: Split into seconds and milliseconds
					$ts = gmp_init($timestamp, 16);
					$ms = gmp_mod($ts, gmp_init("1000", 10));
					$ts = gmp_div($ts, gmp_init("1000", 10));
					$ts = gmp_strval($ts, 10);
					$ms = gmp_strval($ms, 10);
					$ts = gmdate('Y-m-d H:i:s', intval($ts))."'".str_pad($ms, 3/*ms*/, '0', STR_PAD_LEFT).' GMT';
					echo sprintf("%-32s %s\n", "Timestamp:", "[0x$timestamp] $ts");

					$rand = '';
					for ($i=6; $i<16; $i++) {
						$bin = base_convert(substr($uuid, $i*2, 2), 16, 2);
						$bin = str_pad($bin, 8, "0", STR_PAD_LEFT);

						if ($i == 6) {
							// was overwritten by version
							$bin[0] = '?';
							$bin[1] = '?';
							$bin[2] = '?';
							$bin[3] = '?';
						} else if ($i == 8) {
							// was partially overwritten by variant
							$bin[0] = '?';
							$bin[1] = '?';
						}

						$rand .= "$bin ";
					}
					echo sprintf("%-32s %s\n", "Random bits:", trim($rand));

					$rand_bytes = substr(str_replace('-', '', strtolower($uuid)),13);
					$var16a = strtoupper(dechex((hexdec($rand_bytes[3])&3) + 0x0/*00__*/));
					$var16b = strtoupper(dechex((hexdec($rand_bytes[3])&3) + 0x4/*01__*/));
					$var16c = strtoupper(dechex((hexdec($rand_bytes[3])&3) + 0x8/*10__*/));
					$var16d = strtoupper(dechex((hexdec($rand_bytes[3])&3) + 0xC/*11__*/));
					$rand_bytes[3] = '?'; // was partially overwritten by variant
					echo sprintf("%-32s %s\n", "Random bytes:", "[0x$rand_bytes]");
					echo sprintf("%-32s %s\n", "", "      ^");
					echo sprintf("%-32s %s\n", "", "      $var16a, $var16b, $var16c, or $var16d");

					// TODO: convert to and from Base32 CROCKFORD ULID (make 2 methods in uuid_utils.inc.php)
					// e.g. ULID: 01GCZ05N3JFRKBRWKNGCQZGP44
					// "Be aware that all version 7 UUIDs may be converted to ULIDs but not all ULIDs may be converted to UUIDs."

					break;
				case 8:
					/*
					Variant 1, Version 8 UUID
					- 48 bit Custom data
					-  4 bit Version (fix 0x8)
					- 12 bit Custom data
					-  2 bit Variant (fix 0b10)
					- 62 bit Custom data
					*/

					echo sprintf("%-32s %s\n", "Version:", "[8] Custom implementation");

					$custom_data = substr($uuid,0,12).substr($uuid,13); // exclude version nibble
					$custom_data[15] = dechex(hexdec($custom_data[15])&3); // nibble was partially overwritten by variant
					$custom_data = strtolower($custom_data);

					$custom_block1 = substr($uuid,  0, 8);
					$custom_block2 = substr($uuid,  8, 4);
					$custom_block3 = substr($uuid, 12, 4);
					$custom_block4 = substr($uuid, 16, 4);
					$custom_block5 = substr($uuid, 20);

					$custom_block3 = substr($custom_block3, 1); // remove version
					$custom_block4[0] = dechex(hexdec($custom_block4[0])&3); // remove variant

					echo sprintf("%-32s %s\n", "Custom data:", "[0x$custom_data]");
					echo sprintf("%-32s %s\n", "Custom block1 (32 bit):", "[0x$custom_block1]");
					echo sprintf("%-32s %s\n", "Custom block2 (16 bit):", "[0x$custom_block2]");
					echo sprintf("%-32s %s\n", "Custom block3 (12 bit):", "[0x$custom_block3]");
					echo sprintf("%-32s %s\n", "Custom block4 (14 bit):", "[0x$custom_block4]");
					echo sprintf("%-32s %s\n", "Custom block5 (48 bit):", "[0x$custom_block5]");

					break;
				default:
					echo sprintf("%-32s %s\n", "Version:", "[$version] Unknown");
					break;
			}

			break;
		case 2:
			// TODO: Show byte order: 00112233-4455-6677-8899-aabbccddeeff => 33 22 11 00 55 44 77 66 88 99 aa bb cc dd ee ff

			// TODO: Is there any scheme in that legacy Microsoft GUIDs?
			echo sprintf("%-32s %s\n", "Variant:", "[0b110] Reserved for Microsoft Corporation");
			break;
		case 3:
			echo sprintf("%-32s %s\n", "Variant:", "[0b111] Reserved for future use");
			break;
	}

	if (!$echo) {
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	} else {
		return true;
	}
}

function uuid_canonize($uuid) {
	if (!uuid_valid($uuid)) return false;
	return oid_to_uuid(uuid_to_oid($uuid));
}

function oid_to_uuid($oid) {
	if (!is_uuid_oid($oid)) return false;

	if (substr($oid,0,1) == '.') {
		$oid = substr($oid, 1);
	}
	$ary = explode('.', $oid);

	if (!isset($ary[2])) return false;

	$val = $ary[2];

	$x = gmp_init($val, 10);
	$y = gmp_strval($x, 16);
	$y = str_pad($y, 32, "0", STR_PAD_LEFT);
	return substr($y,  0, 8).'-'.
	       substr($y,  8, 4).'-'.
	       substr($y, 12, 4).'-'.
	       substr($y, 16, 4).'-'.
	       substr($y, 20, 12);
}

function is_uuid_oid($oid, $only_allow_root=false) {
	if (substr($oid,0,1) == '.') $oid = substr($oid, 1); // remove leading dot

	$ary = explode('.', $oid);

	if ($only_allow_root) {
		if (count($ary) != 3) return false;
	} else {
		if (count($ary) < 3) return false;
	}

	if ($ary[0] != '2') return false;
	if ($ary[1] != '25') return false;
	for ($i=2; $i<count($ary); $i++) {
		$v = $ary[$i];
		if (!is_numeric($v)) return false;
		if ($i == 2) {
			// Must be in the range of 128 bit UUID
			$test = gmp_init($v, 10);
			if (strlen(gmp_strval($test, 16)) > 32) return false;
		}
		if ($v < 0) return false;
	}

	return true;
}

function uuid_to_oid($uuid) {
	if (!uuid_valid($uuid)) return false;

	$uuid = str_replace(array('-', '{', '}'), '', $uuid);
	$x = gmp_init($uuid, 16);
	return '2.25.'.gmp_strval($x, 10);
}

function uuid_numeric_value($uuid) {
	$oid = uuid_to_oid($uuid);
	if (!$oid) return false;
	return substr($oid, strlen('2.25.'));
}

function uuid_c_syntax($uuid) {
	$uuid = str_replace('{', '', $uuid);
	return '{ 0x' . substr($uuid, 0, 8) .
		', 0x' . substr($uuid, 9, 4) .
		', 0x' . substr($uuid, 14, 4) .
		', { 0x' . substr($uuid, 19, 2).
		', 0x' . substr($uuid, 21, 2) .
		', 0x' . substr($uuid, 24, 2) .
		', 0x' . substr($uuid, 26, 2) .
		', 0x' . substr($uuid, 28, 2) .
		', 0x' . substr($uuid, 30, 2) .
		', 0x' . substr($uuid, 32, 2) .
		', 0x' . substr($uuid, 34, 2) . ' } }';
}

function gen_uuid($prefer_mac_address_based = true) {
	$uuid = $prefer_mac_address_based ? gen_uuid_reordered()/*UUIDv6*/ : false;
	if ($uuid === false) $uuid = gen_uuid_unix_epoch()/*UUIDv7*/;
	return $uuid;
}

# --------------------------------------
// Variant 1, Version 1 (Time based) UUID
# --------------------------------------

function gen_uuid_v1() {
	return gen_uuid_timebased();
}
function gen_uuid_timebased() {
	# On Debian: apt-get install php-uuid
	# extension_loaded('uuid')
	if (function_exists('uuid_create')) {
		# OSSP uuid extension like seen in php5-uuid at Debian 8
		/*
		$x = uuid_create($context);
		uuid_make($context, UUID_MAKE_V1);
		uuid_export($context, UUID_FMT_STR, $uuid);
		return trim($uuid);
		*/

		# PECL uuid extension like seen in php-uuid at Debian 9
		return trim(uuid_create(UUID_TYPE_TIME));
	}

	# On Debian: apt-get install uuid-runtime
	if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
		$out = array();
		$ec = -1;
		exec('uuidgen -t 2>/dev/null', $out, $ec);
		if ($ec == 0) return trim($out[0]);
	}

	# If we hadn't any success yet, then implement the time based generation routine ourselves!
	# Based on https://github.com/fredriklindberg/class.uuid.php/blob/master/class.uuid.php

	$uuid = array(
		'time_low' => 0,		/* 32-bit */
		'time_mid' => 0,		/* 16-bit */
		'time_hi' => 0,			/* 16-bit */
		'clock_seq_hi' => 0,		/*  8-bit */
		'clock_seq_low' => 0,		/*  8-bit */
		'node' => array()		/* 48-bit */
	);

	/*
	 * Get current time in 100 ns intervals. The magic value
	 * is the offset between UNIX epoch and the UUID UTC
	 * time base October 15, 1582.
	 */
	usleep(1); // make sure the timestamp is not used before
	$tp = gettimeofday();
	$time = ($tp['sec'] * 10000000) + ($tp['usec'] * 10) + 0x01B21DD213814000;

	$uuid['time_low'] = $time & 0xffffffff;
	/* Work around PHP 32-bit bit-operation limits */
	$high = intval($time / 0xffffffff);
	$uuid['time_mid'] = $high & 0xffff;
	$uuid['time_hi'] = (($high >> 16) & 0xfff) | (1/*TimeBased*/ << 12);

	/*
	 * We don't support saved state information and generate
	 * a random clock sequence each time.
	 */
	$uuid['clock_seq_hi'] = 0x80 | _random_int(0, 64);
	$uuid['clock_seq_low'] = _random_int(0, 255);

	/*
	 * Node should be set to the 48-bit IEEE node identifier
	 */
	$mac = get_mac_address();
	if ($mac) {
		$node = str_replace('-','',str_replace(':','',$mac));
		for ($i = 0; $i < 6; $i++) {
			$uuid['node'][$i] = hexdec(substr($node, $i*2, 2));
		}

		/*
		 * Now output the UUID
		 */
		return sprintf(
			'%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
			($uuid['time_low']), ($uuid['time_mid']), ($uuid['time_hi']),
			$uuid['clock_seq_hi'], $uuid['clock_seq_low'],
			$uuid['node'][0], $uuid['node'][1], $uuid['node'][2],
			$uuid['node'][3], $uuid['node'][4], $uuid['node'][5]);
	}

	# We cannot generate the timebased UUID!
	return false;
}
function get_mac_address() {
	static $detected_mac = false;

	if ($detected_mac !== false) { // false NOT null!
		return $detected_mac;
	}

	// TODO: This should actually be part of mac_utils.inc.php, but we need it
	//       here, and mac_utils.inc.php shall only be optional. What to do?
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		// Windows
		$cmds = array(
			"ipconfig /all", // faster
			"getmac"
		);
		foreach ($cmds as $cmd) {
			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec == 0) {
				$out = implode("\n",$out);
				$m = array();
				if (preg_match("/([0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2})/ismU", $out, $m)) {
					$detected_mac = strtolower($m[1]);
					return $detected_mac;
				}
			}
		}
	} else if (strtoupper(PHP_OS) == 'DARWIN') {
		// Mac OS X
		$cmds = array(
			"networksetup -listallhardwareports 2>/dev/null",
			"netstat -i 2>/dev/null"
		);
		foreach ($cmds as $cmd) {
			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec == 0) {
				$out = implode("\n",$out);
				$m = array();
				if (preg_match("/([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/ismU", $out, $m)) {
					$detected_mac = $m[1];
					return $detected_mac;
				}
			}
		}
	} else {
		// Linux
		$addresses = @glob('/sys/class/net/'.'*'.'/address');
		foreach ($addresses as $x) {
			if (!strstr($x,'/lo/')) {
				$detected_mac = trim(file_get_contents($x));
				return $detected_mac;
			}
		}
		$cmds = array(
			"netstat -ie 2>/dev/null",
			"ifconfig 2>/dev/null" // only available for root (because it is in sbin)
		);
		foreach ($cmds as $cmd) {
			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec == 0) {
				$out = implode("\n",$out);
				$m = array();
				if (preg_match("/([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/ismU", $out, $m)) {
					$detected_mac = $m[1];
					return $detected_mac;
				}
			}
		}
	}

	$detected_mac = null;
	return $detected_mac;
}

# --------------------------------------
// Variant 1, Version 2 (DCE Security) UUID
# --------------------------------------

define('DCE_DOMAIN_PERSON', 0);
define('DCE_DOMAIN_GROUP', 1);
define('DCE_DOMAIN_ORG', 2);
function gen_uuid_v2($domain, $id) {
	return gen_uuid_dce($domain, $id);
}
function gen_uuid_dce($domain, $id) {
	if (($domain ?? '') === '') throw new Exception("Domain ID missing");
	if (!is_numeric($domain)) throw new Exception("Invalid Domain ID");
	if (($domain < 0) || ($domain > 255)) throw new Exception("Domain ID must be in range 0..255");

	if (($id ?? '') === '') throw new Exception("ID value missing");
	if (!is_numeric($id)) throw new Exception("Invalid ID value");
	if (($id < 0) || ($id > 4294967295)) throw new Exception("ID value must be in range 0..4294967295");

	# Start with a version 1 UUID
	$uuid = gen_uuid_timebased();

	# Add Domain Number
	$uuid = str_pad(dechex($id), 8, '0', STR_PAD_LEFT) . substr($uuid, 8);

	# Add Domain (this overwrites part of the clock sequence)
	$uuid = substr($uuid,0,21) . str_pad(dechex($domain), 2, '0', STR_PAD_LEFT) . substr($uuid, 23);

	# Change version to 2
	$uuid[14] = '2';

	return $uuid;
}

# --------------------------------------
// Variant 1, Version 3 (MD5 name based) UUID
# --------------------------------------

function gen_uuid_v3($namespace_uuid, $name) {
	return gen_uuid_md5_namebased($namespace_uuid, $name);
}
function gen_uuid_md5_namebased($namespace_uuid, $name) {
	if (($namespace_uuid ?? '') === '') throw new Exception("Namespace UUID missing");
	if (!uuid_valid($namespace_uuid)) throw new Exception("Invalid namespace UUID '$namespace_uuid'");

	$namespace_uuid = uuid_canonize($namespace_uuid);
	$namespace_uuid = str_replace('-', '', $namespace_uuid);
	$namespace_uuid = hex2bin($namespace_uuid);

	$hash = md5($namespace_uuid.$name);
	$hash[12] = '3'; // Set version: 3 = MD5
	$hash[16] = dechex(hexdec($hash[16]) & 0x3 | 0x8); // Set variant to "10xx" (RFC4122)

	return substr($hash,  0, 8).'-'.
	       substr($hash,  8, 4).'-'.
	       substr($hash, 12, 4).'-'.
	       substr($hash, 16, 4).'-'.
	       substr($hash, 20, 12);
}

# --------------------------------------
// Variant 1, Version 4 (Random) UUID
# --------------------------------------

function gen_uuid_v4() {
	return gen_uuid_random();
}
function gen_uuid_random() {
	# On Windows: Requires
	#    extension_dir = "C:\php-8.0.3-nts-Win32-vs16-x64\ext"
	#    extension=com_dotnet
	// TODO: can we trust that com_create_guid() always outputs UUIDv4?
	/*
	if (function_exists('com_create_guid')) {
		return strtolower(trim(com_create_guid(), '{}'));
	}
	*/

	# On Debian: apt-get install php-uuid
	# extension_loaded('uuid')
	if (function_exists('uuid_create')) {
		# OSSP uuid extension like seen in php5-uuid at Debian 8
		/*
		$x = uuid_create($context);
		uuid_make($context, UUID_MAKE_V4);
		uuid_export($context, UUID_FMT_STR, $uuid);
		return trim($uuid);
		*/

		# PECL uuid extension like seen in php-uuid at Debian 9
		return trim(uuid_create(UUID_TYPE_RANDOM));
	}

	if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
		# On Debian: apt-get install uuid-runtime
		$out = array();
		$ec = -1;
		exec('uuidgen -r 2>/dev/null', $out, $ec);
		if ($ec == 0) return trim($out[0]);

		# On Debian Jessie: UUID V4 (Random)
		if (file_exists('/proc/sys/kernel/random/uuid')) {
			return trim(file_get_contents('/proc/sys/kernel/random/uuid'));
		}
	}

	# Make the UUID by ourselves
	# Source: http://rogerstringer.com/2013/11/15/generate-uuids-php
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		_random_int( 0, 0xffff ), _random_int( 0, 0xffff ),
		_random_int( 0, 0xffff ),
		_random_int( 0, 0x0fff ) | 0x4000,
		_random_int( 0, 0x3fff ) | 0x8000,
		_random_int( 0, 0xffff ), _random_int( 0, 0xffff ), _random_int( 0, 0xffff )
	);
}

# --------------------------------------
// Variant 1, Version 5 (SHA1 name based) UUID
# --------------------------------------

function gen_uuid_v5($namespace_uuid, $name) {
	return gen_uuid_sha1_namebased($namespace_uuid, $name);
}
function gen_uuid_sha1_namebased($namespace_uuid, $name) {
	if (($namespace_uuid ?? '') === '') throw new Exception("Namespace UUID missing");
	if (!uuid_valid($namespace_uuid)) throw new Exception("Invalid namespace UUID '$namespace_uuid'");

	$namespace_uuid = str_replace('-', '', $namespace_uuid);
	$namespace_uuid = hex2bin($namespace_uuid);

	$hash = sha1($namespace_uuid.$name);
	$hash[12] = '5'; // Set version: 5 = SHA1
	$hash[16] = dechex(hexdec($hash[16]) & 0x3 | 0x8); // Set variant to "0b10__" (RFC4122/DCE1.1)

	return substr($hash,  0, 8).'-'.
	       substr($hash,  8, 4).'-'.
	       substr($hash, 12, 4).'-'.
	       substr($hash, 16, 4).'-'.
	       substr($hash, 20, 12);
}

# --------------------------------------
// Variant 1, Version 6 (Reordered) UUID
# --------------------------------------

function gen_uuid_v6() {
	return gen_uuid_reordered();
}
function gen_uuid_reordered() {
	// Start with a UUIDv1
	$uuid = gen_uuid_timebased();

	// Convert to UUIDv6
	return uuid1_to_uuid6($uuid);
}
function uuid6_to_uuid1($hex) {
	$hex = uuid_canonize($hex);
	if ($hex === false) return false;
	$hex = preg_replace('@[^0-9A-F]@i', '', $hex);
	$hex = substr($hex, 7, 5).
	       substr($hex, 13, 3).
	       substr($hex, 3, 4).
	       '1' . substr($hex, 0, 3).
	       substr($hex, 16);
	return substr($hex,  0, 8).'-'.
	       substr($hex,  8, 4).'-'.
	       substr($hex, 12, 4).'-'.
	       substr($hex, 16, 4).'-'.
	       substr($hex, 20, 12);
}
function uuid1_to_uuid6($hex) {
	$hex = uuid_canonize($hex);
	if ($hex === false) return false;
	$hex = preg_replace('@[^0-9A-F]@i', '', $hex);
	$hex = substr($hex, 13, 3).
	       substr($hex, 8, 4).
	       substr($hex, 0, 5).
	       '6' . substr($hex, 5, 3).
	       substr($hex, 16);
	return substr($hex,  0, 8).'-'.
	       substr($hex,  8, 4).'-'.
	       substr($hex, 12, 4).'-'.
	       substr($hex, 16, 4).'-'.
	       substr($hex, 20, 12);
}

# --------------------------------------
// Variant 1, Version 7 (Unix Epoch) UUID
# --------------------------------------

function gen_uuid_v7() {
	return gen_uuid_unix_epoch();
}
function gen_uuid_unix_epoch() {
	// Start with an UUIDv4
	$uuid = gen_uuid_random();

	// Add the timestamp
	usleep(1); // make sure the timestamp is not repeated
	if (function_exists('gmp_init')) {
		list($ms,$sec) = explode(' ', microtime(false));
		$sec = gmp_init($sec, 10);
		$ms = gmp_init(substr($ms,2,3), 10);
		$unix_ts = gmp_strval(gmp_add(gmp_mul($sec, '1000'), $ms),16);
	} else {
		$unix_ts = dechex((int)round(microtime(true)*1000));
	}
	$unix_ts = str_pad($unix_ts, 12, '0', STR_PAD_LEFT);
	for ($i=0;$i<8;$i++) $uuid[$i] = substr($unix_ts, $i, 1);
	for ($i=0;$i<4;$i++) $uuid[9+$i] = substr($unix_ts, 8+$i, 1);

	// set version
	$uuid[14] = '7';

	return $uuid;
}

# --------------------------------------
// Variant 1, Version 8 (Custom) UUID
# --------------------------------------

function gen_uuid_v8($block1_32bit, $block2_16bit, $block3_12bit, $block4_14bit, $block5_48bit) {
	return gen_uuid_custom($block1_32bit, $block2_16bit, $block3_12bit, $block4_14bit, $block5_48bit);
}
function gen_uuid_custom($block1_32bit, $block2_16bit, $block3_12bit, $block4_14bit, $block5_48bit) {
	if (preg_replace('@[0-9A-F]@i', '', $block1_32bit) != '') throw new Exception("Invalid data for block 1. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block2_16bit) != '') throw new Exception("Invalid data for block 2. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block3_12bit) != '') throw new Exception("Invalid data for block 3. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block4_14bit) != '') throw new Exception("Invalid data for block 4. Must be hex input");
	if (preg_replace('@[0-9A-F]@i', '', $block5_48bit) != '') throw new Exception("Invalid data for block 5. Must be hex input");

	$block1 = str_pad(substr($block1_32bit, -8),  8, '0', STR_PAD_LEFT);
	$block2 = str_pad(substr($block2_16bit, -4),  4, '0', STR_PAD_LEFT);
	$block3 = str_pad(substr($block3_12bit, -4),  4, '0', STR_PAD_LEFT);
	$block4 = str_pad(substr($block4_14bit, -4),  4, '0', STR_PAD_LEFT);
	$block5 = str_pad(substr($block5_48bit,-12), 12, '0', STR_PAD_LEFT);

	$block3[0] = '8'; // Version 8 = Custom
	$block4[0] = dechex((hexdec($block4[0])&3) + 0b1000); // Variant 0b10__ = RFC4122

	return strtolower($block1.'-'.$block2.'-'.$block3.'-'.$block4.'-'.$block5);
}

# --------------------------------------

// http://php.net/manual/de/function.hex2bin.php#113057
if ( !function_exists( 'hex2bin' ) ) {
    function hex2bin( $str ) {
        $sbin = "";
        $len = strlen( $str );
        for ( $i = 0; $i < $len; $i += 2 ) {
            $sbin .= pack( "H*", substr( $str, $i, 2 ) );
        }

        return $sbin;
    }
}
