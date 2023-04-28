<?php

/*
 * MAC utils for PHP
 * Copyright 2017 - 2023 Daniel Marschall, ViaThinkSoft
 * Version 2023-04-29
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

const IEEE_MAC_REGISTRY = __DIR__ . '/../web-data';

/**
 * @param string $mac
 * @return bool
 */
function mac_valid(string $mac): bool {
	$mac = str_replace(array('-', ':'), '', $mac);
	$mac = strtoupper($mac);

	if (strlen($mac) != 12) return false;

	$mac = preg_replace('@[0-9A-F]@', '', $mac);

	return ($mac == '');
}

/**
 * @param string $file
 * @param string $oui_name
 * @param string $mac
 * @return false|string
 */
function _lookup_ieee_registry(string $file, string $oui_name, string $mac) {
	$begin = substr($mac, 0, 2).'-'.substr($mac, 2, 2).'-'.substr($mac, 4, 2);
	$f = file_get_contents($file);

	$f = str_replace("\r", '', $f);

	# We are using a positive-lookahead because entries like the MA-M references have a blank line between organization and address
	preg_match_all('@^\s*'.preg_quote($begin, '@').'\s+\(hex\)\s+(\S+)\s+(.*)\n\n\s*(?=[0-9A-F])@ismU', "$f\n\nA", $m, PREG_SET_ORDER);
	foreach ($m as $n) {
		preg_match('@(\S+)\s+\(base 16\)(.*)$@ism', $n[2], $m);

		if (preg_match('@(.+)-(.+)@ism', $m[1], $o)) {
			$z = hexdec(substr($mac, 6, 6));
			$beg = hexdec($o[1]);
			$end = hexdec($o[2]);
			if (($z < $beg) || ($z > $end)) continue;
		} else {
			$beg = 0x000000;
			$end = 0xFFFFFF;
		}

		$x = trim(preg_replace('@^\s+@im', '', $m[2]));

		# "PRIVATE" entries are only marked at the "(hex)" line, but not at the "(base16)" line
		if ($x == '') $x = trim($n[1]);

		$x = explode("\n", $x);

		$ra_len = strlen(dechex($end-$beg));

		$out = sprintf("%-32s 0x%s\n", "IEEE $oui_name part:", substr($mac, 0, 12-$ra_len));
		$out .= sprintf("%-32s 0x%s\n", "NIC specific part:", substr($mac, 12-$ra_len));
		$out .= sprintf("%-32s %s\n", "Registrant:", $x[0]);
		foreach ($x as $n => $y) {
			if ($n == 0) continue;
			else if ($n == 1) $out .= sprintf("%-32s %s\n", "Address of registrant:", $y);
			else if ($n >= 2) $out .= sprintf("%-32s %s\n", "", $y);
		}

		// TODO: also print the date of last update of the OUI files

		return $out;
	}

	return false;
}

/**
 * @param string $mac
 * @return void
 * @throws Exception
 */
function decode_mac(string $mac) {
	// Amazing website about MAC addresses: https://mac-address.alldatafeeds.com/faq#how-to-recognise-mac-address-application

	if (!mac_valid($mac)) throw new Exception("Invalid MAC address");

	// Format MAC
	$mac = strtoupper($mac);
	$mac = preg_replace('@[^0-9A-F]@', '', $mac);
	if (strlen($mac) != 12) {
		throw new Exception("Invalid MAC address");
	}
	$mac_ = preg_replace('@^(..)(..)(..)(..)(..)(..)$@', '\\1-\\2-\\3-\\4-\\5-\\6', $mac);
	echo sprintf("%-32s %s\n", "MAC address:", $mac_);

	// Empfaengergruppe
	$ig = hexdec($mac[1]) & 1; // Bit #LSB+0 of Byte 1
	$ig_ = ($ig == 0) ? '[0] Individual (Unicast)' : '[1] Group (Multicast)';
	echo sprintf("%-32s %s\n", "Transmission type (I/G flag):", $ig_);

	// Vergabestelle
	$ul = hexdec($mac[1]) & 2; // Bit #LSB+1 of Byte 1
	$ul_ = ($ul == 0) ? '[0] Universally Administered Address (UAA)' : '[1] Locally Administered Address (LAA)';
	echo sprintf("%-32s %s\n", "Administration type (U/L flag):", $ul_);

	// Query IEEE registries
	// TODO: gilt OUI nur bei Individual UAA?
	if (count(glob(IEEE_MAC_REGISTRY.'/*.txt')) > 0) {
		if (
			($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . '/mam.txt', 'OUI-28 (MA-M)', $mac)) ||
			($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . '/oui36.txt', 'OUI-36 (MA-S)', $mac)) ||
			# The IEEE Registration Authority distinguishes between IABs and OUI-36 values. Both are 36-bit values which may be used to generate EUI-48 values, but IABs may not be used to generate EUI-64 values.[6]
			# Note: The Individual Address Block (IAB) is an inactive registry activity, which has been replaced by the MA-S registry product as of January 1, 2014.
			($x = _lookup_ieee_registry(IEEE_MAC_REGISTRY . '/iab.txt', 'IAB', $mac))
		) {
			echo $x;
		} else {
			echo _lookup_ieee_registry(IEEE_MAC_REGISTRY . '/oui.txt', 'OUI-24 (MA-L)', $mac);
		}
	}

	$vm = '';
	// === FAQ "Detection rules which don't have their dedicated page yet" ===
	// https://wiki.xenproject.org/wiki/Xen_Networking
	// https://mcpmag.com/articles/2007/11/27/hey-vm-whats-your-hypervisor.aspx
	// https://www.techrepublic.com/blog/data-center/mac-address-scorecard-for-common-virtual-machine-platforms
	if (mac_between($mac, '00:16:3E:00:00:00', '00:16:3E:FF:FF:FF')) $vm = "Red Hat Xen, XenSource, Novell Xen";
	// http://techgenix.com/mac-address-pool-duplication-hyper-v/
	// https://docs.microsoft.com/en-us/system-center/vmm/network-mac?view=sc-vmm-1807
	// https://blogs.technet.microsoft.com/gbanin/2014/08/27/how-to-solve-mac-address-conflict-on-hyper-v/
	if (mac_between($mac, '00:1D:D8:B7:1C:00', '00:1D:D8:F4:1F:FF')) $vm = "Microsoft SCVMM (System Center Virtual Machine Manager)";
	// https://mcpmag.com/articles/2007/11/27/hey-vm-whats-your-hypervisor.aspx
	// https://www.techrepublic.com/blog/data-center/mac-address-scorecard-for-common-virtual-machine-platforms/
	// https://blogs.technet.microsoft.com/medv/2011/01/24/how-to-manage-vm-mac-addresses-with-the-globalimagedata-xml-file-in-med-v-v1/
	if (mac_between($mac, '00:03:FF:00:00:00', '00:03:FF:FF:FF:FF')) $vm = "Microsoft Virtual PC / Virtual Server";
	// https://mcpmag.com/articles/2007/11/27/hey-vm-whats-your-hypervisor.aspx
	if (mac_between($mac, '00:18:51:00:00:00', '00:18:51:FF:FF:FF')) $vm = "SWsoft";
	// https://macaddress.io/statistics/company/17619
	if (mac_between($mac, '58:9C:FC:00:00:00', '58:9C:FC:FF:FF:FF')) $vm = "bhyve by FreebsdF";
	// https://macaddress.io/statistics/company/17388
	if (mac_between($mac, '50:6B:8D:00:00:00', '50:6B:8D:FF:FF:FF')) $vm = "Nutanix AHV";
	// https://www.centos.org/forums/viewtopic.php?t=26739
	if (mac_between($mac, '54:52:00:00:00:00', '54:52:FF:FF:FF:FF')) $vm = "KVM (proxmox)";
	// Self tested (alldatafeeds.com)
	if (mac_between($mac, '96:00:00:00:00:00', '96:00:FF:FF:FF:FF')) $vm = "Hetzner vServer (based on KVM and libvirt)";
	// === FAQ "How to recognise a VMware's virtual machine by its MAC address?" ===
	if (mac_between($mac, '00:50:56:00:00:00', '00:50:56:FF:FF:FF')) $vm = "VMware vSphere, VMware Workstation, VMware ESX Server";
	if (mac_between($mac, '00:50:56:80:00:00', '00:50:56:BF:FF:FF')) $vm = "VMware vSphere managed by vCenter Server";
	if (mac_between($mac, '00:0C:29:00:00:00', '00:0C:29:FF:FF:FF')) $vm = "VMWare Standalone VMware vSphere, VMware Workstation, VMware Horizon";
	if (mac_between($mac, '00:05:69:00:00:00', '00:05:69:FF:FF:FF')) $vm = "VMware ESX, VMware GSX Server";
	if (mac_between($mac, '00:1C:14:00:00:00', '00:1C:14:FF:FF:FF')) $vm = "VMWare";
	// === FAQ "machine by its MAC address?" ===
	if (mac_between($mac, '00:1C:42:00:00:00', '00:1C:42:FF:FF:FF')) $vm = "Parallels Virtual Machine";
	// === FAQ "How to recognise a Docker container by its MAC address?" ===
	if (mac_between($mac, '02:42:00:00:00:00', '02:42:FF:FF:FF:FF')) $vm = "Docker container";
	// === FAQ =How to recognise a Microsoft Hyper-V's virtual machine by its MAC address?" ===
	if (mac_between($mac, '00:15:5D:00:00:00', '00:15:5D:FF:FF:FF')) $vm = "Microsoft Hyper-V";
	// === FAQ "How to recognise an Oracle Virtual machine by its MAC address?" ===
	if (mac_between($mac, '08:00:27:00:00:00', '08:00:27:FF:FF:FF')) $vm = "Oracle VirtualBox 5.2"; // Pcs Systemtechnik GmbH
	if (mac_between($mac, '52:54:00:00:00:00', '52:54:00:FF:FF:FF')) $vm = "Oracle VirtualBox 5.2 + Vagrant"; // 52:54:00 (Exact MAC: 52:54:00:C9:C7:04)
	if (mac_between($mac, '00:21:F6:00:00:00', '00:21:F6:FF:FF:FF')) $vm = "Oracle VirtualBox 3.3";
	if (mac_between($mac, '00:14:4F:00:00:00', '00:14:4F:FF:FF:FF')) $vm = "Oracle VM Server for SPARC";
	if (mac_between($mac, '00:0F:4B:00:00:00', '00:0F:4B:FF:FF:FF')) $vm = "Oracle Virtual Iron 4";

	if ($vm) {
		echo sprintf("%-32s %s\n", "Special use:", "Virtual machine $vm");
	}

	$app = '';

	// === FAQ "Other MAC address applications"
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	// https://tools.ietf.org/html/rfc1060
	if (mac_between($mac, '03:00:00:01:00:00', '03:00:40:00:00:00')) $app = 'User-defined (per 802 spec), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:1D:00:00:00')) $app = 'Cabletron PC-OV PC discover (on demand), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:1D:42:00:00')) $app = 'Cabletron PC-OV Bridge discover (on demand), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:1D:52:00:00')) $app = 'Cabletron PC-OV MMAC discover (on demand), EtherType is 0x0802';
	if (mac_between($mac, '01:00:3C:00:00:00' , '01:00:3C:FF:FF:FF')) $app = 'Auspex Systems (Serverguard)';
	if (mac_equals($mac, '01:00:10:00:00:20')) $app = 'Hughes Lan Systems Terminal Server S/W download, EtherType is 0x0802';
	if (mac_equals($mac, '01:00:10:FF:FF:20')) $app = 'Hughes Lan Systems Terminal Server S/W request, EtherType is 0x0802';
	if (mac_equals($mac, '01:00:81:00:00:00')) $app = 'Synoptics Network Management';
	if (mac_equals($mac, '01:00:81:00:00:02')) $app = 'Synoptics Network Management';
	if (mac_equals($mac, '01:00:81:00:01:00')) $app = 'Bay Networks (Synoptics) autodiscovery, EtherType is 0x0802 SNAP type is 0x01A2';
	if (mac_equals($mac, '01:00:81:00:01:01')) $app = 'Bay Networks (Synoptics) autodiscovery, EtherType is 0x0802 SNAP type is 0x01A1';
	if (mac_between($mac, '01:20:25:00:00:00', '01:20:25:7F:FF:FF')) $app = 'Control Technology Inc\'s Industrial Ctrl Proto., EtherType is 0x873A';
	if (mac_equals($mac, '01:80:24:00:00:00')) $app = 'Kalpana Etherswitch every 60 seconds, EtherType is 0x0802';
	if (mac_equals($mac, '01:DD:00:FF:FF:FF')) $app = 'Ungermann-Bass boot-me requests, EtherType is 0x7002';
	if (mac_equals($mac, '01:DD:01:00:00:00')) $app = 'Ungermann-Bass Spanning Tree, EtherType is 0x7005';
	if (mac_equals($mac, '03:00:00:00:00:10')) $app = 'OS/2 1.3 EE + Communications Manager, EtherType is 0x80D5';
	if (mac_equals($mac, '03:00:00:00:00:40')) $app = 'OS/2 1.3 EE + Communications Manager, EtherType is 0x80D5';
	if (mac_equals($mac, '03:00:00:00:01:00')) $app = 'OSI All-IS Multicast, EtherType is 0x0802';
	if (mac_equals($mac, '03:00:00:00:02:00')) $app = 'OSI All-ES Multicast, EtherType is 0x0802';
	if (mac_equals($mac, '03:00:00:80:00:00')) $app = 'Discovery Client, EtherType is 0x0802';
	if (mac_equals($mac, '03:00:FF:FF:FF:FF')) $app = 'All Stations address, EtherType is 0x0802';
	if (mac_between($mac, '09:00:0D:00:00:00', '09:00:0D:FF:FF:FF')) $app = 'ICL Oslan Multicast, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:0D:02:00:00')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:0A:3C')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:0A:38')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:0A:39')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:02:FF:FF')) $app = 'ICL Oslan Service discover only on boot';
	if (mac_equals($mac, '09:00:0D:09:00:00')) $app = 'ICL Oslan Service discover as required';
	if (mac_equals($mac, '09:00:1E:00:00:00')) $app = 'Apollo DOMAIN, EtherType is 0x8019';
	if (mac_equals($mac, '09:00:02:04:00:01')) $app = 'Vitalink printer messages, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:02:04:00:02')) $app = 'Vitalink bridge management, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:4C:00:00:0F')) $app = 'BICC Remote bridge adaptive routing (e.g. to Retix), EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4E:00:00:02')) $app = 'Novell IPX, EtherType is 0x8137';
	if (mac_equals($mac, '09:00:6A:00:01:00')) $app = 'TOP NetBIOS';
	if (mac_equals($mac, '09:00:7C:01:00:01')) $app = 'Vitalink DLS Multicast';
	if (mac_equals($mac, '09:00:7C:01:00:03')) $app = 'Vitalink DLS Inlink';
	if (mac_equals($mac, '09:00:7C:01:00:04')) $app = 'Vitalink DLS and non DLS Multicast';
	if (mac_equals($mac, '09:00:7C:02:00:05')) $app = 'Vitalink diagnostics, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:7C:05:00:01')) $app = 'Vitalink gateway, EtherType is 0x8080';
	if (mac_equals($mac, '09:00:7C:05:00:02')) $app = 'Vitalink Network Validation Message';
	if (mac_equals($mac, '09:00:09:00:00:01')) $app = 'HP Probe, EtherType is 0x8005 or 0x0802';
	if (mac_equals($mac, '09:00:09:00:00:04')) $app = 'HP DTC, EtherType is 0x8005';
	if (mac_equals($mac, '09:00:26:01:00:01')) $app = 'Vitalink TransLAN bridge management, EtherType is 0x8038';
	if (mac_equals($mac, '09:00:39:00:70:00')) $app = 'Spider Systems Bridge';
	if (mac_between($mac, '09:00:56:00:00:00', '09:00:56:FE:FF:FF')) $app = 'Stanford reserved';
	if (mac_between($mac, '09:00:56:FF:00:00', '09:00:56:FF:FF:FF')) $app = 'Stanford V Kernel, version 6.0, EtherType is 0x805C';
	if (mac_equals($mac, '09:00:77:00:00:00')) $app = 'Retix Bridge Local Management System, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:77:00:00:01')) $app = 'Retix spanning tree bridges, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:77:00:00:02')) $app = 'Retix Bridge Adaptive routing, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:87:80:FF:FF')) $app = 'Xyplex Terminal Servers, EtherType is 0x0889';
	if (mac_equals($mac, '09:00:87:90:FF:FF')) $app = 'Xyplex Terminal Servers, EtherType is 0x0889';
	if (mac_between($mac, '44:38:39:FF:00:00', '44:38:39:FF:FF:FF')) $app = 'Multi-Chassis Link Aggregation (Cumulus Linux)';
	if (mac_equals($mac, 'FF:FF:00:40:00:01')) $app = 'LANtastic, EtherType is 0x81D6';
	if (mac_equals($mac, 'FF:FF:00:60:00:04')) $app = 'LANtastic, EtherType is 0x81D6';
	if (mac_equals($mac, 'FF:FF:01:E0:00:04')) $app = 'LANtastic';

	// === FAQ "The "CF" series MAC addresses" ===
	// https://www.iana.org/assignments/ppp-numbers/ppp-numbers.xhtml
	// https://tools.ietf.org/html/rfc2153
	// https://tools.ietf.org/html/rfc7042#section-2.3.2
	if (mac_between($mac, 'CF:00:00:00:00:00', 'CF:00:00:FF:FF:FF')) $app = 'Reserved';
	if (mac_equals($mac, 'CF:00:00:00:00:00')) $app = 'Used for Ethernet loopback tests';

	// === FAQ "How to recognise a Broadcast MAC address application?" ===
	if (mac_equals($mac, 'FF:FF:FF:FF:FF:FF')) echo sprintf("%-32s %s\n", "Special use:", "Broadcast messaging");

	// === FAQ "How to recognise a Virtual Router ID by MAC address?" ===
	// https://tools.ietf.org/html/rfc7042#section-5.1
	// https://tools.ietf.org/html/rfc5798
	if (mac_between($mac, '00:00:5E:00:01:00', '00:00:5E:00:01:FF')) $app = 'IPv4 Virtual Router Redundancy Protocol  (VRRP)';
	if (mac_between($mac, '00:00:5E:00:02:00', '00:00:5E:00:02:FF')) $app = 'IPv6 Virtual Router Redundancy Protocol';

	// === FAQ "How to recognise an IP frame by MAC address?" ===
	// https://tools.ietf.org/html/rfc1060
	// https://en.wikipedia.org/wiki/Multicast_address#cite_note-15
	// https://tools.ietf.org/html/rfc2464
	// https://www.iana.org/go/rfc1112
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_between($mac, '01:00:5E:00:00:00', '01:00:5E:7F:FF:FF')) $app = 'IPv4 Multicast (EtherType is 0x0800)';
	if (mac_between($mac, '33:33:00:00:00:00', '33:33:FF:FF:FF:FF')) $app = 'IPv6 Multicast. IPv6 neighbor discovery (EtherType is 0x86DD)'; // TODO: Dabei werden die untersten 32 Bit der IPv6-Multicast-Adresse in die MAC-Adresse eingebettet.
	if (mac_between($mac, '00:00:5E:00:52:13', '00:00:5E:00:52:13')) $app = 'Proxy Mobile IPv6';
	//if (mac_between($mac, '00:00:5E:FE:C0:00:02:00', '00:00:5E:FE:C0:00:02:FF')) $app = 'IPv4 derived documentation';
	//if (mac_between($mac, '00:00:5E:FE:C6:33:64:00', '00:00:5E:FE:C6:33:64:FF')) $app = 'IPv4 derived documentation';
	//if (mac_between($mac, '00:00:5E:FE:CB:00:71:00', '00:00:5E:FE:CB:00:71:FF')) $app = 'IPv4 derived documentation';
	//if (mac_equals($mac, '00:00:5E:FE:EA:C0:00:02')) $app = 'IPv4 multicast derived documentation';
	//if (mac_equals($mac, '00:00:5E:FE:EA:C6:33:64')) $app = 'IPv4 multicast derived documentation';
	//if (mac_equals($mac, '00:00:5E:FE:EA:CB:00:71')) $app = 'IPv4 multicast derived documentation';
	//if (mac_between($mac, '01:00:5E:FE:C0:00:02:00', '01:00:5E:FE:C0:00:02:FF')) $app = 'IPv4 derived documentation';
	//if (mac_between($mac, '01:00:5E:FE:C6:33:64:00', '01:00:5E:FE:C6:33:64:FF')) $app = 'IPv4 derived documentation';
	//if (mac_between($mac, '01:00:5E:FE:CB:00:71:00', '01:00:5E:FE:CB:00:71:FF')) $app = 'IPv4 derived documentation';
	//if (mac_equals($mac, '01:00:5E:FE:EA:C0:00:02')) $app = 'IPv4 multicast derived documentation';
	//if (mac_equals($mac, '01:00:5E:FE:EA:C6:33:64')) $app = 'IPv4 multicast derived documentation';
	//if (mac_equals($mac, '01:00:5E:FE:EA:CB:00:71')) $app = 'IPv4 multicast derived documentation';
	if (mac_between($mac, '01:80:C2:00:00:20', '01:80:C2:00:00:2F')) $app = 'Reserved for use by Multiple Registration Protocol (MRP) applications';
	//if (mac_between($mac, '02:00:5E:FE:00:00:00:00', '02:00:5E:FE:FF:FF:FF:FF')) $app = 'IPv4 Addr Holders';
	if (mac_equals($mac, '03:00:00:20:00:00')) $app = 'IP multicast address';
	if (mac_equals($mac, 'C0:00:00:04:00:00')) $app = 'IP multicast address';
	//if (mac_between($mac, '03:00:5E:FE:00:00:00:00', '03:00:5E:FE:FF:FF:FF:FF')) $app = 'IPv4 Addr Holders';

	// === FAQ "How to recognise a MPLS multicast frame by MAC address?" ===
	// http://www.iana.org/go/rfc5332
	// http://www.iana.org/go/rfc7213
	if (mac_between($mac, '01:00:5E:80:00:00', '01:00:5E:8F:FF:FF')) $app = 'MPLS multicast (EtherType is 0x8847 or 0x8848)';
	if (mac_equals($mac, '01:00:5E:90:00:00')) $app = 'MPLS-TP p2p';

	// === FAQ "How to recognise a Bidirectional Forwarding Detection (BFD) on Link Aggregation Group (LAG) interfaces by MAC address?" ===
	// http://www.iana.org/go/rfc7130
	if (mac_equals($mac, '01:00:5E:90:00:01')) $app = 'Bidirectional Forwarding Detection (BFD) on Link Aggregation Group (LAG) interfaces';

	// === FAQ "How to recognise Token Ring specific functions by MAC address?" ===
	// https://tools.ietf.org/html/rfc1060
	// https://tools.ietf.org/html/rfc1469
	// https://standards.ieee.org/products-services/regauth/grpmac/public.html
	// https://tools.ietf.org/html/rfc2470
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_equals($mac, '03:00:00:00:00:01')) $app = 'NetBIOS (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:02')) $app = 'Locate - Directory Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:04')) $app = 'Synchronous Bandwidth Manager (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:08')) $app = 'Configuration Report Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:10')) $app = 'Ring Error Monitor (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:20')) $app = 'Network Server Heartbeat (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:40')) $app = 'Ring Parameter Monitor (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:00:80')) $app = 'Active Monitor (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:04:00')) $app = 'LAN Manager (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:08:00')) $app = 'Ring Wiring Concentrator (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:10:00')) $app = 'LAN Gateway (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:20:00')) $app = 'Ring Authorization Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:40:00')) $app = 'IMPL Server (Token Ring)';
	if (mac_equals($mac, '03:00:00:00:80:00')) $app = 'Bridge (Token Ring)';
	if (mac_equals($mac, '03:00:00:20:00:00')) $app = 'Single Token-Ring functional address';
	if (mac_equals($mac, '03:00:00:00:00:08')) $app = 'Configuration Report Server (CRS) MAC Group address';
	if (mac_equals($mac, '03:00:00:00:00:10')) $app = 'Ring Error Monitor (REM) MAC Group address';
	if (mac_equals($mac, '03:00:00:00:00:40')) $app = 'Ring Parameter Server (RPS) MAC group address';
	if (mac_equals($mac, '03:00:00:00:01:00')) $app = 'All Intermediate System Network Entities address';
	if (mac_equals($mac, '03:00:00:00:02:00')) $app = 'All End System Network Entities address, and Lobe Media Test (LMT) MAC group address';
	if (mac_equals($mac, '03:00:00:00:04:00')) $app = 'Generic address for all Manager Stations';
	if (mac_equals($mac, '03:00:00:00:08:00')) $app = 'All CONs SNARES address';
	if (mac_equals($mac, '03:00:00:00:10:00')) $app = 'All CONs End System address';
	if (mac_equals($mac, '03:00:00:00:20:00')) $app = 'Loadable Device Generic address';
	if (mac_equals($mac, '03:00:00:00:40:00')) $app = 'Load Server Generic address';
	if (mac_equals($mac, '03:00:00:40:00:00')) $app = 'Generic address for all Agent Stations';
	if (mac_equals($mac, 'C0:00:00:04:00:00')) $app = 'Single Token-Ring functional address';
	if (mac_equals($mac, '03:00:80:00:00:00')) $app = 'IPv6 multicast over Token Ring: all-Nodes (FF01::1 and FF02::1) and solicited node (FF02:0:0:0:0:1:FFXX:XXXX) addresses';
	if (mac_equals($mac, '03:00:40:00:00:00')) $app = 'IPv6 multicast over Token Ring: all-Routers addresses (FF0X::2)';
	if (mac_equals($mac, '03:00:00:80:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 000';
	if (mac_equals($mac, '03:00:00:40:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 001';
	if (mac_equals($mac, '03:00:00:20:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 010';
	if (mac_equals($mac, '03:00:00:10:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 011';
	if (mac_equals($mac, '03:00:00:08:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 100';
	if (mac_equals($mac, '03:00:00:04:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 101';
	if (mac_equals($mac, '03:00:00:02:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 110';
	if (mac_equals($mac, '03:00:00:01:00:00')) $app = 'IPv6 multicast over Token Ring: any other multicast address with three least significant bits = 111';

	// === FAQ "How to recognise an AppleTalk protocols by MAC address?" ===
	// https://tools.ietf.org/html/rfc1060
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_between($mac, '09:00:07:00:00:00', '09:00:07:00:00:FC')) $app = 'AppleTalk zone multicast addresses (EtherType is 0x0802)';
	if (mac_equals($mac, '09:00:07:FF:FF:FF')) $app = 'AppleTalk broadcast address (EtherType is 0x0802)';

	// === FAQ "How to recognise a TRILL protocols by MAC address?" ===
	// http://www.iana.org/go/rfc7455
	// https://tools.ietf.org/html/draft-ietf-trill-oam-framework-04
	// https://standards.ieee.org/products-services/regauth/grpmac/public.html
	// https://tools.ietf.org/html/rfc7455#appendix-C
	if (mac_between($mac, '00:00:5E:90:01:00', '00:00:5E:90:01:00')) $app = 'TRILL OAM';
	if (mac_equals($mac, '01:00:5E:90:01:00')) $app = 'TRILL OAM';
	if (mac_between($mac, '01:80:C2:00:00:40', '01:80:C2:00:00:4F')) $app = 'Group MAC addresses used by the TRILL protocols';

	// === FAQ "How to recognise an IEEE 802.1X MAC address application?" ===
	if (mac_between($mac, '01:0C:CD:01:00:00', '01:0C:CD:01:01:FF')) $app = 'IEC 61850-8-1 GOOSE Type 1/1A, EtherType is 0x88B8';
	if (mac_between($mac, '01:0C:CD:02:00:00', '01:0C:CD:02:01:FF')) $app = 'GSSE (IEC 61850 8-1), EtherType is 0x88B9';
	if (mac_between($mac, '01:0C:CD:04:00:00', '01:0C:CD:04:01:FF')) $app = 'Multicast sampled values (IEC 61850 8-1), EtherType is 0x88BA';
	if (mac_equals($mac, '01:1B:19:00:00:00')) $app = 'General group address - An 802.1Q VLAN Bridge would forward the frame unchanged.';
	if (mac_equals($mac, '01:1B:19:00:00:00')) $app = 'Precision Time Protocol (PTP) version 2 over Ethernet, EtherType is 0x88F7';
	if (mac_equals($mac, '01:80:C2:00:00:00')) $app = 'Bridge Group address Nearest Customer Bridge group address';
	if (mac_equals($mac, '01:80:C2:00:00:00')) $app = 'Spanning Tree Protocol (for bridges) IEEE 802.1D, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:00')) $app = 'Link Layer Discovery Protocol, EtherType is 0x88CC';
	if (mac_between($mac, '01:80:C2:00:00:00', '01:80:C2:00:00:0F')) $app = 'The initial bridging/link protocols block';
	if (mac_between($mac, '01:80:C2:00:00:00', '01:80:C2:00:00:0F')) $app = 'IEEE 802.1D MAC Bridge Filtered MAC Group Addresses';
	if (mac_between($mac, '01:80:C2:00:00:00', '01:80:C2:00:00:0F')) $app = 'IEEE Pause, 802.3x';
	if (mac_equals($mac, '01:80:C2:00:00:0A')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:0B')) $app = 'EDE-SS PEP Address';
	if (mac_equals($mac, '01:80:C2:00:00:0C')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:0D')) $app = 'Provider Bridge MVRP address';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Individual LAN Scope group address, It is intended that no IEEE 802.1 relay device will be defined that will forward frames that carry this destination address';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Nearest Bridge group address';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Link Layer Discovery Protocol, EtherType is 0x88CC';
	if (mac_equals($mac, '01:80:C2:00:00:0E')) $app = 'Precision Time Protocol (PTP) version 2 over Ethernet, EtherType is 0x88F7';
	if (mac_equals($mac, '01:80:C2:00:00:01')) $app = 'IEEE MAC-specific Control Protocols group address';
	if (mac_equals($mac, '01:80:C2:00:00:01')) $app = 'Ethernet flow control (Pause frame) IEEE 802.3x, EtherType is 0x8808';
	if (mac_equals($mac, '01:80:C2:00:00:1A')) $app = 'Generic Address for All Agent Stations';
	if (mac_equals($mac, '01:80:C2:00:00:1B')) $app = 'All Multicast Capable End Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:1C')) $app = 'All Multicast Announcements address';
	if (mac_equals($mac, '01:80:C2:00:00:1D')) $app = 'All Multicast Capable Intermediate Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:1E')) $app = 'All DTR Concentrators MAC group address';
	if (mac_equals($mac, '01:80:C2:00:00:1F')) $app = 'EDE-CC PEP Address';
	if (mac_between($mac, '01:80:C2:00:00:01', '01:80:C2:00:00:0F')) $app = '802.1 alternate Spanning multicast, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:02')) $app = 'Ethernet OAM Protocol IEEE 802.3ah (also known as "slow protocols"), EtherType is 0x8809';
	if (mac_equals($mac, '01:80:C2:00:00:03')) $app = 'Nearest non-TPMR Bridge group address IEEE Std 802.1X PAE address';
	if (mac_equals($mac, '01:80:C2:00:00:03')) $app = 'Link Layer Discovery Protocol, EtherType is 0x88CC';
	if (mac_equals($mac, '01:80:C2:00:00:04')) $app = 'IEEE MAC-specific Control Protocols group address';
	if (mac_equals($mac, '01:80:C2:00:00:05')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:06')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:07')) $app = 'MEF Forum ELMI protocol group address';
	if (mac_equals($mac, '01:80:C2:00:00:08')) $app = 'Provider Bridge group address';
	if (mac_equals($mac, '01:80:C2:00:00:08')) $app = 'Spanning Tree Protocol (for provider bridges) IEEE 802.1ad, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:09')) $app = 'Reserved for future standardization';
	if (mac_equals($mac, '01:80:C2:00:00:10')) $app = 'All LANs Bridge Management group address (deprecated)';
	if (mac_equals($mac, '01:80:C2:00:00:10')) $app = 'Bridge Management, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:11')) $app = 'Load Server generic address';
	if (mac_equals($mac, '01:80:C2:00:00:11')) $app = 'Load Server, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:12')) $app = 'Loadable Device generic address';
	if (mac_equals($mac, '01:80:C2:00:00:12')) $app = 'Loadable Device, EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:13')) $app = 'Transmission of IEEE 1905.1 control packets';
	if (mac_equals($mac, '01:80:C2:00:00:14')) $app = 'All Level 1 Intermediate Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:14')) $app = 'OSI Route level 1 (within area), EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:15')) $app = 'All Level 2 Intermediate Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:15')) $app = 'OSI Route level 2 (between area), EtherType is 0x0802';
	if (mac_equals($mac, '01:80:C2:00:00:16')) $app = 'All CONS End Systems address';
	if (mac_equals($mac, '01:80:C2:00:00:17')) $app = 'All CONS SNARES address';
	if (mac_equals($mac, '01:80:C2:00:00:18')) $app = 'Generic address for All Manager Stations';
	if (mac_equals($mac, '01:80:C2:00:00:19')) $app = 'Groupcast with retries (GCR) MAC group address';
	if (mac_between($mac, '01:80:C2:00:00:20', '01:80:C2:00:00:2F')) $app = 'Reserved for use by Multiple Registration Protocol (MRP) applications';
	if (mac_equals($mac, '01:80:C2:00:00:21')) $app = 'GARP VLAN Registration Protocol (also known as IEEE 802.1q GVRP), EtherType is 0x88f5';
	if (mac_between($mac, '01:80:C2:00:00:30', '01:80:C2:00:00:3F')) $app = 'Destination group MAC addresses for CCM and Linktrace messages';
	if (mac_between($mac, '01:80:C2:00:00:30', '01:80:C2:00:00:3F')) $app = 'Ethernet CFM Protocol IEEE 802.1ag, EtherType is 0x8902';
	if (mac_between($mac, '01:80:C2:00:00:50', '01:80:C2:00:00:FF')) $app = 'Unassigned standard group MAC address';
	if (mac_equals($mac, '01:80:C2:00:01:00')) $app = 'Ring Management Directed Beacon multicast address';
	if (mac_equals($mac, '01:80:C2:00:01:00')) $app = 'FDDI RMT Directed Beacon, EtherType is 0x0802';
	if (mac_between($mac, '01:80:C2:00:01:01', '01:80:C2:00:01:0F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01:80:C2:00:01:10')) $app = 'Status Report Frame Status Report Protocol multicast address';
	if (mac_equals($mac, '01:80:C2:00:01:10')) $app = 'FDDI status report frame, EtherType is 0x0802';
	if (mac_between($mac, '01:80:C2:00:01:11', '01:80:C2:00:01:1F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01:80:C2:00:01:20')) $app = 'All FDDI Concentrator MACs';
	if (mac_between($mac, '01:80:C2:00:01:21', '01:80:C2:00:01:2F')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_equals($mac, '01:80:C2:00:01:30')) $app = 'Synchronous Bandwidth Allocation address';
	if (mac_between($mac, '01:80:C2:00:01:31', '01:80:C2:00:01:FF')) $app = 'Assigned to ISO/IEC JTC1/SC25 for future use';
	if (mac_between($mac, '01:80:C2:00:02:00', '01:80:C2:00:02:FF')) $app = 'Assigned to ETSI for future use';
	if (mac_between($mac, '01:80:C2:00:03:00', '01:80:C2:FF-FF-FF')) $app = 'Unassigned standard group MAC address';
	if (mac_equals($mac, '09:00:4C:00:00:00')) $app = 'BICC 802.1 management, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4C:00:00:0C')) $app = 'BICC Remote bridge STA 802.1(D) Rev8, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4C:00:00:02')) $app = 'BICC 802.1 management, EtherType is 0x0802';
	if (mac_equals($mac, '09:00:4C:00:00:06')) $app = 'BICC Local bridge STA 802.1(D) Rev6, EtherType is 0x0802';
	if (mac_between($mac, '33:33:00:00:00:00', '33:33:FF:FF:FF:FF')) $app = 'IPv6 multicast, EtherType is 0x86DD';

	// === FAQ "How to recognise an ISO 9542 ES-IS protocol's MAC address application?" ===
	// https://standards.ieee.org/products-services/regauth/grpmac/public.html
	if (mac_equals($mac, '09:00:2B:00:00:04')) $app = 'All End System Network Entities address';
	if (mac_equals($mac, '09:00:2B:00:00:05')) $app = 'All Intermediate System Network Entities address';

	// === FAQ "How to recognise an IANA MAC address application?" ===
	// https://www.iana.org/assignments/ethernet-numbers/ethernet-numbers.xhtml
	// http://www.iana.org/go/rfc7042
	// https://tools.ietf.org/html/rfc1060
	if (mac_between($mac, '00:00:5E:00-52:14', '00:00:5E:00:52:FF')) $app = 'Unassigned (small allocations)';
	if (mac_between($mac, '00:00:5E:00:00:00', '00:00:5E:00:00:FF')) $app = 'Reserved and require IESG Ratification for assignment';
	if (mac_between($mac, '00:00:5E:00:03:00', '00:00:5E:00:51:FF')) $app = 'Unassigned';
	if (mac_between($mac, '00:00:5E:00:52:00', '00:00:5E:00:52:FF')) $app = 'Is used for very small assignments. Currently, 3 out of these 256 values have been assigned.';
	if (mac_between($mac, '00:00:5E:00:52:00', '00:00:5E:00:52:00')) $app = 'PacketPWEthA';
	if (mac_between($mac, '00:00:5E:00:52:01', '00:00:5E:00:52:01')) $app = 'PacketPWEthB';
	if (mac_between($mac, '00:00:5E:00:52:02', '00:00:5E:00:52:12')) $app = 'Unassigned (small allocations)';
	if (mac_between($mac, '00:00:5E:00:53:00', '00:00:5E:00:53:FF')) $app = 'Assigned for use in documentation';
	if (mac_between($mac, '00:00:5E:00:54:00', '00:00:5E:90:00:FF')) $app = 'Unassigned';
	if (mac_between($mac, '00:00:5E:90:01:01', '00:00:5E:90:01:FF')) $app = 'Unassigned (small allocations requiring both unicast and multicast)';
	//if (mac_between($mac, '00:00:5E:EF:10:00:00:00', '00:00:5E:EF:10:00:00:FF')) $app = 'General documentation';
	//if (mac_between($mac, '00:00:5E:FF:FE:00:53:00', '00:00:5E:FF:FE:00:53:FF')) $app = 'EUI-48 derived documentation';
	if (mac_between($mac, '01:00:5E:00:00:00', '01:00:5E:7F:FF:FF')) $app = 'DoD Internet Multicast (EtherType is 0x0800)'; // TODO: IPv4-Multicast  (Dabei werden dann die unteren 23 Bit der IP-Multicast-Adresse direkt auf die untersten 23 Bit der MAC-Adresse abgebildet. Der IP-Multicast-Adresse 224.0.0.1 ist somit die Multicast-MAC-Adresse 01-00-5e-00-00-01 fest zugeordnet.)
	if (mac_between($mac, '01:00:5E:80:00:00', '01:00:5E:FF:FF:FF')) $app = 'DoD Internet';
	if (mac_equals($mac, '01:00:5E:90:00:02')) $app = 'AllL1MI-ISs';
	if (mac_equals($mac, '01:00:5E:90:00:03')) $app = 'AllL2MI-ISs';
	if (mac_between($mac, '01:00:5E:90:00:04', '01:00:5E:90:00:FF')) $app = 'Unassigned (small allocations)';
	if (mac_between($mac, '01:00:5E:90:01:01', '01:00:5E:90:01:FF')) $app = 'Unassigned (small allocations requiring both unicast and multicast)';
	if (mac_between($mac, '01:00:5E:90:02:00', '01:00:5E:90:0F:FF')) $app = 'Unassigned';
	if (mac_between($mac, '01:00:5E:90:02:00', '00:00:5E:FF:FF:FF')) $app = 'Unassigned';
	if (mac_between($mac, '01:00:5E:90:10:00', '01:00:5E:90:10:FF')) $app = 'Documentation';
	if (mac_between($mac, '01:00:5E:90:11:00', '01:00:5E:FF:FF:FF')) $app = 'Unassigned';
	//if (mac_between($mac, '01:00:5E:EF:10:00:00:00', '01:00:5E:EF:10:00:00:FF')) $app = 'General documentation';
	//if (mac_between($mac, '02:00:5E:00:00:00:00:00', '02:00:5E:0F:FF:FF:FF:FF')) $app = 'Reserved';
	//if (mac_between($mac, '02:00:5E:10:00:00:00:00', '02:00:5E:10:00:00:00:FF')) $app = 'Documentation';
	//if (mac_between($mac, '02:00:5E:10:00:00:01:00', '02:00:5E:EF:FF:FF:FF:FF')) $app = 'Unassigned';
	//if (mac_between($mac, '02:00:5E:F0:00:00:00:00', '02:00:5E:FD:FF:FF:FF:FF')) $app = 'Reserved';
	//if (mac_between($mac, '02:00:5E:FE:00:00:00:00', '02:00:5E:FE:FF:FF:FF:FF')) $app = 'IPv4 Addr Holders';
	//if (mac_between($mac, '02:00:5E:FF:00:00:00:00', '02:00:5E:FF:FD:FF:FF:FF')) $app = 'Reserved';
	//if (mac_between($mac, '02:00:5E:FF:FE:00:00:00', '02:00:5E:FF:FE:FF:FF:FF')) $app = 'IANA EUI-48 Holders';
	//if (mac_between($mac, '02:00:5E:FF:FF:00:00:00', '02:00:5E:FF:FF:FF:FF:FF')) $app = 'Reserved';
	//if (mac_between($mac, '03:00:5E:00:00:00:00:00', '03:00:5E:0F:FF:FF:FF:FF')) $app = 'Reserved';
	//if (mac_between($mac, '03:00:5E:10:00:00:00:00', '03:00:5E:10:00:00:00:FF')) $app = 'Documentation';
	//if (mac_between($mac, '03:00:5E:10:00:00:01:00', '03:00:5E:EF:FF:FF:FF:FF')) $app = 'Unassigned';
	//if (mac_between($mac, '03:00:5E:F0:00:00:00:00', '03:00:5E:FD:FF:FF:FF:FF')) $app = 'Reserved';
	//if (mac_between($mac, '03:00:5E:FF:00:00:00:00', '03:00:5E:FF:FD:FF:FF:FF')) $app = 'Reserved';
	//if (mac_between($mac, '03:00:5E:FF:FE:00:00:00', '03:00:5E:FF:FE:FF:FF:FF')) $app = 'IANA EUI-48 Holders';
	//if (mac_between($mac, '03:00:5E:FF:FF:00:00:00', '03:00:5E:FF:FF:FF:FF:FF')) $app = 'Reserved';

	// === FAQ "How to recognise a Cisco's MAC address application?" ===
	// https://www.cisco.com/c/en/us/support/docs/switches/catalyst-4500-series-switches/13414-103.html
	// https://tools.ietf.org/html/rfc1060
	// https://en.wikipedia.org/wiki/Multicast_address#cite_note-15
	// http://www.cavebear.com/archive/cavebear/Ethernet/Ethernet.txt
	if (mac_equals($mac, '01:00:0C:00:00:00')) $app = 'Inter Switch Link (ISL)';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'CDP (Cisco Discovery Protocol), VTP (VLAN Trunking Protocol), EtherType is 0x0802';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'Port Aggregation Protocol (PAgP), SNAP HDLC Protocol Type is 0x0104';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'Unidirectional Link Detection (UDLD), SNAP HDLC Protocol Type is 0x0111';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'Dynamic Trunking (DTP), SNAP HDLC Protocol Type is 0x2004';
	if (mac_equals($mac, '01:00:0C:CC:CC:CC')) $app = 'VLAN Trunking (VTP), SNAP HDLC Protocol Type is 0x2003';
	if (mac_equals($mac, '01:00:0C:CC:CC:CD')) $app = 'Cisco Shared Spanning Tree Protocol address, EtherType is 0x0802';
	if (mac_equals($mac, '01:00:0C:CC:CC:CD')) $app = 'Spanning Tree PVSTP+, SNAP HDLC Protocol Type is 0x010B';
	if (mac_equals($mac, '01:00:0C:CD:CD:CD')) $app = 'STP Uplink Fast, SNAP HDLC Protocol Type is 0x200A';
	if (mac_equals($mac, '01:00:0C:CD:CD:CE')) $app = 'VLAN Bridge, SNAP HDLC Protocol Type is 0x010C';
	if (mac_equals($mac, '01:00:0C:DD:DD:DD')) $app = 'CGMP (Cisco Group Management Protocol)';

	// === FAQ "How to recognise an ITU-T's MAC address application?" ===
	// https://www.itu.int/en/ITU-T/studygroups/2017-2020/15/Documents/IEEE-assigned_OUIs-30-06-2017.docx
	if (mac_between($mac, '01:19:A7:00:00:00', '01:19:A7:00:00:FF')) $app = 'R-APS per G.8032';
	if (mac_between($mac, '01:19:A7:52:76:90', '01:19:A7:52:76:9F')) $app = 'Multicast per G.9961';

	// === FAQ "How to recognise Digital Equipment Corporation's MAC address application?" ===
	if (mac_equals($mac, '09:00:2B:00:00:00')) $app = 'DEC MUMPS, EtherType is 0x6009';
	if (mac_equals($mac, '09:00:2B:00:00:0F')) $app = 'DEC Local Area Transport (LAT), EtherType is 0x6004';
	if (mac_equals($mac, '09:00:2B:00:00:01')) $app = 'DEC DSM/DDP, EtherType is 0x8039';
	if (mac_between($mac, '09:00:2B:00:00:10', '09:00:2B:00:00:1F')) $app = 'DEC Experimental';
	if (mac_equals($mac, '09:00:2B:00:00:02')) $app = 'DEC VAXELN, EtherType is 0x803B';
	if (mac_equals($mac, '09:00:2B:00:00:03')) $app = 'DEC Lanbridge Traffic Monitor (LTM), EtherType is 0x8038';
	if (mac_equals($mac, '09:00:2B:00:00:04')) $app = 'DEC MAP End System';
	if (mac_equals($mac, '09:00:2B:00:00:05')) $app = 'DEC MAP Intermediate System';
	if (mac_equals($mac, '09:00:2B:00:00:06')) $app = 'DEC CSMA/CD Encryption, EtherType is 0x803D';
	if (mac_equals($mac, '09:00:2B:00:00:07')) $app = 'DEC NetBios Emulator, EtherType is 0x8040';
	if (mac_equals($mac, '09:00:2B:01:00:00')) $app = 'DEC LanBridge, EtherType is 0x8038';
	if (mac_equals($mac, '09:00:2B:01:00:01')) $app = 'DEC LanBridge, EtherType is 0x8038';
	if (mac_equals($mac, '09:00:2B:02:00:00')) $app = 'DEC DNA Level 2 Routing';
	if (mac_equals($mac, '09:00:2B:02:01:00')) $app = 'DEC DNA Naming Service Advertisement, EtherType is 0x803C';
	if (mac_equals($mac, '09:00:2B:02:01:01')) $app = 'DEC DNA Naming Service Solicitation, EtherType is 0x803C';
	if (mac_equals($mac, '09:00:2B:02:01:02')) $app = 'DEC Distributed Time Service, EtherType is 0x803E';
	if (mac_equals($mac, '09:00:2B:02:01:09')) $app = 'DEC Availability Manager for Distributed Systems DECamds, EtherType is 0x8048';
	if (mac_between($mac, '09:00:2B:03:00:00', '09:00:2B:03:FF:FF')) $app = 'DEC default filtering by bridges';
	if (mac_equals($mac, '09:00:2B:04:00:00')) $app = 'DEC Local Area System Transport (LAST), EtherType is 0x8041';
	if (mac_equals($mac, '09:00:2B:23:00:00')) $app = 'DEC Argonaut Console, EtherType is 0x803A';
	if (mac_equals($mac, 'AB:00:00:01:00:00')) $app = 'DEC Maintenance Operation Protocol (MOP) Dump/Load Assistance, EtherType is 0x6001';
	if (mac_equals($mac, 'AB:00:00:02:00:00')) $app = 'DEC Maintenance Operation Protocol (MOP), EtherType is 0x6002';
	if (mac_equals($mac, 'AB:00:00:03:00:00')) $app = 'DECNET Phase IV end node, EtherType is 0x6003';
	if (mac_equals($mac, 'AB:00:00:04:00:00')) $app = 'DECNET Phase IV Router, EtherType is 0x6003';
	if (mac_between($mac, 'AB:00:00:05:00:00', 'AB:00:03:FF:FF:FF')) $app = 'Reserved DEC';
	if (mac_equals($mac, 'AB:00:03:00:00:00')) $app = 'DEC Local Area Transport (LAT) - old, EtherType is 0x6004';
	if (mac_between($mac, 'AB:00:04:00:00:00', 'AB:00:04:00:FF:FF')) $app = 'Reserved DEC customer private use';
	if (mac_between($mac, 'AB:00:04:01:00:00', 'AB:00:04:01:FF:FF')) $app = 'DEC Local Area VAX Cluster groups System Communication Architecture (SCA) EtherType is 0x6007';

	if ($app) {
		echo sprintf("%-32s %s\n", "Special use:", $app);
	}

}

/**
 * @param string $mac1
 * @param string $mac2
 * @return bool
 * @throws Exception
 */
function mac_equals(string $mac1, string $mac2): bool {
	return mac_between($mac1, $mac2, $mac2);
}

/**
 * @param string $mac
 * @param string $low
 * @param string $high
 * @return bool
 * @throws Exception
 */
function mac_between(string $mac, string $low, string $high): bool {
	if (empty($high)) $high = $low;

	if (!mac_valid($mac)) throw new Exception("Invalid MAC: $mac");
	if (!mac_valid($low)) throw new Exception("Invalid MAC: $low");
	if (!mac_valid($high)) throw new Exception("Invalid MAC: $high");

	$mac = strtoupper(preg_replace('@[^0-9A-F]@', '', $mac));
	$low = strtoupper(preg_replace('@[^0-9A-F]@', '', $low));
	$high = strtoupper(preg_replace('@[^0-9A-F]@', '', $high));

	$mac = gmp_init($mac, 16);
	$low = gmp_init($low, 16);
	$high = gmp_init($high, 16);

	return (gmp_cmp($mac, $low) >= 0) && (gmp_cmp($mac, $high) <= 0);
}