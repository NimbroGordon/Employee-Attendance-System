<?php
/**
 * employees.php – Screen 4: Add / edit / delete employees
 * Full CRUD on the `employees` table.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = db();

// ---------- Handle POST actions (save or delete) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $id    = (int)($_POST['employee_id'] ?? 0);
            $first = trim($_POST['first_name'] ?? '');
            $last  = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $dept  = (int)($_POST['department_id'] ?? 0);

            if ($first === '' || $last === '') {
                throw new RuntimeException('First and last name are required.');
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email address.');
            }

            $emailValue = $email === '' ? null : $email;
            $deptValue  = $dept > 0 ? $dept : null;

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE employees
                                       SET first_name = ?, last_name = ?, email = ?, department_id = ?
                                       WHERE employee_id = ?');
                $stmt->execute([$first, $last, $emailValue, $deptValue, $id]);
                flash('success', 'Employee updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO employees (first_name, last_name, email, department_id)
                                       VALUES (?, ?, ?, ?)');
                $stmt->execute([$first, $last, $emailValue, $deptValue]);
                flash('success', 'Employee added.');
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['employee_id'] ?? 0);
            // attendance rows are removed automatically (ON DELETE CASCADE)
            $pdo->prepare('DELETE FROM employees WHERE employee_id = ?')->execute([$id]);
            flash('success', 'Employee deleted along with their attendance records.');
        }
    } catch (PDOException $ex) {
        // 23000 = integrity constraint violation (e.g. duplicate email)
        $msg = $ex->getCode() === '23000' ? 'That email address is already used by another employee.'
                                           : 'A database error occurred.';
        flash('error', $msg);
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
    }

    header('Location: employees.php');
    exit;
}

// ---------- Data for the page ----------
$editId  = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE employee_id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();

$employees = $pdo->query("
    SELECT e.employee_id, e.first_name, e.last_name, e.email,
           d.department_name, e.department_id
    FROM employees e
    LEFT JOIN departments d ON d.department_id = e.department_id
    ORDER BY e.first_name, e.last_name
")->fetchAll();

render_header('Employees', 'employees');
?>
<div class="grid grid-2">
    <!-- Add / Edit form -->
    <div class="card">
        <h2><?= $editing ? '✏️ Edit employee' : '➕ Add new employee' ?></h2>
        <form method="post" action="employees.php">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="employee_id" value="<?= (int)($editing['employee_id'] ?? 0) ?>">

            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" required
                   value="<?= e($editing['first_name'] ?? '') ?>">

            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" required
                   value="<?= e($editing['last_name'] ?? '') ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= e($editing['email'] ?? '') ?>">

            <label for="department_id">Department</label>
            <select id="department_id" name="department_id">
                <option value="0">— None —</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['department_id'] ?>"
                        <?= ((int)($editing['department_id'] ?? 0) === (int)$d['department_id']) ? 'selected' : '' ?>>
                        <?= e($d['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="btn" type="submit"><?= $editing ? 'Update employee' : 'Add employee' ?></button>
            <?php if ($editing): ?>
                <a class="btn btn-ghost" href="employees.php">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary card -->
    <div class="card">
        <h2>Overview</h2>
        <p>Total employees: <strong><?= count($employees) ?></strong></p>
        <p style="color:var(--muted)">
            Use the form to add a new employee, or press <em>Edit</em> in the table to change
            details. Deleting an employee also deletes their attendance history.
        </p>
    </div>
</div>

<!-- Employee table -->
<div class="card">
    <h2>All employees</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Name</th><th>Email</th><th>Department</th><th style="text-align:right">Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?= (int)$emp['employee_id'] ?></td>
                    <td><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                    <td><?= e($emp['email'] ?? '—') ?></td>
                    <td><?= e($emp['department_name'] ?? '—') ?></td>
                    <td style="text-align:right; white-space:nowrap">
                        <a class="btn btn-small btn-ghost" href="employees.php?edit=<?= (int)$emp['employee_id'] ?>">Edit</a>
                        <form method="post" action="employees.php" style="display:inline"
                              onsubmit="return confirm('Delete this employee and all their attendance records?');">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="employee_id" value="<?= (int)$emp['employee_id'] ?>">
                            <button class="btn btn-small btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_footer(); ?>
