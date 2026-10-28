<?php
require_once __DIR__ . "/php/auth.php";
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Student Management</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
<header class="site-header"><div class="header-container">
    <div class="logo-area"><h1>StudentHub</h1><p>Administrator</p></div>
    <nav class="main-nav" aria-label="Administrator menu"><ul>
        <li><a href="admin.php">Dashboard</a></li><li><a href="students.php" aria-current="page">Students</a></li>
        <li><a href="events_admin.php">Events</a></li><li><a href="php/logout.php">Logout</a></li>
    </ul></nav>
</div></header>
<main class="container management-page">
    <h2 class="page-title">Student Management</h2>
    <p id="studentStatus" class="form-message" role="status">Loading students...</p>
    <section class="management-panel">
        <h2 id="studentFormTitle">Add student</h2>
        <form id="studentForm" class="management-form">
            <input type="hidden" name="id">
            <label>Student ID<input name="student_id" required maxlength="40"></label>
            <label>Full name<input name="full_name" required maxlength="120"></label>
            <label>Email<input type="email" name="email" required maxlength="190"></label>
            <label>Course<input name="course" required maxlength="120"></label>
            <label>Study year<select name="study_year" required><option value="">Select</option><option>1</option><option>2</option><option>3</option><option>4</option></select></label>
            <label>Semester<select name="semester" required><option value="">Select</option><?php for ($i = 1; $i <= 8; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></label>
            <div class="management-actions"><button class="submit-button" type="submit">Save student</button><button class="secondary-button" type="reset" id="cancelStudentEdit">Clear</button></div>
        </form>
    </section>
    <section class="management-panel">
        <h2>Student records</h2>
        <div class="management-filters">
            <label>Search<input type="search" id="studentSearch" placeholder="Name, ID, or email"></label>
            <label>Course filter<select id="courseFilter"><option value="">All courses</option></select></label>
        </div>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Semester</th><th>Actions</th></tr></thead><tbody id="studentRows"></tbody></table></div>
    </section>
</main>
<script src="js/ui.js"></script><script src="js/management.js"></script>
</body>
</html>
