<!DOCTYPE html>
<?php

/**
 * This template is used by PowChallenge to generate a static HTML page for performing proof-of-work challenges.
 */

use Joby\Smol\PoW\SmolPoW;

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>joby.lol | getting there</title>
    <!-- Standard favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <!-- Android/Chrome -->
    <link rel="manifest" href="/site.webmanifest">
    <!-- Microsoft -->
    <meta name="msapplication-TileColor" content="#4a90e2">
    <meta name="theme-color" content="#ffffff">
    <!-- Very basic styles -->
    <style>
        :root {
            --bg: #ffffff;
            --text: #1a1a1a;
            --accent: #4a90e2;
            --link: #2970c7;
            --link-hover: #1d4e8f;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #1a1a1a;
                --text: #f0f0f0;
                --accent: #64a6ed;
                --link: #7eb3f0;
                --link-hover: #9cc4f4;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        main {
            max-width: 600px;
            padding: 2rem;
            text-align: center;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .emoji {
            font-size: 3rem;
            margin: 1rem 0;
        }

        .progress {
            width: 200px;
            height: 4px;
            background: var(--text);
            opacity: 0.2;
            margin: 2rem auto;
            position: relative;
            overflow: hidden;
            border-radius: 2px;
        }

        #smolpow-output {
            margin-top: 1rem;
            color: #2970c7;
            font-family: 'Courier New', Courier, monospace;
        }

        .progress::after {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 50%;
            height: 100%;
            background: var(--accent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(400%);
            }
        }
    </style>
    <script>
        <?php echo SmolPoW::javascript(); ?>
    </script>
</head>

<body>
    <main>
        <div class="emoji">🛡️</div>
        <h1>Bot protection</h1>
        <p>Please wait while your browser solves a cryptographic challenge to prove you are not an automated bot or web scraper. This should take less than a minute.</p>
        <div class="progress"></div>
        <div id="smolpow-output"></div>
    </main>

    <script>
        smolPoW.run();
    </script>
</body>

</html>