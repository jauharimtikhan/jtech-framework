<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $code ?> - <?= $title ?></title>
  <style>
    :root {
      --bg: #0f172a;
      --text: #f8fafc;
      --text-dim: #94a3b8;
      --accent: #3b82f6;
      /* Biru Laravel/Futuristic */
      --accent-glow: rgba(59, 130, 246, 0.2);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Segoe UI', system-ui, sans-serif;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      overflow: hidden;
    }

    .container {
      max-width: 600px;
      padding: 2rem;
      position: relative;
      z-index: 1;
    }

    /* Error Code Besar */
    .error-code {
      font-size: 8rem;
      font-weight: 800;
      line-height: 1;
      background: linear-gradient(135deg, #fff 0%, var(--accent) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.5rem;
      letter-spacing: -5px;
      position: relative;
    }

    /* Efek Glow/Blur di belakang angka */
    .error-code::after {
      content: '<?= $code ?>';
      position: absolute;
      left: 0;
      right: 0;
      top: 0;
      color: var(--accent);
      filter: blur(40px);
      opacity: 0.4;
      z-index: -1;
      -webkit-text-fill-color: initial;
      /* Reset fill override */
    }

    .error-title {
      font-size: 2rem;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    .error-message {
      color: var(--text-dim);
      font-size: 1.1rem;
      margin-bottom: 2.5rem;
      line-height: 1.6;
    }

    .btn-home {
      display: inline-block;
      padding: 0.8rem 2rem;
      background: transparent;
      border: 2px solid var(--accent);
      color: var(--accent);
      text-decoration: none;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-home:hover {
      background: var(--accent);
      color: #fff;
      box-shadow: 0 0 20px var(--accent-glow);
      transform: translateY(-2px);
    }

    /* Hiasan Background (Optional) */
    .bg-pattern {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: radial-gradient(circle at 50% 50%, #1e293b 1px, transparent 1px);
      background-size: 40px 40px;
      opacity: 0.1;
      z-index: 0;
    }
  </style>
</head>

<body>
  <div class="bg-pattern"></div>

  <div class="container">
    <div class="error-code"><?= $code ?></div>
    <div class="error-title"><?= $title ?></div>
    <p class="error-message"><?= $message ?></p>

    <a href="/" class="btn-home">Kembali ke Beranda</a>
  </div>
</body>

</html>