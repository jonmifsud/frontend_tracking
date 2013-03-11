<?php
	Class extension_frontend_tracking extends Extension{
		
		public static $xml;
		
		public function getSubscribedDelegates(){
			return array(
			
				// Used for adding the nonce
				array(
					'page'      => '/frontend/',
					'delegate'  => 'FrontendParamsResolve',
					'callback'  => 'addParams',
				),
				array(
					'page'      => '/frontend/',
					'delegate'  => 'EventPreSaveFilter',
					'callback'  => 'eventPreSaveFilter',
				)

			);
		}

		/**
		 * Add the custom parameters before entry save. This will ensure we can track without passing data with post
		 */
		public function eventPreSaveFilter($context){
			include_once dirname(__FILE__) . '/lib/geoip/geoip.inc';
			$gi = geoip_open(dirname(__FILE__) . '/lib/geoip/data/GeoIP.dat',GEOIP_STANDARD);
			// Visitor's IP
			$context['fields']['ip'] = $_SERVER['REMOTE_ADDR'];
			// Visitor's Country
			$context['fields']['country'] = strtolower(geoip_country_name_by_addr($gi, $context['params']['client-ip']));
			geoip_close($gi);			
			//to replace with Symphony Cookie
			$referer = new Cookie('referer', $validFor , __SYM_COOKIE_PATH__, NULL, false);
			$context['fields']['referer'] = $referer->get('referer');

			//check if a url is being passed
			if (empty($context['fields']['url']))
				$context['fields']['url'] = $_SERVER['HTTP_REFERER'];

			//check if a url is being passed
			if (empty($context['fields']['language']))
				$context['fields']['language'] = FLang::getLang();

			if (empty($context['fields']['website'])){
				$page = Frontend::Page();
				$context['fields']['website'] =$page->_param['root'];
			}
		}

		/**
		 * Add custom params to pool
		 */
		public function addParams($context){
			include_once dirname(__FILE__) . '/lib/geoip/geoip.inc';
			$gi = geoip_open(dirname(__FILE__) . '/lib/geoip/data/GeoIP.dat',GEOIP_STANDARD);
				
			// Client's IP
			$context['params']['client-ip'] = $_SERVER['REMOTE_ADDR'];
			// $context['params']['client-ip'] = '195.158.105.159';
			$context['params']['country-code'] = geoip_country_code_by_addr($gi, $context['params']['client-ip']);
			if (empty($context['params']['country-code'])){
				$context['params']['country-code'] ='MT'; //to be replaced with Default Country
			}
			// $context['params']['country-name'] = 'switzerland';
			geoip_close($gi);
			
			$phoneCodes = include dirname(__FILE__) . '/lib/country/phone_codes.php';
			//country list taken from https://github.com/umpirsky/country-list/tree/master/country/cldr
			$country = include dirname(__FILE__) . '/lib/country/'.FLang::getLang().'/country.php';

			$key = array_search(strtolower($context['params']['country-name']),array_map('strtolower',$country));
			$context['params']['country-name'] = $country[$context['params']['country-code']];
			$context['params']['country-phone-code'] = $phoneCodes[$context['params']['country-code']];
			$this->setReferer($context);
		}

		private function setReferer($context){
			// Look for an provcode and save in session
			// NOTE The empty string (or 0) will not match, resulting in the HTTP_REFERER being used - this is intentional!
			$ref_url_param_name = 'ref';
			$ref_url_param_value = $context['params']["url-{$ref_url_param_name}"];

			$validFor = (30 * 60); // should use a config option to set the validity length

			$referer = new Cookie('referer', $validFor , __SYM_COOKIE_PATH__, NULL, false);
			$old_referer = $referer->get('referer');

			if (!empty($ref_url_param_value)) {
				// We have a code via URL param: ...?ref=12345
				$referer->set('referer',"Ref Code: {$ref_url_param_value} | Referer: {$_SERVER['HTTP_REFERER']} | First URL: {$context['params']['current-url']}");
			} elseif (empty($old_referer) && $_SERVER['HTTP_REFERER']) {
				echo('test');
				//in here we should somewhat separate organic searches
				// Set to HTTP_REFERER, assuming that in session is empty
				$referer->set('referer',"Referer: {$_SERVER['HTTP_REFERER']} | First URL: {$context['params']['current-url']}");
			} elseif (empty($old_referer)) {
				//otherwise set as direct
				$referer->set('referer',"Direct | First URL: {$context['params']['current-url']}");
			}
			// Always make it a param in case a template needs it
			$context['params']['referer'] = $referer->get('referer');
		}
		
		
		public function enable(){
			return $this->install();
		}

		public function disable(){
		}

		public function install(){
		}

		public function uninstall(){
		}

	}

?>