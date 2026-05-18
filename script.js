let activeWindow = null, isDragging = false, offsetX = 0, offsetY = 0, preMaxState = {};
let matrixInterval = null, calcExpr = "", isCalcEvaluated = false;
let snakeInterval, snake = [], food = {}, snakeDx = 10, snakeDy = 0, snakeScore = 0;

function updateClock() {
    const el = document.getElementById('os-clock');
    if (el) el.innerText = new Date().getHours().toString().padStart(2, '0') + ':' + new Date().getMinutes().toString().padStart(2, '0');
}
setInterval(updateClock, 1000);

function toggleStartMenu() {
    const m = document.getElementById('start-menu'), b = document.getElementById('start-btn');
    if (!m) return;
    const isHidden = m.style.display === 'none';
    m.style.display = isHidden ? 'flex' : 'none';
    b.style.boxShadow = isHidden ? 'inset 1px 1px 0 #808080' : 'none';
    b.style.borderColor = isHidden ? '#000 #dfdfdf #dfdfdf #000' : '#dfdfdf #000 #000 #dfdfdf';
}

document.addEventListener('click', e => {
    const m = document.getElementById('start-menu'), b = document.getElementById('start-btn');
    if (m && m.style.display === 'flex' && !m.contains(e.target) && !b.contains(e.target)) toggleStartMenu();
});

function getTab(id) { return document.getElementById(id.replace('-window', '-tab')); }

function openWindow(id) {
    const w = document.getElementById(id), t = getTab(id);
    if (!w) return;
    w.style.display = 'flex';
    if (t) t.style.display = 'flex';
    bringToFront(w);

    if (id === 'notepad-window') {
        document.getElementById('notepad-title').innerText = "Notepad - Untitled";
        document.getElementById('note-title-input').value = "";
        document.getElementById('note-content-input').value = "";
        document.getElementById('delete-note-link').style.display = "none";
    }
    if (id === 'cmd-window') setTimeout(() => document.getElementById('cmd-input').focus(), 100);
    if (id === 'calc-window') calcClear();
    if (id === 'computer-window') {
        document.getElementById('browser-info').innerText = navigator.userAgent;
        document.getElementById('sys-os').innerText = navigator.platform;
        document.getElementById('sys-cpu').innerText = (navigator.hardwareConcurrency || "Unknown") + " Logical Cores";
        document.getElementById('sys-ram').innerText = (navigator.deviceMemory ? ">= " + navigator.deviceMemory : "Unknown") + " GB";
        document.getElementById('sys-screen').innerText = `${screen.width}x${screen.height} @ ${screen.colorDepth}-bit`;
        try {
            const gl = document.createElement('canvas').getContext('webgl');
            const ext = gl ? gl.getExtension('WEBGL_debug_renderer_info') : null;
            document.getElementById('sys-gpu').innerText = ext ? gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) : "Unknown GPU";
        } catch(e) {}
    }
}

function closeWindow(id) {
    const w = document.getElementById(id), t = getTab(id);
    if (w) w.style.display = 'none';
    if (t) t.style.display = 'none';
    if (id === 'cmd-window') stopMatrix(); 
    if (id === 'snake-window') clearInterval(snakeInterval);
    if (id === 'player-window') playerStop();
}

function minimizeWindow(id) { const w = document.getElementById(id); if (w) w.style.display = 'none'; }

function maximizeWindow(id) {
    const w = document.getElementById(id);
    if (!w || id === 'calc-window' || id === 'player-window' || id === 'paint-window') return;
    if (w.dataset.maximized === "true") {
        Object.assign(w.style, preMaxState[id]);
        w.dataset.maximized = "false";
    } else {
        preMaxState[id] = { width: w.style.width || w.offsetWidth + 'px', height: w.style.height || w.offsetHeight + 'px', top: w.style.top || w.offsetTop + 'px', left: w.style.left || w.offsetLeft + 'px' };
        Object.assign(w.style, { top: '0', left: '0', width: '100%', height: 'calc(100% - 28px)' });
        w.dataset.maximized = "true";
    }
    bringToFront(w);
    if (id === 'cmd-window' && matrixInterval) resizeMatrix(); 
}

function toggleTaskbarWindow(id) {
    const w = document.getElementById(id);
    if (!w) return;
    w.style.display === 'none' ? (openWindow(id), bringToFront(w)) : minimizeWindow(id);
}

function openNote(id, title, content) {
    openWindow('notepad-window');
    document.getElementById('notepad-title').innerText = "Notepad - " + title + ".txt";
    document.getElementById('note-title-input').value = title;
    document.getElementById('note-content-input').value = content;
    const d = document.getElementById('delete-note-link');
    d.href = "index.php?delete_note=" + id;
    d.style.display = "flex";
}

function bringToFront(el) { document.querySelectorAll('.window').forEach(w => w.style.zIndex = 100); el.style.zIndex = 101; }

function startDrag(e, id) {
    activeWindow = document.getElementById(id);
    if (activeWindow.dataset.maximized === "true" || ['BUTTON', 'INPUT'].includes(e.target.tagName)) return;
    isDragging = true;
    bringToFront(activeWindow);
    const rect = activeWindow.getBoundingClientRect();
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;
    document.addEventListener('mousemove', dragWindow);
    document.addEventListener('mouseup', stopDrag);
}

function dragWindow(e) { if (isDragging && activeWindow) { activeWindow.style.left = (e.clientX - offsetX) + 'px'; activeWindow.style.top = (e.clientY - offsetY) + 'px'; } }
function stopDrag() { isDragging = false; document.removeEventListener('mousemove', dragWindow); document.removeEventListener('mouseup', stopDrag); }

document.addEventListener('keydown', e => {
    if (matrixInterval) { stopMatrix(); e.preventDefault(); return; }
    
    const sw = document.getElementById('snake-window');
    if (sw && sw.style.display !== 'none') {
        const moves = { ArrowLeft: [-10, 0], a: [-10, 0], ArrowUp: [0, -10], w: [0, -10], ArrowRight: [10, 0], d: [10, 0], ArrowDown: [0, 10], s: [0, 10] };
        if (moves[e.key]) {
            const [nx, ny] = moves[e.key];
            if (!(snakeDx === 10 && nx === -10 || snakeDx === -10 && nx === 10 || snakeDy === 10 && ny === -10 || snakeDy === -10 && ny === 10)) {
                snakeDx = nx; snakeDy = ny; e.preventDefault();
            }
        }
    }

    if (e.key === 'Enter') {
        if (document.activeElement.id === 'ie-url') { navigateIE(); e.preventDefault(); return; }
        if (document.activeElement.id === 'cmd-input') {
            const i = document.getElementById('cmd-input'), o = document.getElementById('cmd-output');
            const c = i.value.trim().toLowerCase();
            let r = "Bad command or file name";
            
            if (c === 'help') r = "Available commands:<br>HELP, WHOAMI, USERS, MATRIX, DATE, CLEAR";
            else if (c === 'whoami') r = osCurrentUser;
            else if (c === 'users') r = "Users:<br>- " + osDbUsers.join("<br>- ");
            else if (c === 'date') r = new Date().toString();
            else if (c === 'clear') { o.innerHTML = ""; i.value = ""; return; }
            else if (c === 'matrix') { startMatrix(); i.value = ""; return; }
            else if (c === '') r = "";

            if (c !== "") o.innerHTML += `C:\\WINDOWS> ${i.value}<br>` + (r ? `${r}<br><br>` : "");
            i.value = ""; o.parentElement.scrollTop = o.parentElement.scrollHeight;
        }
    }
});

function startMatrix() {
    const b = document.getElementById('cmd-window-body'), c = document.createElement('canvas');
    c.id = 'matrix-canvas'; c.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;z-index:50';
    b.appendChild(c); resizeMatrix();
    const ctx = c.getContext('2d'), chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$+-*/=%""\'#&_(),.;:?!\\|{}<>[]^~';
    const drops = Array(Math.floor(c.width / 16)).fill(1);
    matrixInterval = setInterval(() => {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.05)'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.fillStyle = '#0F0'; ctx.font = '16px Courier New';
        drops.forEach((y, i) => {
            ctx.fillText(chars[Math.floor(Math.random() * chars.length)], i * 16, y * 16);
            if (y * 16 > c.height && Math.random() > 0.975) drops[i] = 0;
            drops[i]++;
        });
    }, 33);
}
function stopMatrix() { clearInterval(matrixInterval); matrixInterval = null; document.getElementById('matrix-canvas')?.remove(); document.getElementById('cmd-input')?.focus(); }
function resizeMatrix() { const c = document.getElementById('matrix-canvas'), b = document.getElementById('cmd-window-body'); if (c && b) { c.width = b.offsetWidth; c.height = b.offsetHeight; } }

function calcPress(v) { const s = document.getElementById('calc-screen'); if (isCalcEvaluated && !isNaN(v)) { calcExpr = ""; isCalcEvaluated = false; } else isCalcEvaluated = false; calcExpr += v; s.value = calcExpr; }
function calcClear() { calcExpr = ""; document.getElementById('calc-screen').value = "0"; isCalcEvaluated = false; }
function calcSolve() { try { if (!calcExpr.trim()) return; const r = eval(calcExpr); document.getElementById('calc-screen').value = r; calcExpr = r.toString(); isCalcEvaluated = true; } catch { document.getElementById('calc-screen').value = "Error"; calcExpr = ""; isCalcEvaluated = true; } }

const pC = document.getElementById('paint-canvas');
let pt = pC ? pC.getContext('2d') : null, isP = false;
if (pC) {
    pt.fillStyle = "white"; pt.fillRect(0, 0, pC.width, pC.height);
    pC.onmousedown = e => { isP = true; pt.beginPath(); pt.moveTo(e.offsetX, e.offsetY); };
    pC.onmousemove = e => { if (isP) { pt.lineTo(e.offsetX, e.offsetY); pt.strokeStyle = document.getElementById('paint-color').value; pt.lineWidth = document.getElementById('paint-size').value; pt.lineCap = 'round'; pt.stroke(); } };
    pC.onmouseup = pC.onmouseout = () => isP = false;
}
function clearCanvas() { if (pt && pC) { pt.fillStyle = "white"; pt.fillRect(0, 0, pC.width, pC.height); } }

function navigateIE() { let u = document.getElementById('ie-url').value.trim(); if (u && !u.startsWith('http')) u = 'https://' + u; document.getElementById('ie-url').value = u; document.getElementById('ie-frame').src = u; }
function playerPlay() { document.getElementById('sys-audio')?.play().catch(()=>{}); }
function playerPause() { document.getElementById('sys-audio')?.pause(); }
function playerStop() { const a = document.getElementById('sys-audio'); if (a) { a.pause(); a.currentTime = 0; } }

function initSnake() {
    if (!document.getElementById('snake-canvas')) return;
    snake = [{x: 200, y: 200}, {x: 190, y: 200}, {x: 180, y: 200}]; snakeDx = 10; snakeDy = 0; snakeScore = 0;
    document.getElementById('snake-score').innerText = snakeScore;
    spawnFood();
    clearInterval(snakeInterval); snakeInterval = setInterval(drawSnake, 100);
}
function drawSnake() {
    const c = document.getElementById('snake-canvas'), ctx = c.getContext('2d'), head = { x: snake[0].x + snakeDx, y: snake[0].y + snakeDy };
    if (head.x < 0 || head.x >= c.width || head.y < 0 || head.y >= c.height || snake.some(p => p.x === head.x && p.y === head.y)) {
        ctx.fillStyle = 'red'; ctx.font = '30px Courier New'; ctx.fillText('GAME OVER', 110, 190); return clearInterval(snakeInterval);
    }
    snake.unshift(head);
    if (head.x === food.x && head.y === food.y) { snakeScore += 10; document.getElementById('snake-score').innerText = snakeScore; spawnFood(); } else snake.pop();
    ctx.fillStyle = 'black'; ctx.fillRect(0, 0, c.width, c.height);
    ctx.fillStyle = 'red'; ctx.fillRect(food.x, food.y, 10, 10);
    ctx.fillStyle = '#0f0'; snake.forEach(p => { ctx.fillRect(p.x, p.y, 10, 10); ctx.strokeStyle = 'darkgreen'; ctx.strokeRect(p.x, p.y, 10, 10); });
}
function spawnFood() {
    const c = document.getElementById('snake-canvas');
    food = { x: Math.round(Math.random() * (c.width - 10) / 10) * 10, y: Math.round(Math.random() * (c.height - 10) / 10) * 10 };
    if (snake.some(p => p.x === food.x && p.y === food.y)) spawnFood();
}

document.addEventListener('DOMContentLoaded', () => {
    updateClock();
});
