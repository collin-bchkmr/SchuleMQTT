<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temperaturdiagramm</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mqtt/dist/mqtt.min.js"></script>

</head>
<body>
    <h1>Temperaturdiagramm (Celsius)</h1>
    <canvas id="temperatureChart" width="600" height="200"></canvas>

    <h1>Luftfeuchtigkeit</h1>
    <canvas id="humidityChart" width="600" height="200"></canvas>

    <h1>Licht</h1>
    <canvas id="lightChart" width=600" height=200"></canvas>

    <script>
	const scandata = <?php echo json_encode($arguments["data"], JSON_PRETTY_PRINT) ?>;
	// Vorgabedaten für Temperaturen (JSON)
	scandata.forEach(element => {
	    console.log(element["Temperature"]);
	});
	const temperatureData = [];
	const lightData = [];
	const humidityData = [];

	scandata.forEach(element => {
	    temperatureData.push({"time": element["Time"], "temperature": element["Temperature"]});
	    lightData.push({"time": element["Time"], "light": element["Light"]});
	    humidityData.push({"time": element["Time"], "humidity": element["Humidity"]});
	});

        const temperatureLabels = temperatureData.map(item => item.time);
        const temperatureDataMapped = temperatureData.map(item => item.temperature);
        const lightLabels = lightData.map(item => item.time);
	const lightDataMapped = lightData.map(item => item.light);
	const humidityLabels = humidityData.map(item => item.time);
	const humidityDataMapped = humidityData.map(item => item.humidity);

        // Canvas für das Diagramm abrufen
        const ctxTemperature = document.getElementById('temperatureChart').getContext('2d');
	const ctxLight = document.getElementById('lightChart').getContext('2d');
        const ctxHumidity = document.getElementById('humidityChart').getContext('2d');

        // Diagramm erstellen
        const temperatureChart = new Chart(ctxTemperature, {
            type: 'line', // Liniendiagramm
            data: {
                labels: temperatureLabels, // x-Achsen-Beschriftung (Datum)
                datasets: [{
                    label: 'Temperatur (°C)', // Legende
                    data: temperatureDataMapped, // y-Achsen-Daten (Temperatur)
                    borderColor: '#000000', // Linienfarbe
                    fill: false, // Kein Hintergrund unter der Linie
                    tension: 0.1 // Linie mit leichter Abrundung
                }]
            },
            options: {
                responsive: true, // Diagramm passt sich an Bildschirmgröße an
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Datum'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Temperatur (°C)'
                        },
                        beginAtZero: true // Beginnt nicht bei 0, sondern bei den tatsächlichen Temperaturen
                    }
                }
            }
	});

	const lightChart = new Chart(ctxLight, {
	    type: 'line',
	    data: {
        	    labels: lightLabels,
		    datasets: [{
                        label: 'Licht', // Legende
                        data: lightDataMapped, // y-Achsen-Daten (Temperatur)
                        borderColor: 'rgba(75, 192, 192, 1)', // Linienfarbe
                        fill: false, // Kein Hintergrund unter der Linie
                        tension: 0.1 // Linie mit leichter Abrundung
                }]

	},
		options: {
                responsive: true, // Diagramm passt sich an Bildschirmgröße an
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Datum'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Licht (lx)'
                        },
                        beginAtZero: true // Beginnt nicht bei 0, sondern bei den tatsächlichen Temperaturen
                    }
                }
            }

	});

const humidityChart = new Chart(ctxHumidity, {
            type: 'line',
            data: {
                    labels: humidityLabels,
                    datasets: [{
                        label: 'Humidity', // Legende
                        data: humidityDataMapped, // y-Achsen-Daten (Temperatur)
                        borderColor: 'rgba(75, 192, 192, 1)', // Linienfarbe
                        fill: false, // Kein Hintergrund unter der Linie
                        tension: 0.1 // Linie mit leichter Abrundung
                }]

        },
                options: {
                responsive: true, // Diagramm passt sich an Bildschirmgröße an
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Datum'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Luftfeuchtigkeit (%)'
                        },
                        beginAtZero: true // Beginnt nicht bei 0, sondern bei den tatsächlichen Temperaturen
                    }
                }
            }

        });	

	function updateChartData(newData) {
            // Löschen des ältesten Datums
		temperatureData.shift();  // Entfernt das erste Element des Arrays
		lightData.shift();
                humidityData.shift();

            // Fügen Sie das neue Datenobjekt hinzu
            temperatureData.push({"time": newData["time"], "temperature": newData["temperature"]});
	    lightData.push({"time": newData["time"], "light": newData["light"]});
	    humidityData.push({"time": newData["time"], "humidity": newData["humidity"]});

            // Daten des Diagramms aktualisieren
            temperatureChart.data.labels = temperatureData.map(item => item.time);
            temperatureChart.data.datasets[0].data = temperatureData.map(item => item.temperature);

	    lightChart.data.labels = lightData.map(item => item.time);
	    lightChart.data.datasets[0].data = lightData.map(item => item.light);

	    humidityChart.data.labels = humidityData.map(item => item.time);
	    humidityChart.data.datasets[0].data = humidityData.map(item => item.humidity);
            // Diagramm neu rendern
	    temperatureChart.update();
	    lightChart.update();
	    humidityChart.update();
	}

	// MQTT-Verbindung herstellen
const client = mqtt.connect('ws://10.21.5.142:8080');  // Beispiel-URL für den Broker

client.on('connect', function () {
    console.log('Connected to MQTT Broker');
    client.subscribe('home', function (err) {
        if (err) {
            console.log('Error subscribing to topic:', err);
        }
    });
});

// Wenn eine neue Nachricht empfangen wird
client.on('message', function (topic, message) {
	if (topic === 'home') {
		console.log(message.toString().replaceAll("'", '"'));
        // Nehmen Sie die Temperaturdaten aus der Nachricht
        let newDataJSON = JSON.parse(message.toString().replaceAll("'", '"'));  // Erwarte, dass die Nachricht als JSON gesendet wird

		let time = new Date();
	time = time.toISOString().slice(0,19).replace("T", " ");	
        // Das Datum in einem für das Diagramm verwendbaren Format erstellen
        newData = {
            "time": time,  // Aktuelles Datum und Uhrzeit
		    "temperature": newDataJSON["temperature"],  // Temperatur aus der MQTT-Nachricht
		    "light": newDataJSON["ambient_light"],
		    "humidity": newDataJSON["humidity"]
	};

	console.log(newData);

        // Diagramm mit den neuen Daten aktualisieren
        updateChartData(newData);
    }
});
    </script>
</body>
</html>

