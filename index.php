<?php
// เชื่อมต่อ MariaDB (ข้อมูลจากหน้าจอดำของคุณ)
$conn = new mysqli(”localhost“, ”root“, ”รหัสผ่าน“, ”6023“);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) { die(”Connection failed: “ . $conn->connect_error); }

// เมื่อมีการส่งข้อมูลจากฟอร์มให้บันทึกลง Table
if ($_SERVER[”REQUEST_METHOD“] == ”POST“) {
    $node_val = $_POST[’node_value‘]; 
    $conn->query(”INSERT INTO tree_nodes (value) VALUES (’$node_val‘)“);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BST Commander v4.0 - Full CRUD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
        }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .node-circle { transition: all 0.4s ease; }
        .node-active { stroke: #fbbf24; stroke-width: 5px; filter: drop-shadow(0 0 15px #fbbf24); }
        .link-path { stroke-dasharray: 1000; stroke-dashoffset: 1000; animation: draw 1.2s forwards; }
        @keyframes draw { to { stroke-dashoffset: 0; } }
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="text-slate-200 min-h-screen p-4 md:p-8">

    <div class="max-w-6xl mx-auto w-full glass p-6 rounded-3xl flex justify-between items-center mb-6 shadow-2xl border-b border-blue-500/30">
        <div>
            <h1 class="text-2xl font-800 bg-gradient-to-r from-blue-400 via-cyan-400 to-emerald-400 bg-clip-text text-transparent tracking-tighter">
                BST COMMANDER <span class="text-slate-500 font-light">v4.0</span>
            </h1>
            <p id="systemStatus" class="text-[10px] text-slate-500 uppercase tracking-[0.2em] font-bold mt-1">System Ready • Awaiting Command</p>
        </div>
        <div class="hidden md:flex gap-4 text-right">
            <div class="text-[10px] text-slate-400 font-bold uppercase">Node Count: <span id="nodeCount" class="text-blue-400">0</span></div>
            <div class="text-[10px] text-slate-400 font-bold uppercase">Tree Height: <span id="treeHeight" class="text-emerald-400">0</span></div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto w-full grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <div class="lg:col-span-1 space-y-4">
            <div class="glass p-6 rounded-[2rem] shadow-xl">
                <label class="text-[10px] text-slate-400 uppercase font-bold mb-3 block tracking-widest">Command Input</label>
                <input id="valInput" type="number" placeholder="Value..." 
                    class="w-full bg-slate-900/50 border border-slate-700 rounded-2xl px-4 py-4 text-2xl font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 text-white transition-all mb-4">
                
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="handleInsert()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition-all active:scale-95">INSERT</button>
                    <button onclick="handleSearch()" class="bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-3 rounded-xl transition-all active:scale-95">SEARCH</button>
                    <button onclick="handleDelete()" class="bg-rose-600 hover:bg-rose-500 text-white font-bold py-3 rounded-xl transition-all active:scale-95">DELETE</button>
                    <button onclick="handleUpdate()" class="bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 rounded-xl transition-all active:scale-95">UPDATE</button>
                </div>
                <button onclick="clearTree()" class="w-full mt-4 text-[10px] text-slate-500 hover:text-rose-400 transition-colors uppercase font-bold">Purge Database (Clear)</button>
            </div>

            <div class="glass p-6 rounded-[2rem]">
                <label class="text-[10px] text-slate-400 uppercase font-bold mb-3 block tracking-widest">In-Order Traversal (Sorted)</label>
                <div id="traversalOutput" class="text-emerald-400 font-mono text-xs leading-relaxed bg-black/40 p-4 rounded-xl border border-white/5 min-h-[60px] break-all">
                    []
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 glass rounded-[2.5rem] relative overflow-hidden flex flex-col min-h-[600px] border border-white/5">
            <div class="absolute top-6 left-6 z-10 flex gap-2">
                <span class="bg-blue-500/10 text-blue-400 text-[10px] font-bold px-3 py-1 rounded-full border border-blue-500/20 uppercase tracking-wider">Live topology view</span>
            </div>
            <svg id="treeSvg" width="100%" height="100%" class="flex-1"></svg>
        </div>
    </div>

    <script>
        class Node {
            constructor(val) {
                this.val = val;
                this.left = null;
                this.right = null;
            }
        }

        let root = null;
        let highlightedNode = null;
        const svg = document.getElementById('treeSvg');

        // --- BST Logic Core ---

        function insert(node, val) {
            if (!node) return new Node(val);
            if (val < node.val) node.left = insert(node.left, val);
            else if (val > node.val) node.right = insert(node.right, val);
            return node;
        }

        function remove(node, val) {
            if (!node) return null;
            if (val < node.val) node.left = remove(node.left, val);
            else if (val > node.val) node.right = remove(node.right, val);
            else {
                if (!node.left) return node.right;
                if (!node.right) return node.left;
                let temp = node.right;
                while (temp.left) temp = temp.left;
                node.val = temp.val;
                node.right = remove(node.right, temp.val);
            }
            return node;
        }

        function search(node, val) {
            if (!node || node.val === val) return node;
            if (val < node.val) return search(node.left, val);
            return search(node.right, val);
        }

        function getHeight(node) {
            if (!node) return 0;
            return 1 + Math.max(getHeight(node.left), getHeight(node.right));
        }

        function getInorder(node, res = []) {
            if (node) {
                getInorder(node.left, res);
                res.push(node.val);
                getInorder(node.right, res);
            }
            return res;
        }

        // --- UI Rendering ---

        function updateUI(message) {
            svg.innerHTML = '';
            const width = svg.clientWidth;
            const treeHeight = getHeight(root);
            
            drawTree(root, width / 2, 80, width / 4, 1);
            
            // Update Stats
            const inorderArr = getInorder(root);
            document.getElementById('traversalOutput').textContent = inorderArr.length ? `[ ${inorderArr.join(" , ")} ]` : "[]";
            document.getElementById('nodeCount').textContent = inorderArr.length;
            document.getElementById('treeHeight').textContent = treeHeight;
            
            if(message) {
                const status = document.getElementById('systemStatus');
                status.textContent = message;
                status.classList.add('text-blue-400');
                setTimeout(() => status.classList.remove('text-blue-400'), 1500);
            }
        }

        function drawTree(node, x, y, spacing, level) {
            if (!node) return;

            const nextY = y + 80;
            const circleRadius = 24;

            if (node.left) {
                createLine(x, y, x - spacing, nextY);
                drawTree(node.left, x - spacing, nextY, spacing / 1.8, level + 1);
            }
            if (node.right) {
                createLine(x, y, x + spacing, nextY);
                drawTree(node.right, x + spacing, nextY, spacing / 1.8, level + 1);
            }

            const isHighlighted = highlightedNode === node.val;
            createNode(x, y, node.val, isHighlighted);
        }

        function createLine(x1, y1, x2, y2) {
            const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
            line.setAttribute("x1", x1); line.setAttribute("y1", y1);
            line.setAttribute("x2", x2); line.setAttribute("y2", y2);
            line.setAttribute("class", "link-path stroke-slate-700");
            line.setAttribute("stroke-width", "2");
            svg.appendChild(line);
        }

        function createNode(x, y, val, isActive) {
            const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
            const circleClass = isActive ? "node-circle fill-slate-900 stroke-amber-400 stroke-[4px] node-active" : "node-circle fill-slate-900 stroke-blue-500/50 stroke-[2px]";
            
            g.innerHTML = `
                <circle cx="${x}" cy="${y}" r="22" class="${circleClass}" />
                <text x="${x}" y="${y + 5}" text-anchor="middle" class="fill-white font-bold text-xs pointer-events-none">${val}</text>
            `;
            svg.appendChild(g);
        }

        // --- Actions ---

        function handleInsert() {
            const input = document.getElementById('valInput');
            const val = parseInt(input.value);
            if (isNaN(val)) return;
            
            root = insert(root, val);
            highlightedNode = val;
            updateUI(`SUCCESS: Node ${val} synced to topology`);
            input.value = '';
            input.focus();
        }

        function handleSearch() {
            const val = parseInt(document.getElementById('valInput').value);
            if (isNaN(val)) return;

            const found = search(root, val);
            if (found) {
                highlightedNode = val;
                updateUI(`LOCATED: Node ${val} found in memory`);
            } else {
                highlightedNode = null;
                updateUI(`ERROR: Node ${val} not found`);
            }
        }

        function handleDelete() {
            const val = parseInt(document.getElementById('valInput').value);
            if (isNaN(val)) return;

            root = remove(root, val);
            highlightedNode = null;
            updateUI(`REMOVED: Node ${val} decommissioned`);
            document.getElementById('valInput').value = '';
        }

        function handleUpdate() {
            const oldVal = parseInt(document.getElementById('valInput').value);
            if (isNaN(oldVal)) return;

            if (!search(root, oldVal)) {
                updateUI(`UPDATE FAILED: Target ${oldVal} missing`);
                return;
            }

            const newVal = prompt(`Update Node ${oldVal} to:`);
            if (newVal !== null && !isNaN(parseInt(newVal))) {
                root = remove(root, oldVal);
                root = insert(root, parseInt(newVal));
                highlightedNode = parseInt(newVal);
                updateUI(`REPLACED: ${oldVal} ⮕ ${newVal}`);
            }
        }

        function clearTree() {
            if(confirm("Confirm total system purge?")) {
                root = null;
                highlightedNode = null;
                updateUI("SYSTEM PURGED: Memory empty");
            }
        }

        window.addEventListener('resize', () => updateUI());
        // Initial build
        updateUI();
    </script>
</body>
</html>


