<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Dashboard</title>
    <style>
        body { font-family: Arial; background: #1a1a2e; color: white; padding: 20px; }
        .card {
            background: #16213e; border-radius: 10px; padding: 20px;
            margin: 10px; display: inline-block; min-width: 150px; text-align: center;
        }
        .value { font-size: 2em; color: #00d4ff; font-weight: bold; }
        .label { font-size: 0.9em; color: #aaa; margin-top: 5px; }
        .status-bar {
            display: inline-flex; align-items: center; gap: 10px;
            background: #16213e; border-radius: 10px;
            padding: 12px 20px; margin-bottom: 20px;
        }
        .dot { width: 12px; height: 12px; border-radius: 50%; }
        .dot.online  { background: #00ff88; box-shadow: 0 0 8px #00ff88; }
        .dot.offline { background: #ff4444; box-shadow: 0 0 8px #ff4444; }
        .dot.stale   { background: #ffaa00; box-shadow: 0 0 8px #ffaa00; }
        .warning-banner {
            background: #ff4444; color: white; font-weight: bold;
            border-radius: 10px; padding: 20px;
            margin-bottom: 20px; font-size: 1.1em;
        }
        .warning-banner .missing {
            margin-top: 10px; font-size: 0.9em;
            font-weight: normal; opacity: 0.9;
        }
        .sensor-status {
            background: #16213e; border-radius: 10px;
            padding: 15px 20px; margin-bottom: 20px;
            display: inline-block;
        }
        .sensor-status span { margin-right: 15px; font-size: 0.85em; }
        .ok   { color: #00ff88; }
        .fail { color: #ff4444; }
        .halted { color: #aaa; font-style: italic; }
        #cards-wrap { display: none; }
    </style>
</head>
<body>
    <h1>ESP32 Sensor Dashboard</h1>

    <div class="status-bar">
        <div class="dot offline" id="status-dot"></div>
        <span id="status-text">Loading...</span>
    </div>

    <br>

    <div class="sensor-status" id="sensor-status-row" style="display:none;">
        <span id="s-dht"></span>
        <span id="s-rain"></span>
        <span id="s-pir"></span>
        <span id="s-sonar"></span>
        <span id="s-light"></span>
    </div>

    <br>

    <div id="warning-banner" class="warning-banner" style="display:none;">
        🚨 SYSTEM HALTED — Sensor(s) Disconnected!
        <div class="missing">
            Missing: <strong id="missing-list"></strong><br>
            All sensors must be connected for the system to operate.
        </div>
    </div>

    <div id="cards-wrap">
        <div class="card"><div class="value" id="v-temp">--</div><div class="label">Temperature</div></div>
        <div class="card"><div class="value" id="v-hum">--</div><div class="label">Humidity</div></div>
        <div class="card"><div class="value" id="v-rain">--</div><div class="label">Rain</div></div>
        <div class="card"><div class="value" id="v-motion">--</div><div class="label">Motion</div></div>
        <div class="card"><div class="value" id="v-dist">--</div><div class="label">Distance</div></div>
        <div class="card"><div class="value" id="v-light">--</div><div class="label">Light</div></div>
    </div>

    <p id="waiting-msg">Waiting for ESP32 data...</p>

    <p style="color:#555; margin-top:30px;">Last refreshed: <span id="refresh-time"></span></p>

    <script>
        const POLL_INTERVAL = 3000; // ms — adjust for how "real-time-ish" you want it

        function setStatus(dotClass, text) {
            const dot = document.getElementById('status-dot');
            dot.className = 'dot ' + dotClass;
            document.getElementById('status-text').textContent = text;
        }

        function setSensorBadge(id, label, ok) {
            const el = document.getElementById(id);
            el.textContent = (ok ? '✅ ' : '❌ ') + label;
            el.className = ok ? 'ok' : 'fail';
        }

        async function poll() {
            try {
                const res = await fetch('sensor_data.php', { cache: 'no-store' });
                const data = await res.json();

                document.getElementById('refresh-time').textContent = new Date().toLocaleString();

                if (data.error) {
                    setStatus('offline', data.error === 'No data yet' ? 'No Data Received' : data.error);
                    document.getElementById('sensor-status-row').style.display = 'none';
                    document.getElementById('warning-banner').style.display = 'none';
                    document.getElementById('cards-wrap').style.display = 'none';
                    document.getElementById('waiting-msg').style.display = 'block';
                    return;
                }

                const secondsAgo = Math.floor(Date.now() / 1000) - (data._last_modified || 0);
                let dotClass, statusText;
                if (secondsAgo <= 15) {
                    dotClass = 'online'; statusText = 'ESP32 Connected';
                } else if (secondsAgo <= 60) {
                    dotClass = 'stale'; statusText = `ESP32 Possibly Disconnected (${secondsAgo}s ago)`;
                } else {
                    dotClass = 'offline'; statusText = `ESP32 Offline (${secondsAgo}s ago)`;
                }
                setStatus(dotClass, statusText);

                document.getElementById('sensor-status-row').style.display = 'inline-block';
                setSensorBadge('s-dht',   'DHT11',    data.dht_ok   === true);
                setSensorBadge('s-rain',  'Raindrop', data.rain_ok  === true);
                setSensorBadge('s-pir',   'PIR',      data.pir_ok   === true);
                setSensorBadge('s-sonar', 'HC-SR04',  data.sonar_ok === true);
                setSensorBadge('s-light', 'LM393',    data.light_ok === true);

                document.getElementById('waiting-msg').style.display = 'none';

                if (data.system_ok !== true) {
                    document.getElementById('warning-banner').style.display = 'block';
                    document.getElementById('missing-list').textContent = data.missing || '';
                    document.getElementById('cards-wrap').style.display = 'none';
                } else {
                    document.getElementById('warning-banner').style.display = 'none';
                    document.getElementById('cards-wrap').style.display = 'block';

                    document.getElementById('v-temp').textContent   = (data.temperature  ?? 'N/A') + ' °C';
                    document.getElementById('v-hum').textContent    = (data.humidity     ?? 'N/A') + ' %';
                    document.getElementById('v-rain').textContent   = (data.rain_status  ?? 'N/A');
                    document.getElementById('v-motion').textContent = (data.motion       ?? 'N/A');
                    document.getElementById('v-dist').textContent   = (data.distance     ?? 'N/A') + ' cm';
                    document.getElementById('v-light').textContent  = (data.light        ?? 'N/A');
                }

            } catch (err) {
                setStatus('offline', 'Connection error');
            }
        }

        poll();
        setInterval(poll, POLL_INTERVAL);
    </script>
</body>
</html>