<?php

	class DeviceAPI{
		
		function request(){
			if(isset($_POST['action'])){
				if($_POST['auth']=="password"){
					if(method_exists($this, $_POST['action'])){
						call_user_func(array($this, $_POST['action']));
					} else {
						$this->sendResponse(400, 'Invalid request');
					}
				} else {
					$this->sendResponse(403, 'Authentication token invalid');
				}
			} else {
				$this->sendResponse(400, 'Invalid request, action required');
			}
		}

		// Helper method to get a string description for an HTTP status code
		// From http://www.gen-x-design.com/archives/create-a-rest-api-with-php/ 
		function getStatusCodeMessage($status)
		{
		    // these could be stored in a .ini file and loaded
		    // via parse_ini_file()... however, this will suffice
		    // for an example
		    $codes = Array(
		        100 => 'Continue',
		        101 => 'Switching Protocols',
		        200 => 'OK',
		        201 => 'Created',
		        202 => 'Accepted',
		        203 => 'Non-Authoritative Information',
		        204 => 'No Content',
		        205 => 'Reset Content',
		        206 => 'Partial Content',
		        300 => 'Multiple Choices',
		        301 => 'Moved Permanently',
		        302 => 'Found',
		        303 => 'See Other',
		        304 => 'Not Modified',
		        305 => 'Use Proxy',
		        306 => '(Unused)',
		        307 => 'Temporary Redirect',
		        400 => 'Bad Request',
		        401 => 'Unauthorized',
		        402 => 'Payment Required',
		        403 => 'Forbidden',
		        404 => 'Not Found',
		        405 => 'Method Not Allowed',
		        406 => 'Not Acceptable',
		        407 => 'Proxy Authentication Required',
		        408 => 'Request Timeout',
		        409 => 'Conflict',
		        410 => 'Gone',
		        411 => 'Length Required',
		        412 => 'Precondition Failed',
		        413 => 'Request Entity Too Large',
		        414 => 'Request-URI Too Long',
		        415 => 'Unsupported Media Type',
		        416 => 'Requested Range Not Satisfiable',
		        417 => 'Expectation Failed',
		        500 => 'Internal Server Error',
		        501 => 'Not Implemented',
		        502 => 'Bad Gateway',
		        503 => 'Service Unavailable',
		        504 => 'Gateway Timeout',
		        505 => 'HTTP Version Not Supported'
		    );
		 
		    return (isset($codes[$status])) ? $codes[$status] : '';
		}
		 
		// Helper method to send a HTTP response code/message
		function sendResponse($status = 200, $body = '', $content_type = 'text/html')
		{
		    $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->getStatusCodeMessage($status);
		    header($status_header);
		    header('Content-type: ' . $content_type);
		    echo $body;
		}

		function device_liked(){
			// Check for required parameters
			if(isset($_POST['device_id'])){
				// Put parameters into local variables
				$sender_device_id = $_POST['device_id'];
				// Get connection to db
				$con = getConnector();
				// Update senders number of 'have_liked' in database by +1
				$stmt = $con->prepare("UPDATE instals SET have_liked=have_liked+1 WHERE device_id=?");
				$stmt->bind_param("s", $_POST['device_id']);
				$stmt->execute();
				$stmt->close();
				// Select random device from database
				$stmt = $con->prepare("SELECT device_id FROM instals ORDER BY RAND() LIMIT 1");
				$stmt->execute();
				$stmt->bind_result($reciver_device_id);
				while ($stmt->fetch()){
					break;
				}
				$stmt->close();
				// Update recivers number of 'been_liked' in database by +1
				$stmt = $con->prepare("UPDATE instals SET been_liked=been_liked+1 WHERE device_id=?");
				$stmt->bind_param("s", $reciver_device_id);
				$stmt->execute();
				// Close db connection
				$con->close();

				// Return sender/reciver device ids, encoded with JSON
		        $result = array(
		            "sender_device_id" => $sender_device_id,
		            "reciver_device_id" => $reciver_device_id);
		        $this->sendResponse(202, json_encode($result));
		        return true;
			}
			$this->sendResponse(400, 'Invalid request');
		    return false;
		}

		function create_device(){
			if(isset($_POST['device_id']) && isset($_POST['device_type']) && isset($_POST['app_ver'])){
				// Get connection to db
				$con = getConnector();
				// Insert new entry into db
				$stmt = $con->prepare("INSERT INTO instals(device_id,app_ver,device_type) VALUES(?,?,?)");
				$stmt->bind_param("sis", $_POST['device_id'], $_POST['app_ver'], $_POST['device_type']);
				$stmt->execute();
				$stmt->close();

				// Select new device from database
				$stmt = $con->prepare("SELECT * FROM instals WHERE device_id=?");
				$stmt->bind_param("s", $_POST['device_id']);
				$stmt->execute();
				$stmt->bind_result($id, $have_liked, $been_liked, $device_id, $app_ver, $device_type);
				while ($stmt->fetch()){
					$output[]=array(
						'id'=>$id,
						'device_id'=>$device_id,
						'have_liked'=>$have_liked,
						'been_liked'=>$been_liked,
						'app_ver'=>$app_ver,
						'device_type'=>$device_type
						);
				}
				$stmt->close();

				// Close db connection
				$con->close();

				// Return instal instance, encoded with JSON
		        $result = $output;
		        $this->sendResponse(201, json_encode($result));
		        return true;
			}
			$this->sendResponse(400, 'Invalid request');
		    return false;
		}

		function delete_device(){
			if(isset($_POST['device_id'])){
				// Get connection to db
				$con = getConnector();
				// Remove entry from db
				$stmt = $con->prepare("DELETE FROM instals WHERE device_id=?");
				$stmt->bind_param("s", $_POST['device_id']);
				$stmt->execute();
				$stmt->close();
				// Close db connection
				$con->close();

				$this->sendResponse(200, "device removed");
		        return true;
			}
			$this->sendResponse(400, 'Invalid request');
		    return false;
		}

		function read_device(){
			if(isset($_POST['device_id'])){
				// Get connection to db
				$con = getConnector();
				// Remove entry from db
				$stmt = $con->prepare("SELECT * FROM instals WHERE device_id=?");
				$stmt->bind_param("s", $_POST['device_id']);
				$stmt->execute();

				if ($stmt->fetch()) {
    				$stmt->execute();
    				$stmt->bind_result($id, $have_liked, $been_liked, $device_id, $app_ver, $device_type);
					while ($stmt->fetch()){
						$output[]=array(
							'id'=>$id,
							'device_id'=>$device_id,
							'have_liked'=>$have_liked,
							'been_liked'=>$been_liked,
							'app_ver'=>$app_ver,
							'device_type'=>$device_type
							);
					}
					$stmt->close();
					// Close db connection
					$con->close();
					// Return instal instance, encoded with JSON
			        $result = $output;
			        $this->sendResponse(200, json_encode($result));
			        return true;
				} else {
    				$this->sendResponse(404, 'Not Found');
		    		return false;
				}
				
		    }
		    $this->sendResponse(400, 'Invalid request');
		    return false;
		}
	}

	ini_set('display_errors',1);
	ini_set('display_startup_errors',1);
	error_reporting(-1);

	include 'connector.php';

	$api = new DeviceAPI;
	$api->request();
?>