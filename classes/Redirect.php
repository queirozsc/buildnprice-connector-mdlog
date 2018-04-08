<?php
class Redirect {
	public static function to($location){
		if ($location) {
			if (is_numeric($location)) {
				switch ($location) {
					case 228:
						include 'includes/errors/228.php';
						exit();
					break;
					case 404:
						header('HTTP/1.0 404 Not Found');
						include 'includes/errors/404.php';
						exit();
					break;
					case 503:
						header('HTTP/1.0 503 Forbidden');
						include 'includes/errors/503.php';
						exit();
					break;
					case 528:
						include 'includes/errors/528.php';
						exit();
					break;
				}
			}
			header('Location: ' . $location);
			exit();
		}
	}
}