<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Error - JTech Framework</title>
  <style>
    :root {
      --bg-body: #0f172a;
      /* Slate 900 */
      --bg-card: #1e293b;
      /* Slate 800 */
      --border: #334155;
      /* Slate 700 */
      --text-main: #f1f5f9;
      /* Slate 100 */
      --text-muted: #94a3b8;
      /* Slate 400 */
      --accent-red: #f43f5e;
      /* Rose 500 */
      --accent-red-bg: #881337;
      /* Rose 900 */
      --code-bg: #0b1120;
      /* Almost Black */
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background-color: var(--bg-body);
      color: var(--text-main);
      line-height: 1.5;
      padding: 2rem;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
    }

    /* Header Section */
    .header {
      margin-bottom: 2rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 1rem;
    }

    .exception-type {
      color: var(--text-muted);
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 0.5rem;
    }

    .error-message {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 1rem;
    }

    .file-path {
      background: var(--bg-card);
      padding: 0.75rem 1rem;
      border-radius: 6px;
      font-family: monospace;
      color: var(--text-muted);
      border: 1px solid var(--border);
      display: inline-block;
    }

    .file-path strong {
      color: var(--accent-red);
    }

    /* Code Preview Section */
    .code-window {
      background: var(--bg-card);
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid var(--border);
      margin-bottom: 2rem;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
    }

    .code-header {
      padding: 0.5rem 1rem;
      background: var(--code-bg);
      border-bottom: 1px solid var(--border);
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .code-body {
      padding: 1rem 0;
      overflow-x: auto;
    }

    .code-line {
      display: flex;
      padding: 0.1rem 1rem;
      font-family: 'Consolas', 'Monaco', monospace;
      font-size: 0.9rem;
    }

    .code-line.highlight {
      background-color: rgba(244, 63, 94, 0.2);
      /* Red tint */
      border-left: 3px solid var(--accent-red);
    }

    .line-number {
      width: 50px;
      color: var(--text-muted);
      user-select: none;
      text-align: right;
      padding-right: 1rem;
      opacity: 0.5;
    }

    .line-content {
      white-space: pre;
      color: #e2e8f0;
    }

    .highlight .line-content {
      color: #fff;
      font-weight: bold;
    }

    /* Stack Trace Section */
    .stack-trace {
      background: var(--bg-card);
      border-radius: 8px;
      padding: 1.5rem;
      border: 1px solid var(--border);
    }

    .stack-title {
      font-size: 1.25rem;
      margin-bottom: 1rem;
      color: var(--text-main);
    }

    .trace-item {
      margin-bottom: 0.75rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border);
    }

    .trace-item:last-child {
      border: none;
    }

    .trace-method {
      color: #60a5fa;
      /* Blue */
      font-family: monospace;
      font-weight: 600;
    }

    .trace-file {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
    }
  </style>
</head>

<body>

  <div class="container">
    <div class="header">
      <div class="exception-type"><?= $class ?></div>
      <h1 class="error-message"><?= $message ?></h1>
      <div class="file-path">
        <?= str_replace(getcwd(), '', $file) ?> : <strong><?= $line ?></strong>
      </div>
    </div>

    <div class="code-window">
      <div class="code-header">CODE PREVIEW</div>
      <div class="code-body">
        <?php foreach ($codePreview as $row): ?>
          <div class="code-line <?= $row['highlight'] ? 'highlight' : '' ?>">
            <div class="line-number"><?= $row['number'] ?></div>
            <div class="line-content"><?= $row['code'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="stack-trace">
      <h3 class="stack-title">Stack Trace</h3>
      <?php foreach ($trace as $i => $t): ?>
        <div class="trace-item">
          <div class="trace-method">
            #<?= $i ?>
            <?= $t['class'] ?? '' ?><?= $t['type'] ?? '' ?><?= $t['function'] ?>()
          </div>
          <?php if (isset($t['file'])): ?>
            <div class="trace-file">
              <?= str_replace(getcwd(), '', $t['file']) ?> : <?= $t['line'] ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>


</body>

</html>