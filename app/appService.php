<?php
class appService extends core {
	public function boot() {
		// Собственные методы
	}
	public function register() {
		
		app::singleton('connectClass', function(){
			app::include('app.classes.connectClass');
			return new connectClass;
		});
		
		app::singleton('itemsClass', function(){
			app::include('app.classes.itemsClass');
			return new itemsClass;
		});
		
		app::singleton('catalogClass', function(){
			app::include('app.classes.catalogClass');
			return new catalogClass;
		});
		
		app::singleton('propsClass', function(){
			app::include('app.classes.propsClass');
			return new propsClass;
		});
		
		app::singleton('ordersClass', function(){
			app::include('app.classes.ordersClass');
			return new ordersClass;
		});
		
		exceptions::declare('validate',function($errors){
			return response()->json(['status'=>false,'errors'=>$errors],403);
		});
		
		exceptions::declare('auth',function(){
			
			$http_digest_parse = function($txt) {
				// защита от отсутствующих данных
				$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
				$data = array();
				$keys = implode('|', array_keys($needed_parts));

				preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

				foreach ($matches as $m) {
					$data[$m[1]] = $m[3] ? $m[3] : $m[4];
					unset($needed_parts[$m[1]]);
				}

				return $needed_parts ? false : $data;
			};
			
			$authHeader = function($key) {
				header('HTTP/1.1 401 Unauthorized');
				header('WWW-Authenticate: Digest realm="'.$key.
					   '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($key).'"');
					   return true;
			};
			
			$login = config('AUTH_LOGIN');
			$pass = config('AUTH_PASSWORD');
			
			$users = [
					$login => $pass
				];
			
			$key = 'oti8k1iqcy3NCaot';
			
			if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
				$authHeader($key);
				return true;
			}
			
			if (!($data = $http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) ||
				!isset($users[$data['username']])) {
					
					$authHeader($key);
					return true;
				}
			
			$A1 = md5($data['username'] . ':' . $key . ':' . $users[$data['username']]);
			$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
			$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
			
			
			if ($data['response'] != $valid_response) {
				$authHeader($key);
				return true;
			}
			
			return false;
		});
		
		// 404
		exceptions::declare(404,function(){
			return response()->json(['status'=>false,'message'=>'Not found'],404);
		});
		
		// 405
		exceptions::declare(405,function(){
			return response()->json(['status'=>false,'message'=>'Method not allowed'],405);
		});
		
		// 500
		exceptions::declare(500,function(){
			return response()->json(['status'=>false,'message'=>'Internal Server Error'],405);
		});
	}
}