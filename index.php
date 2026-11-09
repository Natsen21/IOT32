<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>it32 sensors</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #e8eef0;
            --panel: #f8fafb;
            --tile: #ffffff;
            --ink: #10222b;
            --muted: #5b6f78;
            --line: #cad6db;
            --accent: #1f6fa8;
            --ok: #17794f;
            --warn: #b7700b;
            --bad: #c0352b;
            --ok-bg: #dcf1e7;
            --warn-bg: #fbecd0;
            --bad-bg: #f9dcd9;
            --accent-bg: #d9eaf6;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0e1a20;
                --panel: #14252d;
                --tile: #1a303a;
                --ink: #e6eef1;
                --muted: #8fa5af;
                --line: #264049;
                --accent: #5db1e8;
                --ok: #4cc38a;
                --warn: #e3a33b;
                --bad: #ef6b5f;
                --ok-bg: #173a2c;
                --warn-bg: #3b2e12;
                --bad-bg: #432019;
                --accent-bg: #173449;
            }
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Figtree', system-ui, -apple-system, 'Segoe UI', sans-serif;
            line-height: 1.45;
            padding: 28px 20px 40px;
        }
        .wrap { max-width: 1080px; margin: 0 auto; }

        .top {
            display: flex; flex-wrap: wrap; align-items: baseline;
            justify-content: space-between; gap: 6px 24px;
            margin-bottom: 22px;
        }
        h1 {
            font-family: 'Fraunces', Georgia, serif;
            font-weight: 600; font-size: 2rem; letter-spacing: -0.01em;
            margin: 0;
        }
        .top p { margin: 0; color: var(--muted); }

        .grid {
            display: grid; gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 440px), 1fr));
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 22px;
        }
        .panel-head {
            display: flex; flex-wrap: wrap; justify-content: space-between;
            align-items: flex-start; gap: 10px; margin-bottom: 14px;
        }
        h2 { margin: 0; font-size: 1.25rem; font-weight: 700; }
        .blurb { margin: 2px 0 0; color: var(--muted); font-size: 0.92rem; }

        .pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 0.88rem; font-weight: 600;
            background: var(--bad-bg); color: var(--bad);
        }
        .pill .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
        .pill.online { background: var(--ok-bg); color: var(--ok); }
        .pill.online .dot { animation: pulse 2s ease-out infinite; }
        .pill.stale { background: var(--warn-bg); color: var(--warn); }
        .pill.offline { background: var(--bad-bg); color: var(--bad); }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 color-mix(in srgb, currentColor 55%, transparent); }
            100% { box-shadow: 0 0 0 8px transparent; }
        }

        .chips { display: none; flex-wrap: wrap; gap: 8px; list-style: none; padding: 0; margin: 0 0 16px; }
        .chip {
            font-size: 0.85rem; padding: 4px 10px; border-radius: 8px;
            border: 1px solid var(--line); background: var(--tile);
        }
        .chip.ok { color: var(--ok); }
        .chip.fail { color: var(--bad); border-color: var(--bad); }

        .alert {
            display: none;
            background: var(--bad-bg); color: var(--bad);
            border: 1px solid var(--bad); border-radius: 12px;
            padding: 14px 16px; margin-bottom: 4px;
        }
        .alert strong { display: block; font-size: 1.02rem; }
        .alert span { color: var(--ink); font-size: 0.92rem; }

        .readings {
            display: none; gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        }
        .tile {
            background: var(--tile); border: 1px solid var(--line);
            border-radius: 12px; padding: 14px 16px;
        }
        .t-label { color: var(--muted); font-size: 0.88rem; margin-bottom: 4px; }
        .t-value { font-size: 1.5rem; font-weight: 700; }
        .t-value small { font-size: 0.9rem; font-weight: 500; color: var(--muted); margin-left: 3px; }

        .tile.hero { grid-column: 1 / -1; padding: 18px 20px 16px; }
        .big {
            font-family: 'Fraunces', Georgia, serif;
            font-weight: 400; font-size: 4.2rem; line-height: 1;
            letter-spacing: -0.03em;
        }
        .big span { font-size: 1.6rem; color: var(--muted); margin-left: 4px; letter-spacing: 0; }
        .scale {
            position: relative; height: 6px; border-radius: 3px; margin-top: 16px;
            background: linear-gradient(90deg, var(--accent-bg), var(--warn-bg), var(--bad-bg));
        }
        .scale i {
            position: absolute; top: 50%; width: 14px; height: 14px;
            transform: translate(-50%, -50%); border-radius: 50%;
            background: var(--ink); border: 3px solid var(--tile);
            transition: left 0.6s ease;
        }
        .scale-ends { display: flex; justify-content: space-between; color: var(--muted); font-size: 0.78rem; margin-top: 5px; }

        .bar { height: 6px; border-radius: 3px; background: var(--line); margin-top: 10px; overflow: hidden; }
        .bar b { display: block; height: 100%; background: var(--accent); border-radius: 3px; transition: width 0.6s ease; }

        .state { display: flex; align-items: center; gap: 10px; font-size: 1.3rem; font-weight: 700; }
        .state svg { width: 26px; height: 26px; flex: none; }
        .state.accent { color: var(--accent); }
        .state.warn { color: var(--warn); }
        .state.quiet { color: var(--ink); }
        .state.warn svg { animation: pulse-icon 1.2s ease-in-out infinite; }
        @keyframes pulse-icon { 50% { opacity: 0.45; } }

        .empty { margin: 8px 0 4px; color: var(--muted); }

        footer { margin-top: 22px; color: var(--muted); font-size: 0.85rem; }

        @media (max-width: 480px) {
            .big { font-size: 3.4rem; }
            .panel { padding: 18px; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>it32 sensors</h1>
            <p>Live readings from two ESP32 boards</p>
        </div>

        <div class="grid" id="devices"></div>

        <footer>Updates every 3 seconds. Last refreshed <span id="refresh-time">never</span>.</footer>
    </div>

    <script>
        const POLL_INTERVAL = 3000; // ms

        // Keys must match the JSON fields each ESP32 sends.
        const DEVICES = {
            1: {
                name: 'Device 1',
                blurb: 'Temperature, humidity, distance and light',
                sensors: [
                    { ok: 'dht_ok',   label: 'DHT11' },
                    { ok: 'sonar_ok', label: 'HC-SR04' },
                    { ok: 'light_ok', label: 'LM393' },
                ],
                tiles: ['temperature', 'humidity', 'distance', 'light'],
            },
            2: {
                name: 'Device 2',
                blurb: 'Temperature, humidity, rain and motion',
                sensors: [
                    { ok: 'dht_ok',  label: 'DHT11' },
                    { ok: 'rain_ok', label: 'Raindrop' },
                    { ok: 'pir_ok',  label: 'PIR' },
                ],
                tiles: ['temperature', 'humidity', 'rain_status', 'motion'],
            },
        };

        const svg = (inner) =>
            `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;
        const ICON = {
            sun:    svg('<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>'),
            moon:   svg('<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>'),
            rain:   svg('<path d="M7 15a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 8.5 3.5 3.5 0 0 1 17 15z"/><path d="M8 18l-1 2M12 18l-1 2M16 18l-1 2"/>'),
            dry:    svg('<path d="M7 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 11.5 3.5 3.5 0 0 1 17 18z"/>'),
            motion: svg('<circle cx="12" cy="12" r="2"/><path d="M7.5 7.5a6 6 0 0 0 0 9M16.5 7.5a6 6 0 0 1 0 9M4.5 4.5a10 10 0 0 0 0 15M19.5 4.5a10 10 0 0 1 0 15"/>'),
            still:  svg('<circle cx="12" cy="12" r="2"/><circle cx="12" cy="12" r="7" opacity="0.4"/>'),
        };

        const esc = (s) => String(s).replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const clamp = (n, lo, hi) => Math.min(hi, Math.max(lo, n));
        const isNum = (v) => v !== null && v !== undefined && v !== '' && !isNaN(Number(v));

        const TILE = {
            temperature(v) {
                const has = isNum(v);
                const pct = has ? clamp(Number(v) / 50 * 100, 0, 100) : 0;
                return `<div class="tile hero">
                    <div class="t-label">Temperature</div>
                    <div class="big">${has ? esc(v) : '–'}<span>°C</span></div>
                    <div class="scale"><i style="left:${pct}%"></i></div>
                    <div class="scale-ends"><span>0°</span><span>50°</span></div>
                </div>`;
            },
            humidity(v) {
                const has = isNum(v);
                return `<div class="tile">
                    <div class="t-label">Humidity</div>
                    <div class="t-value">${has ? esc(v) : '–'}<small>%</small></div>
                    <div class="bar"><b style="width:${has ? clamp(Number(v), 0, 100) : 0}%"></b></div>
                </div>`;
            },
            distance(v) {
                const has = isNum(v);
                return `<div class="tile">
                    <div class="t-label">Distance</div>
                    <div class="t-value">${has ? esc(v) : '–'}<small>cm</small></div>
                    <div class="bar"><b style="width:${has ? clamp(Number(v) / 200 * 100, 0, 100) : 0}%"></b></div>
                </div>`;
            },
            light(v) {
                const dark = String(v).toLowerCase() === 'dark';
                return `<div class="tile">
                    <div class="t-label">Light</div>
                    <div class="state ${dark ? 'quiet' : 'warn'}" style="animation:none">${dark ? ICON.moon : ICON.sun}${esc(v ?? 'N/A')}</div>
                </div>`;
            },
            rain_status(v) {
                const wet = String(v).toLowerCase() === 'raining';
                return `<div class="tile">
                    <div class="t-label">Rain</div>
                    <div class="state ${wet ? 'accent' : 'quiet'}">${wet ? ICON.rain : ICON.dry}${esc(v ?? 'N/A')}</div>
                </div>`;
            },
            motion(v) {
                const hit = String(v).toLowerCase() === 'detected';
                return `<div class="tile">
                    <div class="t-label">Motion</div>
                    <div class="state ${hit ? 'warn' : 'quiet'}">${hit ? ICON.motion : ICON.still}${esc(v ?? 'N/A')}</div>
                </div>`;
            },
        };

        function ago(s) {
            if (s < 90) return s + 's ago';
            if (s < 5400) return Math.round(s / 60) + ' min ago';
            return Math.round(s / 3600) + ' h ago';
        }

        // Build page skeleton once
        const root = document.getElementById('devices');
        for (const [id, cfg] of Object.entries(DEVICES)) {
            const sec = document.createElement('section');
            sec.className = 'panel';
            sec.innerHTML = `
                <div class="panel-head">
                    <div>
                        <h2>${cfg.name}</h2>
                        <p class="blurb">${cfg.blurb}</p>
                    </div>
                    <div class="pill offline" id="pill-${id}"><span class="dot"></span><span id="pilltext-${id}">Loading</span></div>
                </div>
                <ul class="chips" id="chips-${id}"></ul>
                <div class="alert" id="alert-${id}">
                    <strong>Device stopped: sensor disconnected</strong>
                    <span>Missing: <b id="missing-${id}"></b>. Reconnect it and this board will resume.</span>
                </div>
                <div class="readings" id="readings-${id}"></div>
                <p class="empty" id="empty-${id}">Waiting for this ESP32 to send its first reading.</p>
            `;
            root.appendChild(sec);
        }

        function setPill(id, cls, text) {
            document.getElementById('pill-' + id).className = 'pill ' + cls;
            document.getElementById('pilltext-' + id).textContent = text;
        }

        function render(id, data) {
            const cfg = DEVICES[id];
            const $ = (s) => document.getElementById(s + '-' + id);

            if (!data || data.error) {
                setPill(id, 'offline', data && data.error === 'No data yet' ? 'No data yet' : (data ? data.error : 'No data'));
                $('chips').style.display = 'none';
                $('alert').style.display = 'none';
                $('readings').style.display = 'none';
                $('empty').style.display = 'block';
                return;
            }

            const secondsAgo = Math.max(0, Math.floor(Date.now() / 1000) - (data._last_modified || 0));
            if (secondsAgo <= 15)      setPill(id, 'online',  'Connected');
            else if (secondsAgo <= 60) setPill(id, 'stale',   'Not responding, last seen ' + ago(secondsAgo));
            else                       setPill(id, 'offline', 'Offline, last seen ' + ago(secondsAgo));

            const chips = $('chips');
            chips.style.display = 'flex';
            chips.innerHTML = cfg.sensors.map(s => {
                const ok = data[s.ok] === true;
                return `<li class="chip ${ok ? 'ok' : 'fail'}">${ok ? '✓' : '✕'} ${s.label}</li>`;
            }).join('');

            $('empty').style.display = 'none';

            if (data.system_ok !== true) {
                $('alert').style.display = 'block';
                $('missing').textContent = data.missing || 'unknown sensor';
                $('readings').style.display = 'none';
            } else {
                $('alert').style.display = 'none';
                const r = $('readings');
                r.style.display = 'grid';
                r.innerHTML = cfg.tiles.map(k => TILE[k](data[k])).join('');
            }
        }

        async function poll() {
            try {
                const res = await fetch('sensor_data.php', { cache: 'no-store' });
                const all = await res.json();
                document.getElementById('refresh-time').textContent = new Date().toLocaleTimeString();
                for (const id of Object.keys(DEVICES)) render(id, all[id]);
            } catch (err) {
                for (const id of Object.keys(DEVICES)) setPill(id, 'offline', 'Cannot reach server');
            }
        }

        poll();
        setInterval(poll, POLL_INTERVAL);
    </script>
</body>
</html>
