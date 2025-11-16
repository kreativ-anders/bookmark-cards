/*
 * Modal
 *
 * Pico.css - https://picocss.com
 * Copyright 2019-2022 - Licensed under MIT
 */

// Config
const isOpenClass = 'modal-is-open';
const openingClass = 'modal-is-opening';
const closingClass = 'modal-is-closing';
const DEFAULT_ANIMATION_DURATION = 400; // ms

// State
let visibleModal = null;
let _scrollbarWidthCache = null;
let _openTimer = null;
let _closeTimer = null;

/**
 * Effective animation duration. If the user prefers reduced motion, make
 * animations instantaneous to respect accessibility preferences.
 * @returns {number}
 */
const getAnimationDuration = () => {
  try {
    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    return prefersReduced ? 0 : DEFAULT_ANIMATION_DURATION;
  } catch (e) {
    return DEFAULT_ANIMATION_DURATION;
  }
};


/**
 * Toggle modal open/close state.
 * Expects the trigger element to have a `data-target` attribute that is the id
 * of the modal to toggle.
 * @param {Event} event
 */
const toggleModal = event => {
  if (!event || !event.currentTarget) return;
  event.preventDefault();
  const target = event.currentTarget.getAttribute && event.currentTarget.getAttribute('data-target');
  if (!target) return;
  const modal = document.getElementById(target);
  if (!modal) return;
  isModalOpen(modal) ? closeModal(modal) : openModal(modal);
};

/**
 * Check whether a modal element is currently marked open.
 * @param {HTMLElement} modal
 * @returns {boolean}
 */
const isModalOpen = modal => {
  if (!modal || typeof modal.hasAttribute !== 'function') return false;
  return modal.hasAttribute('open') && modal.getAttribute('open') !== 'false';
};

/**
 * Open a modal element with classes and animation timing. Safe to call
 * multiple times; clears pending close timers to avoid race conditions.
 * @param {HTMLElement} modal
 */
const openModal = modal => {
  if (!modal) return;
  // clear pending close timer if any
  if (_closeTimer) { clearTimeout(_closeTimer); _closeTimer = null; }

  if (isScrollbarVisible()) {
    const width = getScrollbarWidth();
    if (width > 0) document.documentElement.style.setProperty('--scrollbar-width', `${width}px`);
  }

  const duration = getAnimationDuration();
  document.documentElement.classList.add(isOpenClass, openingClass);

  // ensure modal attribute is set synchronously
  try { modal.setAttribute('open', true); } catch (e) { /* ignore */ }

  // clear any pending open timer and set a new one to finalize state
  if (_openTimer) clearTimeout(_openTimer);
  _openTimer = setTimeout(() => {
    visibleModal = modal;
    document.documentElement.classList.remove(openingClass);
    _openTimer = null;
  }, duration); 
};

/**
 * Close a modal element. Clears pending open timers to avoid race conditions
 * and respects reduced-motion preference.
 * @param {HTMLElement} modal
 */
const closeModal = modal => {
  if (!modal) return;
  // clear pending open timer if any
  if (_openTimer) { clearTimeout(_openTimer); _openTimer = null; }

  visibleModal = null;
  document.documentElement.classList.add(closingClass);

  const duration = getAnimationDuration();
  if (_closeTimer) clearTimeout(_closeTimer);
  _closeTimer = setTimeout(() => {
    document.documentElement.classList.remove(closingClass, isOpenClass);
    try { document.documentElement.style.removeProperty('--scrollbar-width'); } catch (e) { /* noop */ }
    try { modal.removeAttribute('open'); } catch (e) { /* noop */ }
    _closeTimer = null;
  }, duration);
};

// Close with a click outside
// Close with a click outside the modal content (backdrop click)
document.addEventListener('click', event => {
  if (!visibleModal) return;
  if (!event || event.defaultPrevented) return;
  const modalContent = visibleModal.querySelector && visibleModal.querySelector('article');
  // if there is no specific content element, treat the click as outside only
  const isClickInside = modalContent ? modalContent.contains(event.target) : false;
  if (!isClickInside) closeModal(visibleModal);
}, { passive: true });

// Close with Esc key
// Close with Esc key
document.addEventListener('keydown', event => {
  if (!event) return;
  if ((event.key === 'Escape' || event.key === 'Esc') && visibleModal) {
    closeModal(visibleModal);
  }
});

/**
 * Measure and cache the scrollbar width. Subsequent calls return the cached
 * value to avoid repeated DOM manipulation.
 * @returns {number}
 */
const getScrollbarWidth = () => {
  if (_scrollbarWidthCache !== null) return _scrollbarWidthCache;
  try {
    const outer = document.createElement('div');
    outer.style.visibility = 'hidden';
    outer.style.overflow = 'scroll';
    outer.style.msOverflowStyle = 'scrollbar';
    outer.style.position = 'absolute';
    outer.style.top = '-9999px';
    document.body.appendChild(outer);

    const inner = document.createElement('div');
    outer.appendChild(inner);

    const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
    outer.parentNode.removeChild(outer);
    _scrollbarWidthCache = typeof scrollbarWidth === 'number' ? scrollbarWidth : 0;
    return _scrollbarWidthCache;
  } catch (e) {
    return 0;
  }
};

/**
 * Determine whether a vertical scrollbar is visible for the document.
 * Uses documentElement scrollHeight vs viewport height for a more accurate
 * measurement than `screen.height`.
 * @returns {boolean}
 */
const isScrollbarVisible = () => {
  try {
    return document.documentElement && document.documentElement.scrollHeight > window.innerHeight;
  } catch (e) {
    return false;
  }
};