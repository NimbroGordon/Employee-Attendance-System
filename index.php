<?php
/**
 * index.php – Screen 2: Main dashboard
 * Shows headline numbers for today and the most recent attendance records.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo   = db();
$today = date('Y-m-d');

// Headline counts
$employeeCount   = (int)$pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
$departmentCount = (int)$pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();

// Today's status counts
$stmt = $pdo->prepare("
    SELECT SUM(status = 'Present') AS p,
           SUM(status = 'Late')    AS l,
           SUM(status = 'Absent')  AS a
    FROM attendance
    WHERE attendance_date = ?
");
$stmt->execute([$today]);
$t = $stmt->fetch();
$present = (int)$t['p'];
$late    = (int)$t['l'];
$absent  = (int)$t['a'];
$summary = attendance_summary($present, $late, $absent);

// Most recent records
$recent = $pdo->query("
    SELECT a.attendance_date, a.status,
           CONCAT(e.first_name, ' ', e.last_name) AS name,
           d.department_name
    FROM attendance a
    JOIN employees e ON e.employee_id = a.employee_id
    LEFT JOIN departments d ON d.department_id = e.department_id
    ORDER BY a.attendance_date DESC, a.attendance_id DESC
    LIMIT 8
")->fetchAll();

render_header('Dashboard', 'dashboard');
?>
<p>Welcome back, <strong><?= e($_SESSION['user_name'] ?? 'User') ?></strong> · <?= date('l, d F Y') ?></p>

<div class="grid grid-4" style="margin-bottom:22px">
    <div class="stat stat-purple"><div class="label">Employees</div><div class="value"><?= $employeeCount ?></div></div>
    <div class="stat stat-cyan"><div class="label">Departments</div><div class="value"><?= $departmentCount ?></div></div>
    <div class="stat stat-green"><div class="label">Present today</div><div class="value"><?= $present ?></div></div>
    <div class="stat stat-red"><div class="label">Absent today</div><div class="value"><?= $absent ?></div></div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>Today's attendance rate</h2>
        <?php if ($summary['total'] === 0): ?>
            <p>Attendance has not been marked for today yet.</p>
            <a class="btn" href="mark.php">Mark attendance now</a>
        <?php else: ?>
            <p style="font-size:2.4rem;font-weight:800;margin:6px 0"><?= $summary['attendance_rate'] ?>%</p>
            <div class="bar"><span style="width:<?= $summary['attendance_rate'] ?>%"></span></div>
            <p style="color:var(--muted)">
                Punctuality: <?= $summary['punctuality'] ?>% ·
                Absenteeism: <?= $summary['absenteeism'] ?>%
            </p>
            <?php foreach ($summary['flags'] as $flag): ?>
                <span class="badge flag-<?= $summary['level'] === 'good' ? 'good' : ($summary['level'] === 'bad' ? 'bad' : 'warn') ?>"><?= e($flag) ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Quick actions</h2>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
            <a class="btn" href="mark.php">✅ Mark attendance</a>
            <a class="btn" href="history.php">📅 Employee history</a>
            <a class="btn" href="reports.php">📊 Department reports</a>
            <a class="btn btn-ghost" href="employees.php">👥 Manage employees</a>
        </div>
    </div>
</div>

<div class="card">
    <h2>Recent attendance records</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Employee</th><th>Department</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?= e($r['attendance_date']) ?></td>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e($r['department_name'] ?? '—') ?></td>
                    <td><span class="badge <?= status_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_footer(); ?>
