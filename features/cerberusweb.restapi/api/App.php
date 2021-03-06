<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class Plugin_RestAPI {
	public static function render($array, $format='json') {
		if(!is_array($array))
			return false;
		
		if('json' == $format) {
			// Fix numeric keys
			if(isset($array['results'])) {
				$filtered_results = array();
				
				foreach($array['results'] as $k => $v) {
					$filtered_results[] = $v;
				}
				
				$array['results'] = $filtered_results;
			}
			
			header("Content-type: application/json; charset=utf-8");
			echo json_encode($array);
			
		} elseif ('xml' == $format) {
			header("Content-type: text/xml; charset=utf-8");
			$xml = new SimpleXMLElement("<response/>");
			self::xml_encode($array, $xml);
			echo $xml->asXML();
			
		} else {
			header("Content-type: text/plain; charset=utf-8");
			echo sprintf("'%s' is not implemented.", DevblocksPlatform::strEscapeHtml($format));
		}
		
		exit;
	}
	
	private static function xml_encode($object, &$xml) {
		if(is_array($object))
		foreach($object as $k => $v) {
			if(is_array($v)) {
				$e = $xml->addChild("array", '');
				$e->addAttribute("key", $k);
				self::xml_encode($v, $e);
				
			} else {
				$e = $xml->addChild("string", htmlspecialchars($v, ENT_QUOTES, LANG_CHARSET_CODE));
				$e->addAttribute("key", (string)$k);
			}
		}
	}
};

if (class_exists('Extension_PreferenceTab')):
class Ch_RestPreferencesTab extends Extension_PreferenceTab {
	const ID = 'rest.preferences.tab.api';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_WebApiCredentials');
		$defaults->id = 'webapi_credentials';
		
		if(null == ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults)))
			return;
		
		// Force filter the view to the current worker's API keys
		$params = array(
			new DevblocksSearchCriteria(SearchFields_WebApiCredentials::WORKER_ID,'=',$active_worker->id),
		);
		
		$view->addParamsRequired($params, true);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showPeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($id) && null == ($model = DAO_WebApiCredentials::get($id)))
			return;
		
		if(!empty($id) && !$active_worker->is_superuser && $active_worker->id != $model->worker_id)
			return;
		
		if(!empty($model))
			$tpl->assign('model', $model);

		$tpl->display('devblocks:cerberusweb.restapi::peek.tpl');
	}
	
	function savePeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$label = DevblocksPlatform::importGPC($_REQUEST['label'],'string','');
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'],'array',array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id)) {
			if(null == ($model = DAO_WebApiCredentials::get($id)))
				return;
		}
		
		if(!empty($id) && !empty($do_delete)) {
			if($active_worker->is_superuser || $active_worker->id == $model->worker_id) {
				DAO_WebApiCredentials::delete($id);
			}
			return;
		}
		
		$allowed_paths = DevblocksPlatform::importGPC($params['allowed_paths'],'string','');
		
		$params = array(
			'allowed_paths' => DevblocksPlatform::parseCrlfString($allowed_paths)
		);
		
		$fields = array(
			DAO_WebApiCredentials::LABEL => (!empty($label) ? $label : 'New API credentials'),
			DAO_WebApiCredentials::PARAMS_JSON => json_encode($params),
		);

		@$generate_new_keys = DevblocksPlatform::importGPC($_REQUEST['regenerate_keys'],'integer',0);
		
		if(empty($id) || $generate_new_keys) {
			$fields[DAO_WebApiCredentials::ACCESS_KEY] = DevblocksPlatform::strLower(CerberusApplication::generatePassword(12));
			$fields[DAO_WebApiCredentials::SECRET_KEY] = DevblocksPlatform::strLower(CerberusApplication::generatePassword(32));
		}
		
		if(empty($id)) { // Create
			$fields[DAO_WebApiCredentials::WORKER_ID] = $active_worker->id;
			DAO_WebApiCredentials::create($fields);
			
		} else { // Edit
			DAO_WebApiCredentials::update($id, $fields);
			
		}
		
		return;
	}
}
endif;

class Ch_RestFrontController implements DevblocksHttpRequestHandler {
	protected $_payload = '';
	
	private function _getRestControllers() {
		$manifests = DevblocksPlatform::getExtensions('cerberusweb.rest.controller', false);
		$controllers = array();
		
		if(is_array($manifests))
		foreach($manifests as $manifest) {
			if(isset($manifest->params['uri']))
			$controllers[$manifest->params['uri']] = $manifest;
		}
		
		return $controllers;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		$db = DevblocksPlatform::getDatabaseService();
		
		// **** BEGIN AUTH
		@$verb = $_SERVER['REQUEST_METHOD'];
		@$header_date = $_SERVER['HTTP_X_DATE'];
		
		// If the custom X-Date: header isn't provided, fall back to Date:
		if(empty($header_date))
			@$header_date = $_SERVER['HTTP_DATE'];
		
		@$header_signature = null;
		
		// Try new header first
		if(isset($_SERVER['HTTP_CERB_AUTH'])) {
			$header_signature = $_SERVER['HTTP_CERB_AUTH'];

		// Fallback to older header
		} elseif(isset($_SERVER['HTTP_CERB5_AUTH'])) {
			$header_signature = $_SERVER['HTTP_CERB5_AUTH'];
		}
		
		@$this->_payload = $this->_getRawPost();
		@list($auth_access_key, $auth_signature) = explode(":", $header_signature, 2);
		$url_parts = parse_url(DevblocksPlatform::getWebPath());
		$url_path = $url_parts['path'];
		$url_query = $this->_sortQueryString(@$_SERVER['QUERY_STRING']);
		$string_to_sign_prefix = "$verb\n$header_date\n$url_path\n$url_query\n$this->_payload";
		
		if(!$this->_validateRfcDate($header_date)) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid timestamp)"));
		}

		// Worker-level auth
		if(null == ($credential = DAO_WebApiCredentials::getByAccessKey($auth_access_key))) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid credentials: access key)"));
		}
		
		if(null == (@$worker = DAO_Worker::get($credential->worker_id))) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid credentials: worker)"));
		}

		$secret = DevblocksPlatform::strLower(md5($credential->secret_key));
		$string_to_sign = "$string_to_sign_prefix\n$secret\n";
		$compare_hash = md5($string_to_sign);

		if(0 != strcmp($auth_signature, $compare_hash)) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid credentials: checksum)"));
		}
		
		// REST extensions
		@array_shift($stack); // rest
		
		// Check this API key's path restrictions
		$requested_path = implode('/', $stack);
		@$allowed_paths = $credential->params['allowed_paths'];
		
		if(empty($allowed_paths)) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (This path is prohibited)"));
		}
		
		$permitted = false;
		
		foreach($allowed_paths as $allowed_path) {
			$pattern = DevblocksPlatform::strToRegExp($allowed_path);
			if(preg_match($pattern, $requested_path)) {
				$permitted = true;
				break;
			}
		}
		
		if(!$permitted) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (You are not authorized to make this request)"));
		}

		// Controller
		
		@$controller_uri = array_shift($stack); // e.g. tickets
		
		$controllers = $this->_getRestControllers();

		if(isset($controllers[$controller_uri])
			&& null != ($controller = DevblocksPlatform::getExtension($controllers[$controller_uri]->id, true, true))) {
			/* @var $controller Extension_RestController */
			
			// Set the active worker
			CerberusApplication::setActiveWorker($worker);
			
			// Set worker language
			DevblocksPlatform::setLocale(!empty($worker->language) ? $worker->language : 'en_US');
			
			// Set worker timezone
			if(!empty($worker->timezone))
				DevblocksPlatform::setTimezone($worker->timezone);
			
			// Set worker time format
			$default_time_format = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::TIME_FORMAT, CerberusSettingsDefaults::TIME_FORMAT);
			DevblocksPlatform::setDateTimeFormat(!empty($worker->time_format) ? $worker->time_format : $default_time_format);
			
			// Handle the request
			$controller->setPayload($this->_payload);
			array_unshift($stack, $verb);
			$controller->handleRequest(new DevblocksHttpRequest($stack));
			
		} else {
			array_unshift($stack, $controller_uri);
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Unknown command (" . implode('/', $stack) . ")"));
		}
	}
	
	private function _sortQueryString($query) {
		// Strip the leading ?
		if(substr($query,0,1)=='?') $query = substr($query,1);
		$args = array();
		$parts = explode('&', $query);
		foreach($parts as $part) {
			$pair = explode('=', $part, 2);
			if(is_array($pair) && 2==count($pair))
				$args[$pair[0]] = $part;
		}
		ksort($args);
		return implode("&", $args);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $rfcDate
	 * @return boolean
	 */
	private function _validateRfcDate($rfcDate) {
		$diff_allowed = 600; // 10 min
		$mktime_rfcdate = strtotime($rfcDate);
		$mktime_rfcnow = strtotime(date('r'));
		$diff = $mktime_rfcnow - $mktime_rfcdate;
		return ($diff > (-1*$diff_allowed) && $diff < $diff_allowed) ? true : false;
	}
	
	private function _getRawPost() {
		$contents = "";
		
		$putdata = fopen( "php://input" , "rb" );
		while(!feof( $putdata ))
			$contents .= fread($putdata, 4096);
		fclose($putdata);

		return $contents;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
	}
};

abstract class Extension_RestController extends DevblocksExtension {
	const ERRNO_CUSTOM = 0;
	const ERRNO_ACL = 1;
	const ERRNO_NOT_IMPLEMENTED = 2;
	const ERRNO_SEARCH_FILTERS_INVALID = 20;
	
	private $_activeWorker = null; /* @var $_activeWorker Model_Worker */
	private $_format = 'json';
	private $_payload = '';
	
	/**
	 *
	 * @param string $message
	 */
	protected function error($code, $message='') {
		// Polymorph for convenience
		if(is_string($code)) {
			$message = $code;
			$code = self::ERRNO_CUSTOM;
		}
		
		// Error codes
		switch(intval($code)) {
			case self::ERRNO_ACL:
				if(empty($message))
					$message = 'Access denied.';
				break;
				
			case self::ERRNO_NOT_IMPLEMENTED:
				if(empty($message))
					$message = 'Not implemented.';
				break;
				
			case self::ERRNO_SEARCH_FILTERS_INVALID:
				if(empty($message))
					$message = 'The provided search filters are invalid.';
				break;
				
			default:
			case self::ERRNO_CUSTOM:
				$code = self::ERRNO_CUSTOM;
				if(empty($message))
					$message = '';
				break;
		}

		if(!is_string($message))
			$message = '';
		
		$out = array(
			'__status' => 'error',
			'__version' => APP_VERSION,
			'__build' => APP_BUILD,
			'__error' => $code,
			'message' => $message,
		);
		
		return Plugin_RestAPI::render($out, $this->_format);
	}
	
	private function _expandResults($array, $expand) {
		$dict = new DevblocksDictionaryDelegate($array);
		
		foreach($expand as $expand_field)
			$dict->$expand_field;
		
		$values = $dict->getDictionary();
		
		// If nothing was loaded, don't add the token
		foreach($expand as $expand_field) {
			if(!isset($values[$expand_field]) || is_null($values[$expand_field]))
				unset($values[$expand_field]);
		}
		
		return $values;
	}
	
	/**
	 *
	 * @param array $array
	 */
	protected function success($array=array()) {
		if(!is_array($array))
			return false;

		@$expand = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['expand'],'string',null));
		
		@$show_meta = DevblocksPlatform::importGPC($_REQUEST['show_meta'],'string','');
		$show_meta = (0 == strlen($show_meta) || !empty($show_meta)) ? true : false;
		
		// [TODO] Bulk load expands (e.g. ticket->org, ticket->sender->org)
		
		// Do we need to lazy load some fields to be helpful?
		if(is_array($expand) && !empty($expand)) {
			if(isset($array['results'])) {
				foreach($array['results'] as $k => $v) {
					if(!isset($array['results'][$k]['_context']))
						continue;
					
					$array['results'][$k] = $this->_expandResults($array['results'][$k], $expand);
				}
				
			} else {
				$array = $this->_expandResults($array, $expand);
			}
		}
		
		// Results meta
		
		if(isset($array['results']) && is_array($array['results'])) {
			$_labels = null;
			$_types = null;

			// Scrub nested lazy-loaded labels and types
			array_walk($array['results'], function(&$result) use (&$_labels, &$_types) {
				if(null == $_labels && isset($result['_labels']))
					$_labels = $result['_labels'];
				
				if(null == $_types && isset($result['_types']))
					$_types = $result['_types'];
				
				unset($result['_labels']);
				unset($result['_types']);
				
				$scrubs = array('_loaded', '__labels', '__types');
				
				foreach($result as $k => $v) {
					foreach($scrubs as $scrub)
						if(substr($k, -strlen($scrub)) == $scrub)
							unset($result[$k]);
				}
			});
			
			// If the client wants to see the meta on resultsets
			if($show_meta) {
				$array['results_meta'] = array();
				
				if(!empty($_labels))
					$array['results_meta']['labels'] = $_labels;
				
				if(!empty($_types))
					$array['results_meta']['types'] = $_types;
				
				if(empty($array['results_meta']))
					unset($array['results_meta']);
			}
		
		// Scrub lazy-loaded labels and types on a single object
		} else if(is_array($array)) {
			$scrubs = array('_loaded', '__labels', '__types');
			
			if(!$show_meta) {
				array_push($scrubs, '_labels', '_types');
			}
			
			foreach($array as $k => $v) {
				foreach($scrubs as $scrub)
					if(substr($k, -strlen($scrub)) == $scrub)
						unset($array[$k]);
			}
		}
		
		$out = array(
			'__status' => 'success',
			'__version' => APP_VERSION,
			'__build' => APP_BUILD,
		) + $array;
		
		// Sort by key
		ksort($out);
		
		return Plugin_RestAPI::render($out, $this->_format);
	}
	
	/**
	 * @deprecated
	 * @return Model_Worker
	 */
	public function getActiveWorker() {
		return CerberusApplication::getActiveWorker();
	}
	
	public function getPayload() {
		return $this->_payload;
	}
	
	public function setPayload($payload) {
		$this->_payload = $payload;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		
		// Figure out our format by looking at the last path argument
		$command = explode('.', array_pop($stack));
		$format = array_pop($command);
		$command = implode('.', $command);
		
		array_push($stack, $command);
		if(null != $format)
			$this->_format = $format;
		
		// Verb
		@$verb = array_shift($stack);
		
		if(0==strcasecmp('PUT',$verb)) {
			$_PUT = array();
			parse_str($this->_payload, $_PUT);
			foreach($_PUT as $k => $v) {
				$_POST[$k] = $v;
				$_REQUEST[$k] = $v;
			}
		}
		
		// Verb Actions
		$method = DevblocksPlatform::strLower($verb) .'Action';
		if(method_exists($this,$method)) {
			call_user_func(array(&$this,$method), $stack);
		}
	}
	
	function getAction($stack) {
		/* Override */
		$this->error('GET not implemented.');
	}
	
	function putAction($stack) {
		/* Override */
		$this->error('PUT not implemented.');
	}
	
	function postAction($stack) {
		/* Override */
		$this->error('POST not implemented.');
	}
	
	function deleteAction($stack) {
		/* Override */
		$this->error('DELETE not implemented.');
	}
	
//	protected function _search($filters, $sortToken, $sortAsc, $page, $limit, $options) {
// [TODO] Overload
//	}

	protected function _handleSearchBuildParamsCustomFields(&$filters, $context) {
		$params = array();
		// Handle custom fields
		if(is_array($filters))
		foreach($filters as $key => $filter) {

			$parts = explode("_",$filter[0],2);
			if(2==count($parts) && 'custom'==$parts[0] && is_numeric($parts[1])) {
				// Custom Fields
				$fields = DAO_CustomField::getByContext($context);

				if(is_array($fields))
				foreach($fields as $field_id => $fieldData) {
					if($field_id === intval($parts[1])) {
						$field = 'cf_'.$field_id;
						unset($filters[$key]);
					}
				}
				
				if(!empty($field)) {
					$params[$field] = new DevblocksSearchCriteria($field, $filter[1], $filter[2]);
				}
			}
		}
		return $params;
	}
	
	protected function _handleSearchBuildParams($filters) {
		// Criteria
		$params = array();
		
		if(is_array($filters))
		foreach($filters as $filter) {
			if(!is_array($filter) && 3 != count($filter))
				$this->error(self::ERRNO_SEARCH_FILTERS_INVALID);
			
			if(null === ($field = $this->translateToken($filter[0], 'search')))
				$this->error(self::ERRNO_SEARCH_FILTERS_INVALID, sprintf("'%s' is not a valid search token.", $filter[0]));
			
			$oper = $filter[1];
			$value = $filter[2];
			
			// Translate OPER_IN from JSON arrays to PHP primitives
			switch($oper) {
				case DevblocksSearchCriteria::OPER_IN:
				case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				case DevblocksSearchCriteria::OPER_NIN:
				case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
					if(!is_array($value) && preg_match('#^\[.*\]$#', $value)) {
						$value = json_decode($value, true);
						
					} elseif(is_array($value)) {
						$value;
						
					} else {
						$value = array($value);
						
					}
					break;
				
				case DevblocksSearchCriteria::OPER_BETWEEN:
					if(!is_array($value) && preg_match('#^\[.*\]$#', $value)) {
						$value = json_decode($value, true);
					}
					break;
			}
			
			$params[$field] = new DevblocksSearchCriteria($field, $oper, $value);
		}
		
		return $params;
	}
	
	protected function _handlePostSearch() {
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'],'string',null);
		
		@$criteria = DevblocksPlatform::importGPC($_REQUEST['criteria'],'array',array());
		@$opers = DevblocksPlatform::importGPC($_REQUEST['oper'],'array',array());
		@$values = DevblocksPlatform::importGPC($_REQUEST['value'],'array',array());

		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer',1);
		@$limit = DevblocksPlatform::importGPC($_REQUEST['limit'],'integer',10);

		@$show_results = DevblocksPlatform::importGPC($_REQUEST['show_results'],'string','');
		$show_results = (0 == strlen($show_results) || !empty($show_results)) ? true: false;
		
		@$subtotals = DevblocksPlatform::importGPC($_REQUEST['subtotals'],'array',array());
		
		@$sortToken = DevblocksPlatform::importGPC($_REQUEST['sortBy'],'string',null);
		@$sortAsc = DevblocksPlatform::importGPC($_REQUEST['sortAsc'],'integer',1);
		
		if(count($criteria) != count($opers) || count($criteria) != count($values))
			$this->error(self::ERRNO_SEARCH_FILTERS_INVALID);
		
		// Filters
		$filters = array();
		
		if(is_array($criteria))
		foreach($criteria as $idx => $token) {
			$field = $token;
			$oper = $opers[$idx];
			$value = $values[$idx];
			
			if(!empty($field))
				$filters[$field] = array($field, $oper, $value);
		}
		
		$options = array(
			'query' => $query,
			'show_results' => $show_results,
			'subtotals' => $subtotals,
		);
		
		$results = $this->search($filters, $sortToken, $sortAsc, $page, $limit, $options);
		
		return $results;
	}
	
	protected function _handleRequiredFields($required, $fields) {
		// Check required fields
		if(is_array($required))
		foreach($required as $reqfield)
			if(!isset($fields[$reqfield]))
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is a required field.", $reqfield));
	}
	
	protected function _handleCustomFields($scope_array) {
		$fields = array();
		
		if(is_array($scope_array))
		foreach($scope_array as $k => $v) {
			$parts = explode("_",$k,2);
			if(2==count($parts) && 'custom'==$parts[0] && is_numeric($parts[1])) {
				$fields[intval($parts[1])] = DevblocksPlatform::importGPC($scope_array[$k]);
			}
		}
		
		return $fields;
	}
	
	protected function _handleSearchTokensCustomFields($context) {
		$tokens = array();
		$cfields = DAO_CustomField::getByContext($context, true);
		
		if(is_array($cfields))
		foreach($cfields as $cfield) {
			switch($cfield->type) {
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_DROPDOWN:
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_WORKER:
					$tokens['custom_' . $cfield->id] = 'cf_' . $cfield->id;
					break;
					
				default:
					break;
			}
		}
		
		return $tokens;
	}
	
	protected function _handleSearchSubtotals($view, $subtotals) {
		$subtotal_data = array();
		
		if(is_array($subtotals) && !empty($subtotals)) {
			foreach($subtotals as $subtotal) {
				if(null === ($field = $this->translateToken($subtotal, 'subtotal')))
					$this->error(self::ERRNO_SEARCH_FILTERS_INVALID, sprintf("'%s' is not a valid subtotal token.", $subtotal));
				
				// [TODO] Can we nest this with arbitrary subtotals?  (worker replies -> group)
				$counts = $view->getSubtotalCounts($field);
				
				$subtotal_data[$subtotal] = array();
				
				foreach($counts as $key => $count) {
					$data = array(
						'label' => $count['label'],
						'hits' => intval($count['hits']),
					);
					
					if(0 != strcasecmp($count['label'], $key))
						$data['key'] = $key;
					
					if(isset($count['children']) && !empty($count['children'])) {
						$data['distribution'] = array();
						
						foreach($count['children'] as $child_key => $child) {
							$child_data = array(
								'label' => $child['label'],
								'hits' => intval($child['hits']),
							);
							
							if(0 != strcasecmp($child['label'], $child_key))
								$child_data['key'] = $child_key;
							
							$data['distribution'][] = $child_data;
						}
					}
					
					$subtotal_data[$subtotal][] = $data;
				}
			}
		}
		
		return $subtotal_data;
	}
	
	protected function _getSearchView($context, $params=array(), $limit=10, $page=0, $sort_by=null, $sort_asc=null) {
		$context_ext = Extension_DevblocksContext::get($context);
		$view = $context_ext->getSearchView(DevblocksPlatform::strAlphaNum('api_search_'.$context, '_', '_')); /* @var $view C4_AbstractView */
		
		$view->is_ephemeral = true;
		$view->addParams($params, true);
		$view->renderLimit = $limit;
		$view->renderPage = max(0,$page-1);
		$view->renderSortBy = $sort_by;
		$view->renderSortAsc = $sort_asc;
		$view->renderTotal = true;
		
		if(!empty($sort_by) && !in_array($sort_by, $view->view_columns))
			$view->view_columns[] = $sort_by;
		
		if(is_array($params))
		foreach(array_keys($params) as $k) {
			if(!in_array($k, $view->view_columns))
				$view->view_columns[] = $k;
		}
		
		// [TODO] Cursors? (ephemeral view id, paging, sort, etc)
		
		return $view;
	}
};

interface IExtensionRestController {
	function getContext($model);
	function search($filters=array(), $sortToken='', $sortAsc=1, $page=1, $limit=10, $options=array());
	function translateToken($token, $type='dao');
};
