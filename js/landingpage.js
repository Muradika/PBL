// ==================== SMOOTH SCROLLING ====================
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});

// ==================== NAVBAR SCROLL EFFECT ====================
let lastScroll = 0;
const navbar = document.querySelector(".navbar");

window.addEventListener("scroll", () => {
  const currentScroll = window.pageYOffset;

  // Add scrolled class when scrolled down
  if (currentScroll > 50) {
    navbar.classList.add("scrolled");
  } else {
    navbar.classList.remove("scrolled");
  }

  lastScroll = currentScroll;
});

// ==================== INTERSECTION OBSERVER FOR ANIMATIONS ====================
const observerOptions = {
  threshold: 0.1,
  rootMargin: "0px 0px -100px 0px",
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = "1";
      entry.target.style.transform = "translateY(0)";
    }
  });
}, observerOptions);

// Observe elements for animation
document.addEventListener("DOMContentLoaded", () => {
  const animateElements = document.querySelectorAll(
    ".stat-item, .feature-card"
  );

  animateElements.forEach((el) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(30px)";
    el.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(el);
  });
});

// ==================== COUNTER ANIMATION FOR STATS ====================
function animateCounter(element, target, duration = 2000) {
  const start = 0;
  const increment = target / (duration / 16); // 60 FPS
  let current = start;

  const timer = setInterval(() => {
    current += increment;
    if (current >= target) {
      element.textContent = target;
      clearInterval(timer);
    } else {
      element.textContent = Math.floor(current);
    }
  }, 16);
}

// Animate stat numbers when visible
const statObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (
        entry.isIntersecting &&
        !entry.target.classList.contains("animated")
      ) {
        const statNumber = entry.target.querySelector(".stat-number");
        const text = statNumber.textContent;

        // Extract number from text (e.g., "1000+" -> 1000)
        const number = parseInt(text.replace(/\D/g, ""));

        if (!isNaN(number)) {
          statNumber.textContent = "0";

          // Start counter animation
          let current = 0;
          const increment = number / 60; // 60 frames

          const counter = setInterval(() => {
            current += increment;
            if (current >= number) {
              statNumber.textContent = text; // Restore original text with suffix
              clearInterval(counter);
            } else {
              statNumber.textContent =
                Math.floor(current) + text.replace(/\d+/g, "");
            }
          }, 30);
        }

        entry.target.classList.add("animated");
      }
    });
  },
  { threshold: 0.5 }
);

// Observe stat items
document.querySelectorAll(".stat-item").forEach((item) => {
  statObserver.observe(item);
});

// ==================== PARALLAX EFFECT FOR BACKGROUND ====================
window.addEventListener("scroll", () => {
  const scrolled = window.pageYOffset;
  const parallax = document.querySelector(".bg-animation");

  if (parallax) {
    parallax.style.transform = `translateY(${scrolled * 0.3}px)`;
  }
});

// ==================== FEATURE CARD TILT EFFECT ====================
document.querySelectorAll(".feature-card").forEach((card) => {
  card.addEventListener("mousemove", (e) => {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    const rotateX = (y - centerY) / 10;
    const rotateY = (centerX - x) / 10;

    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
  });

  card.addEventListener("mouseleave", () => {
    card.style.transform =
      "perspective(1000px) rotateX(0) rotateY(0) translateY(0)";
  });
});

// ==================== LOADING ANIMATION ====================
window.addEventListener("load", () => {
  document.body.style.opacity = "0";
  setTimeout(() => {
    document.body.style.transition = "opacity 0.5s ease";
    document.body.style.opacity = "1";
  }, 100);
});

// ==================== CONSOLE MESSAGE ====================
console.log(
  "%c🚀 SIPAk - Sistem Informasi Pengumuman Akademik Online",
  "color: #ff6b35; font-size: 20px; font-weight: bold;"
);
console.log(
  "%cDeveloped for Politeknik Negeri Batam",
  "color: #2d5a8c; font-size: 14px;"
);
