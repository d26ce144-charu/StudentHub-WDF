<?php
require_once __DIR__ . "/php/auth.php";
require_login();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>StudentHub | Profile</title>

    <link rel="stylesheet" href="style.css?v=3">

</head>


<body>

<header class="site-header">

    <div class="header-container">

        <div class="logo-area">

            <h1>StudentHub</h1>

            <p>Student Portal</p>

        </div>


        <div class="header-actions">
            <button class="menu-toggle" type="button" aria-controls="site-navigation" aria-expanded="false">
                <span class="menu-icon" aria-hidden="true">☰</span>
                <span class="visually-hidden">Toggle navigation</span>
            </button>
            <button class="theme-toggle" type="button" aria-pressed="false">Dark mode</button>
        </div>

        <nav class="main-nav" id="site-navigation" aria-label="Main navigation">

            <ul>

                <li>
                    <a href="index.html">
                        Home
                    </a>
                </li>

                <li>
                    <a href="about.html">About</a>
                </li>

                <li>
                    <a href="profile.php" aria-current="page">
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="event.html">
                        Events
                    </a>
                </li>

                <li>
                    <a href="feedback.html">
                        Feedback
                    </a>
                </li>

                <li>
                    <a href="contact.html">
                        Contact Us
                    </a>
                </li>

                <li>
                    <a href="php/logout.php">
                        Logout
                    </a>
                </li>

            </ul>

        </nav>

    </div>

</header>


<main class="container">

    <div class="page-heading">

        <h2>
            Student Dashboard
        </h2>

        <p>
            View and update your student information.
        </p>

    </div>


    <section
        class="profile-top"
        aria-label="Student profile information"
    >

        <article class="profile-card">

            <div
                class="profile-placeholder"
                aria-label="Student profile placeholder"
                id="profileInitials"
            >
                SN
            </div>


            <h3 id="displayName">
                Loading...
            </h3>


            <p id="displayStudentId">
                Loading...
            </p>

        </article>


        <article class="information-card">

            <h3>
                Profile Information
            </h3>


            <div class="info-grid">

                <div class="info-label">
                    Full Name
                </div>

                <div
                    class="info-value"
                    id="infoName"
                >
                    Loading...
                </div>


                <div class="info-label">
                    Student ID
                </div>

                <div
                    class="info-value"
                    id="infoStudentId"
                >
                    Loading...
                </div>


                <div class="info-label">
                    Email
                </div>

                <div
                    class="info-value"
                    id="infoEmail"
                >
                    Loading...
                </div>


                <div class="info-label">
                    Course
                </div>

                <div
                    class="info-value"
                    id="infoCourse"
                >
                    Loading...
                </div>


                <div class="info-label">
                    Semester
                </div>

                <div
                    class="info-value"
                    id="infoSemester"
                >
                    Loading...
                </div>

            </div>

        </article>

    </section>


    <section class="update-card">

        <h3>
            Update Profile
        </h3>


        <form
            id="profileForm"
            action="php/update_profile.php"
            method="POST"
        >
            <input type="hidden" name="csrf_token" id="profileCsrfToken">

            <div class="form-group">

                <label for="fullName">
                    Full Name
                </label>

                <input
                    type="text"
                    id="fullName"
                    name="fullName"
                    required
                >

            </div>


            <div class="form-group">

                <label for="profileEmail">
                    Email Address
                </label>

                <input
                    type="email"
                    id="profileEmail"
                    name="email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="profileCourse">
                    Course
                </label>

                <input
                    type="text"
                    id="profileCourse"
                    name="course"
                    required
                >

            </div>


            <div class="form-group">

                <label for="profileSemester">
                    Semester
                </label>

                <select
                    id="profileSemester"
                    name="semester"
                    required
                >

                    <option value="1">
                        Semester 1
                    </option>

                    <option value="2">
                        Semester 2
                    </option>

                    <option value="3">
                        Semester 3
                    </option>

                    <option value="4">
                        Semester 4
                    </option>

                    <option value="5">
                        Semester 5
                    </option>

                    <option value="6">
                        Semester 6
                    </option>

                    <option value="7">
                        Semester 7
                    </option>

                    <option value="8">
                        Semester 8
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="update-btn"
            >
                Update Profile
            </button>


            <div
                id="successMessage"
                role="status"
            >
                Profile updated successfully!
            </div>


            <p
                id="profileMessage"
                class="form-message"
                role="alert"
            ></p>

        </form>

    </section>

</main>


<footer class="site-footer">

    <div class="footer-container">

        <p>
            © 2026 StudentHub. All rights reserved.
        </p>


        <nav
            class="footer-links"
            aria-label="Footer navigation"
        >

            <a href="contact.html">
                Contact Us
            </a>

            <a href="feedback.html">
                Feedback
            </a>

        </nav>

    </div>

</footer>


<script src="js/script.js"></script>
<script src="js/ui.js"></script>

</body>

</html>