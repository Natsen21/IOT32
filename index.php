<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Dashboard</title>
    <style>
        body { font-family: Arial; background: #1a1a2e; color: white; padding: 20px; }
        .device {
            background: #0f1a30; border-radius: 14px; padding: 20px;
            margin-bottom: 25px;
        }
        .device h2 { margin-top: 0; color: #00d4ff; }
        .device h2 small { color: #888; font-weight: normal; font-size: 0.6em; }
        .card {
            background: #16213e; border-radius: 10px; padding: 20px;
            margin: 10px; display: inline-block; min-width: 150px; text-align: center;
        }
        .value { font-size: 2em; color: #00d4ff; font-weight: bold; }
        .label { font-size: 0.9em; color: #aaa; margin-top: 5px; }
        .status-bar {
            display: inline-flex; align-items: center; gap: 10px;
            background: #16213e; border-radius: 10px;
            padding: 12px 20px; margin-bottom: 15px;
        }
        .dot { width: 12px; height: 12px; border-radius: 50%; }
        .dot.online  { background: #00ff88; box-shadow: 0 0 8px #00ff88; }
        .dot.offline { background: #ff4444; box-shadow: 0 0 8px #ff4444; }
        .dot.stale   { background: #ffaa00; box-shadow: 0 0 8px #ffaa00; }
        .warning-banner {
            background: #ff4444; color: white; font-weight: bold;
            border-radius: 10px; padding: 20px;
            margin-bottom: 15px; font-size: 1.1em; display: none;
        }
        .warning-banner .missing {
            margin-top: 10px; font-size: 0.9em;
            font-weight: normal; opacity: 0.9;
        }
        .sensor-status {
            background: #16213e; border-radius: 10px;
            padding: 15px 20px; margin-bottom: 15px;
            display: none;
        }
        .sensor-status span { margin-right: 15px; font-size: 0.85em; }
        .ok   { color: #00ff88; }
        .fail { color: #ff4444; }
        .cards-wrap { display: none; }
        .waiting-msg { color: #aaa; }
    </style>
</head>
<body>
    <h1>ESP32 Sensor Dashboard</h1>
    <div id="devices"></div>

    <p style="color:#555; margin-top:30px;">Last refreshed: <span id="refresh-time"></span></p>

    <script>
        const POLL_INTERVAL = 3000; // ms

        // Each device: which sensors it has (badge) and which cards to show.
        // Keys must match the JSON fields the ESP32 sends.
        const DEVICES = {
            1: {
                name: 'Device 1',
                subtitle: 'Ultrasonic · Light · DHT11',
                sensors: [
                    { ok: 'dht_ok',   label: 'DHT11' },
                    { ok: 'sonar_ok', label: 'HC-SR04' },
                    { ok: 'light_ok', label: 'LM393' },
                ],
                cards: [
                    { key: 'temperature', label: 'Temperature', unit: ' °C' },
                    { key: 'humidity',    label: 'Humidity',    unit: ' %' },
                    { key: 'distance',    label: 'Distance',    unit: ' cm' },
                    { key: 'light',       label: 'Light',       unit: '' },
                ],
            },
            2: {
                name: 'Device 2',
                subtitle: 'Rain · Motion · DHT11',
                sensors: [
                    { ok: 'dht_ok',  label: 'DHT11' },
                    { ok: 'rain_ok', label: 'Raindrop' },
                    { ok: 'pir_ok',  label: 'PIR' },
                ],
                cards: [
                    { key: 'temperature', label: 'Temperature', unit: ' °C' },
                    { key: 'humidity',    label: 'Humidity',    unit: ' %' },
                    { key: 'rain_status', label: 'Rain',        unit: '' },
                    { key: 'motion',      label: 'Motion',      unit: '' },
                ],
            },
        };

        // Build the page skeleton once
        const root = document.getElementById('devices');
        for (const [id, cfg] of Object.entries(DEVICES)) {
            const sec = document.createElement('div');
            sec.className = 'device';
            sec.innerHTML = `
                <h2>${cfg.name} <small>${cfg.subtitle}</small></h2>
                <div class="status-bar">
                    <div class="dot offline" id="dot-${id}"></div>
                    <span id="status-${id}">Loading...</span>
                </div><br>
                <div class="sensor-status" id="sensors-${id}"></div><br>
                <div class="warning-banner" id="warn-${id}">
                    🚨 SYSTEM HALTED — Sensor(s) Disconnected!
                    <div class="missing">
                        Missing: <strong id="missing-${id}"></strong><br>
                        All sensors must be connected for this device to operate.
                    </div>
                </div>
                <div class="cards-wrap" id="cards-${id}">
                    ${cfg.cards.map(c => `
                        <div class="card">
                            <div class="value" id="v-${id}-${c.key}">--</div>
                            <div class="label">${c.label}</div>
                        </div>`).join('')}
                </div>
                <p class="waiting-msg" id="wait-${id}">Waiting for ESP32 data...</p>
            `;
            root.appendChild(sec);
        }

        function setStatus(id, dotClass, text) {
            document.getElementById('dot-' + id).className = 'dot ' + dotClass;
            document.getElementById('status-' + id).textContent = text;
        }

        function render(id, data) {
            const cfg = DEVICES[id];
            const $ = s => document.getElementById(s + '-' + id);

            if (!data || data.error) {
                setStatus(id, 'offline', data && data.error === 'No data yet' ? 'No Data Received' : (data ? data.error : 'No data'));
                $('sensors').style.display = 'none';
                $('warn').style.display = 'none';
                $('cards').style.display = 'none';
                $('wait').style.display = 'block';
                return;
            }

            const secondsAgo = Math.floor(Date.now() / 1000) - (data._last_modified || 0);
            if (secondsAgo <= 15)      setStatus(id, 'online',  'ESP32 Connected');
            else if (secondsAgo <= 60) setStatus(id, 'stale',   `ESP32 Possibly Disconnected (${secondsAgo}s ago)`);
            else                       setStatus(id, 'offline', `ESP32 Offline (${secondsAgo}s ago)`);

            const sensors = $('sensors');
            sensors.style.display = 'inline-block';
            sensors.innerHTML = cfg.sensors.map(s => {
                const ok = data[s.ok] === true;
                return `<span class="${ok ? 'ok' : 'fail'}">${ok ? '✅' : '❌'} ${s.label}</span>`;
            }).join('');

            $('wait').style.display = 'none';

            if (data.system_ok !== true) {
                $('warn').style.display = 'block';
                $('missing').textContent = data.missing || '';
                $('cards').style.display = 'none';
            } else {
                $('warn').style.display = 'none';
                $('cards').style.display = 'block';
                for (const c of cfg.cards) {
                    const v = data[c.key];
                    document.getElementById(`v-${id}-${c.key}`).textContent =
                        (v ?? 'N/A') + (v == null ? '' : c.unit);
                }
            }
        }

        async function poll() {
            try {
                const res = await fetch('sensor_data.php', { cache: 'no-store' });
                const all = await res.json();
                document.getElementById('refresh-time').textContent = new Date().toLocaleString();
                for (const id of Object.keys(DEVICES)) render(id, all[id]);
            } catch (err) {
                for (const id of Object.keys(DEVICES)) setStatus(id, 'offline', 'Connection error');
            }
        }

        poll();
        setInterval(poll, POLL_INTERVAL);
    </script>
</body>
</html>
