<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Community Flow Harness</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 2rem; background: #f6f8fb; }
        .card { background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); max-width: 720px; margin: 0 auto; }
        button { background: #2563eb; border: none; color: #fff; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        button:disabled { background: #94a3b8; cursor: progress; }
        pre { background: #0f172a; color: #e2e8f0; padding: 1.5rem; border-radius: 8px; overflow-x: auto; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Community Flow Test Harness</h1>
        <p>This harness executes the end-to-end community join → subscribe → compose → react → notify → leaderboard sequence using real application services.</p>
        <button type="button" id="run-flow" dusk="run-flow">Run Scenario</button>
        <pre id="result" data-status="idle">Awaiting execution…</pre>
    </div>
    <script>
        const button = document.getElementById('run-flow');
        const output = document.getElementById('result');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function runScenario() {
            button.disabled = true;
            output.dataset.status = 'running';
            output.textContent = 'Executing flow…';

            try {
                const response = await fetch('{{ route('testing.community-flow.execute') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ trigger: 'browser-harness' }),
                });

                if (!response.ok) {
                    throw new Error(`Harness returned ${response.status}`);
                }

                const payload = await response.json();
                output.dataset.status = 'complete';
                output.textContent = JSON.stringify(payload, null, 2);
            } catch (error) {
                output.dataset.status = 'error';
                output.textContent = `Flow failed: ${error.message}`;
            } finally {
                button.disabled = false;
            }
        }

        button.addEventListener('click', runScenario);
    </script>
</body>
</html>
