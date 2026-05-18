<?php
session_start();
require 'db.php';

$error = '';

if (isset($_POST['login_action'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $result = $conn->query("SELECT * FROM users WHERE username='$username'");
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['os_user_id'] = $user['id'];
                $_SESSION['os_username'] = $user['username'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (username, password) VALUES ('$username', '$hashed')");
            $_SESSION['os_user_id'] = $conn->insert_id;
            $_SESSION['os_username'] = $username;
            header("Location: index.php");
            exit();
        }
    } else {
        $error = "Fill all fields!";
    }
}

if (isset($_POST['save_note']) && isset($_SESSION['os_user_id'])) {
    $title = $conn->real_escape_string(trim($_POST['note_title']));
    $content = $conn->real_escape_string($_POST['note_content']);
    $user_id = $_SESSION['os_user_id'];
    
    if (!empty($title)) {
        $conn->query("INSERT INTO user_notes (user_id, title, content) VALUES ($user_id, '$title', '$content')");
    }
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_note']) && isset($_SESSION['os_user_id'])) {
    $note_id = (int)$_GET['delete_note'];
    $user_id = $_SESSION['os_user_id'];
    $conn->query("DELETE FROM user_notes WHERE id=$note_id AND user_id=$user_id");
    header("Location: index.php");
    exit();
}

$notes = [];
$all_users = [];
if (isset($_SESSION['os_user_id'])) {
    $user_id = $_SESSION['os_user_id'];
    $res = $conn->query("SELECT * FROM user_notes WHERE user_id=$user_id ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) {
        $notes[] = $row;
    }
    
    $users_res = $conn->query("SELECT username FROM users ORDER BY id ASC");
    while ($u_row = $users_res->fetch_assoc()) {
        $all_users[] = $u_row['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Windows 98 - Web OS</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php if (!isset($_SESSION['os_user_id'])): ?>
    <div class="login-screen">
        <div class="window login-window">
            <div class="title-bar">
                <div class="title-bar-text">Enter Network Password</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Help">?</button>
                    <button type="button" aria-label="Close">x</button>
                </div>
            </div>
            <div class="window-body">
                <p style="margin-top:0;">Type a user name and password to log on to Windows.</p>
                <?php if (!empty($error)): ?>
                    <p style="color: red; font-weight: bold; margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <form method="POST" action="index.php">
                    <div class="form-group">
                        <label>User name:</label>
                        <input type="text" name="username" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="login-buttons">
                        <button type="submit" name="login_action">OK</button>
                        <button type="button" onclick="window.location.reload()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="desktop" id="desktop-area">
        
        <div class="desktop-icons" style="position: relative; z-index: 10;">
            <div class="icon" ondblclick="openWindow('computer-window')">
                <div class="icon-img">💻</div>
                <div class="icon-text">My Computer</div>
            </div>

            <div class="icon" ondblclick="openWindow('ie-window')">
                <div class="icon-img">🌐</div>
                <div class="icon-text">Internet</div>
            </div>

            <div class="icon" ondblclick="openWindow('notepad-window')">
                <div class="icon-img">📝</div>
                <div class="icon-text">Notepad</div>
            </div>
            
            <div class="icon" ondblclick="openWindow('cmd-window')">
                <div class="icon-img">📟</div>
                <div class="icon-text">MS-DOS</div>
            </div>
            
            <div class="icon" ondblclick="openWindow('calc-window')">
                <div class="icon-img">🧮</div>
                <div class="icon-text">Calculator</div>
            </div>

            <div class="icon" ondblclick="openWindow('paint-window')">
                <div class="icon-img">🎨</div>
                <div class="icon-text">Paint</div>
            </div>

            <div class="icon" ondblclick="openWindow('player-window')">
                <div class="icon-img">🎵</div>
                <div class="icon-text">Media Player</div>
            </div>

            <div class="icon" ondblclick="openWindow('snake-window'); initSnake();">
                <div class="icon-img">🐍</div>
                <div class="icon-text">Snake Game</div>
            </div>
            
            <?php foreach ($notes as $note): ?>
                <div class="icon" ondblclick="openNote(<?php echo $note['id']; ?>, '<?php echo addslashes(htmlspecialchars($note['title'])); ?>', '<?php echo addslashes(htmlspecialchars(preg_replace("/\r|\n/", "\\n", $note['content']))); ?>')">
                    <div class="icon-img">📄</div>
                    <div class="icon-text"><?php echo htmlspecialchars($note['title']); ?>.txt</div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="computer-window" class="window movable-window" style="display: none; left: 50px; top: 50px; width: 380px; height: 350px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'computer-window')">
                <div class="title-bar-text">System Properties</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('computer-window')">_</button>
                    <button type="button" aria-label="Maximize" onclick="maximizeWindow('computer-window')">□</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('computer-window')">x</button>
                </div>
            </div>
            <div class="window-body" style="background: white; flex-grow: 1; border: 2px solid; border-color: #808080 #fff #fff #808080; padding: 15px; overflow-y: auto;">
                <h3 style="margin-top: 0;">System:</h3>
                <p>Microsoft Windows 98<br>OS: <span id="sys-os" style="font-weight: bold;">Reading...</span></p>
                <hr>
                <h3>Computer Hardware:</h3>
                <p><strong>CPU:</strong> <span id="sys-cpu">Reading...</span></p>
                <p><strong>GPU:</strong> <span id="sys-gpu">Reading...</span></p>
                <p><strong>RAM:</strong> <span id="sys-ram">Reading...</span></p>
                <p><strong>Display:</strong> <span id="sys-screen">Reading...</span></p>
                <hr>
                <h3>Browser Engine:</h3>
                <p id="browser-info" style="word-break: break-all; font-size: 10px;">Reading...</p>
            </div>
        </div>

        <div id="ie-window" class="window movable-window" style="display: none; left: 80px; top: 80px; width: 600px; height: 450px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'ie-window')">
                <div class="title-bar-text">Internet Explorer</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('ie-window')">_</button>
                    <button type="button" aria-label="Maximize" onclick="maximizeWindow('ie-window')">□</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('ie-window')">x</button>
                </div>
            </div>
            <div class="window-body" style="margin: 0; padding: 2px; display: flex; flex-direction: column; flex-grow: 1;">
                <div style="display: flex; padding: 4px; background: #c0c0c0; gap: 5px; align-items: center; border-bottom: 2px solid #808080;">
                    <span style="font-weight: bold; margin-right: 5px;">Address:</span>
                    <input type="text" id="ie-url" value="https://en.wikipedia.org/wiki/Main_Page" style="flex-grow: 1;">
                    <button type="button" onclick="navigateIE()">Go</button>
                </div>
                <iframe id="ie-frame" src="https://en.wikipedia.org/wiki/Main_Page" style="flex-grow: 1; border: none; border-top: 2px solid #808080; border-left: 2px solid #808080; background: white;"></iframe>
            </div>
        </div>

        <div id="player-window" class="window movable-window" style="display: none; left: 120px; top: 120px; width: 280px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'player-window')">
                <div class="title-bar-text">Media Player</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('player-window')">_</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('player-window')">x</button>
                </div>
            </div>
            <div class="window-body" style="background: #000; padding: 15px; text-align: center; color: #0f0; border: 2px solid; border-color: #808080 #fff #fff #808080;">
                <div id="player-screen" style="margin-bottom: 15px; font-size: 14px; font-weight: bold;">LOCAL MUSIC.MP3</div>
                <div style="display: flex; justify-content: center; gap: 10px;">
                    <button type="button" onclick="playerPlay()" style="font-weight: bold;">▶ PLAY</button>
                    <button type="button" onclick="playerPause()" style="font-weight: bold;">⏸ PAUSE</button>
                    <button type="button" onclick="playerStop()" style="font-weight: bold;">⏹ STOP</button>
                </div>
                <audio id="sys-audio" src="music.mp3"></audio>
            </div>
        </div>

        <div id="snake-window" class="window movable-window" style="display: none; left: 300px; top: 100px; width: 420px; height: 460px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'snake-window')">
                <div class="title-bar-text">Snake Game</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('snake-window')">_</button>
                    <button type="button" aria-label="Maximize" onclick="maximizeWindow('snake-window')">□</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('snake-window')">x</button>
                </div>
            </div>
            <div class="window-body" style="background: #c0c0c0; display: flex; flex-direction: column; align-items: center; padding: 10px; flex-grow: 1;">
                <div style="width: 100%; display: flex; justify-content: space-between; margin-bottom: 5px; font-weight: bold; font-size: 14px;">
                    <span>Score: <span id="snake-score">0</span></span>
                    <button type="button" onclick="initSnake()">Restart</button>
                </div>
                <canvas id="snake-canvas" width="380" height="380" style="background: #000; border: 2px solid; border-color: #808080 #fff #fff #808080;"></canvas>
            </div>
        </div>

        <div id="notepad-window" class="window movable-window" style="display: none; left: 100px; top: 50px; width: 450px; height: 350px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'notepad-window')">
                <div class="title-bar-text" id="notepad-title">Notepad - Untitled</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('notepad-window')">_</button>
                    <button type="button" aria-label="Maximize" onclick="maximizeWindow('notepad-window')">□</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('notepad-window')">x</button>
                </div>
            </div>
            <div class="window-body" style="margin: 0; padding: 2px; display: flex; flex-direction: column; flex-grow: 1;">
                <form method="POST" action="index.php" style="display: flex; flex-direction: column; flex-grow: 1;">
                    <div style="display: flex; padding: 4px; background: #c0c0c0; gap: 5px;">
                        <input type="text" name="note_title" id="note-title-input" placeholder="File name..." required style="flex-grow: 1;">
                        <button type="submit" name="save_note">Save</button>
                        <a href="#" id="delete-note-link" style="display: none; text-decoration: none; color: black; background: #c0c0c0; border: 2px solid; border-color: #dfdfdf #000 #000 #dfdfdf; padding: 2px 10px; font-size: 11px; align-items: center;">Delete</a>
                    </div>
                    <textarea name="note_content" id="note-content-input" style="flex-grow: 1; border: none; border-top: 2px solid #808080; border-left: 2px solid #808080; outline: none; padding: 5px; font-family: 'Courier New', monospace; box-sizing: border-box; resize: none;"></textarea>
                </form>
            </div>
        </div>

        <div id="cmd-window" class="window movable-window" style="display: none; left: 150px; top: 100px; width: 550px; height: 400px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'cmd-window')">
                <div class="title-bar-text">MS-DOS Prompt</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('cmd-window')">_</button>
                    <button type="button" aria-label="Maximize" onclick="maximizeWindow('cmd-window')">□</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('cmd-window')">x</button>
                </div>
            </div>
            <div id="cmd-window-body" class="window-body" style="margin: 0; padding: 0; background-color: #000; color: #c0c0c0; font-family: 'Courier New', monospace; display: flex; flex-direction: column; flex-grow: 1; overflow-y: auto; cursor: text; position: relative;" onclick="document.getElementById('cmd-input').focus()">
                <div id="cmd-output" style="padding: 5px; box-sizing: border-box;">
                    Microsoft(R) Windows 98<br>
                    (C)Copyright Microsoft Corp 1981-1999.<br><br>
                </div>
                <div style="display: flex; padding: 0 5px 5px 5px;">
                    <span style="margin-right: 5px;">C:\WINDOWS></span>
                    <input type="text" id="cmd-input" style="background: transparent; border: none; color: #c0c0c0; font-family: 'Courier New', monospace; outline: none; flex-grow: 1; font-size: 13px;" autocomplete="off">
                </div>
            </div>
        </div>

        <div id="calc-window" class="window movable-window" style="display: none; left: 200px; top: 150px; width: 220px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'calc-window')">
                <div class="title-bar-text">Calculator</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('calc-window')">_</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('calc-window')">x</button>
                </div>
            </div>
            <div class="window-body" style="padding: 10px; background: #c0c0c0;">
                <input type="text" id="calc-screen" readonly value="0" style="width: 100%; box-sizing: border-box; text-align: right; padding: 5px; margin-bottom: 10px; border: 2px solid; border-color: #808080 #fff #fff #808080; background: #fff; font-size: 16px; font-weight: bold;">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
                    <button type="button" class="calc-btn" onclick="calcClear()" style="grid-column: span 3; color: red;">C</button>
                    <button type="button" class="calc-btn" onclick="calcPress('/')">/</button>
                    <button type="button" class="calc-btn" onclick="calcPress('7')">7</button>
                    <button type="button" class="calc-btn" onclick="calcPress('8')">8</button>
                    <button type="button" class="calc-btn" onclick="calcPress('9')">9</button>
                    <button type="button" class="calc-btn" onclick="calcPress('*')">*</button>
                    <button type="button" class="calc-btn" onclick="calcPress('4')">4</button>
                    <button type="button" class="calc-btn" onclick="calcPress('5')">5</button>
                    <button type="button" class="calc-btn" onclick="calcPress('6')">6</button>
                    <button type="button" class="calc-btn" onclick="calcPress('-')">-</button>
                    <button type="button" class="calc-btn" onclick="calcPress('1')">1</button>
                    <button type="button" class="calc-btn" onclick="calcPress('2')">2</button>
                    <button type="button" class="calc-btn" onclick="calcPress('3')">3</button>
                    <button type="button" class="calc-btn" onclick="calcPress('+')">+</button>
                    <button type="button" class="calc-btn" onclick="calcPress('0')" style="grid-column: span 2;">0</button>
                    <button type="button" class="calc-btn" onclick="calcPress('.')">.</button>
                    <button type="button" class="calc-btn" onclick="calcSolve()">=</button>
                </div>
            </div>
        </div>

        <div id="paint-window" class="window" style="display: none; left: 250px; top: 20px; width: 600px; height: 500px;" data-maximized="false">
            <div class="title-bar" onmousedown="startDrag(event, 'paint-window')">
                <div class="title-bar-text">Paint</div>
                <div class="title-bar-controls">
                    <button type="button" aria-label="Minimize" onclick="minimizeWindow('paint-window')">_</button>
                    <button type="button" aria-label="Close" onclick="closeWindow('paint-window')">x</button>
                </div>
            </div>
            <div class="window-body" style="margin: 0; padding: 2px; display: flex; flex-direction: column; flex-grow: 1; background: #c0c0c0;">
                <div style="display: flex; gap: 10px; padding: 5px; align-items: center; border-bottom: 2px solid #808080;">
                    <input type="color" id="paint-color" value="#000000" style="width: 30px; height: 30px; padding: 0; border: 2px solid #808080; cursor: pointer;">
                    <label style="cursor: pointer;">Size: <input type="range" id="paint-size" min="1" max="20" value="3" style="width: 100px; cursor: pointer;"></label>
                    <button type="button" onclick="clearCanvas()" style="cursor: pointer;">Clear</button>
                </div>
                <div id="paint-container" style="flex-grow: 1; background: #808080; padding: 5px; overflow: hidden; display: flex;">
                    <canvas id="paint-canvas" width="570" height="420" style="background: white; border: 2px solid #000; box-shadow: 1px 1px 0 #fff; touch-action: none;"></canvas>
                </div>
            </div>
        </div>

    </div>

    <div class="start-menu" id="start-menu" style="display: none;">
        <div class="start-sidebar">
            <span class="sidebar-text"><b>Windows</b> 98</span>
        </div>
        <div class="start-items">
            <div class="start-item" style="border-bottom: 1px solid #808080; padding-bottom: 10px; margin-bottom: 5px; cursor: default;">
                👤 User: <b><?php echo htmlspecialchars($_SESSION['os_username']); ?></b>
            </div>
            <a href="logout.php" class="start-item">
                <span style="font-size: 16px;">⏻</span> Shut Down...
            </a>
        </div>
    </div>

    <div class="taskbar">
        <button class="start-btn" id="start-btn" onclick="toggleStartMenu()">
            <span style="color: red; font-weight: 900; margin-right: 3px;">⊞</span> Start
        </button>
        <div class="taskbar-apps">
            <div class="taskbar-tab" id="computer-tab" style="display: none;" onclick="toggleTaskbarWindow('computer-window')">💻 System</div>
            <div class="taskbar-tab" id="ie-tab" style="display: none;" onclick="toggleTaskbarWindow('ie-window')">🌐 IE</div>
            <div class="taskbar-tab" id="notepad-tab" style="display: none;" onclick="toggleTaskbarWindow('notepad-window')">📝 Notepad</div>
            <div class="taskbar-tab" id="cmd-tab" style="display: none;" onclick="toggleTaskbarWindow('cmd-window')">📟 MS-DOS</div>
            <div class="taskbar-tab" id="calc-tab" style="display: none;" onclick="toggleTaskbarWindow('calc-window')">🧮 Calc</div>
            <div class="taskbar-tab" id="paint-tab" style="display: none;" onclick="toggleTaskbarWindow('paint-window')">🎨 Paint</div>
            <div class="taskbar-tab" id="player-tab" style="display: none;" onclick="toggleTaskbarWindow('player-window')">🎵 Media</div>
            <div class="taskbar-tab" id="snake-tab" style="display: none;" onclick="toggleTaskbarWindow('snake-window')">🐍 Snake</div>
        </div>
        <div class="tray">
            <span id="os-clock">00:00</span>
        </div>
    </div>

    <script>
        const osCurrentUser = "<?php echo htmlspecialchars($_SESSION['os_username']); ?>";
        const osDbUsers = <?php echo json_encode($all_users); ?>;
    </script>
<?php endif; ?>

<script src="script.js"></script>
</body>
</html>
