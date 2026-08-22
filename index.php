<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <title>
        AI Study Scanner
    </title>


    <script type="module">

        import mermaid from
            'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';


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

            font-family:
                Arial, Helvetica, sans-serif;

            background:
                linear-gradient(135deg, #eef2ff, #f8fafc);

            display: flex;

            flex-direction: column;

            align-items: center;

            padding: 30px;

            margin: 0;

        }


        .container {

            width: 100%;

            max-width: 900px;

            text-align: center;

        }


        h1 {

            font-size: 36px;

            color: #1e293b;

        }


        video {

            width: 100%;

            border-radius: 18px;

            background: black;

            box-shadow:
                0 10px 25px rgba(0, .2);

        }


        canvas {

            display: none;

        }


        .mode-container {

            margin-top: 20px;

            display: flex;

            justify-content: center;

            gap: 15px;

            flex-wrap: wrap;

        }


        .mode-btn {

            padding: 14px 25px;

            border: none;

            border-radius: 12px;

            font-size: 16px;

            cursor: pointer;

            background: #2563eb;

            color: white;

            transition: .2s;

        }


        .mode-btn:hover {

            transform: translateY(-2px);

            background: #1d4ed8;

        }


        #result {

            margin-top: 30px;

            background: white;

            border-radius: 20px;

            padding: 25px;

            box-shadow:
                0 10px 25px rgba(0, 0, 0, .12);

        }


        #flowchart-render {

            margin-top: 25px;

        }


        /* ============================
           FINDER
        ============================ */

        .finder {

            width: 100%;

            max-width: 1100px;

            background: white;

            border-radius: 20px;

            padding: 20px;

            margin-bottom: 30px;

            box-shadow:
                0 10px 25px rgba(0, 0, 0, .12);

            box-sizing: border-box;

        }


        .finder-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 15px;

        }


        .finder-header h2 {

            margin: 0;

            color: #1e293b;

        }


        .refresh-btn {

            border: none;

            background: #2563eb;

            color: white;

            padding: 8px 15px;

            border-radius: 8px;

            cursor: pointer;

        }


        .refresh-btn:hover {

            background: #1d4ed8;

        }


        .file-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fill, minmax(130px, 1fr));

            gap: 15px;

        }


        .file-item {

            padding: 15px;

            border-radius: 12px;

            cursor: default;

            text-align: center;

            user-select: none;

            transition: .15s;

        }


        .file-item:hover {

            background: #eff6ff;

        }


        .file-item.selected {

            background: #dbeafe;

        }


        .file-icon {

            font-size: 48px;

            margin-bottom: 8px;

        }


        .file-name {

            font-size: 14px;

            color: #1e293b;

            word-break: break-word;

        }


        .file-type {

            font-size: 11px;

            color: #64748b;

            margin-top: 4px;

        }


        .empty-files {

            text-align: center;

            color: #64748b;

            padding: 25px;

            grid-column: 1 / -1;

        }


        /* ============================
           QUIZ
        ============================ */

        .study-card {

            background: white;

            padding: 25px;

            border-radius: 18px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .15);

            text-align: left;

        }


        .choice {

            width: 100%;

            padding: 15px;

            margin: 10px 0;

            border-radius: 12px;

            border: none;

            font-size: 16px;

            cursor: pointer;

            background: #e2e8f0;

            text-align: left;

        }


        .choice:hover {

            background: #cbd5e1;

        }


        .choice.correct {

            background: #86efac;

        }


        .choice.wrong {

            background: #fca5a5;

        }


        /* ============================
           FLASHCARDS
        ============================ */

        .flashcard {

            width: 400px;

            height: 250px;

            margin: 30px auto;

            perspective: 1000px;

        }


        .flash-inner {

            width: 100%;

            height: 100%;

            position: relative;

            transition: .5s;

            transform-style: preserve-3d;

            cursor: pointer;

        }


        .flashcard.flip .flash-inner {

            transform:
                rotateY(180deg);

        }


        .flash-front,
        .flash-back {

            position: absolute;

            width: 100%;

            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 25px;

            box-sizing: border-box;

            border-radius: 20px;

            backface-visibility: hidden;

            font-size: 22px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .2);

            background: white;

        }


        .flash-back {

            transform:
                rotateY(180deg);

            background: #eff6ff;

        }


        .action-btn {

            padding: 12px 25px;

            margin: 10px;

            border: none;

            border-radius: 10px;

            background: #2563eb;

            color: white;

            cursor: pointer;

            font-size: 16px;

        }


        /* ============================
           PRESENTATION
        ============================ */

        .presentation-link {

            display: inline-block;

            margin-top: 25px;

            padding: 15px 30px;

            border-radius: 12px;

            background: #16a34a;

            color: white;

            text-decoration: none;

            font-size: 18px;

            font-weight: bold;

        }


        .presentation-link:hover {

            background: #15803d;

        }
    </style>

</head>


<body>


    <!-- =================================
         FINDER
    ================================== -->

    <div class="finder">

        <div class="finder-header">

            <h2>
                My Study Files
            </h2>


            <button class="refresh-btn" onclick="loadFiles()">
                ↻ Refresh
            </button>

        </div>


        <div id="file-grid" class="file-grid">

            <div class="empty-files">

                Loading files...

            </div>

        </div>

    </div>



    <!-- =================================
         SCANNER
    ================================== -->

    <div class="container">


        <h1>
            AI Study Scanner
        </h1>



        <video id="webcam" autoplay playsinline></video>


        <canvas id="canvas"></canvas>




        <div class="mode-container">


            <button class="mode-btn" data-mode="flowchart">

                📊 Flowchart

            </button>



            <button class="mode-btn" data-mode="quiz">

                📝 Quiz

            </button>



            <button class="mode-btn" data-mode="flashcards">

                🃏 Flashcards

            </button>



            <button class="mode-btn" data-mode="presentation">

                📽 Presentation

            </button>



        </div>





        <div id="result">


            <h3>
                Output
            </h3>



            <p id="ai-status">

                Select a mode and scan an image.

            </p>



            <div id="flowchart-render"></div>



        </div>



    </div>






    <script>


        const video =
            document.getElementById("webcam");


        const canvas =
            document.getElementById("canvas");


        const status =
            document.getElementById("ai-status");


        const output =
            document.getElementById("flowchart-render");


        let selectedMode = "flowchart";



        // =================================
        // FINDER
        // =================================


        const fileIcons = {

            flowchart: "📊",

            quiz: "📝",

            flashcards: "🃏",

            presentation: "📽"

        };



        async function loadFiles() {

            const grid =
                document.getElementById("file-grid");


            try {

                const response =
                    await fetch("get_items.php");


                const data =
                    await response.json();


                if (!data.success) {

                    throw new Error(
                        data.error ||
                        "Could not load files."
                    );

                }


                if (!data.items ||
                    data.items.length === 0) {

                    grid.innerHTML = `

                        <div class="empty-files">

                            No generated files yet.

                        </div>

                    `;

                    return;

                }


                grid.innerHTML = "";


                data.items.forEach(item => {


                    const file =
                        document.createElement("div");


                    file.className =
                        "file-item";


                    file.dataset.id =
                        item.id;


                    file.innerHTML = `

                        <div class="file-icon">

                            ${fileIcons[item.type]
                        || "📄"
                        }

                        </div>

                        <div class="file-name">

                            ${escapeHtml(item.name)
                        }

                        </div>

                        <div class="file-type">

                            ${escapeHtml(item.type)
                        }

                        </div>

                    `;


                    file.onclick = () => {

                        document
                            .querySelectorAll(
                                ".file-item"
                            )
                            .forEach(element => {

                                element.classList.remove(
                                    "selected"
                                );

                            });


                        file.classList.add(
                            "selected"
                        );

                    };


                    file.ondblclick = () => {

                        window.open(
                            "opened_item.php?id=" +
                            encodeURIComponent(
                                item.id
                            ),
                            "_blank"
                        );

                    };


                    /*
                     * Right-click = rename
                     */

                    file.oncontextmenu =
                        async event => {

                            event.preventDefault();


                            const newName =
                                prompt(
                                    "Rename item:",
                                    item.name
                                );


                            if (
                                newName &&
                                newName.trim() &&
                                newName.trim() !==
                                item.name
                            ) {

                                try {

                                    const response =
                                        await fetch(
                                            "rename_item.php",
                                            {

                                                method:
                                                    "POST",

                                                headers: {

                                                    "Content-Type":
                                                        "application/json"

                                                },

                                                body:
                                                    JSON.stringify({

                                                        id:
                                                            item.id,

                                                        name:
                                                            newName.trim()

                                                    })

                                            }
                                        );


                                    const result =
                                        await response.json();


                                    if (
                                        !result.success
                                    ) {

                                        alert(
                                            result.error ||
                                            "Rename failed."
                                        );

                                        return;

                                    }


                                    loadFiles();

                                }

                                catch (error) {

                                    alert(
                                        "Rename error: " +
                                        error.message
                                    );

                                }

                            }

                        };


                    grid.appendChild(file);

                });

            }

            catch (error) {

                grid.innerHTML = `

                    <div class="empty-files">

                        Error loading files:
                        ${escapeHtml(error.message)}

                    </div>

                `;

            }

        }



        function escapeHtml(value) {

            const div =
                document.createElement("div");

            div.textContent =
                value ?? "";

            return div.innerHTML;

        }



        loadFiles();



        // =================================
        // CAMERA
        // =================================


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


                status.innerText =
                    "Camera error: " + err.message;


            }


        }






        // =================================
        // MODE BUTTONS
        // =================================


        document
            .querySelectorAll(".mode-btn")
            .forEach(button => {


                button.onclick = () => {


                    selectedMode =
                        button.dataset.mode;


                    scanImage();


                };


            });








        // =================================
        // IMAGE CAPTURE
        // =================================


        async function scanImage() {


            status.innerText =
                "Analyzing...";


            output.innerHTML = "";



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





            const image =
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

                                image: image,

                                mode: selectedMode

                            })


                        }

                    );




                const text =
                    await response.text();


                console.log(
                    "SERVER RESPONSE:"
                );

                console.log(text);


                const data =
                    JSON.parse(text);





                if (!data.success) {


                    status.innerText =
                        data.error;


                    return;


                }





                status.innerText =
                    "Generated successfully!";


                /*
                 * Refresh Finder immediately.
                 */

                loadFiles();







                // =================================
                // FLOWCHART
                // =================================


                if (selectedMode === "flowchart") {



                    let code =
                        data.ai_response;



                    code =
                        code

                            .replace(
                                /```mermaid/gi,
                                ""
                            )

                            .replace(
                                /```/g,
                                ""
                            )

                            .trim();




                    const div =
                        document.createElement("div");


                    div.className =
                        "mermaid";


                    div.textContent =
                        code;



                    output.appendChild(div);



                    await mermaid.run({

                        nodes: [div]

                    });



                }






                // =================================
                // QUIZ
                // =================================


                else if (selectedMode === "quiz") {


                    const quiz =
                        JSON.parse(
                            data.ai_response
                        );


                    createQuiz(quiz);


                }






                // =================================
                // FLASHCARDS
                // =================================


                else if (selectedMode === "flashcards") {


                    const cards =
                        JSON.parse(
                            data.ai_response
                        );


                    createFlashcards(cards);


                }





                // =================================
                // PRESENTATION
                // =================================


                else if (selectedMode === "presentation") {


                    const presentation =
                        JSON.parse(
                            data.ai_response
                        );


                    createPresentation(
                        presentation
                    );


                }




            }


            catch (err) {


                status.innerText =
                    "Error: " + err.message;


            }



        }





        initCamera();



        // =================================
        // GOOGLE SLIDES CREATION
        // =================================


        async function createPresentation(data) {


            output.innerHTML = `

                <div class="study-card">

                    <h2>
                        Creating Google Slides...
                    </h2>

                    <p>
                        Please wait while your presentation
                        is generated.
                    </p>

                </div>

            `;



            try {


                const response =
                    await fetch(

                        "create_slides.php",

                        {

                            method: "POST",

                            headers: {

                                "Content-Type":
                                    "application/json"

                            },


                            body: JSON.stringify(data)

                        }

                    );



                const result =
                    await response.json();





                if (result.success) {


                    output.innerHTML = `


                        <div class="study-card">


                            <h2>
                                Presentation Created 🎉
                            </h2>


                            <p>
                                Your editable Google Slides file
                                is ready.
                            </p>



                            <a
                                class="presentation-link"
                                target="_blank"
                                href="${result.url}"
                            >

                                Open Google Slides

                            </a>


                        </div>


                    `;


                    /*
                     * Presentation was saved by
                     * create_slides.php.
                     */

                    loadFiles();


                }


                else {


                    output.innerHTML = `

                        <div class="study-card">

                            <h2>Error</h2>

                            <p>
                                ${escapeHtml(
                        result.error ||
                        "Unknown error"
                    )}
                            </p>

                        </div>

                    `;


                }


            }


            catch (err) {


                output.innerHTML = `

                    <div class="study-card">

                        Error:
                        ${escapeHtml(
                    err.message
                )}

                    </div>

                `;


            }


        }








        // =================================
        // INTERACTIVE QUIZ
        // =================================


        function createQuiz(data) {


            let current = 0;

            let score = 0;



            function showQuestion() {



                const q =
                    data.questions[current];



                output.innerHTML = `


                    <div class="study-card">


                        <h2>
                            Question
                            ${current + 1}/
                            ${data.questions.length}
                        </h2>



                        <h3>
                            ${escapeHtml(q.question)}
                        </h3>



                        <div id="choices"></div>



                        <p id="feedback"></p>



                    </div>


                `;





                const choices =
                    document.getElementById(
                        "choices"
                    );





                q.choices.forEach(
                    (choice, index) => {


                        const button =
                            document.createElement(
                                "button"
                            );



                        button.className =
                            "choice";


                        button.innerText =
                            choice;




                        button.onclick = () => {



                            document
                                .querySelectorAll(".choice")
                                .forEach(btn => {

                                    btn.disabled = true;

                                });





                            if (index === q.answer) {


                                button.classList.add(
                                    "correct"
                                );


                                score++;


                            }


                            else {


                                button.classList.add(
                                    "wrong"
                                );


                                document
                                    .querySelectorAll(".choice")
                                [q.answer]
                                    .classList.add(
                                        "correct"
                                    );


                            }





                            document
                                .getElementById("feedback")
                                .innerHTML = `


                            <br>

                            ${escapeHtml(
                                    q.explanation
                                )}


                            <br><br>


                            <button
                                class="action-btn"
                                onclick="nextQuestion()"
                            >

                                Next Question

                            </button>


                        `;



                        };




                        choices.appendChild(button);



                    });



            }




            window.nextQuestion = function () {



                current++;



                if (current >= data.questions.length) {



                    output.innerHTML = `


                        <div class="study-card">


                            <h2>
                                Quiz Complete 🎉
                            </h2>



                            <h1>
                                ${score}/
                                ${data.questions.length}
                            </h1>



                        </div>


                    `;

                    return;


                }



                showQuestion();



            };





            showQuestion();



        }








        // =================================
        // QUIZLET STYLE FLASHCARDS
        // =================================


        function createFlashcards(data) {


            let current = 0;




            function showCard() {


                const card =
                    data.cards[current];



                output.innerHTML = `



                    <div>


                        <div
                            class="flashcard"
                            onclick="
                                this.classList.toggle('flip')
                            "
                        >


                            <div class="flash-inner">


                                <div class="flash-front">

                                    ${escapeHtml(
                    card.front
                )}

                                </div>



                                <div class="flash-back">

                                    ${escapeHtml(
                    card.back
                )}

                                </div>


                            </div>


                        </div>





                        <h3>
                            Card
                            ${current + 1}/
                            ${data.cards.length}
                        </h3>



                        <button
                            class="action-btn"
                            onclick="previousCard()"
                        >

                            ← Previous

                        </button>




                        <button
                            class="action-btn"
                            onclick="nextCard()"
                        >

                            Next →

                        </button>



                    </div>


                `;



            }





            window.nextCard = function () {


                if (
                    current <
                    data.cards.length - 1
                ) {

                    current++;

                }


                showCard();


            };






            window.previousCard = function () {


                if (current > 0) {

                    current--;

                }


                showCard();


            };





            showCard();


        }



    </script>


</body>


</html>