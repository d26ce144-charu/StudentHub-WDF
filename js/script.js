document.addEventListener("DOMContentLoaded", () => {

    /*
    ============================================================
    GLOBAL HELPERS
    ============================================================
    */

    function showMessage(element, message, type = "error") {

        if (!element) {
            return;
        }

        element.textContent = message;

        element.className = `form-message ${type}`;

        element.style.display = "block";
    }


    function hideMessage(element) {

        if (!element) {
            return;
        }

        element.textContent = "";

        element.style.display = "none";
    }

    function validateFields(form, messageElement, fields) {

        for (const fieldRule of fields) {

            const field =
                form.elements.namedItem(fieldRule.name);

            if (
                field &&
                !fieldRule.pattern.test(field.value)
            ) {

                showMessage(
                    messageElement,
                    fieldRule.message,
                    "error"
                );

                field.focus();

                return false;
            }

        }

        return true;
    }


    const namePattern =
        /^[\p{L}][\p{L}\s'.-]*$/u;

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    const studentIdPattern =
        /^[A-Za-z0-9-]+$/;

    const coursePattern =
        /^[\p{L}\p{N}][\p{L}\p{N}\s&'().-]*$/u;

    const nonEmptyTextPattern =
        /\S/;


    /*
    ============================================================
    LOGIN
    ============================================================
    */

    const loginForm =
        document.getElementById("loginForm");

    if (loginForm) {

        const loginMessage =
            document.getElementById("loginMessage");


        loginForm.addEventListener(
            "submit",
            async (event) => {

                event.preventDefault();

                hideMessage(loginMessage);

                if (!validateFields(loginForm, loginMessage, [
                    {
                        name: "email",
                        pattern: emailPattern,
                        message: "Enter a valid email address."
                    }
                ])) {
                    return;
                }


                const formData =
                    new FormData(loginForm);


                try {

                    const response =
                        await fetch(
                            "php/login.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        showMessage(
                            loginMessage,
                            result.message,
                            "error"
                        );

                        return;
                    }


                    window.location.href = result.redirect || "profile.php";


                } catch (error) {

                    console.error(error);

                    showMessage(
                        loginMessage,
                        "Unable to connect to the server.",
                        "error"
                    );

                }

            }
        );

    }


    /*
    ============================================================
    REGISTER
    ============================================================
    */

    const registerForm =
        document.getElementById("registerForm");


    if (registerForm) {

        const registerMessage =
            document.getElementById("registerMessage");


        registerForm.addEventListener(
            "submit",
            async (event) => {

                event.preventDefault();

                hideMessage(registerMessage);

                if (!validateFields(registerForm, registerMessage, [
                    {
                        name: "fullname",
                        pattern: namePattern,
                        message: "Name must contain only letters, spaces, apostrophes, periods, or hyphens."
                    },
                    {
                        name: "studentid",
                        pattern: studentIdPattern,
                        message: "Student ID may contain only letters, numbers, or hyphens."
                    },
                    {
                        name: "email",
                        pattern: emailPattern,
                        message: "Enter a valid email address."
                    },
                    {
                        name: "mobile",
                        pattern: /^[6-9]\d{9}$/,
                        message: "Enter a valid 10-digit mobile number."
                    },
                    {
                        name: "course",
                        pattern: coursePattern,
                        message: "Enter a valid course name."
                    },
                    {
                        name: "year",
                        pattern: /^[1-4]$/,
                        message: "Select your year of study."
                    },
                    {
                        name: "semester",
                        pattern: /^[1-6]$/,
                        message: "Select a valid semester."
                    },
                    {
                        name: "password",
                        pattern: /^(?=.*[A-Za-z])(?=.*\d).{8,}$/,
                        message: "Password must be at least 8 characters and include a letter and a number."
                    }
                ])) {
                    return;
                }

                if (!registerForm.querySelector('input[name="gender"]:checked')) {
                    showMessage(registerMessage, "Select a gender option to continue.", "error");
                    registerForm.querySelector('input[name="gender"]').focus();
                    return;
                }

                if (!document.getElementById("terms").checked) {
                    showMessage(registerMessage, "Please accept the terms and conditions.", "error");
                    document.getElementById("terms").focus();
                    return;
                }

                const password =
                    document.getElementById(
                        "register-password"
                    ).value;


                const confirmPassword =
                    document.getElementById(
                        "confirm-password"
                    ).value;


                if (password !== confirmPassword) {

                    showMessage(
                        registerMessage,
                        "Passwords do not match.",
                        "error"
                    );

                    return;
                }


                if (!/^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(password)) {

                    showMessage(
                        registerMessage,
                        "Password must be at least 8 characters and include a letter and a number.",
                        "error"
                    );

                    return;
                }


                const formData =
                    new FormData(registerForm);


                try {

                    const response =
                        await fetch(
                            "php/register.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        showMessage(
                            registerMessage,
                            result.message,
                            "error"
                        );

                        return;
                    }


                    showMessage(
                        registerMessage,
                        "Account created successfully. Redirecting to login...",
                        "success"
                    );


                    registerForm.reset();


                    setTimeout(() => {

                        window.location.href =
                            "login.html";

                    }, 1200);


                } catch (error) {

                    console.error(error);

                    showMessage(
                        registerMessage,
                        "Unable to connect to the server.",
                        "error"
                    );

                }

            }
        );

    }


    /*
    ============================================================
    PROFILE
    ============================================================
    */

    const profileForm =
        document.getElementById("profileForm");


    if (profileForm) {

        const fullName =
            document.getElementById("fullName");

        const email =
            document.getElementById("profileEmail");

        const course =
            document.getElementById("profileCourse");

        const semester =
            document.getElementById("profileSemester");


        const displayName =
            document.getElementById("displayName");

        const displayStudentId =
            document.getElementById(
                "displayStudentId"
            );


        const profileInitials =
            document.getElementById(
                "profileInitials"
            );


        const infoName =
            document.getElementById("infoName");

        const infoStudentId =
            document.getElementById(
                "infoStudentId"
            );

        const infoEmail =
            document.getElementById("infoEmail");

        const infoCourse =
            document.getElementById("infoCourse");

        const infoSemester =
            document.getElementById(
                "infoSemester"
            );


        const successMessage =
            document.getElementById(
                "successMessage"
            );


        const profileMessage =
            document.getElementById(
                "profileMessage"
            );


        /*
        Load profile
        */

        async function loadProfile() {

            try {

                const response =
                    await fetch(
                        "php/get_profile.php"
                    );


                const result =
                    await response.json();


                if (!result.success) {

                    window.location.href =
                        "login.html";

                    return;
                }


                const user =
                    result.user;

                document.getElementById("profileCsrfToken").value =
                    result.csrf_token;

                fullName.value =
                    user.full_name;

                email.value =
                    user.email;

                course.value =
                    user.course;

                semester.value =
                    user.semester;


                displayName.textContent =
                    user.full_name;

                displayStudentId.textContent =
                    user.student_id;


                infoName.textContent =
                    user.full_name;

                infoStudentId.textContent =
                    user.student_id;

                infoEmail.textContent =
                    user.email;

                infoCourse.textContent =
                    user.course;

                infoSemester.textContent =
                    `Semester ${user.semester}`;


                /*
                Generate initials
                */

                const names =
                    user.full_name
                        .trim()
                        .split(/\s+/);


                let initials = "";


                if (names.length >= 2) {

                    initials =
                        names[0][0] +
                        names[names.length - 1][0];

                } else {

                    initials =
                        names[0]?.substring(0, 2) || "ST";

                }


                profileInitials.textContent =
                    initials.toUpperCase();


            } catch (error) {

                console.error(error);

                showMessage(
                    profileMessage,
                    "Unable to load profile.",
                    "error"
                );

            }

        }


        /*
        Update profile
        */

        profileForm.addEventListener(
            "submit",
            async (event) => {

                event.preventDefault();

                hideMessage(profileMessage);

                if (!validateFields(profileForm, profileMessage, [
                    {
                        name: "fullName",
                        pattern: namePattern,
                        message: "Name must contain only letters, spaces, apostrophes, periods, or hyphens."
                    },
                    {
                        name: "email",
                        pattern: emailPattern,
                        message: "Enter a valid email address."
                    },
                    {
                        name: "course",
                        pattern: coursePattern,
                        message: "Enter a valid course name."
                    }
                ])) {
                    return;
                }


                const formData =
                    new FormData(profileForm);


                try {

                    const response =
                        await fetch(
                            "php/update_profile.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        showMessage(
                            profileMessage,
                            result.message,
                            "error"
                        );

                        return;
                    }


                    /*
                    Update visible information
                    */

                    displayName.textContent =
                        fullName.value;

                    infoName.textContent =
                        fullName.value;

                    infoEmail.textContent =
                        email.value;

                    infoCourse.textContent =
                        course.value;

                    infoSemester.textContent =
                        `Semester ${semester.value}`;


                    /*
                    Show success
                    */

                    successMessage.textContent =
                        result.message;

                    successMessage.style.display =
                        "block";


                    setTimeout(() => {

                        successMessage.style.display =
                            "none";

                    }, 3000);


                } catch (error) {

                    console.error(error);

                    showMessage(
                        profileMessage,
                        "Unable to update profile.",
                        "error"
                    );

                }

            }
        );


        loadProfile();

    }


    /*
    ============================================================
    FEEDBACK
    ============================================================
    */

    const feedbackForm =
        document.getElementById(
            "feedbackForm"
        );


    if (feedbackForm) {

        const feedbackMessage =
            document.getElementById(
                "feedbackMessage"
            );


        feedbackForm.addEventListener(
            "submit",
            async (event) => {

                event.preventDefault();

                hideMessage(feedbackMessage);
                hideMessage(feedbackMessage);
                if (!validateFields(feedbackForm, feedbackMessage, [
                    {
                        name: "name",
                        pattern: namePattern,
                        message: "Name must contain only letters, spaces, apostrophes, periods, or hyphens."
                    },
                    {
                        name: "feedback",
                        pattern: nonEmptyTextPattern,
                        message: "Feedback cannot contain only spaces."
                    }
                ])) {
                    return;
                }

                const formData =
                    new FormData(feedbackForm);


                try {

                    const response =
                        await fetch(
                            "php/feedback.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        showMessage(
                            feedbackMessage,
                            result.message,
                            "error"
                        );

                        return;
                    }


                    showMessage(
                        feedbackMessage,
                        result.message,
                        "success"
                    );


                    feedbackForm.reset();


                } catch (error) {

                    console.error(error);

                    showMessage(
                        feedbackMessage,
                        "Unable to submit feedback.",
                        "error"
                    );

                }

            }
        );

    }


    /*
    ============================================================
    CONTACT
    ============================================================
    */

    const contactForm =
        document.getElementById(
            "contactForm"
        );


    if (contactForm) {

        const contactMessage =
            document.getElementById(
                "contactMessage"
            );


        contactForm.addEventListener(
            "submit",
            async (event) => {

                event.preventDefault();

                hideMessage(contactMessage);

                if (!validateFields(contactForm, contactMessage, [
                    {
                        name: "name",
                        pattern: namePattern,
                        message: "Name must contain only letters, spaces, apostrophes, periods, or hyphens."
                    },
                    {
                        name: "email",
                        pattern: emailPattern,
                        message: "Enter a valid email address."
                    },
                    {
                        name: "subject",
                        pattern: nonEmptyTextPattern,
                        message: "Subject cannot contain only spaces."
                    },
                    {
                        name: "message",
                        pattern: nonEmptyTextPattern,
                        message: "Message cannot contain only spaces."
                    }
                ])) {
                    return;
                }


                const formData =
                    new FormData(contactForm);


                try {

                    const response =
                        await fetch(
                            "php/contact.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        showMessage(
                            contactMessage,
                            result.message,
                            "error"
                        );

                        return;
                    }


                    showMessage(
                        contactMessage,
                        result.message,
                        "success"
                    );


                    contactForm.reset();


                } catch (error) {

                    console.error(error);

                    showMessage(
                        contactMessage,
                        "Unable to send your enquiry.",
                        "error"
                    );

                }

            }
        );

    }


    /*
    ============================================================
    PASSWORD CONFIRMATION
    ============================================================
    */

    const registerPassword =
        document.getElementById(
            "register-password"
        );

    const confirmPassword =
        document.getElementById(
            "confirm-password"
        );


    if (
        registerPassword &&
        confirmPassword
    ) {

        confirmPassword.addEventListener(
            "input",
            () => {

                if (
                    confirmPassword.value &&
                    registerPassword.value !==
                    confirmPassword.value
                ) {

                    confirmPassword.setCustomValidity(
                        "Passwords do not match."
                    );

                } else {

                    confirmPassword.setCustomValidity(
                        ""
                    );

                }

            }
        );

    }

});



/* =========================================================
   VISUAL POLISH: auto-style success/error messages
   (Does NOT change any form logic or submissions.)
   ========================================================= */
document.addEventListener("DOMContentLoaded", function () {
    const messageIds = [
        "contactMessage",
        "feedbackMessage",
        "profileMessage",
        "loginMessage",
        "registerMessage"
    ];

    messageIds.forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;

        const observer = new MutationObserver(function () {
            const text = (el.textContent || "").toLowerCase();

            el.classList.remove("success", "error");

            if (!text.trim()) return;

            const isError =
                text.includes("error") ||
                text.includes("invalid") ||
                text.includes("fail") ||
                text.includes("incorrect") ||
                text.includes("required") ||
                text.includes("mismatch") ||
                text.includes("wrong");

            el.classList.add(isError ? "error" : "success");
        });

        observer.observe(el, {
            childList: true,
            characterData: true,
            subtree: true
        });
    });
}); 