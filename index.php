<?php 
	
		
		/**
		* Let's Encrypt
		*
		* WHM details to be supplied via letsencrypt.ini
		* shell_exec + curl MUST also be enabled on the account
		*
		* Example: domain.com/letsencrypt
		* 
		* Optional parameters: 
		* ?force_install=1 Make the WHM re-install, even if the certificate seems valid
		* ?www=0 (default: 1) Skip including www.domain.com
		*
		* After which you should be able to set a daily cron job: 
		* wget http://domain.com/developer/letsencrypt/eels >/dev/null 2>&1
		* 
		* @link https://github.com/kelunik/acme-client/blob/master/doc/advanced-usage.md
		* @link https://digitz.org/blog/lets-encrypt-cpanel-script/
		* @link https://documentation.cpanel.net/display/SDK/Guide+to+WHM+API+1
		* @author hellodaniel
		* 
		*/
	

			// Allow the domain to be passed as a query, otherwise get it from the current domain
			if (isset($_GET['domain']))
				$domain = addslashes(str_replace(' ', '', $_GET['domain'])); 
			else 
				$domain = env('HTTP_HOST'); 
				
			
			if (!is_callable('shell_exec') || !is_callable('exec'))
				die('shell_exec and exec must be enabled'); 
			
			// Force install forces the WHM to install
			if (isset($_GET['force_install']))
				$install = $_GET['force_install']; 
			else 
				$install = false; // << Note: Will become TRUE if certificate is issued
			
				
			// Include www.domain.com when registering certificate
			if (isset($_GET['www']))
				$www = $_GET['www']; 
			else 
				$www = true; 
			
			$client = 'php ./acme-client.phar'; 
			

			$output = []; 
			
			if (is_readable('letsencrypt.ini'))
				$config = parse_ini_file('letsencrypt.ini'); 
			else 
				$output[] = 'Please create the letsconfig.ini config';
			
			
			if (!$config['whm_user'] || !$config['whm_key'] || !$config['cpanel_user']) {
					
				$output[] = 'Configuration values missing'; 
				$output[] = 'Please check letsencrypt.ini'; 
				
			} else {
			
				
				// Create storage if not exists
				if (!file_exists($config['storage'])) 
				    mkdir($config['storage'], 0777, true);
				
				// Easier to read
				$config['storage'] = realpath($config['storage']); 
				
				if ($config['staging']) 
					$server = 'acme-staging.api.letsencrypt.org/directory'; 
				else 
					$server = 'acme-v01.api.letsencrypt.org/directory'; 
			
				$cert_folder = $config['storage'] . '/certs/' . str_replace('/', '.', $server) . '/'; 
				$output[] = 'Cert folder is: ' . $cert_folder; 
				
				$opts = ' -s https://' . $server . ' --storage ' . $config['storage'];
				
				$primary_domain = explode(':', $config['domain'])[0];
				
				$issue = false; 
				
				$cmd = $client . " check --name {$primary_domain} $opts 2>&1"; 
				
				$output[] =  'Executing: <code>' . $cmd . '</code>'; 
	
				// Get the output for human readable
				$output[] = nl2br(shell_exec($cmd)); 
				
				// Exits with a non-zero exit code if renewal needed
				// (essentially the same as above but with an exit code)
				$result = exec($cmd); 
				
				// If a new certificate is required for this domain then issue it
				if (strstr($output[count($output)-1], 'Certificate not found') || $result) 
					$issue = true; 
				
				
				/**
				 * A new certificate is in order. Execute it. 
				 */
				if ($issue || $install) { 
				  
					
					$output[] =   "Setting up a API client using {$config['email']}";
					$cmd = $client . " setup --email {$config['email']} $opts 2>&1"; 
					$output[] =  'Executing: <code>' . $cmd . '</code>'; 
					
					$output[] = shell_exec($cmd);
					
					
					if ($www) {
						
						$domains = explode(':', $config['domain']); 
						foreach ($domains as $host) {
							$config['domain'] .= ':www.' . $host; 
						}
						
					}
					
					$output[] =  "Issuing new cert for {$config['domain']}";
				  $cmd = $client . " issue -d {$config['domain']} -p {$config['public_html']} $opts"; 
				  $output[] = shell_exec($cmd);
					$install = true; 
					
					
				} else {
				  $output[] =  "Renewal not required";
				}
				
				if ($install) {
				
				  $query = "https://127.0.0.1:2087/json-api/listaccts?api.version=1&search={$config['cpanel_user']}&searchtype=user";
				  
				  $curl = curl_init();
				  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
				  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
				  curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
				    
				  $header[0] = "Authorization: WHM {$config['whm_user']}:" . preg_replace("'(\r|\n)'","",$config['whm_key']);
				  curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
				  curl_setopt($curl, CURLOPT_URL, $query);
				  
					$ip = curl_exec($curl);
				  
				  if ($ip == false) {
				    echo "Curl error: " . curl_error($curl);
				  }
				  
					// Get the IP of the first account for the cPanel user
				  $ip = json_decode($ip, true);
				  $ip = $ip['data']['acct']['0']['ip'];
					$output[] = 'Installing certificate via WHM in ip address: ' . $ip;
					
					$cert = urlencode(file_get_contents($cert_folder . $primary_domain . "/cert.pem"));
				  $key = urlencode(file_get_contents($cert_folder . $primary_domain . "/key.pem"));
				  $chain = urlencode(file_get_contents($cert_folder . $primary_domain . "/chain.pem"));
				  $query = "https://127.0.0.1:2087/json-api/installssl?api.version=1&domain={$primary_domain}&crt={$cert}&key={$key}&cab={$chain}&ip=$ip";
					
					curl_setopt($curl, CURLOPT_URL, $query);
				  $result = curl_exec($curl);
				  
				  if ($result == false) {
				    $output[] =  "Curl error: " . curl_error($curl);
				  } else {
						$output[] = $result; 
					}
				  
					curl_close($curl);
				  
				  $output[] = "All Done";
					
					// Mail the designated account with the outcome
					mail($config['email'], 'SSL/TLS for ' . $config['domain'], strip_tags(implode("\n", $output))); 
			
					
				} 
			
			}
			
      // Display the output
      print_r($output); 

