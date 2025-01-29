<?php
require("../vendor/autoload.php");
use Bluerhinos\phpMQTT;

require "Server.php";
require "Response.php";
require "Request.php";
use LonaHTTP\Server;
use LonaHTTP\Request;
use LonaHTTP\Response;

class Dashboard {
	public phpMQTT $mqtt;
    public mysqli $database;

	public function __construct(){
		//$server = "10.21.5.142";
        $server = "192.168.0.10";
        $port = 1883;
		$clientId = "webinterface";

        $this->database = new mysqli($server, "mqtt", "mqtt", "mqtt");
		$this->mqtt = new phpMQTT($server, $port, $clientId);
		$this->mqtt->connect();

		$topics["home"] = array("qos" => 0, "function" => "procMsg");
		$this->mqtt->subscribe($topics, 0);

		
		$this->run();
	}
	
	public function checkLogin(string $user, string $password){
        $results = $this->database->query("SELECT * FROM users WHERE name = '$user';");

        if($results->num_rows > 0) {
            while($row = $results->fetch_assoc()) {
                $pw = openssl_decrypt($row["password"], "aes-256-cbc", "mqtt", 0);
                $password = str_replace(["\n", "\r"], '', $password);
                $pw = str_replace(["\n", "\r"], '', $pw);
                if ($password == $pw) {
                    return true;
                }

            }
        }

        return false;
    }

	public function run(){
		$pid = pcntl_fork();

		if($pid == -1){
			die("Cannot fork");
		}elseif($pid){
			pcntl_signal(SIGTERM, function () use ($pid) {
        			echo "Parent exiting, killing child process...\n";
        			posix_kill($pid, SIGKILL);
        			exit(0);
    			});
			$webserver = new Server(5050);

			$webserver->get("/", function(Request $request, Response $response){
                if( $request->getSession()["username"] != null &&
                    $request->getSession()["password"] != null) {
                    var_dump($request->getSession());
                    if (!$this->checkLogin($request->getSession()["username"], $request->getSession()["password"]))
                        return $response->redirect("/login");
                }

				$results = $this->database->query("SELECT * FROM (SELECT * FROM mqtt ORDER BY id DESC LIMIT 30) AS subquery ORDER BY id ASC;");

				$data = [];
				$count = 0;
				if($results->num_rows > 0) {
					while($row = $results->fetch_assoc()){
						$data[$count] = $row;
						$count++;
					}
				}

				$response->render("./render.php", [
					"data" => $data
				]);
			});

            $webserver->get('/login', function(Request $request, Response $response) {
                if( $request->getSession()["username"] != null &&
                    $request->getSession()["password"] != null){
                    if($this->checkLogin($request->getSession()["username"], $request->getSession()["password"]))
                        return $response->redirect("/");
                }

                $error = false;
                $message = "";

                if($request->getQueryParams()["error"] != null){
                    $error = true;

                    switch($request->getQueryParams()["error"]){
                        case "wrongLogin":
                            $message = "Wrong username or password";
                            break;
                        default:
                            $message = "An unknown error occurred.";
                            break;
                    }
                }

                $response->render("./login.php", [
                    "error" => $error,
                    "message" => $message
                ]);
            });

            $webserver->get('/register', function(Request $request, Response $response) {
                if( $request->getSession()["username"] != null &&
                    $request->getSession()["password"] != null){
                    if($this->checkLogin($request->getSession()["username"], $request->getSession()["password"]))
                        return $response->redirect("/");
                }

                $error = false;
                $message = "";

                if($request->getQueryParams()["error"] != null){
                    $error = true;

                    switch($request->getQueryParams()["error"]){
                        case "alreadyExists":
                            $message = "A user with that name already exists";
                            break;
                        default:
                            $message = "An unknown error occurred.";
                            break;
                    }
                }

                $response->render("./register.php", [
                    "error" => $error,
                    "message" => $message
                ]);
            });

            $webserver->post('/register', function(Request $request, Response $response) {
                if( $request->getBody()["username"] != null &&
                    $request->getBody()["password"] != null){

                    $user = $request->getBody()['username'];
                    $results = $this->database->query("SELECT * FROM users WHERE name = '$user';");

                    $exist = false;
                    if($results->num_rows > 0) {
                        while($row = $results->fetch_assoc()) {
                            $exist = true;
                        }
                    }

                    if(!$exist) {
                        $password = openssl_encrypt($request->getBody()["password"], "aes-256-cbc", "mqtt", 0);
                        $conn->query("INSERT INTO users (name, password) VALUES ('$user', '$password')");
                        $response->setSessionValue("username", $request->getBody()['username']);
                        $response->setSessionValue("password", str_replace("\r\n", "", $request->getBody()['password']));
                        return $response->redirect("/");
                    }
                }

                $response->redirect("/login?error=wrongLogin");
            });

            $webserver->post('/login', function(Request $request, Response $response) {
                if( $request->getBody()["username"] != null &&
                    $request->getBody()["password"] != null){
                    if($this->checkLogin($request->getBody()["username"], $request->getBody()["password"])){
                        $response->setSessionValue("username", $request->getBody()['username']);
                        $response->setSessionValue("password", str_replace("\r\n", "", $request->getBody()['password']));
                        return $response->redirect("/");
                    }
                }

                $response->redirect("/login?error=wrongLogin");
            });

            $webserver->get('/logout', function(Request $request, Response $response) {
                $response->setSessionValue("username", null);
                $response->setSessionValue("password", null);
                $response->redirect("/login");
            });

            $webserver->get("/stylesheet.css", function(Request $request, Response $response) {
                $response->send(file_get_contents("stylesheet.css"), "text/css");
            });

            $webserver->get("/login.css", function(Request $request, Response $response) {
                $response->send(file_get_contents("login.css"), "text/css");
            });

            $webserver->listen();
		}else{
			while($this->mqtt->proc()){}
			$this->mqtt->close();
		}
	}
}

$dashboard = new Dashboard();

function procMsg($topic, $msg){
	$data = json_decode(str_replace("'", '"' ,$msg), true);
	//$server = "10.21.5.142";
    $server = "192.168.0.10";
    $username = "mqtt";
	$password = "mqtt";
	$database = "mqtt";

	$conn = new mysqli($server, $username, $password, $database);

	$light = $data["ambient_light"];
	$humidity = $data["humidity"];
    $temperature = $data["temperature"];+
    $motion = $data["motion"];
    $pressure = $data["pressure"];
    $time = $data["time"];

	$conn->query("INSERT INTO mqtt (Light, Humidity, Temperature, Motion, Pressure, Time) VALUES ($light, $humidity, $temperature, $motion, $pressure, '$time');");

	$conn->close();
	unset($conn);
}
