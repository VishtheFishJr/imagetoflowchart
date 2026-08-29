<?php

require_once 'db.php';


$isLoggedIn =
    !empty($_SESSION["logged_in"]) &&
    !empty($_SESSION["user_id"]);


$currentUserId =
    $_SESSION["user_id"] ?? null;


$currentUsername =
    $_SESSION["username"] ?? null;


$currentRole =
    $_SESSION["role"] ?? "user";


/* Keep the role in sync with the database. */
if ($isLoggedIn) {
    try {
        $roleStmt = $pdo->prepare("
            SELECT role
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $roleStmt->execute([
            $currentUserId
        ]);

        $dbRole = $roleStmt->fetchColumn();

        if ($dbRole) {
            $currentRole = $dbRole;
            $_SESSION["role"] = $dbRole;
        }
    } catch (PDOException $e) {
        // Keep the existing session role if the role lookup fails.
    }
}


$isAdmin =
    $isLoggedIn &&
    $currentRole === "admin";

?>

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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, Helvetica, sans-serif;
            background: #fff;
            color: #111;
        }


        .app {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }


        /* TOP BAR — retained */

        .topbar {
            height: 64px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 0 22px;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
        }


        .app-title {
            font-size: 20px;
            font-weight: 700;
            white-space: nowrap;
            color: #111;
        }


        .topbar-spacer {
            flex: 1;
        }


        .storage-toggle,
        .auth-button,
        .users-button {
            border: 1px solid #bbb;
            background: #fff;
            color: #111;
            padding: 9px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }


        .storage-toggle:hover,
        .auth-button:hover,
        .users-button:hover {
            background: #f0f0f0;
        }


        .auth-button {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }


        /* DARK/LIGHT MODE SLIDER */

        .theme-switch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }


        .theme-switch input {
            display: none;
        }


        .theme-slider {
            width: 46px;
            height: 24px;
            background: #ccc;
            border-radius: 24px;
            position: relative;
            transition: background .2s;
            border: 1px solid #aaa;
        }


        .theme-slider::before {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            left: 2px;
            top: 2px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
        }


        .theme-switch input:checked+.theme-slider::before {
            transform: translateX(22px);
        }


        .theme-label {
            font-size: 14px;
            font-weight: 600;
            color: #111;
            white-space: nowrap;
        }


        /* FINDER — retained */

        #finder {
            display: none;
            position: fixed;
            inset: 64px 0 0 0;
            background: #fff;
            z-index: 90;
        }


        #finder.visible {
            display: flex;
        }


        /* FINDER SIDEBAR — retained */

        .finder-sidebar {
            width: 245px;
            flex-shrink: 0;
            background: #f5f5f5;
            border-right: 1px solid #ddd;
            padding: 20px 12px;
            overflow-y: auto;
        }


        .sidebar-section-title {
            font-size: 12px;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 8px 12px;
            margin-top: 5px;
        }


        .sidebar-item {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2px;
        }


        .sidebar-item:hover {
            background: #e5e5e5;
        }


        .sidebar-item.active {
            background: #ddd;
            color: #111;
            font-weight: 600;
        }


        .sidebar-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }


        .sidebar-count {
            margin-left: auto;
            color: #777;
            font-size: 12px;
        }


        .finder-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }


        .finder-toolbar {
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 0 18px;
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
            padding: 9px 13px;
            border: 1px solid #bbb;
            border-radius: 5px;
            outline: none;
            font-size: 14px;
            background: #fff;
            color: #111;
        }


        .sort-select {
            padding: 9px 10px;
            border: 1px solid #bbb;
            border-radius: 5px;
            background: #fff;
            font-size: 13px;
            color: #111;
        }


        .finder-breadcrumb {
            min-height: 45px;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 0 20px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
            color: #666;
        }


        .breadcrumb-button {
            border: none;
            background: transparent;
            color: #333;
            cursor: pointer;
            font-size: 13px;
            padding: 3px;
        }


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
            background: #fff;
            border-right: 1px solid #ddd;
            padding: 8px;
        }


        .finder-column:last-child {
            flex: 1;
            border-right: none;
        }


        .column-empty {
            text-align: center;
            color: #888;
            padding: 45px 20px;
            font-size: 14px;
        }


        .folder-item,
        .file-item {
            width: 100%;
            min-height: 54px;
            border: none;
            background: transparent;
            border-radius: 5px;
            display: flex;
            align-items: center;
            text-align: left;
            padding: 7px 10px;
            cursor: pointer;
            margin-bottom: 2px;
            color: #111;
        }


        .folder-item:hover,
        .file-item:hover {
            background: #f0f0f0;
        }


        .folder-item.selected,
        .file-item.selected {
            background: #ddd;
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
            color: #111;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .item-meta {
            margin-top: 3px;
            font-size: 11px;
            color: #777;
        }


        .folder-arrow {
            color: #777;
            font-size: 18px;
        }


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
            border-bottom: 1px solid #ddd;
            padding: 11px 0;
            font-size: 14px;
        }


        .details-label {
            color: #666;
        }


        .details-value {
            font-weight: 600;
            text-align: right;
            max-width: 65%;
            word-break: break-word;
        }


        .open-file-button {
            margin-top: 25px;
            padding: 10px 18px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
            color: #111;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }


        .rename-input {
            width: 100%;
            padding: 5px 7px;
            border: 1px solid #777;
            border-radius: 3px;
            font-size: 14px;
            outline: none;
        }


        #contextMenu {
            display: none;
            position: fixed;
            z-index: 500;
            background: #fff;
            border: 1px solid #bbb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
            border-radius: 5px;
            min-width: 160px;
            padding: 5px;
        }


        .context-option {
            width: 100%;
            padding: 9px 12px;
            border: none;
            background: transparent;
            text-align: left;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            color: #111;
        }


        .context-option:hover {
            background: #eee;
        }


        /* SCANNER — functionality retained */

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
            font-size: 32px;
            color: #111;
        }


        video {
            width: 100%;
            border-radius: 0;
            background: #000;
            box-shadow: none;
        }


        canvas {
            display: none;
        }


        .mode-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }


        .mode-btn {
            padding: 10px 18px;
            border: 1px solid #999;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            background: #fff;
            color: #111;
        }


        .mode-btn:hover {
            background: #eee;
        }


        #result {
            margin-top: 30px;
            background: #fff;
            border-radius: 0;
            padding: 20px 0;
            box-shadow: none;
        }


        #flowchart-render {
            margin-top: 25px;
        }


        .study-card {
            background: #fff;
            padding: 20px;
            border-radius: 0;
            box-shadow: none;
            border-top: 1px solid #ddd;
            text-align: left;
        }


        .choice {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
            border: 1px solid #bbb;
            font-size: 16px;
            cursor: pointer;
            background: #fff;
            color: #111;
            text-align: left;
        }


        .choice:hover {
            background: #eee;
        }


        .choice.correct {
            background: #d9f2d9;
        }


        .choice.wrong {
            background: #f5d6d6;
        }


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
            border: 1px solid #bbb;
            border-radius: 0;
            backface-visibility: hidden;
            font-size: 22px;
            box-shadow: none;
            background: #fff;
            color: #111;
        }


        .flash-back {
            transform: rotateY(180deg);
            background: #f5f5f5;
        }


        .action-btn {
            padding: 10px 18px;
            margin: 8px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
            color: #111;
            cursor: pointer;
            font-size: 15px;
        }


        .presentation-link {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 22px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
            color: #111;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
        }

        .app {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* TOP BAR */
        .topbar {
            height: 64px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 0 22px;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .app-title {
            font-size: 20px;
            font-weight: 700;
            white-space: nowrap;
            color: #111;
        }

        .topbar-spacer {
            flex: 1;
        }

        .storage-toggle,
        .auth-button,
        .admin-button {
            border: 1px solid #bbb;
            background: #fff;
            color: #111;
            padding: 9px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .storage-toggle:hover,
        .auth-button:hover,
        .admin-button:hover {
            background: #f0f0f0;
        }

        .auth-button {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        .auth-button:hover {
            background: #333;
        }

        /* CLICKABLE DARK MODE SLIDER */

        .theme-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .theme-switch input {
            display: none;
        }

        .theme-slider {
            width: 52px;
            height: 28px;
            background: #ddd;
            border: 1px solid #bbb;
            border-radius: 30px;
            position: relative;
            transition: .2s;
            display: block;
        }

        .theme-slider::after {
            content: "";
            position: absolute;
            width: 22px;
            height: 22px;
            left: 2px;
            top: 2px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
            transition: .2s;
        }

        .theme-switch input:checked+.theme-slider {
            background: #333;
            border-color: #555;
        }

        .theme-switch input:checked+.theme-slider::after {
            transform: translateX(24px);
        }

        .theme-label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        /* FINDER */

        #finder {
            display: none;
            position: fixed;
            inset: 64px 0 0 0;
            background: #fff;
            z-index: 90;
        }

        #finder.visible {
            display: flex;
        }

        .finder-sidebar {
            width: 245px;
            flex-shrink: 0;
            background: #f5f5f5;
            border-right: 1px solid #ddd;
            padding: 20px 12px;
            overflow-y: auto;
        }

        .sidebar-section-title {
            font-size: 12px;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 8px 12px;
            margin-top: 5px;
        }

        .sidebar-item {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2px;
        }

        .sidebar-item:hover {
            background: #e5e5e5;
        }

        .sidebar-item.active {
            background: #ddd;
            color: #111;
            font-weight: 600;
        }

        .sidebar-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-count {
            margin-left: auto;
            color: #777;
            font-size: 12px;
        }

        .finder-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .finder-toolbar {
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 0 18px;
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
            padding: 9px 13px;
            border: 1px solid #bbb;
            border-radius: 5px;
            outline: none;
            font-size: 14px;
            background: #fff;
            color: #111;
        }

        .sort-select {
            padding: 9px 10px;
            border: 1px solid #bbb;
            border-radius: 5px;
            background: #fff;
            font-size: 13px;
            color: #111;
        }

        .finder-breadcrumb {
            min-height: 45px;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 0 20px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
            color: #666;
        }

        .breadcrumb-button {
            border: none;
            background: transparent;
            color: #333;
            cursor: pointer;
            font-size: 13px;
            padding: 3px;
        }

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
            background: #fff;
            border-right: 1px solid #ddd;
            padding: 8px;
        }

        .finder-column:last-child {
            flex: 1;
            border-right: none;
        }

        .column-empty {
            text-align: center;
            color: #888;
            padding: 45px 20px;
            font-size: 14px;
        }

        .folder-item,
        .file-item {
            width: 100%;
            min-height: 54px;
            border: none;
            background: transparent;
            border-radius: 5px;
            display: flex;
            align-items: center;
            text-align: left;
            padding: 7px 10px;
            cursor: pointer;
            margin-bottom: 2px;
            color: #111;
        }

        .folder-item:hover,
        .file-item:hover {
            background: #f0f0f0;
        }

        .folder-item.selected,
        .file-item.selected {
            background: #ddd;
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
            color: #111;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .item-meta {
            margin-top: 3px;
            font-size: 11px;
            color: #777;
        }

        .folder-arrow {
            color: #777;
            font-size: 18px;
        }

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
            border-bottom: 1px solid #ddd;
            padding: 11px 0;
            font-size: 14px;
        }

        .details-label {
            color: #666;
        }

        .details-value {
            font-weight: 600;
            text-align: right;
            max-width: 65%;
            word-break: break-word;
        }

        .open-file-button {
            margin-top: 25px;
            padding: 10px 18px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
            color: #111;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .rename-input {
            width: 100%;
            padding: 5px 7px;
            border: 1px solid #777;
            border-radius: 3px;
            font-size: 14px;
            outline: none;
        }

        #contextMenu {
            display: none;
            position: fixed;
            z-index: 500;
            background: #fff;
            border: 1px solid #bbb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
            border-radius: 5px;
            min-width: 160px;
            padding: 5px;
        }

        .context-option {
            width: 100%;
            padding: 9px 12px;
            border: none;
            background: transparent;
            text-align: left;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            color: #111;
        }

        .context-option:hover {
            background: #eee;
        }

        /* SCANNER */

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
            font-size: 32px;
            color: #111;
        }

        video {
            width: 100%;
            border-radius: 0;
            background: #000;
            box-shadow: none;
        }

        canvas {
            display: none;
        }

        .mode-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .mode-btn {
            padding: 10px 18px;
            border: 1px solid #999;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            background: #fff;
            color: #111;
        }

        .mode-btn:hover {
            background: #eee;
        }

        #result {
            margin-top: 30px;
            background: #fff;
            border-radius: 0;
            padding: 20px 0;
            box-shadow: none;
        }

        #flowchart-render {
            margin-top: 25px;
        }

        .study-card {
            background: #fff;
            padding: 20px;
            border-radius: 0;
            box-shadow: none;
            border-top: 1px solid #ddd;
            text-align: left;
        }

        .choice {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
            border: 1px solid #bbb;
            font-size: 16px;
            cursor: pointer;
            background: #fff;
            color: #111;
            text-align: left;
        }

        .choice:hover {
            background: #eee;
        }

        .choice.correct {
            background: #d9f2d9;
        }

        .choice.wrong {
            background: #f5d6d6;
        }

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
            border: 1px solid #bbb;
            border-radius: 0;
            backface-visibility: hidden;
            font-size: 22px;
            box-shadow: none;
            background: #fff;
            color: #111;
        }

        .flash-back {
            transform: rotateY(180deg);
            background: #f5f5f5;
        }

        .action-btn {
            padding: 10px 18px;
            margin: 8px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
            color: #111;
            cursor: pointer;
            font-size: 15px;
        }

        .presentation-link {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 22px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
            color: #111;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
        }

        /* =========================================================
           GOOGLE FORMS — ADDED
        ========================================================= */

        .form-link {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 22px;
            border: 1px solid #999;
            border-radius: 5px;
            background: #fff;
            color: #111;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
        }

        .form-link:hover {
            background: #eee;
        }

        /* DARK MODE */

        body.dark-mode {
            background: #111;
            color: #eee;
        }

        body.dark-mode .topbar,
        body.dark-mode #scanner,
        body.dark-mode #result,
        body.dark-mode .study-card,
        body.dark-mode .finder-toolbar,
        body.dark-mode .finder-breadcrumb,
        body.dark-mode .finder-column,
        body.dark-mode .flash-front,
        body.dark-mode .flash-back {
            background: #181818;
            color: #eee;
        }

        body.dark-mode .topbar {
            border-bottom-color: #333;
        }

        body.dark-mode .app-title,
        body.dark-mode h1,
        body.dark-mode .item-name,
        body.dark-mode .finder-title,
        body.dark-mode .details-name,
        body.dark-mode .flash-front,
        body.dark-mode .flash-back {
            color: #eee;
        }

        body.dark-mode .finder-sidebar {
            background: #151515;
            border-right-color: #333;
        }

        body.dark-mode .sidebar-section-title,
        body.dark-mode .sidebar-count,
        body.dark-mode .item-meta,
        body.dark-mode .details-label,
        body.dark-mode .column-empty {
            color: #aaa;
        }

        body.dark-mode .sidebar-item {
            color: #ddd;
        }

        body.dark-mode .sidebar-item:hover,
        body.dark-mode .folder-item:hover,
        body.dark-mode .file-item:hover,
        body.dark-mode .context-option:hover,
        body.dark-mode .mode-btn:hover,
        body.dark-mode .choice:hover,
        body.dark-mode .storage-toggle:hover,
        body.dark-mode .admin-button:hover,
        body.dark-mode .form-link:hover {
            background: #292929;
        }

        body.dark-mode .sidebar-item.active,
        body.dark-mode .folder-item.selected,
        body.dark-mode .file-item.selected {
            background: #333;
            color: #fff;
        }

        body.dark-mode .finder-toolbar,
        body.dark-mode .finder-breadcrumb,
        body.dark-mode .finder-column,
        body.dark-mode .finder-sidebar,
        body.dark-mode .finder-breadcrumb,
        body.dark-mode .details-row {
            border-color: #333;
        }

        body.dark-mode .finder-search,
        body.dark-mode .sort-select,
        body.dark-mode .storage-toggle,
        body.dark-mode .admin-button,
        body.dark-mode .mode-btn,
        body.dark-mode .choice,
        body.dark-mode .action-btn,
        body.dark-mode .open-file-button,
        body.dark-mode .presentation-link,
        body.dark-mode .form-link {
            background: #181818;
            color: #eee;
            border-color: #555;
        }

        body.dark-mode .auth-button {
            background: #eee;
            color: #111;
            border-color: #eee;
        }

        body.dark-mode .auth-button:hover {
            background: #ddd;
        }

        body.dark-mode .theme-label {
            color: #ddd;
        }

        body.dark-mode .theme-slider {
            background: #333;
            border-color: #555;
        }

        body.dark-mode #contextMenu {
            background: #181818;
            border-color: #555;
        }

        body.dark-mode .context-option {
            color: #eee;
        }

        body.dark-mode .flash-back {
            background: #222;
        }

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

            .theme-label {
                display: none;
            }

            .theme-slider {
                width: 46px;
            }

            .theme-switch input:checked+.theme-slider::after {
                transform: translateX(18px);
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


            <!-- DARK MODE SLIDER -->

            <label class="theme-switch" title="Toggle dark mode">

                <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode()">

                <span class="theme-slider"></span>

                <span class="theme-label" id="themeLabel">
                    Light
                </span>

            </label>


            <?php if (!empty($_SESSION["logged_in"])): ?>

                <?php if (
                    isset($_SESSION["role"]) &&
                    $_SESSION["role"] === "admin"
                ): ?>

                    <a href="users.php" class="admin-button">
                        👥 Users
                    </a>

                <?php endif; ?>


                <a href="login.php" class="auth-button">
                    Log Out
                </a>

            <?php else: ?>

                <a href="login.php" class="auth-button">
                    Log In
                </a>

                <a href="signup.php" class="admin-button">
                    Sign Up
                </a>

            <?php endif; ?>


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


                <!-- =================================================
                     GOOGLE FORMS — ADDED
                ================================================= -->

                <button class="sidebar-item" data-folder="form" onclick="openFolder('form')">

                    <span class="sidebar-icon">
                        📋
                    </span>

                    <span>
                        Forms
                    </span>

                    <span class="sidebar-count" id="count-form">
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


                    <!-- =================================================
                         GOOGLE FORMS — ADDED
                    ================================================= -->

                    <button class="mode-btn" data-mode="form">

                        📋 Google Form

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
            },

            /* ADDED: GOOGLE FORMS */

            {
                id: "form",
                name: "Google Forms",
                icon: "📋"
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

                presentation: "Presentations",

                /* ADDED */

                form: "Google Forms"

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


            if (
                folder !== "all" &&
                folder !== "recent"
            ) {

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
                        onclick="openSelected()"
                    >

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

            <?php if (empty($_SESSION["logged_in"])): ?>

                window.location.href =
                    "login.php";

                return;

            <?php endif; ?>


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

                presentation: "Presentation",

                /* ADDED */

                form: "Google Form"

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

                presentation: "📽",

                /* ADDED */

                form: "📋"

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

                    const errorMessage =
                        data.error ||
                        "Something went wrong.";

                    status.innerText =
                        errorMessage;

                    if (
                        errorMessage.toLowerCase().includes(
                            "google account not connected"
                        )
                    ) {
                        const googleLink =
                            document.createElement("a");

                        googleLink.href =
                            "https://vishthefishjr.me/google_login.php";

                        googleLink.target = "_self";
                        googleLink.innerText =
                            "Connect your Google account";

                        googleLink.style.display =
                            "block";

                        googleLink.style.marginTop =
                            "10px";

                        googleLink.style.color =
                            "#2563eb";

                        googleLink.style.textDecoration =
                            "none";

                        status.parentNode.appendChild(
                            googleLink
                        );
                    }

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



                /* =========================================
                   GOOGLE FORM
                ========================================= */

                else if (
                    selectedMode ===
                    "form"
                ) {

                    const form =
                        JSON.parse(
                            data.ai_response
                        );


                    createForm(
                        form
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
                                href="${result.url}"
                            >

                                Open Google Slides

                            </a>

                        </div>

                    `;


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
           GOOGLE FORM CREATION
        ========================================================= */


        async function createForm(
            data
        ) {

            output.innerHTML = `

                <div class="study-card">

                    <h2>
                        Creating Google Form...
                    </h2>

                    <p>
                        Please wait while your
                        form is generated.
                    </p>

                </div>

            `;


            try {

                const response =
                    await fetch(
                        "create_form.php",
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
                                Google Form Created 🎉
                            </h2>

                            <p>
                                Your Google Form is ready.
                            </p>

                            <a
                                class="presentation-link"
                                target="_blank"
                                href="${escapeHtml(result.url)}"
                            >

                                Open Google Form

                            </a>

                        </div>

                    `;


                    await loadItems();

                }

                else {
                    const errorMsg = result.error || "Unknown error";
                    const isPermissionError = errorMsg.toLowerCase().includes("google") || errorMsg.toLowerCase().includes("reconnect") || errorMsg.toLowerCase().includes("permission");

                    output.innerHTML = `
                        <div class="study-card">
                            <h2>Error</h2>
                            <p>${escapeHtml(errorMsg)}</p>
                            ${isPermissionError ? `
                                <br>
                                <a href="google_login.php" class="presentation-link" target="_self">
                                    Reconnect Google Account
                                </a>
                            ` : ''}
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
                                        onclick="nextQuestion()"
                                    >

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
           DARK MODE
        ========================================================= */


        function toggleDarkMode() {

            const checkbox =
                document.getElementById(
                    "darkModeToggle"
                );


            const enabled =
                checkbox.checked;


            document.body.classList.toggle(
                "dark-mode",
                enabled
            );


            localStorage.setItem(
                "aiStudyScannerDarkMode",
                enabled ? "1" : "0"
            );


            const label =
                document.getElementById(
                    "themeLabel"
                );


            if (label) {

                label.innerText =
                    enabled
                        ? "Dark"
                        : "Light";

            }

        }


        function loadDarkMode() {

            const enabled =
                localStorage.getItem(
                    "aiStudyScannerDarkMode"
                ) === "1";


            const checkbox =
                document.getElementById(
                    "darkModeToggle"
                );


            if (checkbox) {

                checkbox.checked =
                    enabled;

            }


            document.body.classList.toggle(
                "dark-mode",
                enabled
            );


            const label =
                document.getElementById(
                    "themeLabel"
                );


            if (label) {

                label.innerText =
                    enabled
                        ? "Dark"
                        : "Light";

            }

        }



        /* =========================================================
           START
        ========================================================= */


        loadDarkMode();

        initCamera();

        <?php if (!empty($_SESSION["logged_in"])): ?>

            loadItems();

        <?php endif; ?>


    </script>


</body>

</html>