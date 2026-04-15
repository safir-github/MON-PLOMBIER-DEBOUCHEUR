/* main.js — MonPlombierDeboucheur.fr — Scripts partagés */

/* ---- Chargement des partials header / footer ---- */
async function loadPartial(id, file) {
  try {
    const res = await fetch(file);
    if (!res.ok) throw new Error('Partial introuvable : ' + file);
    document.getElementById(id).innerHTML = await res.text();
  } catch (e) {
    console.warn(e.message);
  }
}

/* ---- Init header : hamburger + scroll + section active ---- */
function initHeader(currentPage) {
  const header    = document.getElementById('main-header');
  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobileNav');

  // --- Hamburger ---
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => {
      const isOpen = mobileNav.classList.toggle('open');
      hamburger.classList.toggle('open', isOpen);
      hamburger.setAttribute('aria-expanded', isOpen);
    });
    mobileNav.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        mobileNav.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // --- Ombre scroll ---
  if (header) {
    const onScroll = () => {
      header.classList.toggle('scrolled', window.scrollY > 10);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // --- Section active via IntersectionObserver ---
  const sections = ['services', 'avis', 'zone', 'faq', 'contact'];
  const navLinks = document.querySelectorAll('#main-nav [data-section], .mobile-nav [data-section]');

  if (navLinks.length && 'IntersectionObserver' in window) {
    const setActive = (id) => {
      navLinks.forEach(a => {
        a.classList.toggle('active', a.dataset.section === id);
      });
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) setActive(entry.target.id);
      });
    }, { rootMargin: '-50% 0px -45% 0px', threshold: 0 });

    sections.forEach(id => {
      const el = document.getElementById(id);
      if (el) observer.observe(el);
    });
  }
}

/* ---- WordPress image resolver ---- */
/* Si window.WP_IMG_BASE est défini (ex: '/wp-content/uploads/plombier/'),
   toutes les images et backgrounds sont redirigées vers ce dossier.
   Usage dans WordPress : <script>window.WP_IMG_BASE = '/wp-content/uploads/votre-dossier/';</script>
   avant le chargement de main.js. */
function resolveImages() {
  var base = window.WP_IMG_BASE;
  if (!base) return;
  document.querySelectorAll('[data-img]').forEach(function(el) {
    el.src = base + el.dataset.img;
  });
  document.querySelectorAll('[data-bg]').forEach(function(el) {
    el.style.backgroundImage = "url('" + base + el.dataset.bg + "')";
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', resolveImages);
} else {
  resolveImages();
}

/* ---- Accordéon FAQ ---- */
function toggleAcc(btn) {
  const isOpen = btn.classList.contains('ouvert');
  // Fermer tous
  document.querySelectorAll('.acc-btn.ouvert').forEach(b => {
    b.classList.remove('ouvert');
    b.nextElementSibling.classList.remove('ouvert');
  });
  // Ouvrir si était fermé
  if (!isOpen) {
    btn.classList.add('ouvert');
    btn.nextElementSibling.classList.add('ouvert');
  }
}

/* ---- Formulaire hero (simulation) ---- */
function envoyerHeroForm(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type="submit"], .btn-form-hero');
  if (btn) { btn.textContent = 'Envoyé ! On vous rappelle.'; btn.disabled = true; }
}

/* ---- Formulaire contact (simulation) ---- */
function envoyerForm(e) {
  e.preventDefault();
  const btn = e.target.querySelector('.btn-envoyer');
  if (btn) { btn.textContent = 'Demande envoyée — réponse sous 5 min'; btn.disabled = true; }
}
