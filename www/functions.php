<?php


	function test_input($data) {
	  $data = trim($data);
	  $data = stripslashes($data);
	  $data = htmlspecialchars($data);
	  return $data;
	}
  
	function write_php_ini($array, $file)
	{
		$res = array();
		foreach($array as $key => $val)
		{
			if(is_array($val))
			{
				$res[] = "[$key]";
				foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
			}
			else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
		}
		safefilerewrite($file, implode("\r\n", $res));
	}


	function safefilerewrite($fileName, $dataToSave)
	{    if ($fp = fopen($fileName, 'w'))
		{
			$startTime = microtime();
			do
			{            $canWrite = flock($fp, LOCK_EX);
			   // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
			   if(!$canWrite) usleep(round(rand(0, 100)*1000));
			} while ((!$canWrite)and((microtime()-$startTime) < 1000));
	
			//file was locked so now we can store information
			if ($canWrite)
			{            fwrite($fp, $dataToSave);
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}
	}


	function walk($array, $key)
	{
	  if( !is_array( $array)) 
	  {
		  return false;
	  }
	  foreach ($array as $k => $v)
	  {
		  if($k == $key)
		  {
			  return True;
		  }
	  }
	return false;
	}


	function update_interfaces_file($select)
	{
	  $configurationsettings = parse_ini_file("/var/www/routersettings.ini");
	  $networksettings = array();
	  switch ($select) {
		  case "Router":
			//operationmode Router
			array_push($networksettings,"auto lo\n");
			array_push($networksettings,"iface lo inet loopback\n\n");
			if (strcmp($configurationsettings['lantype'],"dhcp") == 0) {
				array_push($networksettings,"auto eth0\n");
				if(!empty($configurationsettings['lanmtu'])) {
					array_push($networksettings,"iface eth0 inet dhcp\n");
					array_push($networksettings,"post-up ifconfig eth0 mtu " . $configurationsettings['lanmtu'] . "\n");
				}
				else {
					array_push($networksettings,"iface eth0 inet dhcp\n");
				}
			array_push($networksettings,"\n");
			}
			if (strcmp($configurationsettings['lantype'],"static") == 0) {
				array_push($networksettings,"auto eth0\n");
				array_push($networksettings,"iface eth0 inet static\n");
				array_push($networksettings,"address " . $configurationsettings['lanip'] . "\n");
				array_push($networksettings,"netmask " . $configurationsettings['lanmask'] . "\n");
				if(!empty($configurationsettings['langw']))
				  array_push($networksettings,"gateway " . $configurationsettings['langw'] . "\n");
				if(!empty($configurationsettings['dns1']) || !empty($configurationsettings['dns2'])) {
					if(!empty($configurationsettings['dns1']))
					  array_push($networksettings,"nameserver " . $configurationsettings['dns1'] . "\n");
					if(!empty($configurationsettings['dns2']))
					  array_push($networksettings,"nameserver " . $configurationsettings['dns2'] . "\n");
				}
				if(!empty($configurationsettings['lanmtu'])) 
					array_push($networksettings,"post-up ifconfig eth0 mtu " . $configurationsettings['lanmtu'] . "\n");
				array_push($networksettings,"\n");
			}
			$strdata = file_get_contents ("/boot/cmdline.txt");
			$arrdata = explode (" ",$strdata);
			foreach($arrdata as $key => $value) {
			  if (strpos($value, 'smsc95xx.macaddr=') !== FALSE) {
				unset($arrdata[$key]);
			  }
			}
			if(!empty($configurationsettings['lanmac'])) {
			  array_push($arrdata,"smsc95xx.macaddr=" . $configurationsettings['lanmac']);
			}
			$arrdata = str_replace("\n","",$arrdata);
			file_put_contents("/boot/cmdline.txt",implode(" ",$arrdata));
			array_push($networksettings,"auto wlan0\n");
			array_push($networksettings,"iface wlan0 inet static\n");
			array_push($networksettings,"address " . $configurationsettings['wifiip'] . "\n");
			array_push($networksettings,"netmask " . $configurationsettings['wifimask'] . "\n");
			file_put_contents("/etc/network/interfaces",implode($networksettings));
		  break;
		 
		  case "Access Point":
			//operationmode access point	
			array_push($networksettings,"auto lo\n");
			array_push($networksettings,"iface lo inet loopback\n\n");
			array_push($networksettings,"allow-hotplug wlan0\n");
			array_push($networksettings,"iface wlan0 inet manual\n");
			array_push($networksettings,"\n");
			array_push($networksettings,"allow-hotplug eth0\n");
			array_push($networksettings,"iface eth0 inet manual\n\n");
			if (strcmp($configurationsettings['lantype'],"dhcp") == 0) {
				array_push($networksettings,"auto br0\n");
				array_push($networksettings,"iface br0 inet dhcp\n");
				if(!empty($configurationsettings['lanmac'])) 
					array_push($networksettings,"hwaddress ether " . $configurationsettings['lanmac'] . "\n");
				array_push($networksettings,"bridge_ports wlan0 eth0\n");
				if(!empty($configurationsettings['lanmtu'])) 
					array_push($networksettings,"post-up ifconfig eth0 mtu " . $configurationsettings['lanmtu'] . "\n");
				array_push($networksettings,"\n");
			}
			if (strcmp($configurationsettings['lantype'],"static") == 0) {
				array_push($networksettings,"auto br0\n");
				array_push($networksettings,"iface br0 inet static\n");
				if(!empty($configurationsettings['lanmac'])) 
					array_push($networksettings,"hwaddress ether " . $configurationsettings['lanmac'] . "\n");
				array_push($networksettings,"bridge_ports wlan0 eth0\n");
				if(!empty($configurationsettings['lanmtu'])) 
					array_push($networksettings,"post-up ifconfig eth0 mtu " . $configurationsettings['lanmtu'] . "\n");
				array_push($networksettings,"address " . $configurationsettings['lanip'] . "\n");
				array_push($networksettings,"netmask " . $configurationsettings['lanmask'] . "\n");
				if(!empty($configurationsettings['langw']))
				  array_push($networksettings,"gateway " . $configurationsettings['langw'] . "\n");
				if(!empty($configurationsettings['dns1']) || !empty($configurationsettings['dns2'])) {
					if(!empty($configurationsettings['dns1']))
					  array_push($networksettings,"nameserver " . $configurationsettings['dns1'] . "\n");
					if(!empty($configurationsettings['dns2']))
					  array_push($networksettings,"nameserver " . $configurationsettings['dns2'] . "\n");
				}
			}
			file_put_contents("/etc/network/interfaces",implode($networksettings));
		  break;
	  }
	}
?> 

