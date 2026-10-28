document.addEventListener("DOMContentLoaded", async () => {
    const eventList = document.getElementById("eventList");
    const eventStatus = document.getElementById("eventStatus");
    const searchInput = document.getElementById("eventSearch");
    const categorySelect = document.getElementById("eventCategory");
    const sortSelect = document.getElementById("eventSort");
    const pagination = document.getElementById("eventPagination");

    if (!eventList || !eventStatus || !searchInput || !categorySelect || !sortSelect || !pagination) {
        return;
    }

    const pageSize = 3;
    let events = [];
    let currentPage = 1;

    function createMeta(label, value) {
        const paragraph = document.createElement("p");
        const strong = document.createElement("strong");
        strong.textContent = `${label}: `;
        paragraph.append(strong, document.createTextNode(value));
        return paragraph;
    }

    function getVisibleEvents() {
        const searchTerm = searchInput.value.trim().toLocaleLowerCase();
        const selectedCategory = categorySelect.value;
        const sortedEvents = events
            .filter((item) => selectedCategory === "all" || item.category === selectedCategory)
            .filter((item) =>
                [item.title, item.description, item.venue, item.category]
                    .some((value) => value.toLocaleLowerCase().includes(searchTerm))
            );

        sortedEvents.sort((first, second) => {
            switch (sortSelect.value) {
                case "date-desc":
                    return second.date.localeCompare(first.date);
                case "title-asc":
                    return first.title.localeCompare(second.title);
                case "title-desc":
                    return second.title.localeCompare(first.title);
                default:
                    return first.date.localeCompare(second.date);
            }
        });

        return sortedEvents;
    }

    function render() {
        const visibleEvents = getVisibleEvents();
        const pageCount = Math.ceil(visibleEvents.length / pageSize);
        currentPage = Math.min(currentPage, Math.max(pageCount, 1));
        const pageEvents = visibleEvents.slice((currentPage - 1) * pageSize, currentPage * pageSize);

        eventList.replaceChildren();
        pagination.replaceChildren();

        if (visibleEvents.length === 0) {
            eventStatus.textContent = "No events match your search. Try another keyword or category.";
            return;
        }

        eventStatus.textContent = `Showing ${pageEvents.length} of ${visibleEvents.length} events.`;

        pageEvents.forEach((item) => {
            const article = document.createElement("article");
            article.className = "event-card";

            const title = document.createElement("h3");
            title.textContent = item.title;

            const description = document.createElement("p");
            description.textContent = item.description;

            const category = document.createElement("span");
            category.className = "event-category";
            category.textContent = item.category;

            const meta = document.createElement("div");
            meta.className = "event-meta";
            meta.append(
                createMeta("Date", new Intl.DateTimeFormat(undefined, {
                    day: "numeric",
                    month: "long",
                    year: "numeric"
                }).format(new Date(`${item.date}T00:00:00`))),
                createMeta("Time", item.time),
                createMeta("Venue", item.venue)
            );

            article.append(title, category, description, meta);
            if (item.poster_path) {
                const poster = document.createElement("img");
                poster.src = item.poster_path;
                poster.alt = `${item.title} poster`;
                poster.loading = "lazy";
                article.prepend(poster);
            }
            const registerButton = document.createElement("button");
            registerButton.type = "button";
            registerButton.className = "secondary-button";
            registerButton.textContent = "Register for event";
            registerButton.addEventListener("click", async () => {
                registerButton.disabled = true;
                try {
                    const tokenResponse = await fetch("php/csrf.php");
                    const tokenResult = await tokenResponse.json();
                    if (!tokenResponse.ok || !tokenResult.token) {
                        throw new Error(tokenResult.message || "Log in before registering for an event.");
                    }
                    const response = await fetch("php/event_registration.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json", "X-CSRF-Token": tokenResult.token },
                        body: JSON.stringify({ event_id: item.id }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || "Registration failed.");
                    eventStatus.textContent = result.message;
                    eventStatus.classList.remove("error");
                } catch (error) {
                    eventStatus.textContent = error.message;
                    eventStatus.classList.add("error");
                    registerButton.disabled = false;
                }
            });
            article.append(registerButton);
            eventList.append(article);
        });

        for (let page = 1; page <= pageCount; page += 1) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "pagination-button";
            button.textContent = String(page);
            button.setAttribute("aria-label", `Page ${page}`);
            button.setAttribute("aria-current", String(page === currentPage));
            button.disabled = page === currentPage;
            button.addEventListener("click", () => {
                currentPage = page;
                render();
            });
            pagination.append(button);
        }
    }

    try {
        const response = await fetch("php/events_api.php");

        if (!response.ok) {
            throw new Error(`Event data request failed with status ${response.status}.`);
        }

        const data = await response.json();

        if (!data.success || !Array.isArray(data.events)) {
            throw new Error(data.message || "Event data must be a JSON array.");
        }

        events = data.events.filter((item) =>
            item &&
            typeof item.id !== "undefined" &&
            typeof item.title === "string" &&
            typeof item.description === "string" &&
            typeof item.date === "string" &&
            typeof item.time === "string" &&
            typeof item.venue === "string" &&
            typeof item.category === "string"
        );

        [...new Set(events.map((item) => item.category))].sort().forEach((category) => {
            const option = document.createElement("option");
            option.value = category;
            option.textContent = category;
            categorySelect.append(option);
        });

        if (events.length === 0) {
            throw new Error("The event data file contains no valid event records.");
        }

        searchInput.addEventListener("input", () => {
            currentPage = 1;
            render();
        });
        categorySelect.addEventListener("change", () => {
            currentPage = 1;
            render();
        });
        sortSelect.addEventListener("change", () => {
            currentPage = 1;
            render();
        });

        render();
    } catch (error) {
        console.error(error);
        eventStatus.textContent = "Events could not be loaded. Please try again later.";
        eventStatus.classList.add("error");
    }
});
