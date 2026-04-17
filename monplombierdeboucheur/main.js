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

/* ---- Gestion CSRF Token ---- */
function getCsrfToken() {
  let token = localStorage.getItem('csrf_token');
  if (!token || token.length < 32) {
    token = generateToken();
    localStorage.setItem('csrf_token', token);
  }
  return token;
}

function generateToken() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let token = '';
  for (let i = 0; i < 32; i++) {
    token += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return token;
}

/* ---- Formulaire hero (envoi réel) ---- */
async function envoyerHeroForm(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button[type="submit"], .btn-form-hero');
  const originalText = btn.textContent;

  // Validation côté client
  const nom = form.querySelector('[id^="h-nom"], [name="nom"]').value.trim();
  const telInput = form.querySelector('[id^="h-tel"], [name="tel"]');
  const ville = form.querySelector('[id^="h-ville"], [name="ville"]').value.trim();
  const service = form.querySelector('[id^="h-service"], [name="service"]').value;

  // Validation téléphone
  const tel = telInput.value.replace(/[^0-9]/g, '');
  if (tel.length < 10 || tel.length > 11 || !tel.match(/^0[67]/)) {
    showFormError(form, 'Téléphone invalide (format 06/07 requis)');
    return;
  }

  if (nom.length < 2) {
    showFormError(form, 'Veuillez entrer votre nom complet');
    return;
  }

  if (ville.length < 2) {
    showFormError(form, 'Veuillez entrer votre ville');
    return;
  }

  // Désactiver le bouton
  btn.disabled = true;
  btn.textContent = 'Envoi en cours...';

  try {
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('nom', nom);
    formData.append('tel', tel);
    formData.append('ville', ville);
    formData.append('service', service || 'Non spécifié');
    formData.append('page_source', window.location.href);

    const response = await fetch('api/process.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      // Succès
      btn.textContent = 'Envoyé ! On vous rappelle.';
      btn.style.backgroundColor = '#28a745';
      form.reset();

      // Mettre à jour le token CSRF
      if (result.csrf_token) {
        localStorage.setItem('csrf_token', result.csrf_token);
      }

      // Réinitialiser le bouton après 3 secondes
      setTimeout(() => {
        btn.textContent = originalText;
        btn.disabled = false;
        btn.style.backgroundColor = '';
      }, 3000);
    } else {
      // Erreur
      showFormError(form, result.message || 'Erreur lors de l\'envoi');
      btn.textContent = originalText;
      btn.disabled = false;
    }
  } catch (error) {
    console.error('Erreur:', error);
    showFormError(form, 'Erreur de connexion. Veuillez réessayer ou nous appeler au 06 42 56 16 71.');
    btn.textContent = originalText;
    btn.disabled = false;
  }
}

/* ---- Formulaire contact (envoi réel) ---- */
async function envoyerForm(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('.btn-envoyer');
  const originalText = btn.textContent;

  // Récupération des données (adapté aux IDs contact.html)
  const nom = form.querySelector('#contact-nom, [name="nom"]')?.value.trim();
  const prenom = form.querySelector('#contact-prenom, [name="prenom"]')?.value.trim();
  const email = form.querySelector('#contact-email, [name="email"]')?.value.trim();
  const telInput = form.querySelector('#contact-tel, [name="tel"]');
  const dept = form.querySelector('#contact-dept, [name="dept"]');
  const type = form.querySelector('#contact-type, [name="type"]');
  const message = form.querySelector('#contact-message, [name="message"]')?.value.trim();

  // Validation
  if (!nom || nom.length < 2) {
    showFormError(form, 'Veuillez entrer votre nom');
    return;
  }

  if (!prenom || prenom.length < 2) {
    showFormError(form, 'Veuillez entrer votre prénom');
    return;
  }

  if (!email || !email.match(/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/)) {
    showFormError(form, 'Email invalide');
    return;
  }

  const tel = telInput.value.replace(/[^0-9]/g, '');
  if (tel.length < 10 || !tel.match(/^0[67]/)) {
    showFormError(form, 'Téléphone invalide (format 06/07)');
    return;
  }

  if (dept && dept.value === '') {
    showFormError(form, 'Veuillez sélectionner votre département');
    return;
  }

  if (type && type.value === '') {
    showFormError(form, 'Veuillez sélectionner le type de demande');
    return;
  }

  if (!message || message.length < 10) {
    showFormError(form, 'Message trop court (min. 10 caractères)');
    return;
  }

  // Désactiver le bouton
  btn.disabled = true;
  btn.textContent = 'Envoi en cours...';

  try {
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('civilite', form.querySelector('#contact-civilite, [name="civilite"]')?.value || 'M.');
    formData.append('nom', nom);
    formData.append('prenom', prenom);
    formData.append('email', email);
    formData.append('tel', tel);
    formData.append('dept', dept?.value || '');
    formData.append('type', type?.value || '');
    formData.append('urgence', form.querySelector('#contact-urgence, [name="urgence"]')?.value || 'Non spécifié');
    formData.append('message', message);
    formData.append('page_source', window.location.href);

    const response = await fetch('api/process.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      btn.textContent = 'Demande envoyée — réponse sous 5 min';
      btn.style.backgroundColor = '#28a745';
      form.reset();

      if (result.csrf_token) {
        localStorage.setItem('csrf_token', result.csrf_token);
      }

      setTimeout(() => {
        btn.textContent = originalText;
        btn.disabled = false;
        btn.style.backgroundColor = '';
      }, 5000);
    } else {
      showFormError(form, result.message || 'Erreur lors de l\'envoi');
      btn.textContent = originalText;
      btn.disabled = false;
    }
  } catch (error) {
    console.error('Erreur:', error);
    showFormError(form, 'Erreur de connexion. Appelez-nous au 06 42 56 16 71.');
    btn.textContent = originalText;
    btn.disabled = false;
  }
}

/* ---- Fonction utilitaire pour afficher les erreurs ---- */
function showFormError(form, message) {
  // Créer ou mettre à jour le message d'erreur
  let errorDiv = form.querySelector('.form-error');

  if (!errorDiv) {
    errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.style.cssText = 'background: #dc3545; color: white; padding: 12px; border-radius: 4px; margin-top: 15px; font-size: 0.9rem;';
    form.insertBefore(errorDiv, form.firstChild);
  }

  errorDiv.textContent = message;
  errorDiv.style.display = 'block';

  // Faire défiler vers l'erreur
  errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  // Masquer après 5 secondes
  setTimeout(() => {
    errorDiv.style.display = 'none';
  }, 5000);
}

