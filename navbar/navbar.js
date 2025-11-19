// Navigation state
let activeNav = 'home';
let isDesktopSearchExpanded = false;
let isMobileSearchOpen = false;
let navIndicator = null;

// Get elements
const searchToggle = document.getElementById('searchToggle');
const searchBar = document.getElementById('searchBar');
const searchInput = document.getElementById('searchInput');

const searchBtnMobile = document.getElementById('searchBtnMobile');
const searchBtnTablet = document.getElementById('searchBtnTablet');
const mobileSearchDropdown = document.getElementById('mobileSearchDropdown');
const mobileSearchInput = document.getElementById('mobileSearchInput');

const loginBtn = document.getElementById('loginBtn');
const registerBtn = document.getElementById('registerBtn');
const profileBtn = document.getElementById('profileBtn');

const allNavItems = document.querySelectorAll('.nav-item');
const themeBtns = document.querySelectorAll('#themeToggleMobile, #themeToggleTablet, #themeToggleDesktop');

function updateThemeToggleIcons() {
    const isDark = document.body.classList.contains('dark');
    const sunSVG = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2m10-10h-2M4 12H2m15.36 6.36-1.41-1.41M6.05 6.05 4.64 4.64m12.72 0-1.41 1.41M6.05 17.95l-1.41 1.41"></path></svg>';
    const moonSVG = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
    themeBtns.forEach(btn => { if (!btn) return; btn.innerHTML = isDark ? moonSVG : sunSVG; });
}

// Sliding active indicator
const bottomNav = document.querySelector('.navbar-scope .bottom-nav');
if (bottomNav) {
    navIndicator = document.createElement('div');
    navIndicator.className = 'nav-active-indicator';
    bottomNav.appendChild(navIndicator);
    bottomNav.classList.add('has-indicator');
}

function getVisibleActiveItem() {
    const candidates = Array.from(document.querySelectorAll('.nav-item.active'));
    for (const el of candidates) {
        if (el.offsetParent !== null) return el;
    }
    // Fallback: any visible nav-item
    const visible = Array.from(document.querySelectorAll('.nav-item')).find(el => el.offsetParent !== null);
    return visible || null;
}

function moveIndicatorTo(target) {
    if (!navIndicator || !bottomNav || !target) return;
    const navRect = bottomNav.getBoundingClientRect();
    const rect = target.getBoundingClientRect();
    const left = rect.left - navRect.left;
    const top = rect.top - navRect.top;
    navIndicator.style.width = rect.width + 'px';
    navIndicator.style.height = rect.height + 'px';
    navIndicator.style.transform = `translate(${left}px, ${top}px)`;
}

function syncIndicator() {
    moveIndicatorTo(getVisibleActiveItem());
}

window.addEventListener('resize', () => {
    // Reposition on layout changes
    syncIndicator();
});

// Observe layout changes caused by responsive breaks and transitions
try {
    if (typeof ResizeObserver !== 'undefined' && bottomNav) {
        const ro = new ResizeObserver(() => {
            syncIndicator();
        });
        ro.observe(bottomNav);
        if (searchBar) ro.observe(searchBar);
        document.querySelectorAll('.nav-mobile, .nav-tablet, .nav-desktop, .nav-items-mobile, .nav-items-tablet, .nav-items-desktop').forEach(el => ro.observe(el));
    }
} catch (_) {}

// Sync after relevant transitions complete (e.g., search bar width)
if (searchBar) {
    searchBar.addEventListener('transitionend', (e) => {
        if (e.propertyName === 'width' || e.propertyName === 'flex' || e.propertyName === 'max-width') {
            syncIndicator();
        }
    });
}
if (bottomNav) {
    bottomNav.addEventListener('transitionend', () => syncIndicator());
}

// Theme toggle morph helper
function morphThemeIcon() {
    themeBtns.forEach(btn => {
        if (!btn) return;
        btn.classList.add('morphing');
        setTimeout(() => btn.classList.remove('morphing'), 180);
    });
    // Update icon slightly after morph starts for visual smoothness
    setTimeout(updateThemeToggleIcons, 60);
}

// Desktop search toggle
if (searchToggle && searchBar) {
    searchToggle.addEventListener('click', () => {
        morphThemeIcon();
        isDesktopSearchExpanded = !isDesktopSearchExpanded;
        
        if (isDesktopSearchExpanded) {
            searchBar.classList.add('expanded');
            setTimeout(() => {
                searchInput && searchInput.focus();
            }, 100);
        } else {
            searchBar.classList.remove('expanded');
        }
        // Temporarily speed up indicator for search open/close
        if (navIndicator) navIndicator.classList.add('fast');
        requestAnimationFrame(() => {
            syncIndicator();
            setTimeout(() => {
                syncIndicator();
                if (navIndicator) navIndicator.classList.remove('fast');
            }, 220);
        });
    });
}

// Mobile/Tablet search toggle
function toggleMobileSearch() {
    isMobileSearchOpen = !isMobileSearchOpen;
    
    if (isMobileSearchOpen) {
        mobileSearchDropdown.classList.add('open');
        setTimeout(() => {
            mobileSearchInput && mobileSearchInput.focus();
        }, 100);
    } else {
        mobileSearchDropdown.classList.remove('open');
    }
    // Temporarily speed up indicator for dropdown open/close
    if (navIndicator) navIndicator.classList.add('fast');
    requestAnimationFrame(() => {
        syncIndicator();
        setTimeout(() => {
            syncIndicator();
            if (navIndicator) navIndicator.classList.remove('fast');
        }, 220);
    });
}

if (searchBtnMobile) {
    searchBtnMobile.addEventListener('click', () => { morphThemeIcon(); toggleMobileSearch(); });
}

if (searchBtnTablet) {
    searchBtnTablet.addEventListener('click', () => { morphThemeIcon(); toggleMobileSearch(); });
}

// Theme toggle buttons: morph on theme change
if (themeBtns && themeBtns.length) {
    themeBtns.forEach(btn => btn.addEventListener('click', morphThemeIcon));
}

// React to theme changes toggled by other scripts (e.g., landing page)
try {
    const mo = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.type === 'attributes' && m.attributeName === 'class') {
                updateThemeToggleIcons();
            }
        }
    });
    mo.observe(document.body, { attributes: true, attributeFilter: ['class'] });
} catch (_) {}

// Initial icon render
updateThemeToggleIcons();

// Navigation item click handlers
allNavItems.forEach(item => {
    item.addEventListener('click', function() {
        const navId = this.getAttribute('data-nav');
        
        // Remove active class from all items with same nav id
        document.querySelectorAll(`[data-nav="${activeNav}"]`).forEach(el => {
            el.classList.remove('active');
        });
        
        // Add active class to all items with new nav id
        document.querySelectorAll(`[data-nav="${navId}"]`).forEach(el => {
            el.classList.add('active');
        });
        
        activeNav = navId;

        // Move sliding indicator to clicked (visible) item
        moveIndicatorTo(this);

        // Smooth scroll mapping for landing page sections
        const idMap = { bag: 'shop', cart: 'reviews' };
        const targetId = idMap[navId] || navId;
        const target = document.getElementById(targetId) || document.querySelector(`[data-section-id="${targetId}"]`);
        if (target) {
            const rect = target.getBoundingClientRect();
            const offset = 80;
            const top = rect.top + (window.pageYOffset || document.documentElement.scrollTop) - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }

        console.log(`Navigated to: ${navId}`);
    });
});

// Initial indicator position
syncIndicator();

// Auth button handlers
if (loginBtn) {
    loginBtn.addEventListener('click', () => {
        console.log('Login triggered');
    });
}

if (registerBtn) {
    registerBtn.addEventListener('click', () => {
        console.log('Register triggered');
    });
}

if (profileBtn) {
    profileBtn.addEventListener('click', () => {
        console.log('Profile triggered');
    });
}

// Search input handlers
if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        console.log('Desktop search:', e.target.value);
    });
}

if (mobileSearchInput) {
    mobileSearchInput.addEventListener('input', (e) => {
        console.log('Mobile search:', e.target.value);
    });
}

// Close mobile search when clicking outside
document.addEventListener('click', (e) => {
    if (isMobileSearchOpen && 
        !mobileSearchDropdown.contains(e.target) && 
        e.target !== searchBtnMobile && 
        e.target !== searchBtnTablet) {
        toggleMobileSearch();
    }
});

// Close desktop search when clicking outside
document.addEventListener('click', (e) => {
    if (isDesktopSearchExpanded && 
        searchBar && 
        !searchBar.contains(e.target)) {
        isDesktopSearchExpanded = false;
        searchBar.classList.remove('expanded');
    }
});
