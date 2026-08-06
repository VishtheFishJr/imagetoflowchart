<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Study Scanner</title>

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

            max-width: 900px;
            width: 100%;
            text-align: center;

        }


        video {

            width: 100%;
            border-radius: 10px;
            background: black;

        }


        canvas {
            display: none;
        }


        button {

            margin: 10px;
            padding: 12px 22px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: #007bff;
            color: white;

        }


        button:hover {

            opacity: .85;

        }


        #result {

            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            overflow-x: auto;

        }


        #flowchart-render {

            margin-top: 20px;

        }


        .mermaid {

            display: flex;
            justify-content: center;

        }


        pre {

            font-size: 16px;
            line-height: 1.5;

        }
    </style>

</head>



<body>


    <div class="container">


        <h2>
            AI Study Scanner
        </h2>



        <video id="webcam" autoplay playsinline></video>


        <canvas id="canvas"></canvas>



        <div>

            <button class="mode-btn" data-mode="flowchart">
                Generate Flowchart
            </button>


            <button class="mode-btn" data-mode="quiz">
                Generate Quiz
            </button>


            <button class="mode-btn" data-mode="flashcards">
                Generate Flashcards
            </button>

        </div>




        <div id="result">


            <strong>
                Output:
            </strong>


            <p id="ai-status">
                Select an option...
            </p>


            <div id="flowchart-render"></div>


        </div>



    </div>





    <script>


        const video =
            document.getElementById("webcam");


        const canvas =
            document.getElementById("canvas");


        const aiStatus =
            document.getElementById("ai-status");


        const flowchartRender =
            document.getElementById("flowchart-render");



        let selectedMode = "flowchart";





        async function initCamera() {


            try {


                const stream =
                    await navigator.mediaDevices.getUserMedia({

                        video: {
                            facingMode: "environment"
                        }

                    });


                video.srcObject = stream;


            }


            catch (err) {


                aiStatus.innerText =
                    "Camera error: " + err.message;


            }


        }






        document.querySelectorAll(".mode-btn")
            .forEach(button => {


                button.addEventListener("click", () => {


                    selectedMode =
                        button.dataset.mode;


                    analyzeImage();


                });


            });







        async function analyzeImage() {



            aiStatus.innerText =
                "Analyzing image...";


            flowchartRender.innerHTML = "";



            canvas.width =
                video.videoWidth;


            canvas.height =
                video.videoHeight;



            const ctx =
                canvas.getContext("2d");



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



                const response =
                    await fetch(

                        "analyze.php",

                        {

                            method: "POST",

                            headers: {

                                "Content-Type":
                                    "application/json"

                            },


                            body: JSON.stringify({

                                image: imageData,

                                mode: selectedMode

                            })

                        }

                    );





                const data =
                    await response.json();






                if (data.success) {



                    aiStatus.innerText =
                        selectedMode +
                        " generated!";





                    if (selectedMode === "flowchart") {



                        let code =
                            data.ai_response;



                        code =
                            code.replace(/```mermaid/gi, "")
                                .replace(/```/g, "")
                                .trim();





                        const div =
                            document.createElement("div");



                        div.className =
                            "mermaid";



                        div.textContent =
                            code;




                        flowchartRender.appendChild(div);




                        await mermaid.run({

                            nodes: [div]

                        });



                    }



                    else {



                        flowchartRender.innerHTML =

                            "<pre>" +

                            data.ai_response +

                            "</pre>";



                    }





                }



                else {


                    aiStatus.innerText =
                        "Error: " + data.error;


                }




            }


            catch (err) {


                aiStatus.innerText =
                    "Network Error: " + err.message;


            }





        }






        initCamera();



    </script>


</body>

</html>