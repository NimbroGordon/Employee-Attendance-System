<?php
/**
 * mark.php – Screen 3: Mark daily attendance
 * Lets the user pick a date and set Present / Late / Absent for each employee.
 * Uses INSERT ... ON DUPLICATE KEY UPDATE so the same day can be edited safely.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo  = db();
$date = valid_date($_GET['date'] ?? $_POST['date'] ?? null, date('Y-m-d'));

// ---------- Save submitted attendance ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $allowed = ['Present', 'Late', 'Absent'];
    $saved   = 0;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO attendance (employee_id, attendance_date, status)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        foreach (($_POST['status'] ?? []) as $employeeId => $status) {
            if (!in_array($status, $allowed, true)) {
                continue;                       // ignore anything unexpected
            }
            $stmt->execute([(int)$employeeId, $date, $status]);
            $saved++;
        }
        $pdo->commit();
        flash('success', "Saved attendance for $saved employee(s) on $date.");
    } catch (Throwable $ex) {
        $pdo->rollBack();
        flash('error', 'Could not save attendance. Please try again.');
    }

    header('Location: mark.php?date=' . urlencode($date));
    exit;
}

// ---------- Load employees with their status for this date ----------
$stmt = $pdo->prepare("
    SELECT e.employee_id, e.first_name, e.last_name,
           d.department_name, a.status
    FROM employees e
    LEFT JOIN departments d ON d.department_id = e.department_id
    LEFT JOIN attendance  a ON a.employee_id = e.employee_id
                           AND a.attendance_date = ?
    ORDER BY d.department_name, e.first_name
");
$stmt->execute([$date]);
$rows = $stmt->fetchAll();

render_header('Mark Attendance', 'mark');
?>
<div class="card">
    <form method="get" class="form-row">
        <div>
            <label for="date">Attendance date</label>
            <input type="date" id="date" name="date" value="<?= e($date) ?>">
        </div>
        <div style="flex:0">
            <button class="btn btn-ghost" type="submit">Load date</button>
        </div>
    </form>
</div>

<form method="post" class="card">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="date" value="<?= e($date) ?>">

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Employee</th><th>Department</th><th>Status for <?= e($date) ?></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): $id = (int)$r['employee_id']; ?>
                <tr>
                    <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= e($r['department_name'] ?? '—') ?></td>
                    <td>
                        <div class="status-pick">
                            <input type="radio" id="p<?= $id ?>" name="status[<?= $id ?>]" value="Present"
                                   <?= $r['status'] === 'Present' ? 'checked' : '' ?>>
                            <label class="p" for="p<?= $id ?>">Present</label>

                            <input type="radio" id="l<?= $id ?>" name="status[<?= $id ?>]" value="Late"
                                   <?= $r['status'] === 'Late' ? 'checked' : '' ?>>
                            <label class="l" for="l<?= $id ?>">Late</label>

                            <input type="radio" id="a<?= $id ?>" name="status[<?= $id ?>]" value="Absent"
                                   <?= $r['status'] === 'Absent' ? 'checked' : '' ?>>
                            <label class="a" for="a<?= $id ?>">Absent</label>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top:18px"><button class="btn" type="submit">💾 Save attendance</button></p>
</form>
<?php render_footer(); ?>
