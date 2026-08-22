<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudySpace — AI Study Scanner</title>

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
        /* =========================================================
   GLOBAL
========================================================= */

        * {
            box-sizing: border-box;
        }

        :root {

            --bg: #f5f7fb;

            --sidebar: #ffffff;

            --card: #ffffff;

            --border: #e2e8f0;

            --text: #172033;

            --muted: #64748b;

            --primary: #4f46e5;

            --primary-hover: #4338ca;

            --secondary: #eef2ff;

            --hover: #f1f5f9;

            --input: #f8fafc;

            --shadow:
                0 8px 30px rgba(15, 23, 42, .08);

            --radius: 16px;

        }


        /* DARK MODE */

        body.dark {

            --bg: #0f172a;

            --sidebar: #111827;

            --card: #1e293b;

            --border: #334155;

            --text: #f8fafc;

            --muted: #94a3b8;

            --primary: #818cf8;

            --primary-hover: #6366f1;

            --secondary: #312e81;

            --hover: #273449;

            --input: #172033;

            --shadow:
                0 8px 30px rgba(0, 0, 0, .3);

        }


        html,
        body {

            margin: 0;

            padding: 0;

            width: 100%;

            min-height: 100%;

        }


        body {

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;

            background: var(--bg);

            color: var(--text);

            transition:
                background .25s,
                color .25s;

        }


        /* =========================================================
   APP
========================================================= */

        .app {

            display: flex;

            min-height: 100vh;

        }


        /* =========================================================
   SIDEBAR
========================================================= */

        .sidebar {

            width: 250px;

            min-height: 100vh;

            background: var(--sidebar);

            border-right:
                1px solid var(--border);

            padding: 22px 15px;

            display: flex;

            flex-direction: column;

            position: fixed;

            left: 0;

            top: 0;

            bottom: 0;

            z-index: 20;

        }


        .logo {

            display: flex;

            align-items: center;

            gap: 11px;

            padding:
                5px 10px 20px;

        }


        .logo-icon {

            width: 42px;

            height: 42px;

            border-radius: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(135deg,
                    #6366f1,
                    #8b5cf6);

            color: white;

            font-size: 21px;

        }


        .logo-text {

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.5px;

        }


        .logo-sub {

            font-size: 11px;

            color: var(--muted);

            margin-top: 1px;

        }


        .sidebar-section {

            margin-top: 14px;

        }


        .sidebar-label {

            padding:
                8px 12px;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .08em;

            color: var(--muted);

        }


        .nav-item {

            width: 100%;

            border: none;

            background: transparent;

            color: var(--muted);

            padding:
                11px 12px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            gap: 12px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            text-align: left;

            margin-bottom: 3px;

        }


        .nav-item:hover {

            background: var(--hover);

            color: var(--text);

        }


        .nav-item.active {

            background: var(--secondary);

            color: var(--primary);

        }


        .nav-icon {

            width: 23px;

            text-align: center;

            font-size: 17px;

        }


        .sidebar-bottom {

            margin-top: auto;

        }


        /* =========================================================
   THEME TOGGLE
========================================================= */

        .theme-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                12px;

            color: var(--muted);

            font-size: 13px;

            font-weight: 600;

        }


        .theme-switch {

            position: relative;

            width: 48px;

            height: 26px;

        }


        .theme-switch input {

            opacity: 0;

            width: 0;

            height: 0;

        }


        .slider {

            position: absolute;

            cursor: pointer;

            inset: 0;

            background: #cbd5e1;

            border-radius: 30px;

            transition: .25s;

        }


        .slider:before {

            content: "";

            position: absolute;

            width: 20px;

            height: 20px;

            left: 3px;

            top: 3px;

            background: white;

            border-radius: 50%;

            transition: .25s;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, .2);

        }


        .theme-switch input:checked+.slider {

            background: #6366f1;

        }


        .theme-switch input:checked+.slider:before {

            transform:
                translateX(22px);

        }


        /* =========================================================
   MAIN
========================================================= */

        .main {

            margin-left: 250px;

            width:
                calc(100% - 250px);

            min-height: 100vh;

        }


        /* =========================================================
   TOPBAR
========================================================= */

        .topbar {

            height: 70px;

            background: var(--sidebar);

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 30px;

            position: sticky;

            top: 0;

            z-index: 10;

        }


        .search {

            width: 360px;

            position: relative;

        }


        .search input {

            width: 100%;

            border:
                1px solid var(--border);

            background: var(--input);

            color: var(--text);

            padding:
                11px 15px 11px 40px;

            border-radius: 11px;

            outline: none;

            font-size: 14px;

        }


        .search input:focus {

            border-color:
                var(--primary);

        }


        .search-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform:
                translateY(-50%);

            color: var(--muted);

        }


        .top-actions {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .student-badge {

            padding:
                9px 13px;

            border-radius: 10px;

            background: var(--secondary);

            color: var(--primary);

            font-size: 13px;

            font-weight: 700;

        }


        /* =========================================================
   CONTENT
========================================================= */

        .content {

            padding: 30px;

        }


        /* =========================================================
   DASHBOARD HEADER
========================================================= */

        .welcome {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;

        }


        .welcome h1 {

            margin: 0;

            font-size: 29px;

            letter-spacing: -.8px;

        }


        .welcome p {

            margin:
                7px 0 0;

            color: var(--muted);

            font-size: 14px;

        }


        .scan-button {

            border: none;

            background:
                linear-gradient(135deg,
                    #4f46e5,
                    #7c3aed);

            color: white;

            border-radius: 11px;

            padding:
                12px 18px;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 6px 15px rgba(79, 70, 229, .25);

        }


        .scan-button:hover {

            transform:
                translateY(-1px);

        }


        /* =========================================================
   STORAGE TOOLBAR
========================================================= */

        .storage-toolbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 18px;

        }


        .breadcrumb {

            display: flex;

            align-items: center;

            gap: 6px;

            font-size: 14px;

            font-weight: 700;

        }


        .breadcrumb span {

            color: var(--muted);

        }


        .toolbar-actions {

            display: flex;

            gap: 7px;

        }


        .tool-btn {

            border:
                1px solid var(--border);

            background: var(--card);

            color: var(--text);

            border-radius: 9px;

            padding:
                8px 11px;

            cursor: pointer;

            font-size: 13px;

        }


        .tool-btn:hover {

            background: var(--hover);

        }


        /* =========================================================
   FINDER
========================================================= */

        .finder {

            background: var(--card);

            border:
                1px solid var(--border);

            border-radius: var(--radius);

            box-shadow: var(--shadow);

            overflow: hidden;

            min-height: 390px;

        }


        .finder-header {

            display: grid;

            grid-template-columns:
                1fr 130px 180px;

            padding:
                12px 18px;

            border-bottom:
                1px solid var(--border);

            color: var(--muted);

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: .07em;

            font-weight: 700;

        }


        .finder-items {

            padding: 5px;

        }


        .file-row {

            display: grid;

            grid-template-columns:
                1fr 130px 180px;

            align-items: center;

            padding:
                13px;

            border-radius: 10px;

            cursor: pointer;

            user-select: none;

        }


        .file-row:hover {

            background: var(--hover);

        }


        .file-row.selected {

            background: var(--secondary);

        }


        .file-name {

            display: flex;

            align-items: center;

            gap: 13px;

            min-width: 0;

        }


        .file-icon {

            width: 38px;

            height: 38px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            flex-shrink: 0;

        }


        .icon-flowchart {

            background: #dcfce7;

        }


        .icon-quiz {

            background: #fef3c7;

        }


        .icon-flashcards {

            background: #fce7f3;

        }


        .icon-presentation {

            background: #dbeafe;

        }


        .file-title {

            font-size: 14px;

            font-weight: 650;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .file-type {

            font-size: 12px;

            color: var(--muted);

        }


        .file-date {

            font-size: 12px;

            color: var(--muted);

        }


        /* =========================================================
   GRID VIEW
========================================================= */

        .grid-view {

            display: none;

            grid-template-columns:
                repeat(auto-fill,
                    minmax(180px, 1fr));

            gap: 15px;

            padding: 17px;

        }


        .grid-view.active {

            display: grid;

        }


        .finder.column-mode .finder-list {

            display: none;

        }


        .file-card {

            background: var(--card);

            border:
                1px solid var(--border);

            border-radius: 14px;

            padding: 18px;

            cursor: pointer;

            min-height: 150px;

            transition: .15s;

        }


        .file-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 22px rgba(0, 0, 0, .08);

        }


        .file-card-icon {

            width: 50px;

            height: 50px;

            border-radius: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

            margin-bottom: 20px;

        }


        .file-card-title {

            font-size: 14px;

            font-weight: 700;

            margin-bottom: 5px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .file-card-date {

            color: var(--muted);

            font-size: 11px;

        }


        /* =========================================================
   EMPTY STORAGE
========================================================= */

        .empty {

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            min-height: 300px;

            color: var(--muted);

        }


        .empty-icon {

            font-size: 50px;

            margin-bottom: 12px;

        }


        .empty h3 {

            color: var(--text);

            margin:
                0 0 5px;

        }


        .empty p {

            margin: 0;

            font-size: 13px;

        }


        /* =========================================================
   SCANNER
========================================================= */

        .scanner-panel {

            margin-top: 28px;

            background: var(--card);

            border:
                1px solid var(--border);

            border-radius: var(--radius);

            box-shadow: var(--shadow);

            padding: 25px;

        }


        .scanner-title {

            margin-bottom: 18px;

        }


        .scanner-title h2 {

            margin: 0;

            font-size: 19px;

        }


        .scanner-title p {

            margin:
                5px 0 0;

            color: var(--muted);

            font-size: 13px;

        }


        video {

            width: 100%;

            max-height: 470px;

            object-fit: cover;

            border-radius: 15px;

            background: black;

        }


        canvas {

            display: none;

        }


        .mode-container {

            margin-top: 18px;

            display: flex;

            gap: 9px;

            flex-wrap: wrap;

        }


        .mode-btn {

            padding:
                10px 15px;

            border:
                1px solid var(--border);

            border-radius: 10px;

            font-size: 13px;

            cursor: pointer;

            background: var(--card);

            color: var(--text);

            font-weight: 650;

        }


        .mode-btn:hover {

            background: var(--hover);

        }


        .mode-btn.active {

            background: var(--primary);

            color: white;

            border-color:
                var(--primary);

        }


        /* =========================================================
   OUTPUT
========================================================= */

        #result {

            margin-top: 20px;

        }


        .study-card {

            background: var(--card);

            padding: 25px;

            border:
                1px solid var(--border);

            border-radius: 17px;

            box-shadow: var(--shadow);

            text-align: left;

        }


        .choice {

            width: 100%;

            padding: 14px;

            margin: 8px 0;

            border-radius: 10px;

            border:
                1px solid var(--border);

            font-size: 15px;

            cursor: pointer;

            background: var(--input);

            color: var(--text);

            text-align: left;

        }


        .choice:hover {

            background: var(--hover);

        }


        .choice.correct {

            background: #86efac;

            color: #14532d;

        }


        .choice.wrong {

            background: #fca5a5;

            color: #7f1d1d;

        }


        .flashcard {

            width: min(400px, 90%);

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

            border-radius: 18px;

            backface-visibility: hidden;

            font-size: 21px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .15);

            background: var(--card);

            border:
                1px solid var(--border);

            text-align: center;

        }


        .flash-back {

            transform:
                rotateY(180deg);

            background:
                var(--secondary);

        }


        .action-btn {

            padding:
                10px 17px;

            margin: 7px;

            border: none;

            border-radius: 9px;

            background: var(--primary);

            color: white;

            cursor: pointer;

            font-size: 14px;

            font-weight: 600;

        }


        .presentation-link {

            display: inline-block;

            margin-top: 20px;

            padding:
                13px 20px;

            border-radius: 10px;

            background: #16a34a;

            color: white;

            text-decoration: none;

            font-size: 15px;

            font-weight: bold;

        }


        /* =========================================================
   RESPONSIVE
========================================================= */

        @media(max-width: 850px) {

            .sidebar {

                width: 70px;

                padding:
                    15px 8px;

            }

            .logo-text,
            .logo-sub,
            .sidebar-label,
            .nav-item span:not(.nav-icon),
            .theme-row>span {

                display: none;

            }

            .logo {

                justify-content: center;

                padding-bottom: 18px;

            }

            .nav-item {

                justify-content: center;

            }

            .theme-row {

                justify-content: center;

            }

            .main {

                margin-left: 70px;

                width:
                    calc(100% - 70px);

            }

            .search {

                width: 240px;

            }

        }


        @media(max-width: 600px) {

            .content {

                padding: 18px;

            }

            .topbar {

                padding:
                    0 18px;

            }

            .student-badge {

                display: none;

            }

            .search {

                width: 100%;

            }

            .welcome {

                flex-direction: column;

            }

            .finder-header,
            .file-row {

                grid-template-columns:
                    1fr 90px;

            }

            .finder-header div:nth-child(3),
            .file-row .file-date {

                display: none;

            }

        }


        /* =========================================================
   CONTEXT MENU
========================================================= */

        .context-menu {

            display: none;

            position: fixed;

            z-index: 1000;

            background: var(--card);

            border:
                1px solid var(--border);

            border-radius: 10px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, .2);

            padding: 5px;

            min-width: 150px;

        }


        .context-menu button {

            display: block;

            width: 100%;

            border: none;

            background: transparent;

            color: var(--text);

            padding: 9px 11px;

            text-align: left;

            border-radius: 7px;

            cursor: pointer;

            font-size: 13px;

        }


        .context-menu button:hover {

            background: var(--hover);

        }


        /* =========================================================
   RENAME DIALOG
========================================================= */

        .rename-modal {

            display: none;

            position: fixed;

            inset: 0;

            z-index: 2000;

            background:
                rgba(15, 23, 42, .45);

            align-items: center;

            justify-content: center;

        }


        .rename-box {

            width: min(400px, 90%);

            background: var(--card);

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 20px 60px rgba(0, 0, 0, .25);

        }


        .rename-box h3 {

            margin-top: 0;

        }


        .rename-input {

            width: 100%;

            padding: 11px;

            border:
                1px solid var(--border);

            border-radius: 9px;

            background: var(--input);

            color: var(--text);

            outline: none;

        }


        .rename-actions {

            display: flex;

            justify-content: flex-end;

            gap: 8px;

            margin-top: 15px;

        }
    </style>

</head>


<body>


    <div class="app">


        <!-- =====================================================
     SIDEBAR
===================================================== -->

        <aside class="sidebar">


            <div class="logo">

                <div class="logo-icon">
                    ✦
                </div>

                <div>

                    <div class="logo-text">
                        StudySpace
                    </div>

                    <div class="logo-sub">
                        AI-powered studying
                    </div>

                </div>

            </div>


            <div class="sidebar-section">

                <div class="sidebar-label">
                    Workspace
                </div>


                <button class="nav-item active" onclick="showStorage('all')">

                    <span class="nav-icon">⌂</span>

                    <span>
                        My Study Space
                    </span>

                </button>


                <button class="nav-item" onclick="showStorage('flowchart')">

                    <span class="nav-icon">📊</span>

                    <span>
                        Flowcharts
                    </span>

                </button>


                <button class="nav-item" onclick="showStorage('quiz')">

                    <span class="nav-icon">📝</span>

                    <span>
                        Quizzes
                    </span>

                </button>


                <button class="nav-item" onclick="showStorage('flashcards')">

                    <span class="nav-icon">🃏</span>

                    <span>
                        Flashcards
                    </span>

                </button>


                <button class="nav-item" onclick="showStorage('presentation')">

                    <span class="nav-icon">📽</span>

                    <span>
                        Presentations
                    </span>

                </button>

            </div>


            <div class="sidebar-section">

                <div class="sidebar-label">
                    Create
                </div>


                <button class="nav-item" onclick="scrollToScanner()">

                    <span class="nav-icon">＋</span>

                    <span>
                        New Study Material
                    </span>

                </button>

            </div>


            <div class="sidebar-bottom">


                <div class="theme-row">

                    <span>
                        Appearance
                    </span>


                    <label class="theme-switch">

                        <input type="checkbox" id="themeToggle">

                        <span class="slider"></span>

                    </label>

                </div>


            </div>


        </aside>



        <!-- =====================================================
     MAIN
===================================================== -->

        <main class="main">


            <header class="topbar">


                <div class="search">

                    <span class="search-icon">
                        🔎
                    </span>

                    <input type="text" id="searchInput" placeholder="Search your study materials...">

                </div>


                <div class="top-actions">

                    <div class="student-badge">
                        🎓 StudySpace
                    </div>

                </div>


            </header>



            <div class="content">


                <!-- =====================================================
     WELCOME
===================================================== -->

                <section class="welcome">


                    <div>

                        <h1>
                            Your study space
                        </h1>

                        <p>
                            Scan notes, make study materials, and keep everything organized.
                        </p>

                    </div>


                    <button class="scan-button" onclick="scrollToScanner()">

                        ✦ Create something

                    </button>


                </section>



                <!-- =====================================================
     STORAGE TOOLBAR
===================================================== -->

                <div class="storage-toolbar">


                    <div class="breadcrumb">

                        <span>
                            StudySpace
                        </span>

                        <span>
                            /
                        </span>

                        <strong id="currentFolder">
                            All Items
                        </strong>

                    </div>


                    <div class="toolbar-actions">

                        <button class="tool-btn" onclick="sortItems()">

                            ↕ Sort

                        </button>


                        <button class="tool-btn" onclick="setView('list')">

                            ☷

                        </button>


                        <button class="tool-btn" onclick="setView('grid')">

                            ▦

                        </button>

                    </div>


                </div>



                <!-- =====================================================
     FINDER
===================================================== -->

                <section class="finder" id="finder">


                    <div class="finder-header">

                        <div>
                            Name
                        </div>

                        <div>
                            Type
                        </div>

                        <div>
                            Modified
                        </div>

                    </div>



                    <div class="finder-list" id="finderList">

                        <div class="empty">

                            <div class="empty-icon">
                                📁
                            </div>

                            <h3>
                                Loading your study space...
                            </h3>

                        </div>

                    </div>



                    <div class="grid-view" id="gridView">

                    </div>


                </section>



                <!-- =====================================================
     SCANNER
===================================================== -->

                <section class="scanner-panel" id="scannerPanel">


                    <div class="scanner-title">

                        <h2>
                            Create study material
                        </h2>

                        <p>
                            Point your camera at notes, worksheets, textbooks, or study guides.
                        </p>

                    </div>


                    <video id="webcam" autoplay playsinline>
                    </video>


                    <canvas id="canvas"></canvas>


                    <div class="mode-container">


                        <button class="mode-btn active" data-mode="flowchart">

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


                        <div class="study-card">

                            <h3>
                                Output
                            </h3>

                            <p id="ai-status">

                                Select a mode and scan an image.

                            </p>


                            <div id="flowchart-render"></div>

                        </div>


                    </div>


                </section>


            </div>

        </main>

    </div>



    <!-- =====================================================
     CONTEXT MENU
===================================================== -->

    <div class="context-menu" id="contextMenu">

        <button onclick="openSelected()">
            Open
        </button>

        <button onclick="renameSelected()">
            Rename
        </button>

    </div>



    <!-- =====================================================
     RENAME MODAL
===================================================== -->

    <div class="rename-modal" id="renameModal">


        <div class="rename-box">


            <h3>
                Rename study material
            </h3>


            <input class="rename-input" id="renameInput" type="text">


            <div class="rename-actions">


                <button class="tool-btn" onclick="closeRename()">

                    Cancel

                </button>


                <button class="action-btn" onclick="saveRename()">

                    Rename

                </button>


            </div>


        </div>

    </div>



    <script>


        /* =========================================================
           GLOBAL STATE
        ========================================================= */

        const video =
            document.getElementById("webcam");

        const canvas =
            document.getElementById("canvas");

        const status =
            document.getElementById("ai-status");

        const output =
            document.getElementById("flowchart-render");

        const finder =
            document.getElementById("finder");

        const finderList =
            document.getElementById("finderList");

        const gridView =
            document.getElementById("gridView");

        const searchInput =
            document.getElementById("searchInput");

        let selectedMode = "flowchart";

        let selectedItem = null;

        let allItems = [];

        let currentFilter = "all";

        let currentView = "list";

        let sortAscending = false;


        /* =========================================================
           DARK MODE
        ========================================================= */

        const themeToggle =
            document.getElementById("themeToggle");


        const savedTheme =
            localStorage.getItem("studyspace-theme");


        if (savedTheme === "dark") {

            document.body.classList.add("dark");

            themeToggle.checked = true;

        }


        themeToggle.addEventListener(
            "change",
            function () {

                if (this.checked) {

                    document.body.classList.add("dark");

                    localStorage.setItem(
                        "studyspace-theme",
                        "dark"
                    );

                }

                else {

                    document.body.classList.remove("dark");

                    localStorage.setItem(
                        "studyspace-theme",
                        "light"
                    );

                }

            }
        );


        /* =========================================================
           CAMERA
        ========================================================= */

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


        initCamera();


        /* =========================================================
           MODE BUTTONS
        ========================================================= */

        document
            .querySelectorAll(".mode-btn")
            .forEach(button => {

                button.onclick = () => {

                    document
                        .querySelectorAll(".mode-btn")
                        .forEach(btn => {

                            btn.classList.remove("active");

                        });

                    button.classList.add("active");

                    selectedMode =
                        button.dataset.mode;

                    scanImage();

                };

            });


        /* =========================================================
           SCAN
        ========================================================= */

        async function scanImage() {

            status.innerText =
                "Analyzing your notes...";

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

                            body:
                                JSON.stringify({

                                    image: image,

                                    mode: selectedMode

                                })

                        }

                    );


                const text =
                    await response.text();


                console.log(
                    "SERVER RESPONSE:",
                    text
                );


                const data =
                    JSON.parse(text);


                if (!data.success) {

                    status.innerText =
                        data.error;

                    return;

                }


                status.innerText =
                    "Generated successfully!";


                /* FLOWCHART */

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


                    output.appendChild(div);


                    await mermaid.run({

                        nodes: [div]

                    });

                }


                /* QUIZ */

                else if (
                    selectedMode ===
                    "quiz"
                ) {

                    const quiz =
                        JSON.parse(
                            data.ai_response
                        );

                    createQuiz(quiz);

                }


                /* FLASHCARDS */

                else if (
                    selectedMode ===
                    "flashcards"
                ) {

                    const cards =
                        JSON.parse(
                            data.ai_response
                        );

                    createFlashcards(cards);

                }


                /* PRESENTATION */

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


                /* Refresh Finder */

                setTimeout(
                    loadItems,
                    700
                );

            }

            catch (err) {

                status.innerText =
                    "Error: " + err.message;

            }

        }


        /* =========================================================
           GOOGLE SLIDES
        ========================================================= */

        async function createPresentation(data) {

            output.innerHTML = `

        <div class="study-card">

            <h2>
                Creating Google Slides...
            </h2>

            <p>
                Your presentation is being designed.
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
                                JSON.stringify(data)

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
                        Your editable Google Slides file is ready.
                    </p>

                    <a
                        class="presentation-link"
                        target="_blank"
                        href="${result.url}">

                        Open Google Slides

                    </a>

                </div>

            `;

                    setTimeout(
                        loadItems,
                        500
                    );

                }

                else {

                    output.innerHTML = `

                <div class="study-card">

                    <h2>
                        Error
                    </h2>

                    <p>
                        ${result.error}
                    </p>

                </div>

            `;

                }

            }

            catch (err) {

                output.innerHTML = `

            <div class="study-card">

                Error:
                ${err.message}

            </div>

        `;

            }

        }


        /* =========================================================
           QUIZ
        ========================================================= */

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
                    ${current + 1}/${data.questions.length}
                </h2>

                <h3>
                    ${q.question}
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
                                .forEach(btn => {

                                    btn.disabled = true;

                                });


                            if (
                                index === q.answer
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
                                    )[q.answer]
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

                            ${q.explanation}

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
                            ${score}/${data.questions.length}
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
           FLASHCARDS
        ========================================================= */

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
                    ">

                    <div class="flash-inner">

                        <div class="flash-front">

                            ${card.front}

                        </div>

                        <div class="flash-back">

                            ${card.back}

                        </div>

                    </div>

                </div>


                <h3>
                    Card
                    ${current + 1}/${data.cards.length}
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

                    if (current > 0) {

                        current--;

                    }

                    showCard();

                };


            showCard();

        }


        /* =========================================================
           STORAGE
        ========================================================= */

        /*
         * Loads the records created by analyze.php /
         * create_slides.php from the generated_items
         * storage system.
         */

        async function loadItems() {

            try {

                /*
                 * This endpoint should be the storage
                 * listing file you already created.
                 */

                const response =
                    await fetch(
                        "get_generated_items.php"
                    );


                const result =
                    await response.json();


                if (
                    result.success &&
                    Array.isArray(result.items)
                ) {

                    allItems =
                        result.items;

                }

                else if (
                    Array.isArray(result)
                ) {

                    allItems =
                        result;

                }

                else {

                    allItems = [];

                }


                renderItems();

            }

            catch (error) {

                console.error(
                    "Could not load generated items:",
                    error
                );


                renderEmpty(
                    "Your study materials will appear here."
                );

            }

        }


        /* =========================================================
           FILTER
        ========================================================= */

        function showStorage(type) {

            currentFilter =
                type;


            const labels = {

                all:
                    "All Items",

                flowchart:
                    "Flowcharts",

                quiz:
                    "Quizzes",

                flashcards:
                    "Flashcards",

                presentation:
                    "Presentations"

            };


            document.getElementById(
                "currentFolder"
            ).innerText =
                labels[type] ||
                "All Items";


            document
                .querySelectorAll(
                    ".nav-item"
                )
                .forEach(
                    item =>
                        item.classList.remove(
                            "active"
                        )
                );


            renderItems();

        }


        /* =========================================================
           RENDER ITEMS
        ========================================================= */

        function renderItems() {

            const search =
                searchInput.value
                    .toLowerCase()
                    .trim();


            let items =
                [...allItems];


            if (
                currentFilter !==
                "all"
            ) {

                items =
                    items.filter(
                        item =>
                            item.type ===
                            currentFilter
                    );

            }


            if (search) {

                items =
                    items.filter(
                        item =>
                            String(
                                item.name || ""
                            )
                                .toLowerCase()
                                .includes(search)
                    );

            }


            items.sort(
                (a, b) => {

                    const da =
                        new Date(
                            a.updated_at ||
                            a.created_at
                        );

                    const db =
                        new Date(
                            b.updated_at ||
                            b.created_at
                        );


                    return sortAscending
                        ? da - db
                        : db - da;

                }
            );


            if (!items.length) {

                renderEmpty(
                    search
                        ? "No study materials match your search."
                        : "Nothing here yet. Scan some notes to get started."
                );

                return;

            }


            finderList.innerHTML = "";

            gridView.innerHTML = "";


            items.forEach(
                item => {

                    renderListItem(item);

                    renderGridItem(item);

                }
            );

        }


        /* =========================================================
           LIST ITEM
        ========================================================= */

        function renderListItem(item) {

            const row =
                document.createElement(
                    "div"
                );


            row.className =
                "file-row";


            row.dataset.id =
                item.id;


            const icon =
                getItemIcon(item.type);


            const iconClass =
                "icon-" +
                item.type;


            row.innerHTML = `

        <div class="file-name">

            <div
                class="file-icon ${iconClass}">

                ${icon}

            </div>

            <div>

                <div class="file-title">

                    ${escapeHtml(
                item.name
            )}

                </div>

                <div class="file-type">

                    ${getItemTypeName(
                item.type
            )}

                </div>

            </div>

        </div>


        <div class="file-type">

            ${getItemTypeName(
                item.type
            )}

        </div>


        <div class="file-date">

            ${formatDate(
                item.updated_at ||
                item.created_at
            )}

        </div>

    `;


            attachFileEvents(
                row,
                item
            );


            finderList.appendChild(
                row
            );

        }


        /* =========================================================
           GRID ITEM
        ========================================================= */

        function renderGridItem(item) {

            const card =
                document.createElement(
                    "div"
                );


            card.className =
                "file-card";


            card.dataset.id =
                item.id;


            const icon =
                getItemIcon(item.type);


            const iconClass =
                "icon-" +
                item.type;


            card.innerHTML = `

        <div
            class="
                file-card-icon
                ${iconClass}
            ">

            ${icon}

        </div>


        <div class="file-card-title">

            ${escapeHtml(
                item.name
            )}

        </div>


        <div class="file-card-date">

            ${getItemTypeName(
                item.type
            )}

            ·

            ${formatDate(
                item.updated_at ||
                item.created_at
            )}

        </div>

    `;


            attachFileEvents(
                card,
                item
            );


            gridView.appendChild(
                card
            );

        }


        /* =========================================================
           FILE EVENTS
        ========================================================= */

        function attachFileEvents(
            element,
            item
        ) {

            let clickTimer = null;


            element.addEventListener(
                "click",
                event => {

                    document
                        .querySelectorAll(
                            ".file-row.selected"
                        )
                        .forEach(
                            el =>
                                el.classList.remove(
                                    "selected"
                                )
                        );


                    if (
                        element.classList.contains(
                            "file-row"
                        )
                    ) {

                        element.classList.add(
                            "selected"
                        );

                    }


                    selectedItem =
                        item;

                }
            );


            /*
             * Double-click opens:
             *
             * opened_item.php?id=ITEM_ID
             */

            element.addEventListener(
                "dblclick",
                event => {

                    event.preventDefault();

                    window.open(
                        "opened_item.php?id=" +
                        encodeURIComponent(
                            item.id
                        ),
                        "_blank"
                    );

                }
            );


            element.addEventListener(
                "contextmenu",
                event => {

                    event.preventDefault();

                    selectedItem =
                        item;

                    showContextMenu(
                        event.clientX,
                        event.clientY
                    );

                }
            );

        }


        /* =========================================================
           ICONS
        ========================================================= */

        function getItemIcon(type) {

            if (
                type === "flowchart"
            ) return "📊";

            if (
                type === "quiz"
            ) return "📝";

            if (
                type === "flashcards"
            ) return "🃏";

            if (
                type === "presentation"
            ) return "📽";

            return "📄";

        }


        function getItemTypeName(type) {

            if (
                type === "flowchart"
            ) return "Flowchart";

            if (
                type === "quiz"
            ) return "Quiz";

            if (
                type === "flashcards"
            ) return "Flashcards";

            if (
                type === "presentation"
            ) return "Presentation";

            return "Study Material";

        }


        /* =========================================================
           DATE
        ========================================================= */

        function formatDate(dateString) {

            if (!dateString)
                return "Recently";


            const date =
                new Date(
                    dateString
                );


            if (
                isNaN(
                    date.getTime()
                )
            ) {

                return "Recently";

            }


            return date.toLocaleDateString(
                undefined,
                {
                    month: "short",
                    day: "numeric",
                    year: "numeric"
                }
            );

        }


        /* =========================================================
           EMPTY
        ========================================================= */

        function renderEmpty(message) {

            finderList.innerHTML = `

        <div class="empty">

            <div class="empty-icon">
                📁
            </div>

            <h3>
                No study materials
            </h3>

            <p>
                ${message}
            </p>

        </div>

    `;


            gridView.innerHTML = "";

        }


        /* =========================================================
           VIEW SWITCH
        ========================================================= */

        function setView(view) {

            currentView =
                view;


            if (
                view === "grid"
            ) {

                finder.classList.add(
                    "column-mode"
                );

                gridView.classList.add(
                    "active"
                );

            }

            else {

                finder.classList.remove(
                    "column-mode"
                );

                gridView.classList.remove(
                    "active"
                );

            }

        }


        /* =========================================================
           SORT
        ========================================================= */

        function sortItems() {

            sortAscending =
                !sortAscending;

            renderItems();

        }


        /* =========================================================
           SEARCH
        ========================================================= */

        searchInput.addEventListener(
            "input",
            renderItems
        );


        /* =========================================================
           CONTEXT MENU
        ========================================================= */

        const contextMenu =
            document.getElementById(
                "contextMenu"
            );


        function showContextMenu(
            x,
            y
        ) {

            contextMenu.style.display =
                "block";

            contextMenu.style.left =
                x + "px";

            contextMenu.style.top =
                y + "px";

        }


        document.addEventListener(
            "click",
            () => {

                contextMenu.style.display =
                    "none";

            }
        );


        /* =========================================================
           OPEN SELECTED
        ========================================================= */

        function openSelected() {

            if (!selectedItem)
                return;


            window.open(
                "opened_item.php?id=" +
                encodeURIComponent(
                    selectedItem.id
                ),
                "_blank"
            );

        }


        /* =========================================================
           RENAME
        ========================================================= */

        const renameModal =
            document.getElementById(
                "renameModal"
            );


        const renameInput =
            document.getElementById(
                "renameInput"
            );


        function renameSelected() {

            if (!selectedItem)
                return;


            renameInput.value =
                selectedItem.name || "";


            renameModal.style.display =
                "flex";


            renameInput.focus();

        }


        function closeRename() {

            renameModal.style.display =
                "none";

        }


        async function saveRename() {

            if (!selectedItem)
                return;


            const newName =
                renameInput.value.trim();


            if (!newName) {

                return;

            }


            try {

                /*
                 * Rename endpoint from the storage system.
                 */

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


                const existing =
                    allItems.find(
                        item =>
                            String(item.id) ===
                            String(selectedItem.id)
                    );


                if (existing) {

                    existing.name =
                        newName;

                }


                closeRename();

                renderItems();

            }

            catch (error) {

                alert(
                    "Rename error: " +
                    error.message
                );

            }

        }


        /* =========================================================
           KEYBOARD SHORTCUT
        ========================================================= */

        renameInput.addEventListener(
            "keydown",
            event => {

                if (
                    event.key ===
                    "Enter"
                ) {

                    saveRename();

                }

                if (
                    event.key ===
                    "Escape"
                ) {

                    closeRename();

                }

            }
        );


        /* =========================================================
           ESCAPE HTML
        ========================================================= */

        function escapeHtml(value) {

            return String(value)
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
           SCROLL TO SCANNER
        ========================================================= */

        function scrollToScanner() {

            document
                .getElementById(
                    "scannerPanel"
                )
                .scrollIntoView({

                    behavior: "smooth",

                    block: "start"

                });

        }


        /* =========================================================
           INITIAL LOAD
        ========================================================= */

        loadItems();


    </script>


</body>

</html>