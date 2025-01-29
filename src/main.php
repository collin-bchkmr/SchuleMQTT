<?php
require("../vendor/autoload.php");
use Bluerhinos\phpMQTT;

require "Server.php";
require "Response.php";
require "Request.php";
use LonaHTTP\Server;
use LonaHTTP\Request;
use LonaHTTP\Response;

require "Client.php";

class Dashboard {
	public LonaDB $client;
	public phpMQTT $mqtt;

	public function __construct(){
		$server = "10.21.5.142";
		$port = 1883;
		$clientId = "webinterface";

		$this->mqtt = new phpMQTT($server, $port, $clientId);
		$this->mqtt->connect();

		$topics["home"] = array("qos" => 0, "function" => "procMsg");
		$this->mqtt->subscribe($topics, 0);

		$this->client = new LonaDB("vserver.lona-development.org", 2040, "root", "test");
		$test = $this->client->createTable("mqtt");
		var_dump($test);
		
		$this->run();
	}
	
	public function procMsg($topic, $msg){
		echo "A";
		$this->client->set("mqtt", "test", $msg);
		echo("$topic: $msg\n");
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
                $server = "10.21.5.142";
                $username = "mqtt";
                $password = "mqtt";
                $database = "mqtt";

				$conn = new mysqli($server, $username, $password, $database);

				$results = $conn->query("SELECT * FROM (SELECT * FROM mqtt ORDER BY id DESC LIMIT 30) AS subquery ORDER BY id ASC;");

				var_dump($results);
				$data = [];
				$count = 0;
				if($results->num_rows > 0) {
					while($row = $results->fetch_assoc()){
						$data[$count] = $row;
						$count++;
					}
				}
				var_dump($data);

				$conn->close();

				$response->render("./render.php", [
					"data" => $data
				]);
			});

            $webserver->get("/login", function(Request $request, Response $response) {
                $response->render("./login.php");
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
	$server = "10.21.5.142";
	$username = "mqtt";
	$password = "mqtt";
	$database = "mqtt";

	$conn = new mysqli($server, $username, $password, $database);

	$light = $data["ambient_light"];
	$humidity = $data["humidity"];
	$temperature = $data["temperature"];

	$conn->query("INSERT INTO mqtt (Light, Humidity, Temperature, Time) VALUES ($light, $humidity, $temperature, NOW());");

	$conn->close();
	unset($conn);
}
