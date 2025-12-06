document.addEventListener("DOMContentLoaded", function () {
  // ========== HAMBURGER MENU ==========
  const hamburger = document.querySelector(".hamburger");
  const navMenu = document.querySelector(".nav-menu");
  const navLinks = document.querySelectorAll(".nav-link");

  // Create overlay element
  const menuOverlay = document.createElement("div");
  menuOverlay.className = "menu-overlay";
  document.body.appendChild(menuOverlay);

  if (hamburger && navMenu) {
    // Toggle menu
    hamburger.addEventListener("click", function (e) {
      e.stopPropagation();
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
      menuOverlay.classList.toggle("active");

      // Prevent body scroll when menu is open
      if (navMenu.classList.contains("active")) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "";
      }
    });

    // Close menu when clicking nav links
    navLinks.forEach((link) => {
      link.addEventListener("click", () => {
        hamburger.classList.remove("active");
        navMenu.classList.remove("active");
        menuOverlay.classList.remove("active");
        document.body.style.overflow = "";
      });
    });

    // Close menu when clicking overlay
    menuOverlay.addEventListener("click", () => {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
      menuOverlay.classList.remove("active");
      document.body.style.overflow = "";
    });
  }

  // ========== SMOOTH SCROLL ANIMATION ==========
  const cards = document.querySelectorAll(".team-card");

  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }, index * 100);
      }
    });
  }, observerOptions);

  cards.forEach((card) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(card);
  });
});
