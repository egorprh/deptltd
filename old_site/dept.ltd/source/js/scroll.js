// Scroll

document.addEventListener("DOMContentLoaded", () => {
    // Effects

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.add("scroll-show"); }
            else { entry.target.classList.remove("scroll-show"); }
        });
    });

    const hiddenElements = document.querySelectorAll(".scroll-hidden");
    hiddenElements.forEach((element) => observer.observe(element));

    // Links

    document.querySelectorAll("a[href='/']").forEach(element => {
        element.addEventListener("click", event => {
            if (window.location.pathname == "/" && window.scrollY != 0) {
                event.preventDefault();
                window.scrollTo({ top: 0, behavior: "smooth" });
            }
        });
    });

    document.querySelectorAll("a[href^='#']").forEach(element => {
        element.addEventListener("click", event => {
            event.preventDefault();
            const target = document.querySelector(`[id='${element.getAttribute("href").replace("#", "")}']`);
            if (target) target.scrollIntoView({ behavior: "smooth" });
        });
    });
});