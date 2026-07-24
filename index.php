<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Flowchart Scanner</title>

    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';

        mermaid.initialize({
            startOnLoad: false,
            theme: "default",
            flowchart: {
                curve: "basis",
                htmlLabels: true
            },
            securityLevel: "loose"
        });

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
            max-width: 800px;
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
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        button:disabled {
            background: #ccc;
        }

        #result {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            overflow-x: auto;
        }

        #flowchart-render {
            margin-top: 20px;
        }

        .mermaid {
            display: flex;
            justify-content: center;
        }
    </style>
</head>


<body>

    <div class="container">

        <h2>AI Flowchart Scanner</h2>

        <video id="webcam" autoplay playsinline></video>

        <canvas id="canvas" style="display:none;"></canvas>

        <button id="capture-btn">
            Snap & Convert to Flowchart
        </button>


        <div id="result">

            <strong>Flowchart Output:</strong>

            <p id="ai-status">
                Click the button to start...
            </p>

            <div id="flowchart-render"></div>

        </div>

    </div>


    <script>

        const video = document.getElementById("webcam");
        const canvas = document.getElementById("canvas");
        const captureBtn = document.getElementById("capture-btn");
        const aiStatus = document.getElementById("ai-status");
        const flowchartRender = document.getElementById("flowchart-render");


        // Camera
        async function initCamera() {

            try {

                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "environment"
                    }
                });

                video.srcObject = stream;

            } catch (err) {

                aiStatus.innerText =
                    "Camera error: " + err.message;
            }

        }


        captureBtn.addEventListener("click", async () => {

            captureBtn.disabled = true;

            aiStatus.innerText =
                "Analyzing image...";


            flowchartRender.innerHTML = "";


            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;


            const ctx = canvas.getContext("2d");

            ctx.drawImage(
                video,
                0,
                0,
                canvas.width,
                canvas.height
            );


            const imageData =
                canvas.toDataURL("image/jpeg");


            try {


                const response = await fetch(
                    "analyze.php",
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            image: imageData
                        })
                    }
                );


                const data = await response.json();



                if (data.success) {


                    aiStatus.innerText =
                        "Flowchart generated!";


                    let code = data.ai_response;


                    // Remove accidental markdown
                    code = code
                        .replace(/```mermaid/gi, "")
                        .replace(/```/g, "")
                        .trim();



                    // Create Mermaid element
                    const div =
                        document.createElement("div");

                    div.className = "mermaid";

                    div.textContent = code;


                    flowchartRender.appendChild(div);



                    // Render
                    await mermaid.run({
                        nodes: [
                            div
                        ]
                    });



                } else {

                    aiStatus.innerText =
                        "Error: " + data.error;

                }



            } catch (err) {

                aiStatus.innerText =
                    "Network Error: " + err.message;

            }


            captureBtn.disabled = false;


        });



        initCamera();

    </script>


</body>

</html>