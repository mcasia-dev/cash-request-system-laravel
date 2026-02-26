<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 | {{ config('app.name') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --bg: #e9e9ea;
            --shell: #f3f3f3;
            --ink: #2f3135;
            --muted: #666c74;
            --dark-btn: #1f1f1f;
            --dark-btn-hover: #121212;
            --light-btn: #ddd9d4;
            --light-btn-hover: #d2cec8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: "Inter", "Segoe UI", Tahoma, sans-serif;
            padding: 20px;
        }

        .page {
            min-height: calc(100vh - 40px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            width: min(720px, 100%);
            text-align: center;
        }

        .media-shell {
            width: min(380px, 100%);
            margin: 0 auto 15px;
            padding: 10px;
            border-radius: 18px;
            background: var(--bg);
        }

        .media {
            width: 100%;
            display: block;
            border-radius: 12px;
            mix-blend-mode: multiply;
            opacity: 0.94;
        }

        .lead {
            margin: 0 auto;
            max-width: 560px;
            font-size: clamp(20px, 3.4vw, 48px);
            line-height: 1.25;
            font-weight: 700;
            text-wrap: balance;
        }

        .sub {
            margin: 18px auto 0;
            max-width: 620px;
            font-size: 20px;
            line-height: 1.5;
            color: var(--muted);
            text-wrap: balance;
        }

        .actions {
            margin-top: 26px;
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            border-radius: 999px;
            padding: 12px 24px;
            text-decoration: none;
            color: var(--ink);
            font-weight: 600;
            font-size: clamp(16px, 1.2vw, 18px);
            transition: 0.2s ease;
            background: var(--light-btn);
            border: 1px solid transparent;
        }

        .btn:hover {
            background: var(--light-btn-hover);
        }

        .btn-primary {
            background: var(--dark-btn);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--dark-btn-hover);
        }

        @media (max-width: 720px) {
            .media-shell {
                margin-bottom: 18px;
            }

            .sub {
                font-size: clamp(16px, 4.8vw, 22px);
            }

            .btn {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <main class="content" role="main" aria-labelledby="error-title">
        <div class="media-shell">
            <img class="media" src="{{ asset('images/404.png') }}" alt="Lost page illustration">
        </div>
        <p class="lead">Oh, the tragedy! This page could not be found.</p>
        <p class="sub">
            The page you are looking for may have moved or no longer exists.
            Let&apos;s get you back on track.
        </p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/') }}">Go to dashboard</a>
            <a class="btn" href="javascript:history.back()">Try again</a>
        </div>
    </main>
</div>
</body>
</html>
