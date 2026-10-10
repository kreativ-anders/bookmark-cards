/*
 * Modal
 *
 * Pico.css - https://picocss.com
 * Copyright 2019-2022 - Licensed under MIT
 */

const isOpenClass = 'modal-is-open';
const openingClass = 'modal-is-opening';
const closingClass = 'modal-is-closing';
const DEFAULT_ANIMATION_DURATION = 400;

let visibleModal = null;
let _scrollbarWidthCache = null;
let _openTimer = null;
let _closeTimer = null;

const getAnimationDuration = () => {
  try {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : DEFAULT_ANIMATION_DURATION;
  } catch (e) {
    return DEFAULT_ANIMATION_DURATION;
  }
};

/**
 * Toggles the modal whose id is the trigger's `data-target`
 * @param {Event} event
 */
const toggleModal = event => {
  if (!event || !event.currentTarget) return;
  event.preventDefault();
  const target = event.currentTarget.getAttribute && event.currentTarget.getAttribute('data-target');
  const modal = target && document.getElementById(target);
  if (!modal) return;
  isModalOpen(modal) ? closeModal(modal) : openModal(modal);
};

const isModalOpen = modal => {
  if (!modal || typeof modal.hasAttribute !== 'function') return false;
  return modal.hasAttribute('open') && modal.getAttribute('open') !== 'false';
};

const openModal = modal => {
  if (!modal) return;
  if (_closeTimer) { clearTimeout(_closeTimer); _closeTimer = null; }

  if (isScrollbarVisible()) {
    const width = getScrollbarWidth();
    if (width > 0) document.documentElement.style.setProperty('--scrollbar-width', `${width}px`);
  }

  document.documentElement.classList.add(isOpenClass, openingClass);
  modal.setAttribute('open', true);

  if (_openTimer) clearTimeout(_openTimer);
  _openTimer = setTimeout(() => {
    visibleModal = modal;
    document.documentElement.classList.remove(openingClass);
    _openTimer = null;
  }, getAnimationDuration());
};

const closeModal = modal => {
  if (!modal) return;
  if (_openTimer) { clearTimeout(_openTimer); _openTimer = null; }

  visibleModal = null;
  document.documentElement.classList.add(closingClass);

  if (_closeTimer) clearTimeout(_closeTimer);
  _closeTimer = setTimeout(() => {
    document.documentElement.classList.remove(closingClass, isOpenClass);
    document.documentElement.style.removeProperty('--scrollbar-width');
    modal.removeAttribute('open');
    _closeTimer = null;
  }, getAnimationDuration());
};

// backdrop click
document.addEventListener('click', event => {
  if (!visibleModal || event.defaultPrevented) return;
  const modalContent = visibleModal.querySelector('article');
  if (!modalContent || !modalContent.contains(event.target)) closeModal(visibleModal);
}, { passive: true });

document.addEventListener('keydown', event => {
  if ((event.key === 'Escape' || event.key === 'Esc') && visibleModal) {
    closeModal(visibleModal);
  }
});

const getScrollbarWidth = () => {
  if (_scrollbarWidthCache !== null) return _scrollbarWidthCache;
  try {
    const outer = document.createElement('div');
    outer.style.visibility = 'hidden';
    outer.style.overflow = 'scroll';
    outer.style.position = 'absolute';
    outer.style.top = '-9999px';
    document.body.appendChild(outer);

    const inner = document.createElement('div');
    outer.appendChild(inner);

    _scrollbarWidthCache = outer.offsetWidth - inner.offsetWidth;
    outer.remove();
    return _scrollbarWidthCache;
  } catch (e) {
    return 0;
  }
};

const isScrollbarVisible = () => document.documentElement.scrollHeight > window.innerHeight;
