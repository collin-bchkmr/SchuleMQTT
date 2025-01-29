<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensordaten Überwachung</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mqtt/dist/mqtt.min.js"></script>
    <link rel="stylesheet" href="stylesheet.css">

</head>
<body>
<div class="container">
    <h1>Sensordaten Überwachung</h1>

    <div class="card hover">
        <div class="toggleTemp hover" onclick="toggleChart('temperatureChart')"><h1 id="temperatureCurrent">Temperatur</h1></div>
        <canvas id="temperatureChart" width="600" height="200" style="display: none;"></canvas>
    </div>

    <div class="card hover">
        <div class="toggleHumidity hover" onclick="toggleChart('humidityChart')"><h1 id="humidityCurrent">Luftfeuchtigkeit</h1></div>
        <canvas id="humidityChart" width="600" height="200" style="display: none;"></canvas>
    </div>

    <div class="card hover">
        <div class="toggleLight hover" onclick="toggleChart('lightChart')"><h1 id="lightCurrent">Licht</h1></div>
        <canvas id="lightChart" width="600" height="200" style="display: none;"></canvas>
    </div>

    <div class="card hover">
        <div class="toggleMotion hover" onclick="toggleChart('motionChart')"><h1 id="motionCurrent">Bewegung</h1></div>
        <canvas id="motionChart" width="600" height="200" style="display: none;"></canvas>
    </div>

    <div class="card hover">
        <div class="togglePressure hover" onclick="toggleChart('pressureChart')"><h1 id="pressureCurrent">Luftdruck</h1></div>
        <canvas id="pressureChart" width="600" height="200" style="display: none;"></canvas>
    </div>

</div>

<script>
    function toggleChart(id) {
        let chart = document.getElementById(id);
        chart.style.display = chart.style.display === 'block' ? 'none' : 'block';
    }

    const client = mqtt.connect('ws://10.21.5.142:8080');
    let latestData = {};

    client.on('connect', function () {
        console.log('Connected to MQTT Broker');
        client.subscribe('home');
    });

    client.on('message', function (topic, message) {
        if (topic === 'home') {
            let newDataJSON = JSON.parse(message.toString().replaceAll("'", '"'));
            let time = new Date().toISOString().slice(0, 19).replace("T", " ");

            let motion;
            if(newDataJSON["motion"]) motion = "Ja";
            else motion = "Nein";

            latestData = {
                "time": time,
                "temperature": newDataJSON["temperature"],
                "light": newDataJSON["ambient_light"],
                "humidity": newDataJSON["humidity"],
                "pressure": newDataJSON["pressure"],
                "motion": newDataJSON["motion"]
            };

            document.getElementById("temperatureCurrent").innerText = `Temperatur: ${latestData.temperature}°C`;
            document.getElementById("humidityCurrent").innerText = `Luftfeuchtigkeit: ${latestData.humidity}%`;
            document.getElementById("lightCurrent").innerText = `Licht: ${latestData.light} lx`;
            document.getElementById("motionCurrent").innerText = `Bewegung: ${motion} `;
            document.getElementById("pressureCurrent").innerText = `Luftdruck: ${latestData.pressure} hPa`;
            updateChartData(latestData);
        }
    });

    const temperatureData = [];
    const lightData = [];
    const humidityData = [];
    const motionData = [];
    const pressureData = [];

    const scandata = <?php echo json_encode($arguments["data"], JSON_PRETTY_PRINT) ?>;
    // Vorgabedaten für Temperaturen (JSON)
    scandata.forEach(element => {
        console.log(element["Temperature"]);
    });

    scandata.forEach(element => {
        temperatureData.push({"time": element["Time"], "temperature": element["Temperature"]});
        lightData.push({"time": element["Time"], "light": element["Light"]});
        humidityData.push({"time": element["Time"], "humidity": element["Humidity"]});
        motionData.push({"time": element["Time"], motion: "Nein"});
        pressureData.push({"time": element["Time"], pressure: 0});
    });

    const temperatureLabels = temperatureData.map(item => item.time);
    const temperatureDataMapped = temperatureData.map(item => item.temperature);
        const lightLabels = lightData.map(item => item.time);
        const lightDataMapped = lightData.map(item => item.light);
        const humidityLabels = humidityData.map(item => item.time);
        const humidityDataMapped = humidityData.map(item => item.humidity);
        const pressureLabels = pressureData.map(item => item.time);
        const pressureDataMapped = pressureData.map(item => item.pressure);
        const motionLabels = humidityData.map(item => item.time);
        const motionDataMapped = humidityData.map(item => item.motion);

    function updateChartData(newData) {
        temperatureData.shift();
        lightData.shift();
        humidityData.shift();
        motionData.shift();
        pressureData.shift();

        temperatureData.push({"time": newData.time, "temperature": newData.temperature});
        lightData.push({"time": newData.time, "light": newData.light});
        humidityData.push({"time": newData.time, "humidity": newData.humidity});
        pressureData.push({"time": newData.time, "pressure": newData.pressure});
        motionData.push({"time": newData.time, "motion": newData.motion});

        temperatureChart.data.labels = temperatureData.map(item => item.time);
        temperatureChart.data.datasets[0].data = temperatureData.map(item => item.temperature);
        lightChart.data.labels = lightData.map(item => item.time);
        lightChart.data.datasets[0].data = lightData.map(item => item.light);
        humidityChart.data.labels = humidityData.map(item => item.time);
        humidityChart.data.datasets[0].data = humidityData.map(item => item.humidity);;
        motionChart.data.labels = motionData.map(item => item.time);
        motionChart.data.datasets[0].data = motionData.map(item => item.motion);
        pressureChart.data.labels = pressureData.map(item => item.time);
        pressureChart.data.datasets[0].data = pressureData.map(item => item.pressure);

        temperatureChart.update();
        lightChart.update();
        humidityChart.update();
        pressureChart.update();
        motionChart.update();
    }

    const ctxTemperature = document.getElementById('temperatureChart').getContext('2d');
        const ctxLight = document.getElementById('lightChart').getContext('2d');
        const ctxHumidity = document.getElementById('humidityChart').getContext('2d');
        const ctxPressure = document.getElementById('pressureChart').getContext('2d');
        const ctxMotion = document.getElementById('motionChart').getContext('2d');

    const temperatureChart = new Chart(ctxTemperature, {
        type: 'line',
        data: { labels: temperatureLabels, datasets: [{ label: 'Temperatur (°C)', data: temperatureDataMapped, borderColor: '#2196f3', fill: false, tension: 0.1 }] },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const lightChart = new Chart(ctxLight, {
        type: 'line',
        data: { labels: lightLabels, datasets: [{ label: 'Licht (Lx)', data: lightDataMapped, borderColor: '#d91e37', fill: false, tension: 0.1 }] },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const humidityChart = new Chart(ctxHumidity, {
        type: 'line',
        data: { labels: humidityLabels, datasets: [{ label: 'Luftfeuchtigkeit (%)', data: humidityDataMapped, borderColor: '#4caf50', fill: false, tension: 0.1 }] },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const motionChart = new Chart(ctxMotion, {
        type: 'line',
        data: { labels: motionLabels, datasets: [{ label: 'Bewegung', data: motionDataMapped, borderColor: '#800080FF', fill: false, tension: 0.1 }] },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const pressureChart = new Chart(ctxPressure, {
        type: 'line',
        data: { labels: pressureLabels, datasets: [{ label: 'Luftdruck (hPa)', data: pressureDataMapped, borderColor: '#FF1493FF', fill: false, tension: 0.1 }] },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>
</body>
</html>
