document.addEventListener("DOMContentLoaded", () => {
    const themeButtons = document.querySelectorAll(".theme-toggle");
    const savedTheme = localStorage.getItem("studenthub-theme");

    function setTheme(theme) {
        document.documentElement.dataset.theme = theme;
        localStorage.setItem("studenthub-theme", theme);

        themeButtons.forEach((button) => {
            const isDark = theme === "dark";
            button.textContent = isDark ? "Light mode" : "Dark mode";
            button.setAttribute("aria-pressed", String(isDark));
        });
    }

    setTheme(savedTheme === "dark" ? "dark" : "light");

    themeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const nextTheme =
                document.documentElement.dataset.theme === "dark" ? "light" : "dark";
            setTheme(nextTheme);
        });
    });

    document.querySelectorAll(".menu-toggle").forEach((button) => {
        const navigation = document.getElementById(button.getAttribute("aria-controls"));

        if (!navigation) {
            return;
        }

        button.addEventListener("click", () => {
            const isOpen = navigation.classList.toggle("is-open");
            button.setAttribute("aria-expanded", String(isOpen));
        });

        navigation.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                navigation.classList.remove("is-open");
                button.setAttribute("aria-expanded", "false");
            });
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                navigation.classList.remove("is-open");
                button.setAttribute("aria-expanded", "false");
            }
        });
    });

    const notice = document.getElementById("noticeBanner");
    const dismissNotice = notice?.querySelector(".notice-dismiss");

    dismissNotice?.addEventListener("click", () => {
        notice.hidden = true;
    });

    const slides = [...document.querySelectorAll(".highlight-slide")];
    let activeSlide = slides.findIndex((slide) => slide.classList.contains("is-active"));

    if (slides.length > 0) {
        document.querySelectorAll("[data-slide]").forEach((button) => {
            button.addEventListener("click", () => {
                slides[activeSlide].classList.remove("is-active");
                const step = button.dataset.slide === "next" ? 1 : -1;
                activeSlide = (activeSlide + step + slides.length) % slides.length;
                slides[activeSlide].classList.add("is-active");
            });
        });
    }

    const welcomeModal = document.getElementById("welcomeModal");
    const closeModal = welcomeModal?.querySelector(".modal-close");

    if (welcomeModal instanceof HTMLDialogElement && !sessionStorage.getItem("welcome-seen")) {
        window.setTimeout(() => {
            if (!welcomeModal.open) {
                welcomeModal.showModal();
            }
        }, 900);

        welcomeModal.addEventListener("close", () => {
            sessionStorage.setItem("welcome-seen", "true");
        });

        closeModal?.addEventListener("click", () => welcomeModal.close());
        welcomeModal.addEventListener("click", (event) => {
            if (event.target === welcomeModal) {
                welcomeModal.close();
            }
        });
    }
});
