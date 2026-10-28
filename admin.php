<?php
require_once __DIR__ . "/php/auth.php";
require_admin();

$stats = [
    "users" => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    "students" => (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    "events" => (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    "registrations" => (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn(),
];
$latest = $pdo->query(
    "SELECT u.full_name, e.title, r.registered_at
     FROM registrations r
     JOIN users u ON u.id = r.user_id
     JOIN events e ON e.id = r.event_id
     ORDER BY r.registered_at DESC LIMIT 10"
)->fetchAll();
$logs = $pdo->query(
    "SELECT a.action, a.entity, a.entity_id, a.created_at, u.full_name
     FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id
     ORDER BY a.created_at DESC LIMIT 10"
)->fetchAll();
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Admin Dashboard</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
<header class="site-header">
    <div class="header-container">
        <div class="logo-area"><h1>StudentHub</h1><p>Administrator</p></div>
        <nav class="main-nav" aria-label="Administrator menu">
            <ul>
                <li><a href="admin.php" aria-current="page">Dashboard</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="events_admin.php">Events</a></li>
                <li><a href="event.html">Public events</a></li>
                <li><a href="php/logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>
<main class="container">
    <h2 class="page-title">Administrator Dashboard</h2>
    <p class="page-intro">Welcome, <?= $escape($_SESSION["user_name"] ?? "Administrator") ?>. Manage StudentHub and review recent activity.</p>
    <section class="management-stats" aria-label="StudentHub statistics">
        <?php foreach (["users" => "Registered users", "students" => "Student records", "events" => "Events", "registrations" => "Registrations"] as $key => $label): ?>
            <article class="information-card"><h3><?= $escape($label) ?></h3><p class="stat-number"><?= $stats[$key] ?></p></article>
        <?php endforeach; ?>
    </section>
    <section class="management-panel">
        <h2>Latest event registrations</h2>
        <?php if (!$latest): ?><p>No registrations have been recorded.</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Student</th><th>Event</th><th>Registered</th></tr></thead><tbody>
        <?php foreach ($latest as $registration): ?><tr>
            <td><?= $escape($registration["full_name"]) ?></td>
            <td><?= $escape($registration["title"]) ?></td>
            <td><?= $escape($registration["registered_at"]) ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <section class="management-panel">
        <h2>Recent audit activity</h2>
        <?php if (!$logs): ?><p>No administrative changes recorded.</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Actor</th><th>Action</th><th>Entity</th><th>Time</th></tr></thead><tbody>
        <?php foreach ($logs as $log): ?><tr>
            <td><?= $escape($log["full_name"] ?? "Deleted account") ?></td>
            <td><?= $escape($log["action"]) ?></td>
            <td><?= $escape($log["entity"] . " #" . $log["entity_id"]) ?></td>
            <td><?= $escape($log["created_at"]) ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
</main>
<script src="js/ui.js"></script>
</body>
</html>
