<?php

require_once 'db.php';

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {

    die("Invalid item.");
}

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM generated_items
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database error.");

}

if (!$item) {

    die("Item not found.");
}

$name = htmlspecialchars(
    $item["name"],
    ENT_QUOTES,
    "UTF-8"
);

$type = $item["type"];

$content = $item["content"];

$presentationUrl =
    $item["presentation_url"];

// ---------------------------------------------------------
// PRESENTATIONS: OPEN THE GOOGLE SLIDES PRESENTATION
// DIRECTLY INSTEAD OF SHOWING AN INTERMEDIATE PAGE.
// ---------------------------------------------------------

if ($type === "presentation" && !empty($presentationUrl)) {

    header("Location: " . $presentationUrl);
    exit;
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $name; ?>
    </title>

    <!-- Mermaid for flowcharts -->
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

        window.dispatchEvent(
            new Event("mermaidReady")
        );

    </script>

    <!-- MathJax for readable mathematical notation -->
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [
                    ["\\(", "\\)"],
                    ["$", "$"]
                ],
                displayMath: [
                    ["\\[", "\\]"],
                    ["$$", "$$"]
                ]
            },
            svg: {
                fontCache: "global"
            }
        };
    </script>

    <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js">
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(135deg,
                    #eef2ff,
                    #f8fafc);

            min-height: 100vh;

            padding: 40px;

        }

        .container {

            max-width: 1100px;

            margin: auto;

        }

        .header {

            background: white;

            border-radius: 18px;

            padding: 25px 30px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .12);

            margin-bottom: 25px;

        }

        .header h1 {

            margin: 0 0 8px 0;

            color: #1e293b;

        }

        .type {

            color: #64748b;

            font-size: 15px;

        }

        .content {

            background: white;

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .12);

        }

        .study-card {

            background: #f8fafc;

            padding: 25px;

            border-radius: 18px;

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

        .flashcard {

            width: 500px;

            max-width: 100%;

            height: 300px;

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

            padding: 30px;

            border-radius: 20px;

            backface-visibility: hidden;

            font-size: 24px;

            text-align: center;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .15);

            background: white;

        }

        .flash-back {

            transform:
                rotateY(180deg);

            background: #eff6ff;

        }

        .presentation-link {

            display: inline-block;

            padding: 16px 30px;

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

        #flowchart {

            overflow-x: auto;

            display: flex;

            justify-content: center;

            width: 100%;

            min-height: 200px;

        }

        #flowchart svg {

            max-width: 100%;

            height: auto;

        }

        .math-content {

            line-height: 1.7;

        }
    </style>

</head>

<body>

    <div class="container">

        <div class="header">

            <h1>
                <?php echo $name; ?>
            </h1>

            <div class="type">

                <?php

                $icons = [

                    "flowchart" =>
                        "📊 Flowchart",

                    "quiz" =>
                        "📝 Quiz",

                    "flashcards" =>
                        "🃏 Flashcards",

                    "presentation" =>
                        "📽 Presentation"

                ];

                echo $icons[$type] ?? "📄 Study File";

                ?>

            </div>

        </div>

        <div class="content">

            <?php if ($type === "flowchart"): ?>

                <div id="flowchart"></div>

                <script>

                    const flowchartCode =
                        <?php echo json_encode($content); ?>;

                    const flowchartDiv =
                        document.getElementById("flowchart");

                    flowchartDiv.className =
                        "mermaid";

                    flowchartDiv.textContent =
                        flowchartCode;

                    async function renderFlowchart() {

                        if (!window.mermaid) {
                            return;
                        }

                        try {

                            await window.mermaid.run({
                                nodes: [flowchartDiv]
                            });

                        } catch (error) {

                            flowchartDiv.className = "";

                            flowchartDiv.textContent =
                                "Unable to render this flowchart: "
                                + error.message;

                        }

                    }

                    if (window.mermaid) {

                        renderFlowchart();

                    } else {

                        window.addEventListener(
                            "mermaidReady",
                            renderFlowchart,
                            { once: true }
                        );

                    }

                </script>

            <?php elseif ($type === "quiz"): ?>

                <div id="quiz" class="math-content"></div>

                <script>

                    const quizData =
                        <?php echo json_encode(
                            json_decode($content, true),
                            JSON_UNESCAPED_UNICODE
                        ); ?>;

                    let currentQuestion = 0;

                    let score = 0;

                    function typesetMath(element) {

                        if (
                            window.MathJax &&
                            window.MathJax.typesetPromise
                        ) {

                            window.MathJax.typesetPromise(
                                [element]
                            ).catch(() => { });

                        }

                    }

                    function showQuestion() {

                        const q =
                            quizData.questions[currentQuestion];

                        const quiz =
                            document.getElementById("quiz");

                        quiz.innerHTML = `

        <div class="study-card">

            <h2>
                Question
                ${currentQuestion + 1}
                /
                ${quizData.questions.length}
            </h2>

            <h3>
                ${q.question}
            </h3>

            <div id="choices"></div>

            <p id="feedback"></p>

        </div>

    `;

                        const choices =
                            document.getElementById("choices");

                        q.choices.forEach(
                            (choice, index) => {

                                const button =
                                    document.createElement("button");

                                button.className =
                                    "choice";

                                button.innerHTML =
                                    choice;

                                button.onclick = () => {

                                    document
                                        .querySelectorAll(".choice")
                                        .forEach(
                                            btn =>
                                                btn.disabled = true
                                        );

                                    if (index === q.answer) {

                                        button.classList.add(
                                            "correct"
                                        );

                                        score++;

                                    } else {

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

                    <p>
                        ${q.explanation}
                    </p>

                    <button
                        class="action-btn"
                        onclick="nextQuestion()"
                    >

                        Next Question

                    </button>

                `;

                                    typesetMath(
                                        document.getElementById(
                                            "quiz"
                                        )
                                    );

                                };

                                choices.appendChild(button);

                            }
                        );

                        typesetMath(quiz);

                    }

                    window.nextQuestion = function () {

                        currentQuestion++;

                        if (
                            currentQuestion >=
                            quizData.questions.length
                        ) {

                            document.getElementById("quiz")
                                .innerHTML = `

            <div class="study-card">

                <h2>
                    Quiz Complete 🎉
                </h2>

                <h1>
                    ${score}/${quizData.questions.length}
                </h1>

            </div>

        `;

                            typesetMath(
                                document.getElementById("quiz")
                            );

                            return;

                        }

                        showQuestion();

                    };

                    showQuestion();

                </script>

            <?php elseif ($type === "flashcards"): ?>

                <div id="flashcards" class="math-content"></div>

                <script>

                    const flashcardData =
                        <?php echo json_encode(
                            json_decode($content, true),
                            JSON_UNESCAPED_UNICODE
                        ); ?>;

                    let currentCard = 0;

                    function typesetFlashcards() {

                        const element =
                            document.getElementById(
                                "flashcards"
                            );

                        if (
                            window.MathJax &&
                            window.MathJax.typesetPromise
                        ) {

                            window.MathJax.typesetPromise(
                                [element]
                            ).catch(() => { });

                        }

                    }

                    function showCard() {

                        const card =
                            flashcardData.cards[currentCard];

                        document.getElementById(
                            "flashcards"
                        ).innerHTML = `

        <div
            class="flashcard"
            onclick="
                this.classList.toggle('flip')
            "
        >

            <div class="flash-inner">

                <div class="flash-front">

                    ${card.front}

                </div>

                <div class="flash-back">

                    ${card.back}

                </div>

            </div>

        </div>

        <h3 style="text-align:center">

            Card
            ${currentCard + 1}
            /
            ${flashcardData.cards.length}

        </h3>

        <div style="text-align:center">

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

                        typesetFlashcards();

                    }

                    window.nextCard = function () {

                        if (
                            currentCard <
                            flashcardData.cards.length - 1
                        ) {

                            currentCard++;

                        }

                        showCard();

                    };

                    window.previousCard = function () {

                        if (currentCard > 0) {

                            currentCard--;

                        }

                        showCard();

                    };

                    showCard();

                </script>

            <?php elseif ($type === "presentation"): ?>

                <div class="study-card">

                    <h2>
                        Presentation
                    </h2>

                    <p>
                        This presentation was generated as
                        an editable Google Slides presentation.
                    </p>

                    <?php if ($presentationUrl): ?>

                        <a class="presentation-link" href="<?php echo htmlspecialchars(
                            $presentationUrl,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>" target="_blank">

                            Open Google Slides

                        </a>

                    <?php else: ?>

                        <p>
                            No Google Slides URL was saved.
                        </p>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <div class="study-card math-content">

                    <pre><?php

                    echo htmlspecialchars(
                        $content,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    ?></pre>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>