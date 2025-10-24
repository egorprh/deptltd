/**
 * Scroll Animations Manager
 * Handles scroll-based animations and smooth scrolling behavior
 * Uses Intersection Observer API for performance optimization
 */

export class ScrollAnimations {
    constructor() {
        this.init();
    }

    /**
     * Initialize scroll animations
     * Sets up intersection observer and smooth scrolling
     */
    init() {
        this.setupIntersectionObserver();
        this.setupSmoothScrolling();
    }

    /**
     * Setup Intersection Observer for scroll animations
     * Monitors elements entering viewport and triggers animations
     */
    setupIntersectionObserver() {
        // Check if Intersection Observer is supported
        if (!('IntersectionObserver' in window)) {
            console.warn('IntersectionObserver not supported, falling back to scroll events');
            this.setupScrollFallback();
            return;
        }

        // Create intersection observer with options
        const observerOptions = {
            root: null, // Use viewport as root
            rootMargin: '0px 0px -50px 0px', // Trigger when element is 50px from bottom of viewport
            threshold: 0.1 // Trigger when 10% of element is visible
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add show class when element enters viewport
                    entry.target.classList.add('scroll-show');
                    entry.target.classList.remove('scroll-hidden');
                } else {
                    // Optionally hide element when it leaves viewport
                    // entry.target.classList.add('scroll-hidden');
                    // entry.target.classList.remove('scroll-show');
                }
            });
        }, observerOptions);

        // Observe all elements with scroll-hidden class
        const hiddenElements = document.querySelectorAll('.scroll-hidden');
        hiddenElements.forEach(element => {
            observer.observe(element);
        });
    }

    /**
     * Setup smooth scrolling for anchor links
     * Handles smooth scrolling behavior for internal navigation
     */
    setupSmoothScrolling() {
        // Find all anchor links that point to elements on the same page
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        
        anchorLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                
                // Skip if href is just "#"
                if (href === '#') return;
                
                const targetElement = document.querySelector(href);
                
                if (targetElement) {
                    e.preventDefault();
                    
                    // Calculate offset for fixed header (if any)
                    const headerOffset = 80; // Adjust based on your header height
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    // Smooth scroll to target
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    /**
     * Fallback for browsers without Intersection Observer
     * Uses scroll event listener (less performant but more compatible)
     */
    setupScrollFallback() {
        let ticking = false;

        const updateScrollAnimations = () => {
            const hiddenElements = document.querySelectorAll('.scroll-hidden');
            
            hiddenElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150; // Trigger when element is 150px from top of viewport
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('scroll-show');
                    element.classList.remove('scroll-hidden');
                }
            });
            
            ticking = false;
        };

        const requestTick = () => {
            if (!ticking) {
                requestAnimationFrame(updateScrollAnimations);
                ticking = true;
            }
        };

        // Throttled scroll event listener
        window.addEventListener('scroll', requestTick);
        
        // Initial check
        updateScrollAnimations();
    }

    /**
     * Manually trigger scroll animation for specific element
     * @param {string} selector - CSS selector for the element
     */
    triggerAnimation(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.add('scroll-show');
            element.classList.remove('scroll-hidden');
        }
    }

    /**
     * Reset scroll animation for specific element
     * @param {string} selector - CSS selector for the element
     */
    resetAnimation(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.add('scroll-hidden');
            element.classList.remove('scroll-show');
        }
    }
}
