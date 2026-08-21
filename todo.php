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
