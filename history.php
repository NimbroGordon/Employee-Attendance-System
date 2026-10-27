<?php
/**
 * history.php – Screen 5: Individual attendance history
 * Shows every record for one employee, optionally within a date range.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();

$employees = $pdo->query("
    SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name
    FROM employees ORDER BY first_name, last_name
")->fetchAll();

$empId = (int)($_GET['employee_id'] ?? 0);
$from  = valid_date($_GET['from'] ?? null, '1970-01-01');
$to    = valid_date($_GET['to'] ?? null, '9999-12-31');

$records = [];
$summary = null;
$employeeName = '';

if ($empId > 0) {
    $stmt = $pdo->prepare("
        SELECT attendance_date, status
        FROM attendance
        WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?
        ORDER BY attendance_date DESC
    ");
    $stmt->execute([$empId, $from, $to]);
    $records = $stmt->fetchAll();

    // Counts for the summary
    $counts = ['Present' => 0, 'Late' => 0, 'Absent' => 0];
    foreach ($records as $r) {
        $counts[$r['status']]++;
    }
    $summary = attendance_summary($counts['Present'], $counts['Late'], $counts['Absent']);

    foreach ($employees as $emp) {
        if ((int)$emp['employee_id'] === $empId) {
            $employeeName = $emp['name'];
        }
    }
}

render_header('Employee History', 'history');
?>
<div class="card">
    <form method="get" class="form-row">
        <div>
            <label for="employee_id">Employee</label>
            <select id="employee_id" name="employee_id" required>
                <option value="">— Choose an employee —</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= (int)$emp['employee_id'] ?>" <?= $empId === (int)$emp['employee_id'] ? 'selected' : '' ?>>
                        <?= e($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="from">From</label>
            <input type="date" id="from" name="from" value="<?= $from === '1970-01-01' ? '' : e($from) ?>">
        </div>
        <div>
            <label for="to">To</label>
            <input type="date" id="to" name="to" value="<?= $to === '9999-12-31' ? '' : e($to) ?>">
        </div>
        <div style="flex:0"><button class="btn" type="submit">Show history</button></div>
    </form>
</div>

<?php if ($empId > 0 && $summary): ?>
    <div class="grid grid-4" style="margin-bottom:22px">
        <div class="stat stat-purple"><div class="label">Records</div><div class="value"><?= $summary['total'] ?></div></div>
        <div class="stat stat-green"><div class="label">Attendance rate</div><div class="value"><?= $summary['attendance_rate'] ?>%</div></div>
        <div class="stat stat-cyan"><div class="label">Punctuality</div><div class="value"><?= $summary['punctuality'] ?>%</div></div>
        <div class="stat stat-red"><div class="label">Absenteeism</div><div class="value"><?= $summary['absenteeism'] ?>%</div></div>
    </div>

    <div class="card">
        <h2><?= e($employeeName) ?> – records</h2>
        <?php foreach ($summary['flags'] as $flag): ?>
            <span class="badge flag-<?= $summary['level'] === 'good' ? 'good' : ($summary['level'] === 'bad' ? 'bad' : 'warn') ?>"><?= e($flag) ?></span>
        <?php endforeach; ?>

        <div class="table-wrap" style="margin-top:14px">
            <table>
                <thead><tr><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="2" style="color:var(--muted)">No attendance records in this period.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?= e($r['attendance_date']) ?></td>
                        <td><span class="badge <?= status_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($empId === 0): ?>
    <div class="card"><p style="color:var(--muted)">Select an employee above to view their attendance history.</p></div>
<?php endif; ?>
<?php render_footer(); ?>
