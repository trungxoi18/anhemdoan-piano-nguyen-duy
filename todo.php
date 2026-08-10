<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// API XỬ LÝ AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action == 'add') {
        $noiDung = trim($_POST['noiDung'] ?? '');
        if (!empty($noiDung)) {
            $st = $conn->prepare("INSERT INTO vieccanlam (maTaiKhoan, noiDung) VALUES (?, ?)");
            $st->bind_param("is", $user_id, $noiDung);
            if ($st->execute()) {
                echo json_encode(['success' => true, 'maViec' => $conn->insert_id]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }

    if ($action == 'toggle') {
        $maViec = intval($_POST['maViec']);
        $status = intval($_POST['status']); // 1 or 0
        $st = $conn->prepare("UPDATE vieccanlam SET trangThai = ? WHERE maViec = ? AND maTaiKhoan = ?");
        $st->bind_param("iii", $status, $maViec, $user_id);
        if ($st->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    if ($action == 'delete') {
        $maViec = intval($_POST['maViec']);
        $st = $conn->prepare("DELETE FROM vieccanlam WHERE maViec = ? AND maTaiKhoan = ?");
        $st->bind_param("ii", $maViec, $user_id);
        if ($st->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }
}

// Lấy danh sách việc cần làm của user hiện tại
$todos = [];
$sql = "SELECT * FROM vieccanlam WHERE maTaiKhoan = $user_id ORDER BY trangThai ASC, ngayTao DESC";
$res = $conn->query($sql);
if ($res) {
    while($row = $res->fetch_assoc()) {
        $todos[] = $row;
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .todo-container {
        max-width: 800px;
        margin: 0 auto;
        animation: fadeInUp 0.5s ease;
    }

    .todo-header {
        background: linear-gradient(135deg, rgba(124, 92, 252, 0.15), rgba(99, 102, 241, 0.05));
        border: 1px solid rgba(124, 92, 252, 0.25);
        padding: 36px 40px;
        border-radius: var(--radius-xl);
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
    }

    .todo-header::before {
        content: ''; position: absolute; top: -50%; right: -10%; width: 50%; height: 200%;
        background: radial-gradient(circle, rgba(124, 92, 252, 0.1) 0%, transparent 70%); pointer-events: none;
    }

    .todo-title {
        font-size: 1.8rem; font-weight: 800; color: #7c5cfc; margin: 0 0 10px 0;
        display: flex; align-items: center; gap: 12px;
    }

    .todo-subtitle { margin: 0; font-size: 15px; color: var(--text-secondary); }

    .todo-form {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        padding: 24px;
        margin-bottom: 24px;
        display: flex;
        gap: 16px;
    }

    .todo-input {
        flex: 1;
        background: rgba(0,0,0,0.2);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        padding: 14px 20px;
        color: var(--text-primary);
        font-family: inherit; font-size: 15px;
        outline: none; transition: 0.3s;
    }

    .todo-input:focus {
        border-color: #7c5cfc;
        background: rgba(124, 92, 252, 0.05);
        box-shadow: 0 0 0 3px rgba(124, 92, 252, 0.15);
    }

    .btn-add-todo {
        background: linear-gradient(135deg, #7c5cfc, #6366f1);
        color: #fff;
        border: none;
        border-radius: var(--radius-md);
        padding: 0 28px;
        font-weight: 700; font-size: 15px;
        cursor: pointer;
        transition: 0.3s;
        display: flex; align-items: center; gap: 8px;
        box-shadow: 0 4px 16px rgba(124, 92, 252, 0.3);
    }

    .btn-add-todo:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(124, 92, 252, 0.4);
    }

    .todo-list {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        padding: 12px;
    }

    .todo-item {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px dashed var(--glass-border);
        transition: 0.2s;
    }
    .todo-item:last-child { border-bottom: none; }
    .todo-item:hover { background: rgba(255,255,255,0.02); border-radius: 12px; }

    .todo-checkbox {
        appearance: none;
        width: 24px; height: 24px;
        border: 2px solid var(--text-muted);
        border-radius: 6px;
        margin-right: 16px;
        cursor: pointer;
        position: relative;
        transition: 0.2s;
        flex-shrink: 0;
    }
    
    .todo-checkbox:checked {
        background: #7c5cfc;
        border-color: #7c5cfc;
    }

    .todo-checkbox:checked::after {
        content: ''; position: absolute;
        width: 6px; height: 12px;
        border: solid white; border-width: 0 2px 2px 0;
        transform: rotate(45deg);
        top: 2px; left: 7px;
    }

    .todo-text {
        flex: 1;
        font-size: 15px;
        color: var(--text-primary);
        transition: 0.3s;
    }

    .todo-item.completed .todo-text {
        color: var(--text-muted);
        text-decoration: line-through;
    }

    .todo-date {
        font-size: 12px;
        color: var(--text-muted);
        margin-right: 16px;
    }

    .btn-delete-todo {
        background: transparent;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: 0.2s;
        display: flex; align-items: center; justify-content: center;
    }

    .btn-delete-todo:hover {
        background: rgba(248, 113, 113, 0.1);
        color: var(--danger);
    }
    
    .empty-state {
        text-align: center; padding: 60px 20px; color: var(--text-muted);
    }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="todo-container">
            
            <div class="todo-header">
                <h1 class="todo-title">
                    <span class="material-symbols-rounded" style="font-size: 36px;">task_alt</span>
                    Việc Cần Làm
                </h1>
                <p class="todo-subtitle">Ghi chú lại những công việc bạn cần hoàn thành trong ngày.</p>
            </div>

            <form class="todo-form" id="todoForm" onsubmit="addTodo(event)">
                <input type="text" id="todoInput" class="todo-input" placeholder="Nhập công việc mới..." required autocomplete="off">
                <button type="submit" class="btn-add-todo">
                    <span class="material-symbols-rounded">add</span> Thêm
                </button>
            </form>

            <div class="todo-list" id="todoList">
                <?php if (count($todos) > 0): ?>
                    <?php foreach ($todos as $t): ?>
                    <div class="todo-item <?php echo $t['trangThai'] ? 'completed' : ''; ?>" id="todo-<?php echo $t['maViec']; ?>">
                        <input type="checkbox" class="todo-checkbox" onchange="toggleTodo(<?php echo $t['maViec']; ?>, this.checked)" <?php echo $t['trangThai'] ? 'checked' : ''; ?>>
                        <div class="todo-text"><?php echo htmlspecialchars($t['noiDung']); ?></div>
                        <div class="todo-date"><?php echo date('d/m H:i', strtotime($t['ngayTao'])); ?></div>
                        <button class="btn-delete-todo" onclick="deleteTodo(<?php echo $t['maViec']; ?>)">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" id="emptyState">
                        <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 16px;">celebration</span>
                        <h3>Bạn đã hoàn thành mọi việc!</h3>
                        <p>Hãy tận hưởng thời gian nghỉ ngơi hoặc thêm công việc mới.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
    function addTodo(e) {
        e.preventDefault();
        const input = document.getElementById('todoInput');
        const text = input.value.trim();
        if (!text) return;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('noiDung', text);

        fetch('todo.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Reload trang để render lại (cách đơn giản và an toàn)
                window.location.reload();
            } else {
                alert('Có lỗi xảy ra khi thêm công việc.');
            }
        });
    }

    function toggleTodo(id, isChecked) {
        const formData = new FormData();
        formData.append('action', 'toggle');
        formData.append('maViec', id);
        formData.append('status', isChecked ? 1 : 0);

        fetch('todo.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const item = document.getElementById('todo-' + id);
                if (isChecked) {
                    item.classList.add('completed');
                } else {
                    item.classList.remove('completed');
                }
            }
        });
    }

    function deleteTodo(id) {
        if (!confirm('Xóa công việc này?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('maViec', id);

        fetch('todo.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const item = document.getElementById('todo-' + id);
                item.style.opacity = '0';
                setTimeout(() => {
                    item.remove();
                    // Kiểm tra nếu rỗng
                    const list = document.getElementById('todoList');
                    if (list.querySelectorAll('.todo-item').length === 0) {
                        window.location.reload(); // Reload to show empty state
                    }
                }, 200);
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
