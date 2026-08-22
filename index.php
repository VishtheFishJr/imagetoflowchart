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
        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(135deg,
                    #eef2ff,
                    #f8fafc);

            color: #1e293b;

        }



        /* =====================================================
           MAIN APP
        ===================================================== */

        .app {

            min-height: 100vh;

            display: flex;

            flex-direction: column;

        }



        /* =====================================================
           TOP BAR
        ===================================================== */

        .topbar {

            height: 64px;

            background: rgba(255, 255, 255, .92);

            backdrop-filter: blur(15px);

            border-bottom:
                1px solid #dbe3ef;

            display: flex;

            align-items: center;

            padding:
                0 22px;

            gap: 20px;

            position: sticky;

            top: 0;

            z-index: 100;

        }



        .app-title {

            font-size: 20px;

            font-weight: 700;

            white-space: nowrap;

            color: #172033;

        }



        .topbar-spacer {

            flex: 1;

        }



        .storage-toggle {

            border: none;

            background: #2563eb;

            color: white;

            padding:
                10px 17px;

            border-radius: 10px;

            cursor: pointer;

            font-size: 14px;

            font-weight: 600;

        }



        .storage-toggle:hover {

            background: #1d4ed8;

        }



        /* =====================================================
           FINDER
        ===================================================== */

        #finder {

            display: none;

            position: fixed;

            inset: 64px 0 0 0;

            background: #f8fafc;

            z-index: 90;

        }



        #finder.visible {

            display: flex;

        }



        /* =====================================================
           FINDER SIDEBAR
        ===================================================== */

        .finder-sidebar {

            width: 245px;

            flex-shrink: 0;

            background:
                rgba(241, 245, 249, .96);

            border-right:
                1px solid #d8e0ea;

            padding:
                20px 12px;

            overflow-y: auto;

        }



        .sidebar-section-title {

            font-size: 12px;

            font-weight: 700;

            color: #64748b;

            text-transform: uppercase;

            letter-spacing: .06em;

            padding:
                8px 12px;

            margin-top: 5px;

        }



        .sidebar-item {

            width: 100%;

            border: none;

            background: transparent;

            text-align: left;

            padding:
                10px 12px;

            border-radius: 8px;

            font-size: 14px;

            cursor: pointer;

            color: #334155;

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 2px;

        }



        .sidebar-item:hover {

            background: #e2e8f0;

        }



        .sidebar-item.active {

            background: #dbeafe;

            color: #1d4ed8;

            font-weight: 600;

        }



        .sidebar-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;

        }



        .sidebar-count {

            margin-left: auto;

            color: #64748b;

            font-size: 12px;

        }



        /* =====================================================
           FINDER MAIN
        ===================================================== */

        .finder-main {

            flex: 1;

            min-width: 0;

            display: flex;

            flex-direction: column;

        }



        .finder-toolbar {

            height: 60px;

            background: white;

            border-bottom:
                1px solid #dbe3ef;

            display: flex;

            align-items: center;

            padding:
                0 18px;

            gap: 12px;

        }



        .finder-title {

            font-size: 18px;

            font-weight: 700;

            white-space: nowrap;

        }



        .finder-search {

            margin-left: auto;

            width: 250px;

            padding:
                9px 13px;

            border:
                1px solid #cbd5e1;

            border-radius: 9px;

            outline: none;

            font-size: 14px;

        }



        .finder-search:focus {

            border-color: #2563eb;

        }



        .sort-select {

            padding:
                9px 10px;

            border:
                1px solid #cbd5e1;

            border-radius: 9px;

            background: white;

            font-size: 13px;

        }



        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .finder-breadcrumb {

            min-height: 45px;

            display: flex;

            align-items: center;

            gap: 7px;

            padding:
                0 20px;

            background: #f8fafc;

            border-bottom:
                1px solid #e2e8f0;

            font-size: 13px;

            color: #64748b;

        }



        .breadcrumb-button {

            border: none;

            background: transparent;

            color: #2563eb;

            cursor: pointer;

            font-size: 13px;

            padding: 3px;

        }



        /* =====================================================
           COLUMN VIEW
        ===================================================== */

        .finder-content {

            flex: 1;

            overflow: hidden;

            display: flex;

        }



        .finder-column {

            width: 300px;

            min-width: 300px;

            overflow-y: auto;

            overflow-x: hidden;

            background: white;

            border-right:
                1px solid #dbe3ef;

            padding:
                8px;

        }



        .finder-column:last-child {

            flex: 1;

            border-right: none;

        }



        .column-empty {

            text-align: center;

            color: #94a3b8;

            padding: 45px 20px;

            font-size: 14px;

        }



        /* =====================================================
           FOLDERS
        ===================================================== */

        .folder-item,
        .file-item {

            width: 100%;

            min-height: 54px;

            border: none;

            background: transparent;

            border-radius: 8px;

            display: flex;

            align-items: center;

            text-align: left;

            padding:
                7px 10px;

            cursor: pointer;

            margin-bottom: 2px;

        }



        .folder-item:hover,
        .file-item:hover {

            background: #f1f5f9;

        }



        .folder-item.selected,
        .file-item.selected {

            background: #dbeafe;

        }



        .file-icon,
        .folder-icon {

            width: 40px;

            font-size: 25px;

            text-align: center;

            flex-shrink: 0;

        }



        .item-info {

            min-width: 0;

            flex: 1;

        }



        .item-name {

            font-size: 14px;

            font-weight: 600;

            color: #1e293b;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }



        .item-meta {

            margin-top: 3px;

            font-size: 11px;

            color: #94a3b8;

        }



        .folder-arrow {

            color: #94a3b8;

            font-size: 18px;

        }



        /* =====================================================
           FILE DETAILS
        ===================================================== */

        .file-details {

            padding: 35px;

            max-width: 600px;

        }



        .details-icon {

            font-size: 70px;

            margin-bottom: 15px;

        }



        .details-name {

            font-size: 25px;

            font-weight: 700;

            margin-bottom: 12px;

            word-break: break-word;

        }



        .details-row {

            display: flex;

            justify-content: space-between;

            border-bottom:
                1px solid #e2e8f0;

            padding:
                11px 0;

            font-size: 14px;

        }



        .details-label {

            color: #64748b;

        }



        .details-value {

            font-weight: 600;

            text-align: right;

            max-width: 65%;

            word-break: break-word;

        }



        .open-file-button {

            margin-top: 25px;

            padding:
                12px 22px;

            border: none;

            border-radius: 9px;

            background: #2563eb;

            color: white;

            cursor: pointer;

            font-size: 15px;

            font-weight: 600;

        }



        .open-file-button:hover {

            background: #1d4ed8;

        }



        /* =====================================================
           RENAME
        ===================================================== */

        .rename-input {

            width: 100%;

            padding:
                5px 7px;

            border:
                2px solid #2563eb;

            border-radius: 5px;

            font-size: 14px;

            outline: none;

        }



        /* =====================================================
           CONTEXT MENU
        ===================================================== */

        #contextMenu {

            display: none;

            position: fixed;

            z-index: 500;

            background: white;

            border:
                1px solid #cbd5e1;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, .18);

            border-radius: 8px;

            min-width: 160px;

            padding: 5px;

        }



        .context-option {

            width: 100%;

            padding:
                9px 12px;

            border: none;

            background: transparent;

            text-align: left;

            border-radius: 6px;

            cursor: pointer;

            font-size: 13px;

        }



        .context-option:hover {

            background: #f1f5f9;

        }



        /* =====================================================
           SCANNER
        ===================================================== */

        #scanner {

            min-height: 100vh;

        }



        .container {

            width: 100%;

            max-width: 900px;

            margin: 0 auto;

            text-align: center;

            padding: 30px;

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
                0 10px 25px rgba(0, 0, 0, .2);

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

            padding:
                14px 25px;

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



        /* =====================================================
           STUDY CARDS
        ===================================================== */

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



        /* =====================================================
           FLASHCARDS
        ===================================================== */

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

            transform: rotateY(180deg);

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

            transform: rotateY(180deg);

            background: #eff6ff;

        }



        .action-btn {

            padding:
                12px 25px;

            margin: 10px;

            border: none;

            border-radius: 10px;

            background: #2563eb;

            color: white;

            cursor: pointer;

            font-size: 16px;

        }



        /* =====================================================
           PRESENTATION
        ===================================================== */

        .presentation-link {

            display: inline-block;

            margin-top: 25px;

            padding:
                15px 30px;

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



        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 800px) {

            .finder-sidebar {

                width: 190px;

            }

            .finder-column {

                min-width: 240px;

                width: 240px;

            }

            .finder-search {

                width: 150px;

            }

        }
    </style>

</head>


<body>


    <div class="app">


        <!-- =====================================================
         TOP BAR
    ===================================================== -->

        <div class="topbar">

            <div class="app-title">
                AI Study Scanner
            </div>


            <div class="topbar-spacer"></div>


            <button class="storage-toggle" onclick="toggleFinder()">

                📁 My Files

            </button>

        </div>



        <!-- =====================================================
         FINDER
    ===================================================== -->

        <div id="finder">


            <!-- SIDEBAR -->

            <aside class="finder-sidebar">


                <div class="sidebar-section-title">
                    Favorites
                </div>


                <button class="sidebar-item active" data-folder="all" onclick="openFolder('all')">

                    <span class="sidebar-icon">
                        📁
                    </span>

                    <span>
                        All Files
                    </span>

                    <span class="sidebar-count" id="count-all">
                        0
                    </span>

                </button>


                <button class="sidebar-item" data-folder="recent" onclick="openFolder('recent')">

                    <span class="sidebar-icon">
                        🕘
                    </span>

                    <span>
                        Recents
                    </span>

                </button>



                <div class="sidebar-section-title">
                    Study Files
                </div>


                <button class="sidebar-item" data-folder="flowchart" onclick="openFolder('flowchart')">

                    <span class="sidebar-icon">
                        📊
                    </span>

                    <span>
                        Flowcharts
                    </span>

                    <span class="sidebar-count" id="count-flowchart">
                        0
                    </span>

                </button>


                <button class="sidebar-item" data-folder="quiz" onclick="openFolder('quiz')">

                    <span class="sidebar-icon">
                        📝
                    </span>

                    <span>
                        Quizzes
                    </span>

                    <span class="sidebar-count" id="count-quiz">
                        0
                    </span>

                </button>


                <button class="sidebar-item" data-folder="flashcards" onclick="openFolder('flashcards')">

                    <span class="sidebar-icon">
                        🃏
                    </span>

                    <span>
                        Flashcards
                    </span>

                    <span class="sidebar-count" id="count-flashcards">
                        0
                    </span>

                </button>


                <button class="sidebar-item" data-folder="presentation" onclick="openFolder('presentation')">

                    <span class="sidebar-icon">
                        📽
                    </span>

                    <span>
                        Presentations
                    </span>

                    <span class="sidebar-count" id="count-presentation">
                        0
                    </span>

                </button>


            </aside>



            <!-- FINDER MAIN -->

            <main class="finder-main">


                <div class="finder-toolbar">


                    <div class="finder-title" id="finder-title">

                        All Files

                    </div>


                    <input type="text" id="finder-search" class="finder-search" placeholder="Search files..."
                        oninput="renderFinder()">


                    <select id="sort-select" class="sort-select" onchange="renderFinder()">

                        <option value="updated">
                            Date Modified
                        </option>

                        <option value="name">
                            Name
                        </option>

                        <option value="type">
                            Type
                        </option>

                    </select>


                </div>



                <div class="finder-breadcrumb" id="finder-breadcrumb">

                    <button class="breadcrumb-button" onclick="openFolder('all')">

                        📁 All Files

                    </button>

                </div>



                <div class="finder-content" id="finder-content">


                    <div class="finder-column" id="folder-column">

                    </div>


                    <div class="finder-column" id="file-column">

                        <div class="column-empty">

                            Select a folder

                        </div>

                    </div>


                    <div class="finder-column" id="details-column">

                        <div class="column-empty">

                            Select a file

                        </div>

                    </div>


                </div>


            </main>

        </div>



        <!-- =====================================================
         SCANNER
    ===================================================== -->

        <div id="scanner">


            <div class="container">


                <h1>
                    AI Study Scanner
                </h1>


                <video id="webcam" autoplay playsinline>
                </video>


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


        </div>


    </div>



    <!-- =====================================================
     CONTEXT MENU
===================================================== -->

    <div id="contextMenu">


        <button class="context-option" onclick="renameSelected()">

            ✏️ Rename

        </button>


        <button class="context-option" onclick="openSelected()">

            📂 Open

        </button>

    </div>



    <script>


        /* =========================================================
           FINDER STATE
        ========================================================= */


        let allItems = [];

        let currentFolder = "all";

        let selectedItem = null;

        let selectedFolder = null;



        /* =========================================================
           FOLDER DEFINITIONS
        ========================================================= */


        const folderDefinitions = [

            {
                id: "flowchart",
                name: "Flowcharts",
                icon: "📊"
            },

            {
                id: "quiz",
                name: "Quizzes",
                icon: "📝"
            },

            {
                id: "flashcards",
                name: "Flashcards",
                icon: "🃏"
            },

            {
                id: "presentation",
                name: "Presentations",
                icon: "📽"
            }

        ];



        /* =========================================================
           GET ITEMS
        ========================================================= */


        async function loadItems() {


            try {


                const response =
                    await fetch("get_items.php");


                const data =
                    await response.json();


                if (Array.isArray(data)) {

                    allItems = data;

                }

                else if (
                    Array.isArray(data.items)
                ) {

                    allItems = data.items;

                }

                else {

                    allItems = [];

                }


                updateCounts();


                renderFinder();


            }

            catch (error) {

                console.error(
                    "Could not load generated items:",
                    error
                );

            }

        }



        /* =========================================================
           COUNTS
        ========================================================= */


        function updateCounts() {


            document
                .getElementById("count-all")
                .innerText =
                allItems.length;


            folderDefinitions.forEach(folder => {


                const count =
                    allItems.filter(
                        item =>
                            item.type === folder.id
                    ).length;


                const element =
                    document.getElementById(
                        "count-" + folder.id
                    );


                if (element) {

                    element.innerText = count;

                }

            });

        }



        /* =========================================================
           FOLDER NAVIGATION
        ========================================================= */


        function openFolder(folder) {


            currentFolder = folder;

            selectedItem = null;


            document
                .querySelectorAll(".sidebar-item")
                .forEach(item => {

                    item.classList.remove("active");

                });


            const active =
                document.querySelector(
                    `.sidebar-item[data-folder="${folder}"]`
                );


            if (active) {

                active.classList.add("active");

            }


            const titles = {

                all: "All Files",

                recent: "Recents",

                flowchart: "Flowcharts",

                quiz: "Quizzes",

                flashcards: "Flashcards",

                presentation: "Presentations"

            };


            document
                .getElementById("finder-title")
                .innerText =
                titles[folder] || "Files";


            renderFinder();

        }



        /* =========================================================
           FILTER ITEMS
        ========================================================= */


        function getVisibleItems() {


            let items = [...allItems];


            if (currentFolder !== "all") {


                if (currentFolder === "recent") {


                    items.sort(
                        (a, b) =>
                            new Date(b.updated_at || b.created_at)
                            -
                            new Date(a.updated_at || a.created_at)
                    );


                    items =
                        items.slice(0, 20);

                }

                else {

                    items =
                        items.filter(
                            item =>
                                item.type === currentFolder
                        );

                }

            }


            const search =
                document
                    .getElementById("finder-search")
                    .value
                    .trim()
                    .toLowerCase();


            if (search) {

                items =
                    items.filter(item =>

                        String(item.name || "")
                            .toLowerCase()
                            .includes(search)

                    );

            }


            const sort =
                document
                    .getElementById("sort-select")
                    .value;


            if (sort === "name") {

                items.sort(
                    (a, b) =>
                        String(a.name || "")
                            .localeCompare(
                                String(b.name || "")
                            )
                );

            }


            else if (sort === "type") {

                items.sort(
                    (a, b) =>
                        String(a.type || "")
                            .localeCompare(
                                String(b.type || "")
                            )
                );

            }


            else {

                items.sort(
                    (a, b) =>
                        new Date(
                            b.updated_at ||
                            b.created_at
                        )
                        -
                        new Date(
                            a.updated_at ||
                            a.created_at
                        )
                );

            }


            return items;

        }



        /* =========================================================
           RENDER FINDER
        ========================================================= */


        function renderFinder() {


            const folderColumn =
                document.getElementById(
                    "folder-column"
                );


            const fileColumn =
                document.getElementById(
                    "file-column"
                );


            const detailsColumn =
                document.getElementById(
                    "details-column"
                );


            folderColumn.innerHTML = "";

            fileColumn.innerHTML = "";

            detailsColumn.innerHTML = `

        <div class="column-empty">

            Select a file

        </div>

    `;



            /* -----------------------------------------
               FOLDER COLUMN
            ----------------------------------------- */


            if (
                currentFolder === "all" ||
                currentFolder === "recent"
            ) {


                folderDefinitions.forEach(folder => {


                    const count =
                        allItems.filter(
                            item =>
                                item.type === folder.id
                        ).length;


                    const button =
                        document.createElement("button");


                    button.className =
                        "folder-item";


                    button.innerHTML = `

                <span class="folder-icon">
                    ${folder.icon}
                </span>

                <span class="item-info">

                    <span class="item-name">
                        ${escapeHtml(folder.name)}
                    </span>

                    <span class="item-meta">
                        ${count} item${count === 1 ? "" : "s"}
                    </span>

                </span>

                <span class="folder-arrow">
                    ›
                </span>

            `;


                    button.onclick = () => {


                        selectedFolder =
                            folder.id;


                        document
                            .querySelectorAll(".folder-item")
                            .forEach(el =>
                                el.classList.remove(
                                    "selected"
                                )
                            );


                        button.classList.add(
                            "selected"
                        );


                        renderFolderFiles(
                            folder.id
                        );

                    };


                    folderColumn.appendChild(
                        button
                    );

                });


            }


            else {


                folderColumn.innerHTML = `

            <div class="column-empty">

                📁

                <br><br>

                ${escapeHtml(
                    getFolderName(currentFolder)
                )}

            </div>

        `;


                renderFolderFiles(
                    currentFolder
                );

            }


        }



        /* =========================================================
           RENDER FILE COLUMN
        ========================================================= */


        function renderFolderFiles(folder) {


            const fileColumn =
                document.getElementById(
                    "file-column"
                );


            fileColumn.innerHTML = "";


            let items =
                getVisibleItems();


            if (folder !== "all" &&
                folder !== "recent") {

                items =
                    items.filter(
                        item =>
                            item.type === folder
                    );

            }


            if (!items.length) {


                fileColumn.innerHTML = `

            <div class="column-empty">

                This folder is empty.

            </div>

        `;


                return;

            }


            items.forEach(item => {


                const button =
                    document.createElement("button");


                button.className =
                    "file-item";


                button.dataset.id =
                    item.id;


                button.innerHTML = `

            <span class="file-icon">

                ${getTypeIcon(item.type)}

            </span>

            <span class="item-info">

                <span
                    class="item-name"
                    title="${escapeHtml(item.name || "Untitled")}">

                    ${escapeHtml(
                    item.name || "Untitled"
                )}

                </span>

                <span class="item-meta">

                    ${getTypeName(item.type)}
                    •
                    ${formatDate(
                    item.updated_at ||
                    item.created_at
                )}

                </span>

            </span>

        `;


                button.onclick = () => {


                    document
                        .querySelectorAll(".file-item")
                        .forEach(el =>
                            el.classList.remove(
                                "selected"
                            )
                        );


                    button.classList.add(
                        "selected"
                    );


                    selectedItem = item;


                    renderDetails(item);

                };


                button.ondblclick = () => {

                    openItem(item);

                };


                button.oncontextmenu = event => {


                    event.preventDefault();


                    selectedItem = item;


                    showContextMenu(
                        event.clientX,
                        event.clientY
                    );

                };


                fileColumn.appendChild(
                    button
                );

            });

        }



        /* =========================================================
           DETAILS COLUMN
        ========================================================= */


        function renderDetails(item) {


            const details =
                document.getElementById(
                    "details-column"
                );


            details.innerHTML = `

        <div class="file-details">

            <div class="details-icon">

                ${getTypeIcon(item.type)}

            </div>


            <div class="details-name">

                ${escapeHtml(
                item.name || "Untitled"
            )}

            </div>


            <div class="details-row">

                <span class="details-label">
                    Kind
                </span>

                <span class="details-value">
                    ${getTypeName(item.type)}
                </span>

            </div>


            <div class="details-row">

                <span class="details-label">
                    Created
                </span>

                <span class="details-value">
                    ${formatDate(
                item.created_at
            )}
                </span>

            </div>


            <div class="details-row">

                <span class="details-label">
                    Modified
                </span>

                <span class="details-value">
                    ${formatDate(
                item.updated_at ||
                item.created_at
            )}
                </span>

            </div>


            <div class="details-row">

                <span class="details-label">
                    ID
                </span>

                <span class="details-value">
                    ${item.id}
                </span>

            </div>


            <button
                class="open-file-button"
                onclick="openSelected()">

                Open

            </button>


        </div>

    `;

        }



        /* =========================================================
           OPEN ITEM
        ========================================================= */


        function openItem(item) {


            if (!item || !item.id) {

                return;

            }


            window.open(
                "opened_item.php?id=" +
                encodeURIComponent(item.id),

                "_blank"
            );

        }



        /* =========================================================
           OPEN SELECTED
        ========================================================= */


        function openSelected() {


            if (!selectedItem) {

                return;

            }


            openItem(
                selectedItem
            );

        }



        /* =========================================================
           RENAME
        ========================================================= */


        async function renameSelected() {


            if (!selectedItem) {

                hideContextMenu();

                return;

            }


            hideContextMenu();


            const fileElement =
                document.querySelector(
                    `.file-item[data-id="${selectedItem.id}"]`
                );


            if (!fileElement) {

                return;

            }


            const nameElement =
                fileElement.querySelector(
                    ".item-name"
                );


            const oldName =
                selectedItem.name ||
                "Untitled";


            const input =
                document.createElement("input");


            input.className =
                "rename-input";


            input.value =
                oldName;


            nameElement.replaceWith(
                input
            );


            input.focus();

            input.select();



            let finished = false;



            async function finishRename(
                save
            ) {


                if (finished) {

                    return;

                }


                finished = true;


                const newName =
                    input.value.trim();


                if (
                    !save ||
                    !newName ||
                    newName === oldName
                ) {

                    renderFinder();

                    return;

                }


                try {


                    const response =
                        await fetch(
                            "rename_item.php",
                            {

                                method: "POST",

                                headers: {

                                    "Content-Type":
                                        "application/json"

                                },

                                body:
                                    JSON.stringify({

                                        id:
                                            selectedItem.id,

                                        name:
                                            newName

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
                            "Could not rename item."
                        );

                        return;

                    }


                    selectedItem.name =
                        newName;


                    const index =
                        allItems.findIndex(
                            item =>
                                String(item.id) ===
                                String(selectedItem.id)
                        );


                    if (index !== -1) {

                        allItems[index].name =
                            newName;

                    }


                    renderFinder();

                    renderFolderFiles(
                        currentFolder
                    );


                }

                catch (error) {


                    console.error(
                        error
                    );


                    alert(
                        "Could not rename item."
                    );

                }

            }



            input.onkeydown =
                event => {


                    if (
                        event.key === "Enter"
                    ) {

                        finishRename(true);

                    }


                    if (
                        event.key === "Escape"
                    ) {

                        finishRename(false);

                    }

                };


            input.onblur =
                () => {

                    finishRename(true);

                };

        }



        /* =========================================================
           F2 RENAME
        ========================================================= */


        document.addEventListener(
            "keydown",
            event => {


                if (
                    event.key === "F2" &&
                    selectedItem &&
                    document.getElementById(
                        "finder"
                    ).classList.contains("visible")
                ) {

                    event.preventDefault();

                    renameSelected();

                }


                if (
                    event.key === "Enter" &&
                    selectedItem &&
                    document.getElementById(
                        "finder"
                    ).classList.contains("visible")
                ) {

                    openSelected();

                }

            }
        );



        /* =========================================================
           CONTEXT MENU
        ========================================================= */


        function showContextMenu(
            x,
            y
        ) {


            const menu =
                document.getElementById(
                    "contextMenu"
                );


            menu.style.display =
                "block";


            menu.style.left =
                x + "px";


            menu.style.top =
                y + "px";

        }



        function hideContextMenu() {


            document
                .getElementById(
                    "contextMenu"
                )
                .style.display =
                "none";

        }



        document.addEventListener(
            "click",
            hideContextMenu
        );



        /* =========================================================
           FINDER TOGGLE
        ========================================================= */


        function toggleFinder() {


            const finder =
                document.getElementById(
                    "finder"
                );


            const scanner =
                document.getElementById(
                    "scanner"
                );


            if (
                finder.classList.contains(
                    "visible"
                )
            ) {


                finder.classList.remove(
                    "visible"
                );


                scanner.style.display =
                    "block";


            }

            else {


                finder.classList.add(
                    "visible"
                );


                scanner.style.display =
                    "none";


                loadItems();


            }

        }



        /* =========================================================
           HELPERS
        ========================================================= */


        function getFolderName(
            type
        ) {


            const folder =
                folderDefinitions.find(
                    item =>
                        item.id === type
                );


            return folder
                ? folder.name
                : "Files";

        }



        function getTypeName(
            type
        ) {


            const names = {

                flowchart: "Flowchart",

                quiz: "Quiz",

                flashcards: "Flashcards",

                presentation: "Presentation"

            };


            return names[type] ||
                "Study File";

        }



        function getTypeIcon(
            type
        ) {


            const icons = {

                flowchart: "📊",

                quiz: "📝",

                flashcards: "🃏",

                presentation: "📽"

            };


            return icons[type] ||
                "📄";

        }



        function formatDate(
            date
        ) {


            if (!date) {

                return "Unknown";

            }


            const parsed =
                new Date(
                    date
                );


            if (
                Number.isNaN(
                    parsed.getTime()
                )
            ) {

                return date;

            }


            return parsed.toLocaleDateString(
                undefined,
                {

                    month: "short",

                    day: "numeric",

                    year: "numeric"

                }
            );

        }



        function escapeHtml(
            value
        ) {


            return String(value ?? "")
                .replace(
                    /&/g,
                    "&amp;"
                )
                .replace(
                    /</g,
                    "&lt;"
                )
                .replace(
                    />/g,
                    "&gt;"
                )
                .replace(
                    /"/g,
                    "&quot;"
                )
                .replace(
                    /'/g,
                    "&#039;"
                );

        }



        /* =========================================================
           CAMERA
        ========================================================= */


        const video =
            document.getElementById(
                "webcam"
            );


        const canvas =
            document.getElementById(
                "canvas"
            );


        const status =
            document.getElementById(
                "ai-status"
            );


        const output =
            document.getElementById(
                "flowchart-render"
            );


        let selectedMode =
            "flowchart";



        async function initCamera() {


            try {


                const stream =
                    await navigator.mediaDevices
                        .getUserMedia({

                            video: {
                                facingMode:
                                    "environment"
                            }

                        });


                video.srcObject =
                    stream;


            }

            catch (err) {


                status.innerText =
                    "Camera error: " +
                    err.message;

            }

        }



        /* =========================================================
           MODE BUTTONS
        ========================================================= */


        document
            .querySelectorAll(".mode-btn")
            .forEach(button => {


                button.onclick = () => {


                    selectedMode =
                        button.dataset.mode;


                    scanImage();

                };

            });



        /* =========================================================
           IMAGE CAPTURE
        ========================================================= */


        async function scanImage() {


            status.innerText =
                "Analyzing...";


            output.innerHTML =
                "";


            canvas.width =
                video.videoWidth;


            canvas.height =
                video.videoHeight;


            const ctx =
                canvas.getContext(
                    "2d"
                );


            ctx.drawImage(

                video,

                0,

                0,

                canvas.width,

                canvas.height

            );


            const image =
                canvas.toDataURL(
                    "image/jpeg"
                );



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

                            body:
                                JSON.stringify({

                                    image:
                                        image,

                                    mode:
                                        selectedMode

                                })

                        }

                    );



                const text =
                    await response.text();


                console.log(
                    "SERVER RESPONSE:"
                );


                console.log(
                    text
                );


                const data =
                    JSON.parse(
                        text
                    );



                if (!data.success) {


                    status.innerText =
                        data.error;


                    return;

                }



                status.innerText =
                    "Generated successfully!";



                /* =========================================
                   FLOWCHART
                ========================================= */


                if (
                    selectedMode ===
                    "flowchart"
                ) {


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
                        document.createElement(
                            "div"
                        );


                    div.className =
                        "mermaid";


                    div.textContent =
                        code;


                    output.appendChild(
                        div
                    );


                    await mermaid.run({

                        nodes: [div]

                    });


                }



                /* =========================================
                   QUIZ
                ========================================= */


                else if (
                    selectedMode ===
                    "quiz"
                ) {


                    const quiz =
                        JSON.parse(
                            data.ai_response
                        );


                    createQuiz(
                        quiz
                    );

                }



                /* =========================================
                   FLASHCARDS
                ========================================= */


                else if (
                    selectedMode ===
                    "flashcards"
                ) {


                    const cards =
                        JSON.parse(
                            data.ai_response
                        );


                    createFlashcards(
                        cards
                    );

                }



                /* =========================================
                   PRESENTATION
                ========================================= */


                else if (
                    selectedMode ===
                    "presentation"
                ) {


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
                    "Error: " +
                    err.message;

            }



        }



        /* =========================================================
           GOOGLE SLIDES CREATION
        ========================================================= */


        async function createPresentation(
            data
        ) {


            output.innerHTML = `

        <div class="study-card">

            <h2>
                Creating Google Slides...
            </h2>

            <p>
                Please wait while your
                presentation is generated.
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

                            body:
                                JSON.stringify(
                                    data
                                )

                        }

                    );


                const result =
                    await response.json();



                if (
                    result.success
                ) {


                    output.innerHTML = `

                <div class="study-card">

                    <h2>
                        Presentation Created 🎉
                    </h2>

                    <p>
                        Your editable Google Slides
                        file is ready.
                    </p>

                    <a
                        class="presentation-link"
                        target="_blank"
                        href="${result.url}">

                        Open Google Slides

                    </a>

                </div>

            `;


                    /*
                     * Refresh Finder so the newly
                     * generated presentation appears.
                     */

                    await loadItems();


                }


                else {


                    output.innerHTML = `

                <div class="study-card">

                    <h2>
                        Error
                    </h2>

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



        /* =========================================================
           INTERACTIVE QUIZ
        ========================================================= */


        function createQuiz(
            data
        ) {


            let current = 0;

            let score = 0;



            function showQuestion() {


                const q =
                    data.questions[
                    current
                    ];


                output.innerHTML = `

            <div class="study-card">

                <h2>
                    Question
                    ${current + 1}/
                    ${data.questions.length}
                </h2>

                <h3>
                    ${escapeHtml(
                    q.question
                )}
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
                                .querySelectorAll(
                                    ".choice"
                                )
                                .forEach(
                                    btn => {

                                        btn.disabled =
                                            true;

                                    }
                                );



                            if (
                                index ===
                                q.answer
                            ) {


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
                                    .querySelectorAll(
                                        ".choice"
                                    )
                                [q.answer]
                                    .classList.add(
                                        "correct"
                                    );

                            }



                            document
                                .getElementById(
                                    "feedback"
                                )
                                .innerHTML = `

                            <br>

                            ${escapeHtml(
                                    q.explanation
                                )}

                            <br><br>

                            <button
                                class="action-btn"
                                onclick="nextQuestion()">

                                Next Question

                            </button>

                        `;

                        };


                        choices.appendChild(
                            button
                        );

                    }
                );

            }



            window.nextQuestion =
                function () {


                    current++;


                    if (
                        current >=
                        data.questions.length
                    ) {


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



        /* =========================================================
           QUIZLET FLASHCARDS
        ========================================================= */


        function createFlashcards(
            data
        ) {


            let current = 0;



            function showCard() {


                const card =
                    data.cards[
                    current
                    ];


                output.innerHTML = `

            <div>

                <div
                    class="flashcard"
                    onclick="
                        this.classList.toggle('flip')
                    ">

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
                    onclick="previousCard()">

                    ← Previous

                </button>


                <button
                    class="action-btn"
                    onclick="nextCard()">

                    Next →

                </button>

            </div>

        `;

            }



            window.nextCard =
                function () {


                    if (
                        current <
                        data.cards.length - 1
                    ) {

                        current++;

                    }


                    showCard();

                };



            window.previousCard =
                function () {


                    if (
                        current > 0
                    ) {

                        current--;

                    }


                    showCard();

                };



            showCard();

        }



        /* =========================================================
           START
        ========================================================= */


        initCamera();

        loadItems();


    </script>


</body>

</html>