<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Flowchart Scanner</title>
    <script type="module">
        import mermaid from '[https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs](https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs)';
        mermaid.initialize({ startOnLoad: false, theme: 'default' });
        window.mermaid = mermaid;
    </script>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: #f4f4f9;
        }

        .container {
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        video,
        canvas {
            width: 100%;
            border-radius: 8px;
            background: #000;
        }

        button {
            margin-top: 15px;
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            background: #007bff;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
        }

        button:disabled {
            background: #ccc;
        }

        #result {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        #ai-status {
            color: #666;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>AI Flowchart Scanner</h2>

        <video id="webcam" autoplay playsinline></video>
        <canvas id="canvas" style="display: none;"></canvas>

        <button id="capture-btn">Snap & Convert to Flowchart</button>

        <div id="result">
            <strong>Flowchart Output:</strong>
            <p id="ai-status">Click "Snap & Convert to Flowchart" to start...</p>
            <div id="flowchart-render"></div>
        </div>
    </div>

    <script>
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('capture-btn');
        const aiStatus = document.getElementById('ai-status');
        const flowchartRender = document.getElementById('flowchart-render');

        // 1. Request access to the user's camera
        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
            } catch (err) {
                aiStatus.innerText = "Error accessing camera: " + err.message;
            }
        }

        // 2. Capture Frame and Send to PHP
        captureBtn.addEventListener('click', async () => {
            captureBtn.disabled = true;
            aiStatus.innerText = "Analyzing image with AI...";
            flowchartRender.innerHTML = "";

            // Set canvas size matching video frame
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Draw frame onto canvas
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert frame to Base64 image URL
            const imageDataUrl = canvas.toDataURL('image/jpeg');

            try {
                // POST to analyze.php
                const response = await fetch('analyze.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image: imageDataUrl })
                });

                const data = await response.json();

                if (data.success) {
                    aiStatus.innerText = "Flowchart generated successfully!";

                    // Format Mermaid syntax into container
                    flowchartRender.innerHTML = `<pre class="mermaid">${data.ai_response}</pre>`;

                    // Render diagram visually using Mermaid
                    if (window.mermaid) {
                        await window.mermaid.run({
                            nodes: [flowchartRender.querySelector('.mermaid')]
                        });
                    }
                } else {
                    aiStatus.innerText = "Error: " + (data.error || "Failed to analyze.");
                }
            } catch (error) {
                aiStatus.innerText = "Network Error: " + error.message;
            } finally {
                captureBtn.disabled = false;
            }
        });

        // Initialize camera on page load
        initCamera();
    </script>

</body>

</html>