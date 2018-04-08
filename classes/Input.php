<?php
class Input {
	public static function exists($type='post'){
		switch ($type) {
			case 'post':
				return (!empty($_POST) ? true : false);
			break;
			case 'get':
				return (!empty($_GET) ? true : false);
			break;
			case 'put':
				return (!empty($_PUT) ? true : false);
			break;
			case 'file':
				return (!empty($_FILES) ? true : false);
			break;
			default:
				return false;
			break;
		}
	}

	public static function get($item){
		if (isset($_POST[$item])) {
			return $_POST[$item];
		} else if(isset($_GET[$item])){
			return $_GET[$item];
		} else if(isset($_PUT[$item])){
			return $_PUT[$item];
		} else if(isset($_FILES[$item])){
			return $_FILES[$item];
		}
		return '';
	}
}