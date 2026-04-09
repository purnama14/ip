<?php
/**
 * IP & Device Info Page
 * A premium, standalone PHP page to display visitor details and auto-close.
 */

// IP Detection
$ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim(end($ips));
}

// Fetch Geo Info (Muted @ to prevent warnings if offline/blocked)
$geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip?fields=status,message,country,regionName,city,zip,timezone,isp,org,as,query"), true);

if (!$geo || $geo['status'] !== 'success') {
    $geo = [
        'city' => 'Local / Private',
        'regionName' => 'N/A',
        'country' => 'N/A',
        'isp' => 'Internal/Unknown',
        'timezone' => 'UTC',
        'query' => $ip
    ];
}

$userAgent = $_SERVER['HTTP_USER_AGENT'];

// Minimal Browser Detection
function getBrowser($ua) {
    if (strpos($ua, 'Opera') || strpos($ua, 'OPR/')) return 'Opera';
    if (strpos($ua, 'Edge') || strpos($ua, 'Edg/')) return 'Edge';
    if (strpos($ua, 'Chrome')) return 'Chrome';
    if (strpos($ua, 'Safari')) return 'Safari';
    if (strpos($ua, 'Firefox')) return 'Firefox';
    if (strpos($ua, 'MSIE') || strpos($ua, 'Trident/7')) return 'Internet Explorer';
    return 'Unknown Browser';
}

function getOS($ua) {
    if (strpos($ua, 'Windows')) return 'Windows';
    if (strpos($ua, 'Android')) return 'Android';
    if (strpos($ua, 'iPhone') || strpos($ua, 'iPad')) return 'iOS';
    if (strpos($ua, 'Macintosh')) return 'macOS';
    if (strpos($ua, 'Linux')) return 'Linux';
    return 'Unknown OS';
}

$browser = getBrowser($userAgent);
$os = getOS($userAgent);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network & Device Identity</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0c;
            --card-bg: rgba(255, 255, 255, 0.03);
            --accent: #8b5cf6;
            --secondary: #06b6d4;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.15) 0%, transparent 40%);
        }

        .container {
            width: 90%;
            max-width: 500px;
            animation: fadeInScale 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 24px;
            padding: 1px;
            background: linear-gradient(45deg, var(--accent), var(--secondary));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0.3;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-dim);
            font-size: 0.875rem;
        }

        .data-grid {
            display: grid;
            gap: 1.25rem;
        }

        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .data-item:last-child {
            border-bottom: none;
        }

        .label {
            color: var(--text-dim);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .value {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .ip-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(139, 92, 246, 0.2);
            color: #c4b5fd;
            border-radius: 99px;
            font-family: monospace;
            font-size: 1rem;
        }

        .countdown-container {
            margin-top: 2.5rem;
            text-align: center;
        }

        .countdown-bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            margin-top: 1rem;
            overflow: hidden;
        }

        .countdown-progress {
            height: 100%;
            background: linear-gradient(to right, var(--accent), var(--secondary));
            width: 100%;
            transition: width 1s linear;
        }

        .status-msg {
            font-size: 0.75rem;
            color: var(--text-dim);
            margin-top: 0.5rem;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .card { padding: 1.5rem; }
            .header h1 { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <h1>Session Identity</h1>
            <p>Connection and hardware insights</p>
        </div>

        <div class="data-grid">
            <div class="data-item">
                <span class="label">IP Address</span>
                <span class="value IP-badge"><?php echo htmlspecialchars($geo['query']); ?></span>
            </div>
            <div class="data-item">
                <span class="label">Location</span>
                <span class="value"><?php echo htmlspecialchars($geo['city'] . ', ' . $geo['country']); ?></span>
            </div>
            <div class="data-item">
                <span class="label">Provider</span>
                <span class="value"><?php echo htmlspecialchars($geo['isp']); ?></span>
            </div>
            <div class="data-item">
                <span class="label">Platform</span>
                <span class="value"><?php echo $os; ?></span>
            </div>
            <div class="data-item">
                <span class="label">Browser</span>
                <span class="value"><?php echo $browser; ?></span>
            </div>
            <div class="data-item">
                <span class="label">Display</span>
                <span class="value" id="res-val">Detecting...</span>
            </div>
            <div class="data-item">
                <span class="label">Language</span>
                <span class="value" id="lang-val"><?php echo Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']) ?? 'en'; ?></span>
            </div>
        </div>

        <div class="countdown-container">
            <span class="status-msg" id="status-text">Closing this session in <span id="timer">5</span>s</span>
            <div class="countdown-bar">
                <div class="countdown-progress" id="progress"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Screen Resolution Detection
    document.getElementById('res-val').textContent = window.screen.width + ' x ' + window.screen.height;

    // Countdown Logic
    let timeLeft = 5;
    const timerEl = document.getElementById('timer');
    const progressEl = document.getElementById('progress');
    const statusTextEl = document.getElementById('status-text');

    const countdown = setInterval(() => {
        timeLeft -= 1;
        timerEl.textContent = timeLeft;
        progressEl.style.width = (timeLeft / 5 * 100) + '%';

        if (timeLeft <= 0) {
            clearInterval(countdown);
            statusTextEl.textContent = "Attempting to close window...";
            
            // Try closing
            window.close();

            // Fallback for browser security
            setTimeout(() => {
                statusTextEl.innerHTML = '<span style="color: #ef4444;">Please close this tab manually.</span>';
            }, 1000);
        }
    }, 1000);
</script>

</body>
</html>
