<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Camera Scanner</title>
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
            max-width: 500px;
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
            text-align: left;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>AI Camera Scanner</h2>

        <video id="webcam" autoplay playsinline></video>
        <canvas id="canvas" style="display: none;"></canvas>

        <button id="capture-btn">Snap & Ask AI</button>

        <div id="result">
            <strong>AI Analysis:</strong>
            <p id="ai-text">Click "Snap & Ask AI" to start...</p>
        </div>
    </div>

    <script>
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('capture-btn');
        const aiText = document.getElementById('ai-text');

        // 1. Request access to the user's camera
        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
            } catch (err) {
                aiText.innerText = "Error accessing camera: " + err.message;
            }
        }

        // 2. Capture Frame and Send to PHP
        captureBtn.addEventListener('click', async () => {
            captureBtn.disabled = true;
            aiText.innerText = "Analyzing image with AI...";

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
                    aiText.innerText = data.ai_response;
                } else {
                    aiText.innerText = "Error: " + (data.error || "Failed to analyze.");
                }
            } catch (error) {
                aiText.innerText = "Network Error: " + error.message;
            } finally {
                captureBtn.disabled = false;
            }
        });

        // Initialize on page load
        initCamera();
    </script>

</body>

</html>