document.addEventListener("DOMContentLoaded", () => {
    const studentForm = document.getElementById("studentForm");
    const eventForm = document.getElementById("eventForm");
    const csrfRequest = async () => {
        const response = await fetch("php/csrf.php");
        const result = await response.json();
        if (!response.ok || !result.token) {
            throw new Error(result.message || "Unable to initialize the request token.");
        }
        return result.token;
    };

    if (studentForm) {
        const status = document.getElementById("studentStatus");
        const rows = document.getElementById("studentRows");
        const search = document.getElementById("studentSearch");
        const courseFilter = document.getElementById("courseFilter");
        const title = document.getElementById("studentFormTitle");
        let allStudents = [];

        const setStatus = (message, isError = false) => {
            status.textContent = message;
            status.classList.toggle("error", isError);
            status.classList.toggle("success", !isError);
        };
        const loadStudents = async () => {
            const query = new URLSearchParams({ search: search.value.trim(), course: courseFilter.value });
            const response = await fetch(`php/students_api.php?${query}`);
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || "Unable to load student records.");
            }
            allStudents = result.students;
            const courses = [...new Set(allStudents.map((student) => student.course))].sort();
            const selectedCourse = courseFilter.value;
            courseFilter.replaceChildren(new Option("All courses", ""));
            courses.forEach((course) => courseFilter.add(new Option(course, course)));
            courseFilter.value = selectedCourse;
            rows.replaceChildren();
            allStudents.forEach((student) => {
                const row = document.createElement("tr");
                [student.student_id, student.full_name, student.email, student.course, student.study_year, student.semester]
                    .forEach((value) => {
                        const cell = document.createElement("td");
                        cell.textContent = value;
                        row.append(cell);
                    });
                const actions = document.createElement("td");
                const edit = document.createElement("button");
                edit.type = "button";
                edit.className = "table-action";
                edit.textContent = "Edit";
                edit.addEventListener("click", () => {
                    Object.entries(student).forEach(([key, value]) => {
                        if (studentForm.elements.namedItem(key)) {
                            studentForm.elements.namedItem(key).value = value;
                        }
                    });
                    title.textContent = "Edit student";
                    studentForm.scrollIntoView({ behavior: "smooth", block: "start" });
                });
                const remove = document.createElement("button");
                remove.type = "button";
                remove.className = "table-action danger";
                remove.textContent = "Delete";
                remove.addEventListener("click", async () => {
                    if (!window.confirm(`Delete ${student.full_name}?`)) return;
                    try {
                        await submitStudent({ action: "delete", id: student.id });
                        setStatus("Student deleted successfully.");
                        await loadStudents();
                    } catch (error) {
                        setStatus(error.message, true);
                    }
                });
                actions.append(edit, remove);
                row.append(actions);
                rows.append(row);
            });
            if (!allStudents.length) setStatus("No student records match those filters.");
        };
        const submitStudent = async (data) => {
            const token = await csrfRequest();
            const response = await fetch("php/students_api.php", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-Token": token },
                body: JSON.stringify(data),
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || "Student operation failed.");
            return result;
        };

        studentForm.addEventListener("submit", async (event) => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(studentForm));
            const action = data.id ? "update" : "create";
            try {
                const result = await submitStudent({ ...data, action });
                setStatus(result.message);
                studentForm.reset();
                title.textContent = "Add student";
                await loadStudents();
            } catch (error) {
                setStatus(error.message, true);
            }
        });
        studentForm.addEventListener("reset", () => {
            window.setTimeout(() => { title.textContent = "Add student"; }, 0);
        });
        search.addEventListener("input", () => loadStudents().catch((error) => setStatus(error.message, true)));
        courseFilter.addEventListener("change", () => loadStudents().catch((error) => setStatus(error.message, true)));
        loadStudents().catch((error) => setStatus(error.message, true));
    }

    if (eventForm) {
        const status = document.getElementById("eventAdminStatus");
        const rows = document.getElementById("eventRows");
        const title = document.getElementById("eventFormTitle");
        const setStatus = (message, isError = false) => {
            status.textContent = message;
            status.classList.toggle("error", isError);
            status.classList.toggle("success", !isError);
        };
        const loadEvents = async () => {
            const response = await fetch("php/events_api.php");
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || "Unable to load events.");
            rows.replaceChildren();
            result.events.forEach((item) => {
                const row = document.createElement("tr");
                [item.title, item.date, item.category, item.venue, item.poster_path ? "Uploaded" : "None"]
                    .forEach((value) => {
                        const cell = document.createElement("td");
                        cell.textContent = value;
                        row.append(cell);
                    });
                const actions = document.createElement("td");
                const edit = document.createElement("button");
                edit.type = "button";
                edit.className = "table-action";
                edit.textContent = "Edit";
                edit.addEventListener("click", () => {
                    ["id", "title", "description", "date", "time", "venue", "category"].forEach((key) => {
                        eventForm.elements.namedItem(key).value = item[key] ?? "";
                    });
                    title.textContent = "Edit event";
                    eventForm.scrollIntoView({ behavior: "smooth", block: "start" });
                });
                const remove = document.createElement("button");
                remove.type = "button";
                remove.className = "table-action danger";
                remove.textContent = "Delete";
                remove.addEventListener("click", async () => {
                    if (!window.confirm(`Delete ${item.title}?`)) return;
                    try {
                        await saveEvent({ action: "delete", id: item.id });
                        setStatus("Event deleted successfully.");
                        await loadEvents();
                    } catch (error) {
                        setStatus(error.message, true);
                    }
                });
                actions.append(edit, remove);
                row.append(actions);
                rows.append(row);
            });
            if (!result.events.length) setStatus("No events have been added.");
        };
        const saveEvent = async (data) => {
            const token = await csrfRequest();
            const body = data instanceof FormData ? data : new FormData();
            if (!(data instanceof FormData)) {
                Object.entries(data).forEach(([key, value]) => body.append(key, value));
            }
            body.append("csrf_token", token);
            const response = await fetch("php/events_api.php", { method: "POST", body });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || "Event operation failed.");
            return result;
        };

        eventForm.addEventListener("submit", async (event) => {
            event.preventDefault();
            const body = new FormData(eventForm);
            body.set("action", body.get("id") ? "update" : "create");
            try {
                const result = await saveEvent(body);
                setStatus(result.message);
                eventForm.reset();
                title.textContent = "Add event";
                await loadEvents();
            } catch (error) {
                setStatus(error.message, true);
            }
        });
        eventForm.addEventListener("reset", () => {
            window.setTimeout(() => { title.textContent = "Add event"; }, 0);
        });
        loadEvents().catch((error) => setStatus(error.message, true));
    }
});
