/*!
 * Bootstrap v5.3.0 JavaScript Bundle
 * Copyright 2011-2023 The Bootstrap Authors
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
 */

(function() {
    'use strict';

    // Bootstrap Dropdown
    class Dropdown {
        constructor(element) {
            this.element = element;
            this.menu = element.nextElementSibling;
            this.init();
        }

        init() {
            this.element.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });

            document.addEventListener('click', (e) => {
                if (!this.element.contains(e.target) && !this.menu.contains(e.target)) {
                    this.hide();
                }
            });
        }

        toggle() {
            if (this.menu.classList.contains('show')) {
                this.hide();
            } else {
                this.show();
            }
        }

        show() {
            this.menu.classList.add('show');
            this.element.setAttribute('aria-expanded', 'true');
        }

        hide() {
            this.menu.classList.remove('show');
            this.element.setAttribute('aria-expanded', 'false');
        }
    }

    // Bootstrap Collapse
    class Collapse {
        constructor(element) {
            this.element = element;
            this.target = document.querySelector(element.getAttribute('data-bs-target'));
            this.init();
        }

        init() {
            this.element.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        }

        toggle() {
            if (this.target.classList.contains('show')) {
                this.hide();
            } else {
                this.show();
            }
        }

        show() {
            this.target.style.height = 'auto';
            this.target.classList.add('show');
            this.element.setAttribute('aria-expanded', 'true');
        }

        hide() {
            this.target.style.height = '0';
            this.target.classList.remove('show');
            this.element.setAttribute('aria-expanded', 'false');
        }
    }

    // Bootstrap Alert
    class Alert {
        constructor(element) {
            this.element = element;
            this.init();
        }

        init() {
            const closeBtn = this.element.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.close();
                });
            }
        }

        close() {
            this.element.style.opacity = '0';
            setTimeout(() => {
                if (this.element.parentNode) {
                    this.element.parentNode.removeChild(this.element);
                }
            }, 150);
        }
    }

    // Bootstrap Modal
    class Modal {
        constructor(element) {
            this.element = element;
            this.backdrop = null;
            this.init();
        }

        init() {
            const triggers = document.querySelectorAll(`[data-bs-target="#${this.element.id}"]`);
            triggers.forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.show();
                });
            });

            const closeButtons = this.element.querySelectorAll('[data-bs-dismiss="modal"]');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    this.hide();
                });
            });
        }

        show() {
            this.createBackdrop();
            this.element.style.display = 'block';
            this.element.classList.add('show');
            document.body.classList.add('modal-open');
        }

        hide() {
            this.element.classList.remove('show');
            setTimeout(() => {
                this.element.style.display = 'none';
                this.removeBackdrop();
                document.body.classList.remove('modal-open');
            }, 150);
        }

        createBackdrop() {
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'modal-backdrop fade show';
            this.backdrop.addEventListener('click', () => {
                this.hide();
            });
            document.body.appendChild(this.backdrop);
        }

        removeBackdrop() {
            if (this.backdrop) {
                this.backdrop.parentNode.removeChild(this.backdrop);
                this.backdrop = null;
            }
        }
    }

    // Auto-initialize components
    function initializeComponents() {
        // Initialize dropdowns
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(element => {
            new Dropdown(element);
        });

        // Initialize collapses
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(element => {
            new Collapse(element);
        });

        // Initialize alerts
        document.querySelectorAll('.alert').forEach(element => {
            new Alert(element);
        });

        // Initialize modals
        document.querySelectorAll('.modal').forEach(element => {
            new Modal(element);
        });

        // Initialize navbar toggler
        const navbarToggler = document.querySelector('.navbar-toggler');
        if (navbarToggler) {
            const target = document.querySelector(navbarToggler.getAttribute('data-bs-target'));
            if (target) {
                navbarToggler.addEventListener('click', () => {
                    target.classList.toggle('show');
                });
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeComponents);
    } else {
        initializeComponents();
    }

    // Export for manual initialization
    window.bootstrap = {
        Dropdown,
        Collapse,
        Alert,
        Modal
    };

})();