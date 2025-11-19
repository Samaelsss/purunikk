# Purunik Website Repository

## Project Overview
This is a static HTML/CSS/JavaScript website for Purunik, an eco-friendly woven bags brand. Built with vanilla JavaScript, no build tools required.

## Technology Stack
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Target Framework for E2E Tests**: Playwright
- **Server**: Static file server (Python http.server or equivalent)

## Project Structure
```
c:\Users\Samael\Pictures\Purunik\
├── index.html          # Main HTML file with all sections
├── styles.css          # All styling and responsive design
├── script.js           # Interactive functionality
├── tests/              # E2E test files (Playwright)
│   └── e2e/
│       └── *.spec.ts   # Test specifications
└── .zencoder/
    └── rules/
        └── repo.md     # This file
```

## Key Features Implemented
1. **Navigation Bar**: Logo, search bar, filter labels, icon buttons, action buttons
2. **Hero Section**: Circular logo, text, CTA button with smooth scroll, images
3. **Sejarah Purunik Section**: Brown background card with history text and circular image
4. **Meet the Team Section**: 3 team cards with avatar badges, hover effects
5. **Search Separator**: Full-width dark brown bar with search + add button
6. **Latest Reviews Section**: 3 review cards with star ratings (hover fill effect)
7. **Background Image Section**: Decorative full-width image
8. **Footer Icons**: 4 icon buttons with alert functionality
9. **Footer Menu**: Multi-column footer with links and copyright

## Interactive Elements
- Search input clear button (×)
- Filter label toggle (active state with checkmark)
- Smooth scroll "Learn more" button
- Star rating hover effect (fills on hover)
- Plus button alert "Add new item"
- Footer icon alerts
- Team card hover scale effect
- Scroll animations for cards

## Color Palette
- Primary Brown: #73512C
- Secondary Brown: #B08F70
- Cream: #F9F4E1
- Dark Brown: #543310
- Accent Purple: #E0D6FF

## Responsive Design
- Mobile-first approach
- Breakpoints: <640px (37px), 640px (56px), 768px (74px), 1024px (111px), 1920px (148px), 2560px (185px), 3200px (222px), 3840px (259px)
- Border widths scale proportionally (37px increment per major breakpoint)
- Flexible grid layouts
- Responsive typography

## Running the Project
1. Start a local server: `python -m http.server 8000`
2. Open `http://localhost:8000` in browser
3. Or open directly: `file:///c:/Users/Samael/Pictures/Purunik/index.html`

## Testing
E2E tests are written in Playwright and cover:
- Navigation and interaction
- Button functionality
- Filter toggles
- Smooth scrolling
- Alert triggers
- Hover effects
- Responsive layout

Run tests with: `npx playwright test`

## Accessibility Features
- Semantic HTML
- Keyboard accessible buttons
- ARIA labels where applicable
- Focus states on interactive elements
- Alt text for images