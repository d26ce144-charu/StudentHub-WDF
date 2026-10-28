<?php
require_once __DIR__ . "/php/auth.php";
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Event Management</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
<header class="site-header"><div class="header-container">
    <div class="logo-area"><h1>StudentHub</h1><p>Administrator</p></div>
    <nav class="main-nav" aria-label="Administrator menu"><ul>
        <li><a href="admin.php">Dashboard</a></li><li><a href="students.php">Students</a></li>
        <li><a href="events_admin.php" aria-current="page">Events</a></li><li><a href="php/logout.php">Logout</a></li>
    </ul></nav>
</div></header>
<main class="container management-page">
    <h2 class="page-title">Event Management</h2>
    <p id="eventAdminStatus" class="form-message" role="status">Ready to manage events.</p>
    <section class="management-panel">
        <h2 id="eventFormTitle">Add event</h2>
        <form id="eventForm" class="management-form" enctype="multipart/form-data">
            <input type="hidden" name="id">
            <label>Title<input name="title" required maxlength="200"></label>
            <label>Description<textarea name="description" required maxlength="5000"></textarea></label>
            <label>Date<input type="date" name="date" required></label>
            <label>Time<input name="time" required maxlength="30" placeholder="10:00 AM"></label>
            <label>Venue<input name="venue" required maxlength="160"></label>
            <label>Category<input name="category" required maxlength="80"></label>
            <label>Poster (JPEG, PNG, WebP; up to 2 MB)<input type="file" name="poster" accept="image/jpeg,image/png,image/webp"></label>
            <div class="management-actions"><button class="submit-button" type="submit">Save event</button><button class="secondary-button" type="reset" id="cancelEventEdit">Clear</button></div>
        </form>
    </section>
    <section class="management-panel">
        <h2>Events</h2>
        <div class="table-wrap"><table><thead><tr><th>Title</th><th>Date</th><th>Category</th><th>Venue</th><th>Poster</th><th>Actions</th></tr></thead><tbody id="eventRows"></tbody></table></div>
    </section>
</main>
<script src="js/ui.js"></script><script src="js/management.js"></script>
</body>
</html>
